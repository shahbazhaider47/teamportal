<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks_model
 * -----------------------------------------------------------------------------
 * Data access for Tasks module.
 *
 * --- REFACTOR NOTES ---
 * 1.  MODIFIED:  `list_tasks` now JOINs `users` to fetch `assignee_fullname`.
 * 2.  SIMPLIFIED: `list_tasks` now uses a private `_apply_filters` helper.
 * 3.  FIXED:     Renamed `add_attachment` to `create_attachment` (matches controller).
 * 4.  FIXED:     Renamed `toggle_checklist_finished` to `toggle_checklist_item` (matches controller).
 * 5.  FIXED:     `add_checklist_item` now accepts `(int $taskId, array $data)` to match controller.
 * 6.  ADDED:     `get_task_simple` (for quick permission checks).
 * 7.  ADDED:     `get_attachment(int $id)` (was missing).
 * 8.  ADDED:     `get_checklist_item(int $id)` (was missing).
 * 9.  ADDED:     `create_checklist_items_batch` (for the create modal).
 * 10. ADDED:     `cron_generate_recurring` (placeholder, was missing from module file).
 * -----------------------------------------------------------------------------
 */
class Tasks_model extends CI_Model
{
    /** @var string[] */
    protected $tables = [
        'tasks'          => 'tasks',
        'comments'       => 'task_comments',
        'checklist'      => 'task_checklist_items',
        'attachments'    => 'task_attachments',
        'activity'       => 'task_activity',
        'users'          => 'users',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================================
     * Helpers
     * ========================================================================= */

    protected function encode_json($value): ?string
    {
        if ($value === null) return null;
        if (is_string($value)) return $value;
        $json = json_encode(array_values(array_filter($value))); // Ensure it's a clean array
        return $json === false ? null : $json;
    }

    protected function decode_json(?string $json): array
    {
        if (!$json) return [];
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    protected function setting(string $key, $default = null)
    {
        return function_exists('get_setting') ? get_setting($key, $default) : $default;
    }

    /* =========================================================================
     * TASKS
     * ========================================================================= */

    /**
     * Create task (returns inserted ID)
     * $data keys are mapped directly to table columns.
     */
    public function create_task(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Whitelist of allowed fields for creation
        $allowed = [
            'name', 'description', 'priority', 'status', 'startdate', 'duedate',
            'addedfrom', 'updated_by', 'assignee_id', 'rel_id', 'rel_type',
            'milestone', 'kanban_order', 'milestone_order', 'visible_to_team',
            'deadline_notified', 'recurring', 'recurring_type', 'repeat_every',
            'is_recurring_from', 'cycles', 'total_cycles',
            'last_recurring_date'
        ];

        $row = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $row[$key] = $data[$key];
            }
        }

        // Apply defaults
        $row['dateadded'] = $data['dateadded'] ?? $now;
        $row['status']    = $data['status']    ?? 'not_started';
        $row['priority']  = $data['priority']  ?? 'normal';

        // Handle JSON fields
        if (array_key_exists('followers', $data)) {
            $row['followers_json'] = $this->encode_json($data['followers']);
        }

        if (empty($row['name']) || empty($row['addedfrom'])) {
            return 0; // Missing required fields
        }

        $this->db->insert($this->tables['tasks'], $row);
        return (int)$this->db->insert_id();
    }

    /**
     * Update task by ID (returns bool)
     * $data accepts same keys as create (will only update provided keys).
     */
    public function update_task(int $taskId, array $data): bool
    {
        if ($taskId <= 0) return false;

        // Whitelist of allowed fields for update
        $allowed = [
            'name', 'description', 'priority', 'status', 'startdate', 'duedate',
            'datefinished', 'addedfrom', 'updated_by', 'assignee_id', 'rel_id', 'rel_type',
            'milestone', 'kanban_order', 'milestone_order', 'visible_to_team',
            'deadline_notified', 'recurring', 'recurring_type', 'repeat_every',
            'is_recurring_from', 'cycles', 'total_cycles',
            'last_recurring_date'
        ];

        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        // Handle JSON fields
        if (array_key_exists('followers', $data)) {
            $update['followers_json'] = $this->encode_json($data['followers']);
        } elseif (array_key_exists('followers_json', $data)) {
            $update['followers_json'] = $this->encode_json($data['followers_json']);
        }

        if (!$update) return true; // nothing to do

        $this->db->where('id', $taskId)->update($this->tables['tasks'], $update);
        return $this->db->affected_rows() >= 0;
    }

    /**
     * Get single task (array) with decoded followers
     */
    public function get_task(int $taskId): ?array
    {
        if ($taskId <= 0) return null;
        $row = $this->db->get_where($this->tables['tasks'], ['id' => $taskId])->row_array();
        if (!$row) return null;
        $row['followers'] = $this->decode_json($row['followers_json'] ?? null);
        return $row;
    }

    /**
     * [ADDED] Get a minimal task row, optimized for policy checks.
     */
    public function get_task_simple(int $taskId): ?array
    {
        if ($taskId <= 0) return null;
        return $this->db
            ->select('id, assignee_id, addedfrom, followers_json, rel_id, rel_type')
            ->from($this->tables['tasks'])
            ->where('id', $taskId)
            ->get()
            ->row_array();
    }

    /**
     * Delete task + cascade deletes (comments, checklist, attachments, activity)
     */
    public function delete_task(int $taskId): bool
    {
        if ($taskId <= 0) return false;

        // Note: Filesystem attachments should be deleted by the controller
        
        $this->db->trans_start();
        $this->db->delete($this->tables['comments'],    ['taskid' => $taskId]);
        $this->db->delete($this->tables['checklist'],   ['taskid' => $taskId]);
        $this->db->delete($this->tables['attachments'], ['taskid' => $taskId]);
        $this->db->delete($this->tables['activity'],    ['taskid' => $taskId]);
        $this->db->delete($this->tables['tasks'],       ['id'     => $taskId]);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * [SIMPLIFIED] Applies WHERE clauses to the DB builder based on filters.
     */
    private function _apply_filters(array $filters = []): void
    {
        $t = $this->tables['tasks'];
        $u = $this->tables['users'];

        if (!empty($filters['q'])) {
            $term = trim((string)$filters['q']);
            $this->db->group_start()
                     ->like("$t.name", $term)
                     ->or_like("$t.description", $term)
                     ->group_end();
        }

        if (!empty($filters['status'])) {
            $this->db->where_in("$t.status", (array)$filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where_in("$t.priority", (array)$filters['priority']);
        }
        if (isset($filters['assignee_id'])) { // Allow filtering for '0' (unassigned)
            $this->db->where("$t.assignee_id", (int)$filters['assignee_id']);
        }
        if (!empty($filters['addedfrom'])) {
            $this->db->where("$t.addedfrom", (int)$filters['addedfrom']);
        }
        if (!empty($filters['rel_type'])) {
            $this->db->where("$t.rel_type", $filters['rel_type']);
        }
        if (!empty($filters['rel_id'])) {
            $this->db->where("$t.rel_id", (int)$filters['rel_id']);
        }
        if (!empty($filters['due_from'])) {
            $this->db->where("$t.duedate >=", $filters['due_from']);
        }
        if (!empty($filters['due_to'])) {
            $this->db->where("$t.duedate <=", $filters['due_to']);
        }
        if (isset($filters['visible_to_team'])) { // Allow filtering for '0'
            $this->db->where("$t.visible_to_team", (int)$filters['visible_to_team']);
        }
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $this->db->where_in("$t.id", array_map('intval', $filters['ids']));
        }
        
        // [ADDED] Scope for users who can only 'view_own'
        if (!empty($filters['scoped_user_id'])) {
            $uid = (int)$filters['scoped_user_id'];
            $this->db->group_start()
                ->where("$t.addedfrom", $uid)
                ->or_where("$t.assignee_id", $uid)
                // This JSON search is slow but necessary for 'view_own'
                ->or_like("$t.followers_json", '"' . $uid . '"') 
                ->group_end();
        }
    }


    /**
     * [MODIFIED] List tasks with filters + pagination.
     * Now includes assignee's name.
     */
public function list_tasks(array $filters = [], int $limit = 50, int $offset = 0, string $orderBy = 'duedate', string $dir = 'asc'): array
{
    $t = $this->tables['tasks'];
    $u = $this->tables['users'];

    // --- Base SELECT + JOIN for assignee fields
// --- Base SELECT + JOIN for assignee fields + attachment_count
$attachmentsTable = $this->db->dbprefix($this->tables['attachments']);

$this->db->select("
    $t.*,
    $u.id              AS assignee_user_id,
    $u.firstname       AS assignee_firstname,
    $u.lastname        AS assignee_lastname,
    $u.profile_image   AS assignee_profile_image,
    (
        SELECT COUNT(*)
        FROM {$attachmentsTable} ta
        WHERE ta.taskid = $t.id
    ) AS attachment_count
", false);
$this->db->from($t);
$this->db->join($u, "$u.id = $t.assignee_id", 'left');


    // Filters
    $this->_apply_filters($filters);

    // Count AFTER filters
    $countQuery = clone $this->db;
    $total = (int)$countQuery->count_all_results('', false);

    // Ordering
    $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
    $map = [
        'priority'  => "$t.priority",
        'status'    => "$t.status",
        'dateadded' => "$t.dateadded",
        'duedate'   => "$t.duedate IS NULL, $t.duedate",
        'updated'   => "$t.datefinished IS NULL, $t.datefinished",
    ];
    $orderExpr = $map[$orderBy] ?? $map['duedate'];
    $this->db->order_by($orderExpr . ' ' . $dir, '', false);

    // Page
    $this->db->limit($limit, $offset);
    $rows = $this->db->get()->result_array();

    if (!$rows) {
        return ['total' => 0, 'rows' => []];
    }

    // --- Enrichment: names, avatars, follower cards ----------------------
    // Collect all follower IDs across rows so we can resolve in one query
    $allFollowerIds = [];
    foreach ($rows as &$r) {
        $r['followers'] = $this->decode_json($r['followers_json'] ?? null);
        foreach ((array)$r['followers'] as $fid) {
            $fid = (int)$fid; if ($fid > 0) $allFollowerIds[$fid] = true;
        }
    }
    unset($r);

    // Bulk-load follower users
    $userMap = [];
    if (!empty($allFollowerIds)) {
        $ids = array_map('intval', array_keys($allFollowerIds));
        if ($ids) {
            $us = $this->db->select('id, firstname, lastname, profile_image')
                           ->from($u)
                           ->where_in('id', $ids)
                           ->get()->result_array();
            foreach ($us as $uu) {
                $uid = (int)$uu['id'];
                $userMap[$uid] = [
                    'id'     => $uid,
                    'name'   => trim(($uu['firstname'] ?? '') . ' ' . ($uu['lastname'] ?? '')) ?: ('User#'.$uid),
                    'avatar' => $this->avatar_url($uu['profile_image'] ?? null),
                ];
            }
        }
    }

    // Per-row finalize
    foreach ($rows as &$r) {
        // Assignee name
        $fn = trim((string)($r['assignee_firstname'] ?? ''));
        $ln = trim((string)($r['assignee_lastname']  ?? ''));
        $r['assignee_name']   = ($fn || $ln) ? trim("$fn $ln") : ($r['assignee_id'] ? 'User#'.$r['assignee_id'] : '—');
        // Assignee avatar
        $r['assignee_avatar'] = $this->avatar_url($r['assignee_profile_image'] ?? null);

        // Followers rich cards for UI
        $cards = [];
        foreach ((array)$r['followers'] as $fid) {
            $fid = (int)$fid; if ($fid <= 0) continue;
            if (isset($userMap[$fid])) {
                $cards[] = $userMap[$fid];
            } else {
                // fallback if some ID wasn't resolved (deleted/inactive user)
                $cards[] = [
                    'id'     => $fid,
                    'name'   => 'User#'.$fid,
                    'avatar' => $this->avatar_url(null),
                ];
            }
        }
        $r['followers_cards'] = $cards;
    }
    unset($r);

    return ['total' => $total, 'rows' => $rows];
}


/**
 * Build absolute avatar URL from a stored profile_image value.
 * Falls back to your global helper if available; else uses a default.
 */
private function avatar_url($profileImage): string
{
    // Prefer your global helper if present
    if (function_exists('user_avatar_url')) {
        return (string) user_avatar_url($profileImage ?? null);
    }

    // Fallbacks: adjust paths to your project structure
    $this->load->helper('url');
    if (!empty($profileImage)) {
        // If profileImage already looks like a URL, return as-is
        if (preg_match('#^https?://#i', $profileImage)) {
            return $profileImage;
        }
        return base_url(ltrim($profileImage, '/'));
    }
    return base_url('assets/images/default-avatar.png'); // make sure this exists
}

    /* =========================================================================
     * FOLLOWERS / ASSIGNEE utilities
     * ========================================================================= */

    public function set_assignee(int $taskId, ?int $userId): bool
    {
        return $this->update_task($taskId, ['assignee_id' => $userId]);
    }

    public function get_followers(int $taskId): array
    {
        $row = $this->db->select('followers_json')->get_where($this->tables['tasks'], ['id' => $taskId])->row_array();
        return $this->decode_json($row['followers_json'] ?? null);
    }

    public function set_followers(int $taskId, array $userIds): bool
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        return $this->update_task($taskId, ['followers_json' => $userIds]);
    }

    public function add_follower(int $taskId, int $userId): bool
    {
        $f = $this->get_followers($taskId);
        if ($userId > 0 && !in_array($userId, $f, true)) {
            $f[] = $userId;
            return $this->set_followers($taskId, $f);
        }
        return true;
    }

    public function remove_follower(int $taskId, int $userId): bool
    {
        $f = $this->get_followers($taskId);
        $f = array_values(array_diff($f, [(int)$userId]));
        return $this->set_followers($taskId, $f);
    }

    /* =========================================================================
     * COMMENTS
     * ========================================================================= */

    public function add_comment(int $taskId, int $userId, string $comment): int
    {
        $row = [
            'taskid'    => $taskId,
            'user_id'   => $userId,
            'comment'   => $comment,
            'dateadded' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->tables['comments'], $row);
        return (int)$this->db->insert_id();
    }

    public function delete_comment(int $commentId): bool
    {
        $this->db->delete($this->tables['comments'], ['id' => $commentId]);
        return $this->db->affected_rows() > 0;
    }

    public function list_comments(int $taskId): array
    {
        // Accept multiple synonyms; default to DESC (newest first)
        $pref  = strtolower((string) $this->setting('tasks_comments_order', 'descending'));
        $asc   = in_array($pref, ['asc','ascending','oldest','oldest_first'], true);
        $order = $asc ? 'ASC' : 'DESC';
    
        return $this->db
            ->where('taskid', $taskId)            // keep your column name
            ->order_by('dateadded', $order)
            ->get($this->tables['comments'])
            ->result_array();
    }


    /* =========================================================================
     * CHECKLIST
     * ========================================================================= */

    /**
     * [FIXED] Changed signature to accept an array of data.
     */
    public function add_checklist_item(int $taskId, array $data): int
    {
        $row = [
            'taskid'        => $taskId,
            'description'   => trim($data['description'] ?? '...'),
            'finished'      => 0,
            'dateadded'     => date('Y-m-d H:i:s'),
            'addedfrom'     => (int)($data['addedfrom'] ?? $data['created_by'] ?? 0),
            'finished_from' => null,
            'list_order'    => (int)($data['list_order'] ?? $data['order'] ?? 0),
            'assigned'      => isset($data['assigned']) ? (int)$data['assigned'] : null,
        ];
        $this->db->insert($this->tables['checklist'], $row);
        return (int)$this->db->insert_id();
    }

    /**
     * [ADDED] Helper to batch-insert checklist items from the create modal.
     */
    public function create_checklist_items_batch(int $taskId, array $descriptions, int $addedfrom): int
    {
        if (empty($descriptions)) {
            return 0;
        }

        $batch = [];
        $now = date('Y-m-d H:i:s');
        foreach ($descriptions as $i => $desc) {
            $desc = trim($desc);
            if (empty($desc)) continue;
            
            $batch[] = [
                'taskid'      => $taskId,
                'description' => $desc,
                'finished'    => 0,
                'dateadded'   => $now,
                'addedfrom'   => $addedfrom,
                'list_order'  => $i + 1,
            ];
        }

        if (empty($batch)) {
            return 0;
        }

        return $this->db->insert_batch($this->tables['checklist'], $batch);
    }

    public function update_checklist_item(int $itemId, array $data): bool
    {
        $allowed = ['description','finished','finished_from','list_order','assigned'];
        $upd = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $upd[$k] = $data[$k];
            }
        }
        if (!$upd) return true;
        $this->db->where('id', $itemId)->update($this->tables['checklist'], $upd);
        return $this->db->affected_rows() >= 0;
    }

    /**
     * [FIXED] Renamed from `toggle_checklist_finished` to match controller.
     */
    public function toggle_checklist_item(int $itemId, int $userId): int
    {
        $row = $this->get_checklist_item($itemId);
        if (!$row) return -1; // Not found

        $finished = (int)$row['finished'] ? 0 : 1;
        $upd = [
            'finished'      => $finished,
            'finished_from' => $finished ? $userId : null,
        ];
        $this->db->where('id', $itemId)->update($this->tables['checklist'], $upd);
        return $finished;
    }

    /**
     * [ADDED] Was missing, but controller was calling it.
     */
    public function get_checklist_item(int $itemId): ?array
    {
        return $this->db->get_where($this->tables['checklist'], ['id' => $itemId])->row_array();
    }

    public function list_checklist(int $taskId): array
    {
        return $this->db
            ->where('taskid', $taskId)
            ->order_by('list_order', 'ASC')
            ->order_by('id', 'ASC')
            ->get($this->tables['checklist'])
            ->result_array();
    }

    public function delete_checklist_item(int $itemId): bool
    {
        $this->db->delete($this->tables['checklist'], ['id' => $itemId]);
        return $this->db->affected_rows() > 0;
    }

    public function checklist_summary(int $taskId): array
    {
        $rows = $this->db->select('finished, COUNT(*) as cnt')
            ->from($this->tables['checklist'])
            ->where('taskid', $taskId)
            ->group_by('finished')
            ->get()->result_array();

        $total = 0; $done = 0;
        foreach ($rows as $r) {
            $total += (int)$r['cnt'];
            if ((int)$r['finished'] === 1) $done += (int)$r['cnt'];
        }
        return ['total' => $total, 'done' => $done, 'pending' => max(0, $total - $done)];
    }

    /* =========================================================================
     * ATTACHMENTS
     * ========================================================================= */

    /**
     * [FIXED] Renamed from `add_attachment` to match controller.
     * Controller should handle filesystem logic.
     */
public function create_attachment(array $data): int
{
    // Enforce max attachments per settings (0 => disabled)
    $max = (int) $this->setting('tasks_max_attachments', 10);
    if ($max > 0) {
        $cnt = (int) $this->db
            ->where('taskid', (int)$data['taskid'])
            ->count_all_results($this->tables['attachments']);
        if ($cnt >= $max) return 0;
    }

    $row = [
        'taskid'      => (int)$data['taskid'],
        'file_name'   => $data['file_name'],
        'file_path'   => $data['file_path'],
        'uploaded_by' => (int)($data['uploaded_by'] ?? $data['added_by'] ?? 0),
        'uploaded_at' => ($data['uploaded_at'] ?? $data['created_at'] ?? date('Y-m-d H:i:s')),
    ];
    $this->db->insert($this->tables['attachments'], $row);
    return (int)$this->db->insert_id();
}

/** NEW: count attachments for a task */
public function count_attachments(int $taskId): int
{
    if ($taskId <= 0) return 0;
    return (int) $this->db
        ->where('taskid', $taskId)
        ->count_all_results($this->tables['attachments']);
}


/**
 * Return one attachment (enriched) or null.
 * Adds:
 *  - uploaded_by_name
 *  - uploaded_by_avatar (if helper available, else best-effort)
 *  - uploaded_by_url
 */
public function get_attachment(int $attachmentId): ?array
{
    if ($attachmentId <= 0) return null;

    $ta = $this->db->dbprefix($this->tables['attachments']); // e.g., task_attachments
    $u  = $this->db->dbprefix('users');

    $row = $this->db
        ->select("
            {$ta}.id,
            {$ta}.taskid,
            {$ta}.file_name,
            {$ta}.file_path,
            {$ta}.uploaded_by,
            {$ta}.uploaded_at,
            {$u}.id    AS uploader_id,
            TRIM(CONCAT(COALESCE({$u}.firstname,''),' ',COALESCE({$u}.lastname,''))) AS uploaded_by_name,
            {$u}.profile_image AS uploaded_by_profile_image
        ", false)
        ->from("{$ta}")
        ->join($u, "{$u}.id = {$ta}.uploaded_by", "left")
        ->where("{$ta}.id", $attachmentId)
        ->limit(1)
        ->get()
        ->row_array();

    if (!$row) return null;

    // Optional enrichments for the view
    if (!empty($row['uploader_id'])) {
        $row['uploaded_by_url'] = site_url('users/view/'.$row['uploader_id']);
    } else {
        $row['uploaded_by_url'] = '';
    }

    // Avatar URL (prefer helper if available)
    $pi = $row['uploaded_by_profile_image'] ?? null;
    if (function_exists('user_avatar_url')) {
        $row['uploaded_by_avatar'] = user_avatar_url($pi);
    } else {
        $row['uploaded_by_avatar'] = $pi
            ? base_url('uploads/users/profile/'.$pi)
            : base_url('assets/images/default.png');
    }

    return $row;
}

/**
 * List all attachments for a task (newest first), enriched with uploader info.
 * Adds per row:
 *  - uploaded_by_name
 *  - uploaded_by_avatar (if helper available, else best-effort)
 *  - uploaded_by_url
 */
public function list_attachments(int $taskId): array
{
    if ($taskId <= 0) return [];

    $ta = $this->db->dbprefix($this->tables['attachments']);
    $u  = $this->db->dbprefix('users');

    $rows = $this->db
        ->select("
            {$ta}.id,
            {$ta}.taskid,
            {$ta}.file_name,
            {$ta}.file_path,
            {$ta}.uploaded_by,
            {$ta}.uploaded_at,
            {$u}.id    AS uploader_id,
            TRIM(CONCAT(COALESCE({$u}.firstname,''),' ',COALESCE({$u}.lastname,''))) AS uploaded_by_name,
            {$u}.profile_image AS uploaded_by_profile_image
        ", false)
        ->from("{$ta}")
        ->join($u, "{$u}.id = {$ta}.uploaded_by", "left")
        ->where("{$ta}.taskid", $taskId)
        ->order_by("{$ta}.uploaded_at", "DESC")
        ->get()
        ->result_array();

    if (!$rows) return [];

    $hasHelper = function_exists('user_avatar_url');
    foreach ($rows as &$r) {
        // URL to uploader profile
        $r['uploaded_by_url'] = !empty($r['uploader_id'])
            ? site_url('users/view/'.$r['uploader_id'])
            : '';

        // Avatar URL
        $pi = $r['uploaded_by_profile_image'] ?? null;
        if ($hasHelper) {
            $r['uploaded_by_avatar'] = user_avatar_url($pi);
        } else {
            $r['uploaded_by_avatar'] = $pi
                ? base_url('uploads/users/profile/'.$pi)
                : base_url('assets/images/default.png');
        }
    }
    unset($r);

    return $rows;
}

/**
 * Delete one attachment record.
 * (Controller handles file unlinking; we only remove the row.)
 */
public function delete_attachment(int $attachmentId): bool
{
    if ($attachmentId <= 0) return false;

    $this->db->where('id', $attachmentId)->delete($this->tables['attachments']);
    return $this->db->affected_rows() > 0;
}


    /* =========================================================================
     * ACTIVITY
     * ========================================================================= */

    public function add_activity(int $taskId, ?int $userId, string $activity, ?string $description = null): int
    {
        $row = [
            'taskid'      => $taskId,
            'user_id'     => $userId,
            'activity'    => $activity,
            'description' => $description,
            'dateadded'   => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->tables['activity'], $row);
        return (int)$this->db->insert_id();
    }

    /* =========================================================================
     * STATUS / TRANSITIONS (data-level only)
     * ========================================================================= */

    public function set_status(int $taskId, string $status, ?int $userId = null, bool $autoFinishDate = true): bool
    {
        $upd = ['status' => $status];

        if ($autoFinishDate) {
            if ($status === 'completed') {
                $upd['datefinished'] = date('Y-m-d H:i:s');
            } else {
                $upd['datefinished'] = null;
            }
        }

        $ok = $this->update_task($taskId, $upd);
        if ($ok) {
            $this->add_activity($taskId, $userId, 'status_changed', $status);
        }
        return $ok;
    }

    /* =========================================================================
     * CRON
     * ========================================================================= */

    /**
     * [ADDED] Placeholder for recurring task generation.
     * This was defined in `tasks.php` (module file) but missing here.
     */
    public function cron_generate_recurring()
    {
        // --- TODO: Add logic to find recurring tasks due to be created ---
        // 1. Find all tasks where `recurring` = 1
        // 2. Check `recurring_type`, `repeat_every`, and `last_recurring_date`
        // 3. If a new task is due, create it using `create_task()`
        // 4. Update the parent task's `last_recurring_date` and `total_cycles`
        
        $logMsg = '[Tasks Module] Cron: `cron_generate_recurring` executed (no logic yet).';
        log_message('info', $logMsg);
        
        // Return a string for the cron log
        return $logMsg;
    }


/* -------------------------------------------------------------
 * User resolvers (scoped here for Tasks)
 * ----------------------------------------------------------- */

/** Return a single user row (or null) */
public function user_get_by_id(int $id): ?array
{
    if ($id <= 0) return null;
    $row = $this->db->get_where($this->tables['users'], ['id' => $id])->row_array();
    return $row ?: null;
}

/** Bulk fetch and return a map: id => row */
public function user_get_map_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return [];
    $rows = $this->db->where_in('id', $ids)->get($this->tables['users'])->result_array();
    $map  = [];
    foreach ($rows as $r) { $map[(int)$r['id']] = $r; }
    return $map;
}

/** Human name for a user id; accepts an optional $map from user_get_map_by_ids() */
public function user_get_name(?int $id, array $map = []): string
{
    $id = (int)($id ?? 0);
    if ($id <= 0) return '';
    $row = $map[$id] ?? $this->user_get_by_id($id);
    if (!$row) return 'User#'.$id;

    $first = trim((string)($row['firstname'] ?? ''));
    $last  = trim((string)($row['lastname']  ?? ''));
    $full  = trim($first.' '.$last);
    return $full !== '' ? $full : (string)($row['username'] ?? ('User#'.$id));
}

/** Avatar URL for a user id; prefers user_avatar_url() helper if available */
public function user_get_avatar(?int $id, array $map = []): ?string
{
    $id = (int)($id ?? 0);
    if ($id <= 0) return null;
    $row = $map[$id] ?? $this->user_get_by_id($id);
    if (!$row) return null;

    $profile = $row['profile_image'] ?? null;

    if (function_exists('user_avatar_url')) {
        return user_avatar_url($profile);
    }
    if (!empty($profile)) {
        return function_exists('base_url')
            ? base_url('uploads/avatars/'.ltrim($profile, '/'))
            : '/uploads/avatars/'.ltrim($profile, '/');
    }
    return null;
}

/** Compact list for dropdowns (id + fullname), active users only */
public function user_get_active_minimal_list(): array
{
    return $this->db
        ->select('id, TRIM(CONCAT(COALESCE(firstname,""), " ", COALESCE(lastname,""))) AS fullname', false)
        ->from($this->tables['users'])
        ->where('is_active', 1)
        ->order_by('fullname', 'ASC')
        ->get()
        ->result_array();
}

/** Resolve an array of user IDs into cards: id, name, avatar */
public function user_resolve_cards(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return [];
    $map = $this->user_get_map_by_ids($ids);

    $out = [];
    foreach ($ids as $uid) {
        $out[] = [
            'id'     => $uid,
            'name'   => $this->user_get_name($uid, $map),
            'avatar' => $this->user_get_avatar($uid, $map),
        ];
    }
    return $out;
}


public function checklist_summary_batch(array $taskIds): array
{
    $taskIds = array_values(array_unique(array_filter(array_map('intval', $taskIds))));
    if (!$taskIds) return [];

    $t = $this->tables['checklist'];
    $rows = $this->db
        ->select("taskid,
                  COUNT(*) AS total,
                  SUM(CASE WHEN finished = 1 THEN 1 ELSE 0 END) AS done", false)
        ->from($t)
        ->where_in('taskid', $taskIds)
        ->group_by('taskid')
        ->get()->result_array();

    $out = [];
    foreach ($rows as $r) {
        $total   = (int)$r['total'];
        $done    = (int)$r['done'];
        $pending = max(0, $total - $done);
        $percent = $total > 0 ? (int)round(($done / $total) * 100) : 0;
        $out[(int)$r['taskid']] = [
            'total'   => $total,
            'done'    => $done,
            'pending' => $pending,
            'percent' => $percent,
        ];
    }
    return $out;
}


/**
 * Batch count comments (discussions) per task.
 */
public function comments_count_batch(array $taskIds): array
{
    $taskIds = array_values(array_unique(array_filter(array_map('intval', $taskIds))));
    if (!$taskIds) {
        return [];
    }

    $tbl = $this->tables['comments']; // 'task_comments'
    $rows = $this->db->select('taskid, COUNT(*) AS total', false)
        ->from($tbl)
        ->where_in('taskid', $taskIds)
        ->group_by('taskid')
        ->get()
        ->result_array();

    $out = [];
    foreach ($rows as $r) {
        $out[(int)$r['taskid']] = (int)$r['total'];
    }
    return $out;
}

/**
 * Batch count checklist items per task.
 * (Separate from checklist_summary_batch because sometimes you just need totals.)
 */
public function checklist_items_count_batch(array $taskIds): array
{
    $taskIds = array_values(array_unique(array_filter(array_map('intval', $taskIds))));
    if (!$taskIds) {
        return [];
    }

    $tbl = $this->tables['checklist']; // 'task_checklist_items'
    $rows = $this->db->select('taskid, COUNT(*) AS total', false)
        ->from($tbl)
        ->where_in('taskid', $taskIds)
        ->group_by('taskid')
        ->get()
        ->result_array();

    $out = [];
    foreach ($rows as $r) {
        $out[(int)$r['taskid']] = (int)$r['total'];
    }
    return $out;
}


/**
 * One-stop builder for all "meta chip" numbers on the Tasks index.
 *
 * For each task ID, returns:
 *  - attachments   => int
 *  - project_tasks => int
 *  - comments      => int
 *  - checklists    => int
 *  - members       => int (addedfrom + assignee + followers)
 */
public function build_meta_counters_for_rows(array $rows): array
{
    if (!$rows) {
        return [];
    }

    // Normalize task IDs
    $taskIds = [];
    foreach ($rows as $r) {
        $tid = (int)($r['id'] ?? 0);
        if ($tid > 0) {
            $taskIds[] = $tid;
        }
    }
    $taskIds = array_values(array_unique($taskIds));
    if (!$taskIds) {
        return [];
    }

    // Batch DB work
    $attachments   = $this->attachments_count_batch($taskIds);        // already exists in this model
    $comments      = $this->comments_count_batch($taskIds);
    $checklistItems= $this->checklist_items_count_batch($taskIds);

    // Init base structure
    $meta = [];
    foreach ($taskIds as $tid) {
        $meta[$tid] = [
            'attachments'   => $attachments[$tid]   ?? 0,
            'project_tasks' => $projectTasks[$tid]  ?? 0,
            'comments'      => $comments[$tid]      ?? 0,
            'checklists'    => $checklistItems[$tid]?? 0,
            'members'       => 0, // calculated below
        ];
    }

    // Members = unique (addedfrom + assignee + followers[])
    foreach ($rows as $r) {
        $tid = (int)($r['id'] ?? 0);
        if ($tid <= 0 || !isset($meta[$tid])) {
            continue;
        }

        $set = [];

        if (!empty($r['addedfrom'])) {
            $set[(int)$r['addedfrom']] = true;
        }
        if (!empty($r['assignee_id'])) {
            $set[(int)$r['assignee_id']] = true;
        }

        // Followers: list_tasks() has already decoded followers_json into $r['followers']
        foreach ((array)($r['followers'] ?? []) as $fid) {
            $fid = (int)$fid;
            if ($fid > 0) {
                $set[$fid] = true;
            }
        }

        $meta[$tid]['members'] = count($set);
    }

    return $meta;
}


/** NEW: attachments_count_batch() */
public function attachments_count_batch(array $taskIds): array
{
    $taskIds = array_values(array_unique(array_filter(array_map('intval', $taskIds))));
    if (!$taskIds) return [];

    $ta   = $this->tables['attachments']; // 'task_attachments'
    $rows = $this->db
        ->select('taskid, COUNT(*) AS total', false)
        ->from($ta)
        ->where_in('taskid', $taskIds)
        ->group_by('taskid')
        ->get()
        ->result_array();

    $out = [];
    foreach ($rows as $r) {
        $out[(int)$r['taskid']] = (int)$r['total'];
    }
    return $out;
}


public function update_task_fields(int $taskId, array $fields): bool
{
    if (!$taskId || !$fields) return false;
    $this->db->where('id', $taskId)->update('tasks', $fields);
    return $this->db->affected_rows() >= 0;
}


public function get_activity(int $taskId): array
{
    return $this->db->where('taskid', $taskId)
        ->order_by('dateadded', 'DESC')
        ->get($this->tables['activity'])
        ->result_array();
}

public function list_activity(int $taskId, int $limit = 200): array
{
    if ($taskId <= 0) return [];

    $ta = $this->db->dbprefix($this->tables['activity']);
    $u  = $this->db->dbprefix($this->tables['users']);

    $rows = $this->db
        ->select("
            {$ta}.id,
            {$ta}.taskid,
            {$ta}.user_id,
            {$ta}.activity,
            {$ta}.description,
            {$ta}.dateadded,
            {$u}.firstname,
            {$u}.lastname,
            {$u}.profile_image
        ", false)
        ->from($ta)
        ->join($u, "{$u}.id = {$ta}.user_id", "left")
        ->where("{$ta}.taskid", $taskId)
        ->order_by("{$ta}.id", "DESC")
        ->limit($limit)
        ->get()
        ->result_array();

    if (!$rows) return [];

    // Enrich: user_name + user_avatar
    $out = [];
    foreach ($rows as $r) {
        $name = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));
        if ($name === '') $name = $r['user_id'] ? ('User #' . (int)$r['user_id']) : 'System';

        // avatar_url() already exists in this model
        $avatar = $this->avatar_url($r['profile_image'] ?? null);

        $out[] = [
            'id'          => (int)$r['id'],
            'taskid'      => (int)$r['taskid'],
            'user_id'     => (int)$r['user_id'],
            'user_name'   => $name,
            'user_avatar' => $avatar,
            'activity'    => (string)$r['activity'],
            'description' => (string)($r['description'] ?? ''),
            'dateadded'   => (string)$r['dateadded'],
        ];
    }
    return $out;
}

/**
 * Calendar feed (3-arg signature, aligned with your other modules)
 * Returns rows with keys: id, title, description, status, priority, start_date, end_date
 * Scopes to "own" visibility for the given $user_id:
 *   - creator (addedfrom = user)
 *   - assignee (assignee_id = user)
 *   - follower (followers_json contains user id)
 */
public function get_calendar_events($start, $end, $user_id)
{
    // Normalize dates to Y-m-d
    try {
        $start_date = (new DateTime((string)$start))->format('Y-m-d');
        $end_date   = (new DateTime((string)$end))->format('Y-m-d');
    } catch (Exception $e) {
        log_message('error', 'Tasks_model::get_calendar_events invalid dates: '.$e->getMessage());
        return [];
    }

    $t = $this->tables['tasks'];

    // Build query using only existing columns in your schema
    $this->db->select("
        {$t}.id,
        {$t}.name        AS title,
        {$t}.description,
        {$t}.status,
        {$t}.priority,
        {$t}.startdate   AS start_date,
        {$t}.duedate     AS end_date,
        {$t}.addedfrom,
        {$t}.assignee_id,
        {$t}.followers_json
    ", false);
    $this->db->from($t);

    // Scope to the requesting user (own visibility)
    $uid = (int)$user_id;
    if ($uid > 0) {
        $this->db->group_start()
            ->where("{$t}.addedfrom",   $uid)
            ->or_where("{$t}.assignee_id", $uid)
            ->or_like("{$t}.followers_json", '"' . $uid . '"')
        ->group_end();
    }

    // Overlap with calendar window:
    //  - if both startdate and duedate exist: standard overlap check
    //  - if only startdate exists: startdate within window
    //  - if only duedate exists: duedate within window
    $startEsc = $this->db->escape($start_date);
    $endEsc   = $this->db->escape($end_date);

    $this->db->group_start()
        ->group_start()
            ->where("{$t}.startdate IS NOT NULL", null, false)
            ->where("{$t}.duedate   IS NOT NULL", null, false)
            ->where("NOT ({$t}.duedate < {$startEsc} OR {$t}.startdate > {$endEsc})", null, false)
        ->group_end()
        ->or_group_start()
            ->where("{$t}.startdate IS NOT NULL", null, false)
            ->where("{$t}.duedate   IS NULL", null, false)
            ->where("{$t}.startdate >=", $start_date)
            ->where("{$t}.startdate <=", $end_date)
        ->group_end()
        ->or_group_start()
            ->where("{$t}.startdate IS NULL", null, false)
            ->where("{$t}.duedate   IS NOT NULL", null, false)
            ->where("{$t}.duedate >=", $start_date)
            ->where("{$t}.duedate <=", $end_date)
        ->group_end()
    ->group_end();

    $result = $this->db->get()->result_array();

    // Debug logs in the exact style you’re using elsewhere
    //log_message('debug', "Tasks_model::get_calendar_events using start={$start_date} end={$end_date} SQL: " . $this->db->last_query());
    //log_message('debug', 'Tasks_model::get_calendar_events result: ' . json_encode($result));
    
    // Optional: hydrate assignees/followers arrays for extendedProps consumers
    if ($result) {
        $assigneeIds = [];
        $followerIds = [];
        foreach ($result as $r) {
            if (!empty($r['assignee_id'])) $assigneeIds[(int)$r['assignee_id']] = true;
            foreach ($this->decode_json($r['followers_json'] ?? null) as $fid) {
                $fid = (int)$fid; if ($fid > 0) $followerIds[$fid] = true;
            }
        }
        $needUserIds = array_values(array_unique(array_merge(array_keys($assigneeIds), array_keys($followerIds))));
        $userMap = $needUserIds ? $this->user_get_map_by_ids($needUserIds) : [];

        foreach ($result as &$r) {
            $r['assignees'] = [];
            if (!empty($r['assignee_id'])) {
                $aid = (int)$r['assignee_id'];
                $r['assignees'][] = ['id' => $aid, 'name' => $this->user_get_name($aid, $userMap)];
            }
            $r['followers'] = [];
            foreach ($this->decode_json($r['followers_json'] ?? null) as $fid) {
                $fid = (int)$fid; if ($fid <= 0) continue;
                $r['followers'][] = ['id' => $fid, 'name' => $this->user_get_name($fid, $userMap)];
            }
            // Optional: unset the raw JSON to keep payload clean
            // unset($r['followers_json'], $r['addedfrom'], $r['assignee_id']);
        }
        unset($r);
    }

    return $result;
}

/**
 * Back-compat adapter for callers using the array options signature.
 * Example input: ['start'=>'Y-m-d','end'=>'Y-m-d','user_id'=>123,'scope'=>'global|own']
 * Note: we currently enforce "own" scope identical to the 3-arg method.
 */
public function get_calendar_events_opts(array $opts): array
{
    $start  = $opts['start']   ?? null;
    $end    = $opts['end']     ?? null;
    $userId = $opts['user_id'] ?? 0;

    return $this->get_calendar_events($start, $end, $userId);
}
    
}