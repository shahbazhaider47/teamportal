<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Notepad_model extends CI_Model
{
    /* ---------- FOLDERS ---------- */

    public function folders_for_user(int $uid): array
    {
        return $this->db->select('id, name, color, icon, created_at, updated_at')
                        ->from('notepad_folders')
                        ->where('user_id', $uid)
                        ->where('deleted_at IS NULL', null, false)
                        ->order_by('name','ASC')
                        ->get()->result_array();
    }

    public function folder_create(int $uid, array $data): int
    {
        $row = [
            'user_id'    => $uid,
            'name'       => $data['name']  ?? null,
            'color'      => $data['color'] ?? null,
            'icon'       => $data['icon']  ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert('notepad_folders', $row);
        return (int)$this->db->insert_id();
    }

    /* ---------- NOTES ---------- */

    public function notes_for_user(int $uid, array $filters): array
    {
        $this->db->from('notepad_notes n')
                 ->select('
                    n.id, n.user_id, n.folder_id, n.title, n.content,
                    n.is_pinned, n.is_favorite, n.is_locked,
                    n.status, n.color, n.sort_order,
                    n.updated_by, n.created_at, n.updated_at,
                    f.name AS folder_name, f.color AS folder_color, f.icon AS folder_icon
                 ', false)
                 ->join('notepad_folders f', 'f.id = n.folder_id AND f.deleted_at IS NULL', 'left')
                 ->where('n.user_id', $uid)
                 ->where('n.deleted_at IS NULL', null, false);

        if (!empty($filters['status'])) {
            $this->db->where('n.status', $filters['status']);
        }
        if (!empty($filters['folder_id'])) {
            $this->db->where('n.folder_id', (int)$filters['folder_id']);
        }

        // pinned first, then updated_at desc
        $this->db->order_by('n.is_pinned', 'DESC')
                 ->order_by('COALESCE(n.updated_at, n.created_at)', 'DESC', false);

        return $this->db->get()->result_array();
    }

    public function note_create(array $payload): int
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'user_id'     => (int)$payload['user_id'],
            'folder_id'   => $payload['folder_id'] ?? null,
            'title'       => $payload['title'] ?? '',
            'content'     => $payload['content'] ?? '',
            'is_pinned'   => (int)($payload['is_pinned'] ?? 0),
            'is_favorite' => (int)($payload['is_favorite'] ?? 0),
            'is_locked'   => (int)($payload['is_locked'] ?? 0),
            'status'      => $payload['status'] ?? 'active',
            'color'       => $payload['color'] ?? null,
            'sort_order'  => (int)($payload['sort_order'] ?? 0),
            'updated_by'  => (int)($payload['updated_by'] ?? $payload['user_id']),
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
        $this->db->insert('notepad_notes', $row);
        return (int)$this->db->insert_id();
    }

    public function note_update(int $uid, int $id, array $payload): bool
    {
        return $this->db->where('id', $id)
                        ->where('user_id', $uid)
                        ->update('notepad_notes', [
                            'folder_id'   => $payload['folder_id'] ?? null,
                            'title'       => $payload['title'] ?? '',
                            'content'     => $payload['content'] ?? '',
                            'is_pinned'   => (int)($payload['is_pinned'] ?? 0),
                            'is_favorite' => (int)($payload['is_favorite'] ?? 0),
                            'is_locked'   => (int)($payload['is_locked'] ?? 0),
                            'status'      => $payload['status'] ?? 'active',
                            'color'       => $payload['color'] ?? null,
                            'sort_order'  => (int)($payload['sort_order'] ?? 0),
                            'updated_by'  => (int)($payload['updated_by'] ?? $uid),
                            'updated_at'  => date('Y-m-d H:i:s'),
                        ]);
    }

    public function note_partial_update(int $uid, int $id, array $set): bool
    {
        $set['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)
                        ->where('user_id', $uid)
                        ->update('notepad_notes', $set);
    }

    public function note_soft_delete(int $uid, int $id): bool
    {
        return $this->db->where('id', $id)
                        ->where('user_id', $uid)
                        ->update('notepad_notes', ['deleted_at'=>date('Y-m-d H:i:s')]);
    }

    public function note_belongs_to(int $uid, int $note_id): bool
    {
        $cnt = $this->db->from('notepad_notes')
                        ->where('id', $note_id)
                        ->where('user_id', $uid)
                        ->where('deleted_at IS NULL', null, false)
                        ->count_all_results();
        return $cnt > 0;
    }

}