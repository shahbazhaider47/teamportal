<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Announcement_model extends CI_Model
{
    public function get_categories()
    {
        return $this->db->get('announcement_categories')->result_array();
    }

    public function get_visible_announcements($user_id, $role)
    {
        $role = strtolower($role);
    
        $this->db->select('a.*, 
                           c.name AS category_name, 
                           c.color AS category_color,
                           CONCAT(u.firstname, " ", u.lastname) AS sender_name')
                 ->from('announcements a')
                 ->join('users u', 'u.id = a.created_by', 'left')
                 ->join('announcement_categories c', 'c.id = a.category_id', 'left')
                 ->where('a.is_published', 1);
    
        // 🔹 Only upcoming + not older than 3 days
        $this->db->where('a.created_at >=', date('Y-m-d H:i:s', strtotime('-3 days')));
    
        // 🔹 Role-based visibility
        if ($role !== 'admin') {
            $this->db->group_start()
                     ->where('a.sent_to', 'all')
                     ->or_where('LOWER(a.sent_to)', $role)
                     ->group_end();
        }
    
        $this->db->order_by('a.priority', 'DESC')
                 ->order_by('a.created_at', 'DESC');
    
        return $this->db->get()->result_array();
    }

    public function get_popup_announcements($user_id, $role)
    {
        $role = strtolower($role);
    
        $dismissed_ids_sql = $this->db->select('announcement_id')
                                      ->from('announcement_dismissals')
                                      ->where('user_id', (int)$user_id)
                                      ->get_compiled_select();
    
        $this->db->select('a.*, c.name AS category_name, c.color AS category_color,
                           CONCAT(u.firstname, " ", u.lastname) AS sender_name')
                 ->from('announcements a')
                 ->join('users u', 'u.id = a.created_by', 'left')
                 ->join('announcement_categories c', 'c.id = a.category_id', 'left')
                 ->where('a.is_published', 1)
                 ->where("a.id NOT IN ($dismissed_ids_sql)", null, false);
    
        if ($role !== 'admin') {
            $this->db->group_start()
                     ->where('a.sent_to', 'all')
                     ->or_where('LOWER(a.sent_to)', $role)
                     ->group_end();
        }
    
        $this->db->order_by('a.priority', 'DESC')
                 ->order_by('a.created_at', 'DESC');
    
        return $this->db->get()->result_array();
    }


public function save_announcement($data)
{
    $this->db->trans_start();

    if (empty($data['id'])) {
        $data['created_by'] = $this->session->userdata('user_id');
        $this->db->insert('announcements', $data);
        $announcement_id = $this->db->insert_id();
    } else {
        $announcement_id = (int)$data['id'];
        unset($data['id']);
        $this->db->where('id', $announcement_id)->update('announcements', $data);
    }

    $this->db->trans_complete();
    return $this->db->trans_status() ? $announcement_id : false;
}


public function get_announcement($id)
{
    return $this->db
        ->select('a.*, 
                  CONCAT(u.firstname, " ", u.lastname) AS sender_name,
                  u.profile_image AS sender_profile_image,
                  c.name  AS category_name,
                  c.color AS category_color')
        ->from('announcements a')
        ->join('users u', 'u.id = a.created_by', 'left')
        ->join('announcement_categories c', 'c.id = a.category_id', 'left')
        ->where('a.id', (int)$id)
        ->get()
        ->row_array();
}


public function get_all_announcements()
{
    $this->db->select('a.*, c.name as category_name, c.color as category_color, 
                      CONCAT(u.firstname, " ", u.lastname) AS sender_name')
             ->from('announcements a')
             ->join('announcement_categories c', 'c.id = a.category_id', 'left')
             ->join('users u', 'u.id = a.created_by', 'left')
             ->where('a.is_published', 1)
             ->order_by('a.created_at', 'DESC')
             ->limit(5);

    return $this->db->get()->result_array();
}


public function get_user_announcements($user_id)
{
    $role = strtolower($this->session->userdata('user_role') ?? '');

    $this->db->select('a.*, c.name AS category_name, c.color AS category_color,
                       CONCAT(u.firstname, " ", u.lastname) AS sender_name')
             ->from('announcements a')
             ->join('users u', 'u.id = a.created_by', 'left')
             ->join('announcement_categories c', 'c.id = a.category_id', 'left')
             ->where('a.is_published', 1);

    if ($role !== 'admin') {
        $this->db->group_start()
                 ->where('a.sent_to', 'all')
                 ->or_where('LOWER(a.sent_to)', $role)
                 ->group_end();
    }

    $this->db->order_by('a.priority', 'DESC')
             ->order_by('a.created_at', 'DESC')
             ->limit(5);

    return $this->db->get()->result_array();
}

}
