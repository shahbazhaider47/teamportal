<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Events_model extends CI_Model
{
    protected $table = 'calendar_events';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_events($start = null, $end = null, $user_id = null)
    {
        $this->db->select('*');
        
        if ($start && $end) {
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
        }
        
        if ($user_id) {
            $this->db->group_start()
                ->where('is_private', 0)
                ->or_where('created_by', $user_id)
                ->group_end();
        }
        
        $query = $this->db->get($this->table);
        $events = $query->result_array();

        foreach ($events as &$event) {
            $event['start'] = date('c', strtotime($event['start']));
            $event['end'] = $event['end'] ? date('c', strtotime($event['end'])) : null;
            $event['allDay'] = false;
            $event['classNames'] = [$event['className']];
            unset($event['className']);
        }

        return $events;
    }

    /**
     * Add a new event to the database.
     */
    public function add_event($data)
    {
        $insert = [
            'title'       => strip_tags($data['title']),
            'description' => strip_tags($data['description'] ?? null),
            'start'       => $data['start'],
            'end'         => $data['end'] ?? null,
            'className'   => in_array($data['className'], ['event-primary', 'event-success', 'event-warning', 'event-info', 'event-secondary']) 
                            ? $data['className'] : 'event-primary',
            'created_by'  => $data['created_by'] ?? $this->session->userdata('user_id'),
            'created_at'  => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert($this->table, $insert);
        return $this->db->insert_id();
    }

    /**
     * Update an existing event.
     */
    public function update_event($id, $data)
    {
        $update = [];
        if (isset($data['title']))       $update['title'] = strip_tags($data['title']);
        if (isset($data['description'])) $update['description'] = strip_tags($data['description']);
        if (isset($data['start']))       $update['start'] = $data['start'];
        if (isset($data['end']))         $update['end'] = $data['end'];
        if (isset($data['className']))   $update['className'] = in_array($data['className'], ['event-primary', 'event-success', 'event-warning', 'event-info', 'event-secondary']) 
                                                        ? $data['className'] : 'event-primary';

        if (!empty($update)) {
            $this->db->where('id', $id);
            return $this->db->update($this->table, $update);
        }
        return false;
    }

    /**
     * Delete an event by ID.
     */
    public function delete_event($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Get a single event by ID.
     */
    public function get_event($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get($this->table);
        return $query->row_array();
    }
}
