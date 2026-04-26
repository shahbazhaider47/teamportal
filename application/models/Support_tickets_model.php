<?php defined('BASEPATH') or exit('No direct script access allowed');

class Support_tickets_model extends CI_Model
{
    protected $table = 'support_tickets';

    /** Allowed enums (keep in sync with DB) */
    private $valid_statuses   = ['open','in_progress','waiting_user','on_hold','resolved','closed'];
    private $valid_priorities = ['low','normal','high','urgent'];
    private $code_prefix      = 'SUP';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /* -----------------------------------------------------------
     * Creation + SLA helpers
     * ----------------------------------------------------------- */

    /**
     * Create a ticket and its first public message in ONE transaction.
     * @param array $ticket      subject, department_id, requester_id, [priority], [tags], [watchers], [assignee_id]
     * @param array $first_post  body, [attachments]
     * @param array $options     reserved for future (e.g., notify=false)
     * @return int ticket_id
     * @throws Exception on failure (transaction rolled back)
     */
    public function create(array $ticket, array $first_post, array $options = [])
    {
        $now = date('Y-m-d H:i:s');

        // Normalize & validate
        $ticket['code']       = $ticket['code'] ?? $this->generate_unique_code();
        $ticket['status']     = $this->normalize_enum($ticket['status'] ?? 'open', $this->valid_statuses, 'open');
        $ticket['priority']   = $this->normalize_enum($ticket['priority'] ?? 'normal', $this->valid_priorities, 'normal');
        $ticket['source']     = $ticket['source'] ?? 'web';
        $ticket['tags']       = $this->json_or_null($ticket['tags'] ?? null);
        $ticket['watchers']   = $this->json_or_null($ticket['watchers'] ?? null);
        $ticket['files_count']= 0;
        $ticket['created_at'] = $now;
        $ticket['updated_at'] = $now;
        $ticket['last_activity_at'] = $now;

        // Compute SLA based on settings JSON
        $sla = $this->compute_sla_deadlines((int)$ticket['department_id'], $now);
        $ticket['first_response_due_at'] = $sla['first_due'];
        $ticket['resolution_due_at']     = $sla['resolution_due'];

        $this->db->trans_begin();

        // 1) Insert ticket
        $this->db->insert($this->table, $ticket);
        $ticket_id = (int)$this->db->insert_id();
        if ($ticket_id <= 0) {
            $this->db->trans_rollback();
            throw new Exception('Failed to create ticket');
        }

        // 2) Insert initial public message
        $first_attachments = $first_post['attachments'] ?? [];
        $this->load->model('support/Support_posts_model', 'posts');
        $this->posts->add_message($ticket_id, (int)$ticket['requester_id'], (string)($first_post['body'] ?? ''), $first_attachments, /*$is_staff=*/false);

        // 3) If not assigned, auto-assign to default assignee per settings JSON
        if (empty($ticket['assignee_id'])) {
            $defaults = $this->get_dept_defaults((int)$ticket['department_id']);
            if (!empty($defaults['default_assignee'])) {
                $this->assign($ticket_id, (int)$defaults['default_assignee'], ['silent' => true]);
            }
        }

        // 4) bump files_count if initial message had attachments
        if (!empty($first_attachments)) {
            $this->bump_files_count($ticket_id, count($first_attachments));
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            throw new Exception('Transaction failed creating ticket');
        }
        $this->db->trans_commit();

        return $ticket_id;
    }

    /**
     * Generate a human code and ensure uniqueness by checking the DB.
     */
    public function generate_unique_code(): string
    {
        $year = date('Y');
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $seq  = str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $code = "{$this->code_prefix}-{$year}-{$seq}";
            $exists = $this->db->select('id')->from($this->table)->where('code', $code)->limit(1)->get()->row_array();
            if (!$exists) return $code;
        }
        // Fallback (extremely unlikely): use microtime suffix
        return "{$this->code_prefix}-{$year}-" . substr((string)microtime(true), -6);
    }

    /**
     * Compute SLA deadlines from system_settings JSON overrides or global defaults.
     */
    public function compute_sla_deadlines(int $department_id, string $created_at): array
    {
        $defaults = $this->get_dept_defaults($department_id);
        $firstMins = (int)($defaults['sla_first_response_mins'] ?? (int)get_setting('support_default_first_response_mins') ?: 480);
        $resMins   = (int)($defaults['sla_resolution_mins'] ?? (int)get_setting('support_default_resolution_mins') ?: 2880);

        return [
            'first_due'      => date('Y-m-d H:i:s', strtotime($created_at . " +{$firstMins} minutes")),
            'resolution_due' => date('Y-m-d H:i:s', strtotime($created_at . " +{$resMins} minutes")),
        ];
    }

    private function get_dept_defaults(int $department_id): array
    {
        $json = (string)(get_setting('support_dept_defaults_json') ?? '[]');
        $rows = json_decode($json, true) ?: [];
        foreach ($rows as $r) {
            if ((int)($r['department_id'] ?? 0) === $department_id) return $r;
        }
        return [
            'default_assignee'        => null,
            'sla_first_response_mins' => (int)get_setting('support_default_first_response_mins') ?: 480,
            'sla_resolution_mins'     => (int)get_setting('support_default_resolution_mins') ?: 2880,
        ];
    }

    /* -----------------------------------------------------------
     * Read / List
     * ----------------------------------------------------------- */

    public function find(int $id, bool $with_posts = true)
    {
        $row = $this->db->get_where($this->table, ['id' => $id])->row_array();
        if (!$row) return null;

        $row['tags']     = $this->json_decode_or_array($row['tags'] ?? null);
        $row['watchers'] = $this->json_decode_or_array($row['watchers'] ?? null);

        if ($with_posts) {
            $order = (get_setting('support_replies_order') === 'descending') ? 'DESC' : 'ASC';
            $this->load->model('support/Support_posts_model', 'posts');
            $row['posts'] = $this->posts->get_by_ticket($id, $order);
        }
        return $row;
    }

    public function find_by_code(string $code, bool $with_posts = true)
    {
        $row = $this->db->get_where($this->table, ['code' => $code])->row_array();
        if (!$row) return null;

        $row['tags']     = $this->json_decode_or_array($row['tags'] ?? null);
        $row['watchers'] = $this->json_decode_or_array($row['watchers'] ?? null);

        if ($with_posts) {
            $order = (get_setting('support_replies_order') === 'descending') ? 'DESC' : 'ASC';
            $this->load->model('support/Support_posts_model', 'posts');
            $row['posts'] = $this->posts->get_by_ticket((int)$row['id'], $order);
        }
        return $row;
    }


    /**
     * List tickets with filters + ordering.
     * Note: `list` is kept for backward compatibility; prefer list_tickets().
     */
    public function list(array $filters = [], int $limit = 20, int $offset = 0, array $order = ['last_activity_at' => 'DESC'])
    {
        return $this->list_tickets($filters, $limit, $offset, $order);
    }

    public function list_tickets(array $filters = [], int $limit = 20, int $offset = 0, array $order = ['last_activity_at' => 'DESC'])
    {
        if (!empty($filters['department_id'])) $this->db->where('department_id', (int)$filters['department_id']);
        if (!empty($filters['assignee_id']))   $this->db->where('assignee_id', (int)$filters['assignee_id']);
        if (!empty($filters['requester_id']))  $this->db->where('requester_id', (int)$filters['requester_id']);
        if (!empty($filters['status']))        $this->db->where('status', $filters['status']);
        if (!empty($filters['priority']))      $this->db->where('priority', $filters['priority']);

        if (!empty($filters['q'])) {
            $this->db->group_start()
                     ->like('subject', $filters['q'])
                     ->group_end();
        }

        foreach ($order as $col => $dir) {
            $this->db->order_by($col, $dir);
        }

        $this->db->limit($limit, $offset);
        $rows = $this->db->get($this->table)->result_array();

        foreach ($rows as &$r) {
            $r['tags']     = $this->json_decode_or_array($r['tags'] ?? null);
            $r['watchers'] = $this->json_decode_or_array($r['watchers'] ?? null);
        }
        return $rows;
    }

    /* -----------------------------------------------------------
     * Mutations
     * ----------------------------------------------------------- */

    public function assign(int $ticket_id, int $assignee_id, array $opts = [])
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', $ticket_id)->update($this->table, [
            'assignee_id'      => $assignee_id,
            'updated_at'       => $now,
            'last_activity_at' => $now,
        ]);
        return $this->db->affected_rows() > 0;
    }

    public function set_status(int $ticket_id, string $status)
    {
        $status = $this->normalize_enum($status, $this->valid_statuses, 'open');

        $now = date('Y-m-d H:i:s');
        $payload = [
            'status'           => $status,
            'updated_at'       => $now,
            'last_activity_at' => $now,
        ];
        if ($status === 'resolved') $payload['resolved_at'] = $now;
        if ($status === 'closed')   $payload['closed_at']   = $now;

        $this->db->where('id', $ticket_id)->update($this->table, $payload);
        return $this->db->affected_rows() > 0;
    }

    public function touch_last_activity(int $ticket_id)
    {
        $this->db->where('id', $ticket_id)->update($this->table, [
            'last_activity_at' => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function bump_files_count(int $ticket_id, int $by = 1)
    {
        $this->db->set('files_count', 'files_count + ' . (int)$by, false)
                 ->where('id', $ticket_id)->update($this->table);
    }

    public function add_watcher(int $ticket_id, int $user_id): bool
    {
        $row = $this->db->select('watchers')->from($this->table)->where('id', $ticket_id)->get()->row_array();
        $arr = $this->json_decode_or_array($row['watchers'] ?? null);
        if (!in_array($user_id, $arr, true)) $arr[] = $user_id;
        $this->db->where('id', $ticket_id)->update($this->table, ['watchers' => json_encode(array_values($arr))]);
        return $this->db->affected_rows() > 0;
    }

    public function remove_watcher(int $ticket_id, int $user_id): bool
    {
        $row = $this->db->select('watchers')->from($this->table)->where('id', $ticket_id)->get()->row_array();
        $arr = array_values(array_filter($this->json_decode_or_array($row['watchers'] ?? null), fn($v) => (int)$v !== $user_id));
        $this->db->where('id', $ticket_id)->update($this->table, ['watchers' => $arr ? json_encode($arr) : null]);
        return $this->db->affected_rows() > 0;
    }

    public function delete_ticket(int $ticket_id): bool
    {
        // posts have ON DELETE CASCADE
        $this->db->delete($this->table, ['id' => $ticket_id]);
        return $this->db->affected_rows() > 0;
    }


    // ADD these (already present in your controller, moving logic here):
    public function get_departments_rows(): array
    {
        return $this->db->select('id, name')->from('departments')->order_by('name','ASC')->get()->result_array();
    }
    
    public function get_departments(): array
    {
        return $this->db->select('id, name')->from('departments')->order_by('name','ASC')->get()->result_array();
    }
    
    public function get_departments_map(): array
    {
        $rows = $this->get_departments_rows();
        $out = [];
        foreach ($rows as $r) { $out[(int)$r['id']] = (string)$r['name']; }
        return $out;
    }

    /* -----------------------------------------------------------
     * Cron
     * ----------------------------------------------------------- */

    /**
     * Auto-close resolved tickets after N days of inactivity.
     * Returns number of affected tickets.
     */
    public function cron_auto_close(): int
    {
        $days = (int)(get_setting('support_auto_close_days') ?? 5);
        if ($days <= 0) return 0;

        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $this->db->where('status', 'resolved');
        $this->db->where('last_activity_at <', $threshold);
        $this->db->update($this->table, [
            'status'     => 'closed',
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows();
    }

    /* -----------------------------------------------------------
     * Internal helpers
     * ----------------------------------------------------------- */

    private function json_or_null($val)
    {
        if ($val === null) return null;
        if (is_string($val)) {
            $trim = trim($val);
            if ($trim === '' || $trim === 'null') return null;
            // assume already a JSON string
            return $trim;
        }
        return json_encode($val);
    }

    private function json_decode_or_array($json): array
    {
        if (!$json) return [];
        if (is_array($json)) return $json;
        $arr = json_decode((string)$json, true);
        return is_array($arr) ? $arr : [];
    }

    private function normalize_enum($value, array $allowed, $fallback)
    {
        $v = strtolower((string)$value);
        return in_array($v, $allowed, true) ? $v : $fallback;
    }

    
    // Quick check: is this user a watcher on this ticket?
    public function is_watcher(int $ticket_id, int $user_id): bool
    {
        $row = $this->db->select('watchers')->from($this->table)->where('id', $ticket_id)->get()->row_array();
        if (!$row) return false;
        $arr = $this->json_decode_or_array($row['watchers'] ?? null);
        foreach ($arr as $v) {
            if ((int)$v === (int)$user_id) return true;
        }
    
        // Fallback for TEXT patterns (defensive)
        $uid = (int)$user_id;
        $this->db->reset_query();
        $exists = $this->db->select('id')->from($this->table)
            ->where('id', (int)$ticket_id)
            ->group_start()
                ->where('(JSON_VALID(watchers) AND JSON_SEARCH(watchers, "one", '.$this->db->escape($uid).') IS NOT NULL)', null, false)
                ->or_where('watchers LIKE', '%"'.$uid.'"%')
                ->or_where('watchers LIKE', '%['.$uid.',%')
                ->or_where('watchers LIKE', '%,'.$uid.',%')
                ->or_where('watchers LIKE', '%,'.$uid.']%')
            ->group_end()
            ->limit(1)->get()->row_array();
        return (bool)$exists;
    }


public function list_watched_by_user(
    int $user_id,
    int $limit = 50,
    int $offset = 0,
    array $order = ['last_activity_at' => 'DESC']
): array {
    $uid = (int)$user_id;

    $this->db->from($this->table);

    // requester/assignee are NOT included here on purpose — only true "watcher" tickets
    // Robust watcher matching (JSON column, JSON text, or varchar):
    $this->db->group_start()
        // MySQL JSON case
        ->or_where('(JSON_VALID(watchers) AND JSON_SEARCH(watchers, "one", '.$this->db->escape($uid).') IS NOT NULL)', null, false)
        // JSON stored as text (quoted or unquoted ints)
        ->or_like('watchers', '"'.$uid.'"')      // …,"12",…
        ->or_like('watchers', '['.$uid.',')     // [12,…
        ->or_like('watchers', ','.$uid.',')     // ,12,
        ->or_like('watchers', ','.$uid.']')     // ,12]
    ->group_end();

    foreach ($order as $col => $dir) {
        $this->db->order_by($col, $dir);
    }
    $this->db->limit($limit, $offset);

    $rows = $this->db->get()->result_array();

    // normalize arrays
    foreach ($rows as &$r) {
        $r['tags']     = $this->json_decode_or_array($r['tags'] ?? null);
        $r['watchers'] = $this->json_decode_or_array($r['watchers'] ?? null);
    } unset($r);

    return $rows;
}
    
}
