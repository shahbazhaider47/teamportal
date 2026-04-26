<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crm_activity_model extends CI_Model
{
    protected $table = 'crm_activity';

    public function __construct()
    {
        parent::__construct();
    }

    public function log(array $data): int
    {
        $payload = [
            'user_id'     => isset($data['user_id']) ? (int)$data['user_id'] : null,
            'rel_type'    => trim((string)($data['rel_type'] ?? '')),
            'rel_id'      => isset($data['rel_id']) ? (int)$data['rel_id'] : null,
            'action'      => trim((string)($data['action'] ?? '')),
            'description' => isset($data['description']) ? trim((string)$data['description']) : null,
            'metadata'    => null,
            'ip_address'  => $data['ip_address'] ?? $this->input->ip_address(),
            'created_at'  => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        if (!empty($data['metadata'])) {
            $payload['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($payload['rel_type'] === '' || $payload['action'] === '') {
            return 0;
        }

        $this->db->insert($this->table, $payload);
        return (int)$this->db->insert_id();
    }

    public function get_by_relation(string $relType, int $relId, int $limit = 50): array
    {
        return $this->db
            ->select('a.*, u.fullname as user_name')
            ->from($this->table . ' a')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->where('a.rel_type', $relType)
            ->where('a.rel_id', $relId)
            ->order_by('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    public function get_all(array $filters = [], int $limit = 100): array
    {
        $this->db
            ->select('a.*, u.fullname as user_name')
            ->from($this->table . ' a')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->order_by('a.created_at', 'DESC')
            ->limit($limit);

        if (!empty($filters['rel_type'])) {
            $this->db->where('a.rel_type', trim((string)$filters['rel_type']));
        }

        if (!empty($filters['rel_id'])) {
            $this->db->where('a.rel_id', (int)$filters['rel_id']);
        }

        if (!empty($filters['action'])) {
            $this->db->where('a.action', trim((string)$filters['action']));
        }

        if (!empty($filters['user_id'])) {
            $this->db->where('a.user_id', (int)$filters['user_id']);
        }

        return $this->db->get()->result_array();
    }
}