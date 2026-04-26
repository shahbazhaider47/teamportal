<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Crm_files_model extends CI_Model
{
    protected $table = 'crm_files';
    protected $pk    = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
     * CORE GETTERS
     * ======================================================= */

    public function get(int $id): ?array
    {
        $row = $this->db
            ->where($this->pk, $id)
            ->limit(1)
            ->get($this->table)
            ->row_array();

        return $row ?: null;
    }

    public function get_with_meta(int $id): ?array
    {
        $row = $this->db
            ->select("
                f.*,
                creator.fullname AS created_by_name,
                updater.fullname AS updated_by_name
            ")
            ->from($this->table . ' f')
            ->join('users creator', 'creator.id = f.created_by', 'left')
            ->join('users updater', 'updater.id = f.updated_by', 'left')
            ->where('f.id', $id)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    public function get_by_relation(string $relatedType, int $relatedId): array
    {
        return $this->db
            ->select("
                f.*,
                creator.fullname AS created_by_name,
                updater.fullname AS updated_by_name
            ")
            ->from($this->table . ' f')
            ->join('users creator', 'creator.id = f.created_by', 'left')
            ->join('users updater', 'updater.id = f.updated_by', 'left')
            ->where('f.related_type', trim($relatedType))
            ->where('f.related_id', $relatedId)
            ->order_by('f.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function count_by_relation(string $relatedType, int $relatedId): int
    {
        return (int)$this->db
            ->where('related_type', trim($relatedType))
            ->where('related_id', $relatedId)
            ->count_all_results($this->table);
    }

    /* =========================================================
     * CRUD
     * ======================================================= */

    public function insert(array $data): int
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db->insert($this->table, $data);
        return (int)$this->db->insert_id();
    }

    public function insert_batch(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($rows as &$row) {
            if (!isset($row['created_at'])) {
                $row['created_at'] = $now;
            }
        }
        unset($row);

        return (bool)$this->db->insert_batch($this->table, $rows);
    }

    public function update(int $id, array $data): bool
    {
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return (bool)$this->db
            ->where($this->pk, $id)
            ->update($this->table, $data);
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db
            ->where($this->pk, $id)
            ->delete($this->table);
    }

    public function delete_by_relation(string $relatedType, int $relatedId): bool
    {
        return (bool)$this->db
            ->where('related_type', trim($relatedType))
            ->where('related_id', $relatedId)
            ->delete($this->table);
    }

    /* =========================================================
     * VALIDATION / HELPERS
     * ======================================================= */

    public function relation_exists(string $relatedType, int $relatedId): bool
    {
        $relatedType = trim(strtolower($relatedType));
        if ($relatedId <= 0 || $relatedType === '') {
            return false;
        }

        switch ($relatedType) {
            case 'lead':
                return (bool)$this->db->where('id', $relatedId)->count_all_results('crm_leads');

            case 'client':
                return (bool)$this->db->where('id', $relatedId)->count_all_results('crm_clients');

            case 'proposal':
                return (bool)$this->db->where('id', $relatedId)->count_all_results('crm_proposals');

            case 'activity':
                return (bool)$this->db->where('id', $relatedId)->count_all_results('crm_activity');

            case 'note':
                return (bool)$this->db->where('id', $relatedId)->count_all_results('crm_notes');

            default:
                return false;
        }
    }

    public function allowed_related_types(): array
    {
        return [
            'lead',
            'client',
            'proposal',
            'activity',
            'note',
        ];
    }

    public function is_allowed_related_type(string $relatedType): bool
    {
        return in_array(trim(strtolower($relatedType)), $this->allowed_related_types(), true);
    }

    public function build_storage_path(string $relatedType, int $relatedId): string
    {
        $relatedType = trim(strtolower($relatedType));
        return FCPATH . 'uploads/crm/' . $relatedType . '/' . $relatedId . '/';
    }

    public function build_db_path(string $relatedType, int $relatedId, string $fileName): string
    {
        $relatedType = trim(strtolower($relatedType));
        return 'uploads/crm/' . $relatedType . '/' . $relatedId . '/' . $fileName;
    }

    public function ensure_directory(string $absolutePath): bool
    {
        if (is_dir($absolutePath)) {
            return true;
        }

        return @mkdir($absolutePath, 0755, true);
    }
}