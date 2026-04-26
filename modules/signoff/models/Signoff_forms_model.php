<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Signoff_forms_model extends CI_Model
{
    protected $table = 'signoff_forms';

    /**
     * Get all forms (admin only)
     */
    public function get_all_forms()
    {
        return $this->db->order_by('id', 'DESC')->get($this->table)->result_array();
    }

    /**
     * Get active forms assigned to a team (or global forms)
     * @param int|null $team_id
     * @return array
     */
    public function get_forms_for_team($team_id = null)
    {
        $this->db->where('is_active', 1);
        if ($team_id) {
            $this->db->where('(team_id IS NULL OR team_id = ' . (int)$team_id . ')', NULL, FALSE);
        }
        return $this->db->order_by('id', 'DESC')->get($this->table)->result_array();
    }

    /**
     * Insert a new signoff form
     */
    public function insert_form($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * Get a single form by ID
     */
    public function get_form($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row_array();
    }

    /**
     * Update a form by ID
     */
    public function update_form($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Delete a form by ID
     */
    public function delete_form($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    
    // Add inside class Signoff_forms_model

    /**
     * Get active forms for a user by:
     * - Global (both NULL)
     * - Team match (team_id == user team)
     * - Position match (position_id == user position)
     */
    // Inside class Signoff_forms_model
    public function get_forms_for_user($team_id = null, $position_id = null): array
    {
        $team_id     = $team_id ? (int)$team_id : null;
        $position_id = $position_id ? (int)$position_id : null;
    
        $this->db->from($this->table)->where('is_active', 1);
    
        // (A) Global: team_id IS NULL AND position_id IS NULL
        $this->db->group_start()
            ->where('team_id IS NULL', null, false)
            ->where('position_id IS NULL', null, false)
        ->group_end();
    
        // (B) Team-assigned
        if ($team_id) {
            $this->db->or_group_start()
                ->where('team_id', $team_id)
                ->where('position_id IS NULL', null, false)
            ->group_end();
        }
    
        // (C) Position-assigned
        if ($position_id) {
            $this->db->or_group_start()
                ->where('position_id', $position_id)
                ->where('team_id IS NULL', null, false)
            ->group_end();
        }
    
        $this->db->order_by('id', 'DESC');
        return $this->db->get()->result_array();
    }


}