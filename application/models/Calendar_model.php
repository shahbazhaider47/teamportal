<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calendar_model extends CI_Model
{
    public function get_events($start, $end, $user_id = null)
    {
        $this->db->select('*');
        $this->db->from('calendar_events');
        
        // Get events that start within the range OR end within the range OR span the range
        $this->db->group_start()
            ->where('start >=', $start)
            ->where('start <=', $end)
            ->or_group_start()
            ->where('end >=', $start)
            ->where('end <=', $end)
            ->or_group_start()
            ->where('start <=', $start)
            ->where('end >=', $end)
            ->group_end()
            ->group_end()
            ->group_end();
            
        // Privacy filter
        $this->db->group_start()
            ->where('is_private', 0);
        if ($user_id) {
            $this->db->or_where('created_by', $user_id);
        }
        $this->db->group_end();

        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_birthdays_in_range($start, $end)
    {
        $startDate = new DateTime($start);
        $endDate   = new DateTime($end);
    
        $this->db->select('id, fullname, emp_dob');
        $this->db->from('users');
        $this->db->where('emp_dob IS NOT NULL', null, false);
        $this->db->where('is_active', 1);
    
        $users = $this->db->get()->result_array();
    
        $results = [];
        $year = (int) date('Y');
    
        foreach ($users as $u) {
            $dob = new DateTime($u['emp_dob']);
    
            $birthday = DateTime::createFromFormat(
                'Y-m-d',
                $year . '-' . $dob->format('m-d')
            );
    
            if ($birthday >= $startDate && $birthday <= $endDate) {
                $results[] = [
                    'id'       => $u['id'],
                    'name'     => $u['fullname'],
                    'birthday' => $birthday->format('Y-m-d'),
                ];
            }
        }
    
        usort($results, function ($a, $b) {
            return strtotime($a['birthday']) <=> strtotime($b['birthday']);
        });
    
        return $results;
    }
    
}