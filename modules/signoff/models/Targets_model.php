<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Targets_model extends CI_Model
{
    protected $table = 'signoff_targets';

    /**
     * Return target "scopes" (team_id + form_id + start/end) with computed progress.
     * $filters = [
     *   'team_id'    => int|0|null,   // optional
     *   'form_id'    => int|null,     // optional
     *   'start_date' => 'YYYY-MM-DD', // optional, used to filter overlapping ranges
     *   'end_date'   => 'YYYY-MM-DD', // optional, used to filter overlapping ranges
     * ]
     *
     * Output rows include:
     *  - id, team_id, team_name, form_id, form_title, start_date, end_date
     *  - targets_json (raw), targets (array)
     *  - targets_list: [field, field_label, target_value, achieved_value]
     *  - targets_total, achieved_total, progress_pct
     */
    public function get_scoped_targets($filters = [])
    {
        $this->db->select("
                t.*,
                tm.name AS team_name,
                f.title AS form_title,
                f.fields AS form_fields
            ")
            ->from($this->table . ' t')
            ->join('teams tm', 'tm.id = t.team_id', 'left') // team_id may be 0 for global
            ->join('signoff_forms f', 'f.id = t.form_id', 'left');

        // Optional filters
        if (!empty($filters['team_id']) || $filters['team_id'] === '0' || $filters['team_id'] === 0) {
            $this->db->where('t.team_id', (int)$filters['team_id']);
        }
        if (!empty($filters['form_id'])) {
            $this->db->where('t.form_id', (int)$filters['form_id']);
        }

        // Date overlap filter: include scopes that intersect [start_date, end_date]
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $start = $filters['start_date'];
            $end   = $filters['end_date'];
            // Overlap condition:
            // NOT (t.end_date < :start OR t.start_date > :end)
            $this->db->where("NOT (t.end_date < " . $this->db->escape($start) . " OR t.start_date > " . $this->db->escape($end) . ")", null, false);
        }

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['targets']      = $this->_decode_json($row['targets_json']);
            $fields_meta         = $this->_decode_json($row['form_fields']);
            $label_map           = $this->_build_label_map($fields_meta);
            $row['targets_list'] = [];

            $achieved_total = 0.0;
            $targets_total  = 0.0;

            foreach ($row['targets'] as $field => $target_value) {
                $label          = isset($label_map[$field]) ? $label_map[$field] : $field;
                $achieved_value = $this->_aggregate_achieved_for_scope(
                    (int)$row['team_id'],
                    (int)$row['form_id'],
                    $field,
                    $row['start_date'],
                    $row['end_date']
                );

                $row['targets_list'][] = [
                    'field'          => $field,
                    'field_label'    => $label,
                    'target_value'   => (float)$target_value,
                    'achieved_value' => (float)$achieved_value,
                ];

                $targets_total  += (float)$target_value;
                $achieved_total += (float)$achieved_value;
            }

            $row['targets_total']  = $targets_total;
            $row['achieved_total'] = $achieved_total;
            $row['progress_pct']   = $targets_total > 0 ? round(($achieved_total / $targets_total) * 100, 1) : 0.0;
        }

        return $rows;
    }


    public function get_targets($filter = [])
    {
        $this->db->select('t.*,
            u.firstname AS user_firstname, u.lastname AS user_lastname, u.emp_team,
            tm.name AS team_name,
            f.title AS form_title,
            f.fields AS form_fields
        ')
        ->from($this->table . ' t')
        ->join('users u', 'u.id = t.created_by', 'left') // since user_id is removed
        ->join('teams tm', 'tm.id = t.team_id', 'left')
        ->join('signoff_forms f', 'f.id = t.form_id', 'left')
        ->where('u.is_active', 1);
    
        if (!empty($filter['user_id'])) {
            $this->db->where('t.created_by', (int)$filter['user_id']);
        }
        if (!empty($filter['start_date'])) {
            $this->db->where('t.start_date >=', $filter['start_date']);
        }
        if (!empty($filter['end_date'])) {
            $this->db->where('t.end_date <=', $filter['end_date']);
        }
    
        $rows = $this->db->get()->result_array();
    
        foreach ($rows as &$row) {
            $row['user_name']   = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
            $row['targets']     = json_decode($row['targets_json'], true) ?: [];
            $fields_meta        = json_decode($row['form_fields'], true);
    
            $targets_list = [];
            foreach ($row['targets'] as $field => $target_value) {
                $label = $field;
                if (is_array($fields_meta)) {
                    foreach ($fields_meta as $fm) {
                        if (!empty($fm['name']) && $fm['name'] === $field) {
                            $label = $fm['label'] ?? $field;
                            break;
                        }
                    }
                }
                // updated to work with date range
                $achieved = $this->get_achieved_range($row['form_id'], $field, $row['start_date'], $row['end_date']);
                $targets_list[] = [
                    'field'          => $field,
                    'field_label'    => $label,
                    'target_value'   => (float)$target_value,
                    'achieved_value' => (float)$achieved,
                ];
            }
            $row['targets_list'] = $targets_list;
        }
        return $rows;
    }


    public function get_achieved_range($form_id, $field, $start_date, $end_date)
    {
        $this->db->select('fields_data')
            ->from('signoff_submissions')
            ->where('form_id', $form_id)
            ->where('submission_date >=', $start_date)
            ->where('submission_date <=', $end_date)
            ->where('status', 'approved');
    
        $rows = $this->db->get()->result_array();
    
        $sum = 0;
        foreach ($rows as $r) {
            $fields = json_decode($r['fields_data'], true);
            if (isset($fields[$field]) && is_numeric($fields[$field])) {
                $sum += (float)$fields[$field];
            }
        }
        return $sum;
    }

    /**
     * Upsert scope by (team_id, form_id, start_date, end_date).
     * Expects:
     *  - team_id (int), form_id (int), start_date (Y-m-d), end_date (Y-m-d),
     *  - targets_json (array), created_by, created_at, updated_by, updated_at
     */
    public function insert_or_update_scope(array $data)
    {
        $team_id    = (int)$data['team_id'];
        $form_id    = (int)$data['form_id'];
        $start_date = (string)$data['start_date'];
        $end_date   = (string)$data['end_date'];

        $exists = $this->db->get_where($this->table, [
            'team_id'    => $team_id,
            'form_id'    => $form_id,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ])->row_array();

        $payload = [
            'targets_json' => json_encode($data['targets_json'], JSON_UNESCAPED_UNICODE),
            'updated_by'   => (int)$data['updated_by'],
            'updated_at'   => (string)$data['updated_at'],
        ];

        if ($exists) {
            $this->db->where('id', (int)$exists['id'])->update($this->table, $payload);
            return (int)$exists['id'];
        }

        $insert = [
            'team_id'     => $team_id,
            'form_id'     => $form_id,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'targets_json'=> json_encode($data['targets_json'], JSON_UNESCAPED_UNICODE),
            'created_by'  => (int)$data['created_by'],
            'created_at'  => (string)$data['created_at'],
            'updated_by'  => (int)$data['updated_by'],
            'updated_at'  => (string)$data['updated_at'],
        ];
        $this->db->insert($this->table, $insert);
        return (int)$this->db->insert_id();
    }

    /**
     * Get a single scope row by id
     */
    public function get_scope($id)
    {
        $this->db->select("
                t.*,
                tm.name AS team_name,
                f.title AS form_title,
                f.fields AS form_fields
            ")
            ->from($this->table . ' t')
            ->join('teams tm', 'tm.id = t.team_id', 'left')
            ->join('signoff_forms f', 'f.id = t.form_id', 'left')
            ->where('t.id', (int)$id);

        $row = $this->db->get()->row_array();
        if ($row) {
            $row['targets'] = $this->_decode_json($row['targets_json']);
        }
        return $row;
    }

    /**
     * Update an existing scope (partial).
     * $data may include: start_date, end_date, targets_json (array), updated_by, updated_at
     */
    public function update_scope($id, array $data)
    {
        $payload = [];

        if (isset($data['start_date']))  { $payload['start_date'] = (string)$data['start_date']; }
        if (isset($data['end_date']))    { $payload['end_date']   = (string)$data['end_date']; }
        if (isset($data['targets_json'])) {
            $payload['targets_json'] = json_encode($data['targets_json'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['updated_by']))  { $payload['updated_by'] = (int)$data['updated_by']; }
        if (isset($data['updated_at']))  { $payload['updated_at'] = (string)$data['updated_at']; }

        if (empty($payload)) return false;

        $this->db->where('id', (int)$id)->update($this->table, $payload);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a scope by id
     */
    public function delete_scope($id)
    {
        $this->db->where('id', (int)$id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

    /* ========================= *
     *      Helper methods       *
     * ========================= */

    /**
     * Sum achieved value from approved signoff_submissions within the scope:
     * - team filter (active users only; team_id = 0 means global / all active users)
     * - form filter (s.form_id = $form_id)
     * - date range [start_date, end_date]
     * - field extracted from JSON s.fields_data
     */
    private function _aggregate_achieved_for_scope($team_id, $form_id, $field, $start_date, $end_date)
    {
        // Collect matching submissions
        $this->db->select('s.fields_data')
            ->from('signoff_submissions s')
            ->join('users u', 'u.id = s.user_id', 'inner')
            ->where('s.status', 'approved')
            ->where('DATE(s.submission_date) >=', $start_date)
            ->where('DATE(s.submission_date) <=', $end_date)
            ->where('u.is_active', 1);

        if ($team_id > 0) {
            $this->db->where('u.emp_team', (int)$team_id);
        }
        if ($form_id > 0) {
            // If your signoff_submissions table does not have form_id, remove this where.
            $this->db->where('s.form_id', (int)$form_id);
        }

        $rows = $this->db->get()->result_array();

        $sum = 0.0;
        foreach ($rows as $r) {
            $fields = $this->_decode_json($r['fields_data']);
            if (isset($fields[$field]) && is_numeric($fields[$field])) {
                $sum += (float)$fields[$field];
            }
        }
        return $sum;
    }

    /**
     * Build a map: field_name => label from form fields JSON
     */
    private function _build_label_map($fields_meta)
    {
        $map = [];
        if (is_array($fields_meta)) {
            foreach ($fields_meta as $fm) {
                if (!empty($fm['name'])) {
                    $map[(string)$fm['name']] = !empty($fm['label']) ? (string)$fm['label'] : (string)$fm['name'];
                }
            }
        }
        return $map;
    }

    /**
     * Safe JSON decode → array
     */
    private function _decode_json($json)
    {
        if ($json === null || $json === '' || $json === false) return [];
        if (is_array($json)) return $json;
        $data = json_decode((string)$json, true);
        return is_array($data) ? $data : [];
    }
}
