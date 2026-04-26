<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Employee_transfer_model
 *
 * Handles all logic for transferring an employee to a new
 * office location and/or department/team.
 *
 * Writes to:
 *   - users              (office_id, work_location, emp_department,
 *                         emp_team, emp_teamlead, emp_manager, emp_reporting)
 *   - employee_movements (audit trail, movement_type = 'transfer' or
 *                         'location_change' or 'department_change')
 *
 * Usage:
 *   $this->load->model('Employee_transfer_model');
 */
class Employee_transfer_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ═══════════════════════════════════════════════════════════
     *  LOOKUPS — used to populate the transfer modal
     * ═══════════════════════════════════════════════════════════ */

    /**
     * All active offices for the destination dropdown.
     */
    public function get_offices(): array
    {
        return $this->db
            ->select('id, office_name, office_code, city, country, timezone, currency')
            ->from('company_offices')
            ->where('is_active', 1)
            ->order_by('is_head_office', 'DESC')
            ->order_by('office_name',    'ASC')
            ->get()
            ->result_array();
    }

    /**
     * All active departments.
     */
    public function get_departments(): array
    {
        return $this->db
            ->select('id, name')
            ->from('departments')
            ->order_by('name', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * All active teams, optionally scoped to a department.
     */
    public function get_teams(int $dept_id = 0): array
    {
        $this->db->select('t.id, t.name, t.department_id, d.name AS department_name')
                 ->from('teams t')
                 ->join('departments d', 'd.id = t.department_id', 'left')
                 ->order_by('t.name', 'ASC');

        if ($dept_id > 0) {
            $this->db->where('t.department_id', $dept_id);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Active managers for reporting-line dropdown.
     */
    public function get_managers(): array
    {
        return $this->db
            ->select('id, firstname, lastname, emp_id, emp_department, emp_team')
            ->from('users')
            ->where('is_active', 1)
            ->where('LOWER(user_role)', 'manager')
            ->order_by('firstname', 'ASC')
            ->order_by('lastname',  'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Active team leads for the destination team.
     */
    public function get_team_leads(int $team_id = 0): array
    {
        $this->db->select('id, firstname, lastname, emp_id, emp_team')
                 ->from('users')
                 ->where('is_active', 1)
                 ->where('LOWER(user_role)', 'teamlead');

        if ($team_id > 0) {
            $this->db->where('emp_team', $team_id);
        }

        return $this->db
            ->order_by('firstname', 'ASC')
            ->order_by('lastname',  'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Active positions for title change during transfer.
     */
    public function get_positions(): array
    {
        return $this->db
            ->select('id, title, code, department_id')
            ->from('hrm_positions')
            ->where('status', 1)
            ->order_by('title', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Fetch the current snapshot of an employee (for "from" fields in movement).
     */
    public function get_employee_snapshot(int $user_id): ?array
    {
        $row = $this->db
            ->select('
                u.id, u.firstname, u.lastname, u.emp_id,
                u.office_id, u.work_location, u.emp_department,
                u.emp_team, u.emp_teamlead, u.emp_manager,
                u.emp_reporting, u.emp_title, u.current_salary,
                o.office_name, o.city AS office_city,
                d.name  AS department_name,
                t.name  AS team_name,
                p.title AS position_title
            ')
            ->from('users u')
            ->join('company_offices o', 'o.id = u.office_id',      'left')
            ->join('departments d',     'd.id = u.emp_department',  'left')
            ->join('teams t',           't.id = u.emp_team',        'left')
            ->join('hrm_positions p',   'p.id = u.emp_title',       'left')
            ->where('u.id', $user_id)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    /* ═══════════════════════════════════════════════════════════
     *  CORE — execute the transfer
     * ═══════════════════════════════════════════════════════════ */

    /**
     * Transfer an employee.
     *
     * $data keys (all optional except user_id & effective_date):
     *   user_id          int     — employee being transferred
     *   effective_date   string  — YYYY-MM-DD
     *   to_office_id     int     — destination office
     *   to_department_id int     — destination department
     *   to_team_id       int     — destination team
     *   to_title_id      int     — new position/title
     *   to_manager_id    int     — new direct manager
     *   to_teamlead_id   int     — new team lead (written to emp_teamlead)
     *   to_salary        float   — new salary (leave 0 to keep current)
     *   work_location    string  — free-text location override
     *   reason           string  — transfer reason
     *   remarks          string  — internal notes
     *   created_by       int     — user executing the transfer
     *
     * Returns ['success' => bool, 'message' => string, 'movement_id' => int|null]
     */
    public function transfer(array $data): array
    {
        $userId        = (int)($data['user_id']          ?? 0);
        $effectiveDate = $data['effective_date']          ?? date('Y-m-d');
        $toOfficeId    = (int)($data['to_office_id']      ?? 0);
        $toDeptId      = (int)($data['to_department_id']  ?? 0);
        $toTeamId      = (int)($data['to_team_id']        ?? 0);
        $toTitleId     = (int)($data['to_title_id']       ?? 0);
        $toManagerId   = (int)($data['to_manager_id']     ?? 0);
        $toTeamLeadId  = (int)($data['to_teamlead_id']    ?? 0);
        $toSalary      = (float)($data['to_salary']       ?? 0);
        $workLocation  = trim($data['work_location']      ?? '');
        $reason        = trim($data['reason']             ?? '');
        $remarks       = trim($data['remarks']            ?? '');
        $createdBy     = (int)($data['created_by']        ?? 0);

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'Invalid employee ID.', 'movement_id' => null];
        }

        // Snapshot BEFORE the change
        $before = $this->get_employee_snapshot($userId);
        if (!$before) {
            return ['success' => false, 'message' => 'Employee not found.', 'movement_id' => null];
        }

        // Determine which movement_type best describes this transfer
        $movementType = $this->_resolve_movement_type(
            $before,
            $toOfficeId, $toDeptId, $toTeamId
        );

        // Build the users update payload — only include fields that are actually changing
        $userUpdate = [];

        if ($toOfficeId > 0 && $toOfficeId !== (int)($before['office_id'] ?? 0)) {
            $userUpdate['office_id'] = $toOfficeId;
            // Auto-populate work_location from office if not manually set
            if ($workLocation === '') {
                $office = $this->db->select('office_name, city, country')
                                   ->where('id', $toOfficeId)
                                   ->get('company_offices')->row_array();
                if ($office) {
                    $workLocation = trim(
                        ($office['office_name'] ?? '') . ', ' .
                        ($office['city'] ?? '')
                    );
                }
            }
        }

        if ($workLocation !== '') {
            $userUpdate['work_location'] = $workLocation;
        }

        if ($toDeptId > 0 && $toDeptId !== (int)($before['emp_department'] ?? 0)) {
            $userUpdate['emp_department'] = $toDeptId;
        }

        if ($toTeamId > 0 && $toTeamId !== (int)($before['emp_team'] ?? 0)) {
            $userUpdate['emp_team'] = $toTeamId;
        }

        if ($toTitleId > 0 && $toTitleId !== (int)($before['emp_title'] ?? 0)) {
            $userUpdate['emp_title'] = $toTitleId;
        }

        if ($toManagerId > 0 && $toManagerId !== (int)($before['emp_manager'] ?? 0)) {
            $userUpdate['emp_manager']   = $toManagerId;
            $userUpdate['emp_reporting'] = $toManagerId; // keep emp_reporting in sync
        }

        if ($toTeamLeadId > 0 && $toTeamLeadId !== (int)($before['emp_teamlead'] ?? 0)) {
            $userUpdate['emp_teamlead'] = $toTeamLeadId;
        }

        if ($toSalary > 0 && $toSalary !== (float)($before['current_salary'] ?? 0)) {
            $userUpdate['current_salary'] = $toSalary;
        }

        if (empty($userUpdate)) {
            return ['success' => false, 'message' => 'No changes detected — transfer not saved.', 'movement_id' => null];
        }

        $userUpdate['updated_at'] = date('Y-m-d H:i:s');

        // Build the employee_movements row
        $movement = [
            'user_id'              => $userId,
            'movement_type'        => $movementType,
            'from_title_id'        => $before['emp_title']      ?: null,
            'from_department_id'   => $before['emp_department'] ?: null,
            'from_team_id'         => $before['emp_team']       ?: null,
            'from_manager_id'      => $before['emp_manager']    ?: null,
            'from_salary'          => $before['current_salary'] ?: null,
            'to_title_id'          => $toTitleId   > 0 ? $toTitleId   : ($before['emp_title']      ?: null),
            'to_department_id'     => $toDeptId    > 0 ? $toDeptId    : ($before['emp_department'] ?: null),
            'to_team_id'           => $toTeamId    > 0 ? $toTeamId    : ($before['emp_team']       ?: null),
            'to_manager_id'        => $toManagerId > 0 ? $toManagerId : ($before['emp_manager']    ?: null),
            'to_salary'            => $toSalary    > 0 ? $toSalary    : ($before['current_salary'] ?: null),
            'effective_date'       => $effectiveDate,
            'reason'               => $reason  !== '' ? $reason  : null,
            'remarks'              => $remarks !== '' ? $remarks : null,
            'created_by'           => $createdBy > 0  ? $createdBy : null,
            'created_at'           => date('Y-m-d H:i:s'),
        ];

        // Execute inside a transaction
        $this->db->trans_start();

        $this->db->where('id', $userId)->update('users', $userUpdate);

        $this->db->insert('employee_movements', $movement);
        $movementId = (int)$this->db->insert_id();

        // If team changed, sync emp_teamlead for team members
        if (isset($userUpdate['emp_team']) && $toTeamLeadId > 0) {
            // New team members should report to the team lead
            $this->db->where('emp_team', $toTeamId)
                     ->where('id !=', $toTeamLeadId)
                     ->update('users', ['emp_teamlead' => $toTeamLeadId]);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return [
                'success'     => false,
                'message'     => 'Database error — transfer rolled back.',
                'movement_id' => null,
            ];
        }

        return [
            'success'     => true,
            'message'     => 'Employee transferred successfully.',
            'movement_id' => $movementId,
        ];
    }

    /**
     * Determine the most specific movement_type for the audit record.
     */
    private function _resolve_movement_type(
        array $before,
        int   $toOfficeId,
        int   $toDeptId,
        int   $toTeamId
    ): string {
        $officeChanging = $toOfficeId > 0 && $toOfficeId !== (int)($before['office_id']      ?? 0);
        $deptChanging   = $toDeptId   > 0 && $toDeptId   !== (int)($before['emp_department'] ?? 0);
        $teamChanging   = $toTeamId   > 0 && $toTeamId   !== (int)($before['emp_team']       ?? 0);

        if ($deptChanging) { return 'department_change'; }
        if ($teamChanging) { return 'team_change'; }
        if ($officeChanging) { return 'location_change'; }
        return 'transfer'; // generic when only manager/title/salary changes
    }

    /* ═══════════════════════════════════════════════════════════
     *  HISTORY — fetch past transfers for a user
     * ═══════════════════════════════════════════════════════════ */

    /**
     * All transfer/location/department/team movements for one employee.
     */
    public function get_transfer_history(int $user_id): array
    {
        return $this->db
            ->select('
                em.id, em.movement_type, em.effective_date,
                em.reason, em.remarks, em.created_at,
                ft.title   AS from_title,
                tt.title   AS to_title,
                fd.name    AS from_department,
                td.name    AS to_department,
                fteam.name AS from_team,
                tteam.name AS to_team,
                em.from_salary, em.to_salary,
                cb.firstname AS done_by_first,
                cb.lastname  AS done_by_last
            ')
            ->from('employee_movements em')
            ->join('hrm_positions ft',  'ft.id = em.from_title_id',      'left')
            ->join('hrm_positions tt',  'tt.id = em.to_title_id',        'left')
            ->join('departments fd',    'fd.id = em.from_department_id', 'left')
            ->join('departments td',    'td.id = em.to_department_id',   'left')
            ->join('teams fteam',       'fteam.id = em.from_team_id',    'left')
            ->join('teams tteam',       'tteam.id = em.to_team_id',      'left')
            ->join('users cb',          'cb.id = em.created_by',         'left')
            ->where('em.user_id', $user_id)
            ->where_in('em.movement_type', [
                'transfer', 'location_change',
                'department_change', 'team_change',
            ])
            ->order_by('em.effective_date', 'DESC')
            ->get()
            ->result_array();
    }
}