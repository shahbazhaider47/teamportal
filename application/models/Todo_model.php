<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Todo_model extends CI_Model
{
    protected $table = 'todos';

    
    
    public function get_by_user($user_id)
    {
        $users = $this->db->dbprefix('users');
    
        return $this->db
            ->select("
                t.*,
                COALESCE(
                    NULLIF(u.fullname, ''),
                    NULLIF(CONCAT(COALESCE(u.firstname,''), ' ', COALESCE(u.lastname,'')), ' '),
                    NULLIF(u.username, ''),
                    u.email
                ) AS created_by_name
            ", false)
            ->from($this->table . ' t')
            ->join("$users u", 'u.id = t.user_id', 'left')
            ->where('t.user_id', $user_id)
            ->order_by('t.is_completed', 'ASC')
            ->order_by('t.id', 'DESC')
            ->get()
            ->result_array();
    }
    
    public function get_all(): array
    {
        $users = $this->db->dbprefix('users');
    
        return $this->db
            ->select("
                t.*,
                COALESCE(
                    NULLIF(u.fullname, ''),
                    NULLIF(CONCAT(COALESCE(u.firstname,''), ' ', COALESCE(u.lastname,'')), ' '),
                    NULLIF(u.username, ''),
                    u.email
                ) AS created_by_name
            ", false)
            ->from($this->table . ' t')
            ->join("$users u", 'u.id = t.user_id', 'left')
            ->order_by('t.created_at', 'DESC')
            ->get()
            ->result_array();
    }


    public function add($user_id, $todo_name, ?string $rel_type = null, ?int $rel_id = null)
    {
        $data = [
            'user_id'      => (int) $user_id,
            'todo_name'    => $todo_name,
            'is_completed' => 0,
            'created_at'   => date('Y-m-d H:i:s'),
            'completed_at' => null,
        ];
    
        // Only set these if provided (table already has the columns)
        if ($rel_type !== null && $rel_type !== '') {
            $data['rel_type'] = $rel_type;
        }
        if ($rel_id !== null) {
            $data['rel_id'] = (int) $rel_id;
        }
    
        $this->db->insert($this->table, $data);
    
        // For existing code that doesn’t care, this is still “truthy”
        return $this->db->insert_id() ?: false;
    }


    public function set_completed($id, $completed = 1, $user_id = null)
    {
        $this->db->where('id', $id);
        if ($user_id !== null) {
            $this->db->where('user_id', $user_id);
        }
        return $this->db->update($this->table, [
            'is_completed' => $completed ? 1 : 0,
            'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
        ]);
    }

    
    // Used for Dashboard/Widget for filtering, supports 'completed', 'not_completed', or null for all
    public function get_visible_todos($user_id, $status = null)
    {
        $users = $this->db->dbprefix('users');
    
        $this->db->select("
                t.*,
                COALESCE(
                    NULLIF(u.fullname, ''),
                    NULLIF(CONCAT(COALESCE(u.firstname,''), ' ', COALESCE(u.lastname,'')), ' '),
                    NULLIF(u.username, ''),
                    u.email
                ) AS created_by_name
            ", false)
            ->from($this->table . ' t')
            ->join("$users u", 'u.id = t.user_id', 'left')
            ->where('t.user_id', $user_id);
    
        if ($status === 'completed') {
            $this->db->where('t.is_completed', 1);
        } elseif ($status === 'not_completed') {
            $this->db->where('t.is_completed', 0);
        }
    
        $this->db->order_by('t.is_completed', 'ASC')
                 ->order_by('t.created_at', 'DESC');
    
        return $this->db->get()->result_array();
    }


    // --- FINAL: Add these, so your controller works as expected ---
    public function complete($id, $user_id = null)
    {
        return $this->set_completed($id, 1, $user_id);
    }

    public function uncomplete($id, $user_id = null)
    {
        return $this->set_completed($id, 0, $user_id);
    }
}
