<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hrm_documents_model extends CI_Model
{
    protected $table = 'hrm_documents';
    protected $users_table = 'users';

    public function get_all($limit = null, $offset = null)
    {
        $this->db->select('d.*, u.firstname, u.lastname, u.fullname, u.profile_image');
        $this->db->from($this->table . ' d');
        $this->db->join($this->users_table . ' u', 'd.user_id = u.id', 'left');
        
        // Add search functionality
        if ($this->input->get('search')) {
            $search = $this->input->get('search');
            $this->db->group_start();
            $this->db->like('d.title', $search);
            $this->db->or_like('d.doc_type', $search);
            $this->db->or_like('u.firstname', $search);
            $this->db->or_like('u.lastname', $search);
            $this->db->group_end();
        }
        
        // Add document type filter
        if ($this->input->get('doc_type')) {
            $this->db->where('d.doc_type', $this->input->get('doc_type'));
        }
        
        // Add user filter
        if ($this->input->get('user_id')) {
            $this->db->where('d.user_id', $this->input->get('user_id'));
        }
        
        $this->db->order_by('d.updated_at', 'DESC');
        
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get()->result_array();
    }

    public function count_all()
    {
        $this->db->from($this->table . ' d');
        
        // Add search filter if exists
        if ($this->input->get('search')) {
            $search = $this->input->get('search');
            $this->db->group_start();
            $this->db->like('d.title', $search);
            $this->db->or_like('d.doc_type', $search);
            $this->db->group_end();
        }
        
        return $this->db->count_all_results();
    }

    public function get($id)
    {
        $this->db->select('d.*, u.firstname, u.lastname');
        $this->db->from($this->table . ' d');
        $this->db->join($this->users_table . ' u', 'd.user_id = u.id', 'left');
        $this->db->where('d.id', $id);
        return $this->db->get()->row_array();
    }

    public function get_by_user($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get($this->table)->result_array();
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id)
    {
        // First get the document to delete the file
        $document = $this->get($id);
        
        if ($document && $document['file']) {
            $file_path = './uploads/hrm/documents/' . $document['file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        return $this->db->where('id', $id)->delete($this->table);
    }
    
    public function get_expiring_documents($days = 30)
    {
        $date = date('Y-m-d', strtotime("+$days days"));
        $this->db->select('d.*, u.firstname, u.lastname');
        $this->db->from($this->table . ' d');
        $this->db->join($this->users_table . ' u', 'd.user_id = u.id', 'left');
        $this->db->where('d.expiry_date <=', $date);
        $this->db->where('d.expiry_date >=', date('Y-m-d'));
        $this->db->order_by('d.expiry_date', 'ASC');
        return $this->db->get()->result_array();
    }
}