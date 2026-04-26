<?php defined('BASEPATH') or exit('No direct script access allowed');

class Support_posts_model extends CI_Model
{
    protected $table = 'support_posts';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Add a public message to a ticket.
     * @param int    $ticket_id
     * @param int    $author_id
     * @param string $body
     * @param array  $attachments [{name,path,size,mime}, ...]
     * @param bool   $is_staff    If true and first public reply, sets first_responded_at
     */
    public function add_message(int $ticket_id, int $author_id, string $body, array $attachments = [], bool $is_staff = false)
    {
        $row = [
            'ticket_id'   => $ticket_id,
            'author_id'   => $author_id,
            'type'        => 'message',
            'body'        => $body,
            'attachments' => $attachments ? json_encode($attachments) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $row);

        // Touch ticket activity
        $this->load->model('support/Support_tickets_model', 'tickets');
        $this->tickets->touch_last_activity($ticket_id);

        // If staff and first response isn't set, set it now
        if ($is_staff) {
            $ticket = $this->db->select('first_responded_at')->from('support_tickets')->where('id', $ticket_id)->get()->row_array();
            if ($ticket && empty($ticket['first_responded_at'])) {
                $this->db->where('id', $ticket_id)->update('support_tickets', [
                    'first_responded_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        if (!empty($attachments)) {
            $this->tickets->bump_files_count($ticket_id, count($attachments));
        }

        return (int)$this->db->insert_id();
    }

    /**
     * Add an internal note (not visible to requester).
     */
    public function add_note(int $ticket_id, int $author_id, string $body, array $attachments = [])
    {
        $row = [
            'ticket_id'   => $ticket_id,
            'author_id'   => $author_id,
            'type'        => 'note',
            'body'        => $body,
            'attachments' => $attachments ? json_encode($attachments) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->table, $row);

        $this->load->model('support/Support_tickets_model', 'tickets');
        $this->tickets->touch_last_activity($ticket_id);

        if (!empty($attachments)) {
            $this->tickets->bump_files_count($ticket_id, count($attachments));
        }

        return (int)$this->db->insert_id();
    }

    public function get_by_ticket(int $ticket_id, string $order = 'ASC')
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->where('ticket_id', $ticket_id)->order_by('created_at', $order);
        $rows = $this->db->get($this->table)->result_array();

        foreach ($rows as &$r) {
            $r['attachments'] = $r['attachments'] ? json_decode($r['attachments'], true) : [];
        }
        return $rows;
    }
}
