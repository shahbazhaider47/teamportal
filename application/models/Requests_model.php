<?php defined('BASEPATH') or exit('No direct script access allowed');

class Requests_model extends CI_Model
{
    protected string $table = 'requests';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'request_no'    => $data['request_no'],
            'type'          => $data['type'],
            'requested_by'  => $data['requested_by'],
            'assigned_to'   => $data['assigned_to'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'status'        => $data['status'] ?? 'pending',
            'priority'      => $data['priority'] ?? 'normal',
            'payload'       => json_encode($data['payload'], JSON_UNESCAPED_UNICODE),
            'attachments'   => !empty($data['attachments'])
                ? json_encode($data['attachments'], JSON_UNESCAPED_UNICODE)
                : null,
            'submitted_at'  => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $this->db->insert($this->table, $insert);

        return (int) $this->db->insert_id();
    }

    public function get_by_user_and_type(
        int $user_id,
        string $type,
        array $filters = []
    ): array {
        $this->db->from($this->table);
        $this->db->where('requested_by', $user_id);
        $this->db->where('type', $type);

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }

        if (!empty($filters['limit'])) {
            $this->db->limit((int) $filters['limit']);
        }

        $this->db->order_by('submitted_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_all_by_user(int $user_id, array $filters = []): array
    {
        $this->db->from($this->table);
        $this->db->where('requested_by', $user_id);

        if (!empty($filters['type'])) {
            $this->db->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }

        $this->db->order_by('submitted_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_request_stats(int $user_id, array $filters = []): array
    {
        $stats = [
            'total'    => 0,
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
            'other'    => 0,
        ];

        $this->db->select('status, COUNT(*) AS total');
        $this->db->from($this->table);
        $this->db->where('requested_by', $user_id);

        if (!empty($filters['type'])) {
            $this->db->where('type', $filters['type']);
        }

        $this->db->group_by('status');

        $rows = $this->db->get()->result_array();

        foreach ($rows as $row) {
            $status = $row['status'];
            $count  = (int) $row['total'];

            $stats['total'] += $count;

            if (isset($stats[$status])) {
                $stats[$status] = $count;
            } else {
                $stats['other'] += $count;
            }
        }

        return $stats;
    }

    public function get_existing_for_selector(
        int $user_id,
        string $type,
        int $limit = 5
    ): array {
        $this->db->select('id, request_no, status, submitted_at');
        $this->db->from($this->table);
        $this->db->where('requested_by', $user_id);
        $this->db->where('type', $type);
        $this->db->order_by('submitted_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    public function generate_request_no(): string
    {
        $year = date('Y');
        $this->db->select('COUNT(*) AS total');
        $this->db->from($this->table);
        $this->db->where('YEAR(created_at)', $year, false);

        $count = (int) ($this->db->get()->row()->total ?? 0);

        return sprintf('REQ-%s-%06d', $year, $count + 1);
    }

    public function get_by_id(int $id): ?array
    {
        $row = $this->db
            ->from($this->table)
            ->where('id', $id)
            ->limit(1)
            ->get()
            ->row_array();
    
        if (!$row) {
            return null;
        }
    
        $row['payload'] = !empty($row['payload'])
            ? json_decode($row['payload'], true)
            : [];
    
        $row['attachments'] = !empty($row['attachments'])
            ? json_decode($row['attachments'], true)
            : [];
    
        return $row;
    }

    public function delete(int $id): bool
    {
        return $this->db
            ->where('id', $id)
            ->delete($this->table);
    }
        
}


