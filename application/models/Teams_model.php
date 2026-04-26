<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Teams_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all teams with department, lead, and manager names + member count.
     */
    public function get_all_teams(): array
    {
        return $this->db
            ->select('
                t.id,
                t.name,
                t.department_id,
                t.teamlead_id,
                t.manager_id,
                t.created_at,
                t.updated_at,
                d.name   AS department_name,
                TRIM(CONCAT(tl.firstname, " ", tl.lastname)) AS lead_name,
                tl.profile_image AS lead_avatar,
                tl.emp_id        AS lead_emp_id,
                TRIM(CONCAT(mg.firstname, " ", mg.lastname)) AS manager_name,
                mg.profile_image AS manager_avatar,
                mg.emp_id        AS manager_emp_id,
                (SELECT COUNT(*) FROM users u
                  WHERE u.emp_team = t.id AND u.is_active = 1) AS member_count
            ')
            ->from('teams t')
            ->join('departments d',  'd.id = t.department_id', 'left')
            ->join('users tl',       'tl.id = t.teamlead_id',  'left')
            ->join('users mg',       'mg.id = t.manager_id',   'left')
            ->order_by('t.name', 'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Get active users eligible to be team leads (role = teamlead or manager).
     */
    public function get_eligible_leads(): array
    {
        return $this->db
            ->select('id, firstname, lastname, emp_id, user_role')
            ->from('users')
            ->where('is_active', 1)
            ->group_start()
                ->where('LOWER(user_role)', 'teamlead')
                ->or_where('LOWER(user_role)', 'team lead')
            ->group_end()
            ->order_by('firstname', 'ASC')
            ->order_by('lastname',  'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Get active users eligible to be managers.
     */
    public function get_eligible_managers(): array
    {
        return $this->db
            ->select('id, firstname, lastname, emp_id, user_role')
            ->from('users')
            ->where('is_active', 1)
            ->where('LOWER(user_role)', 'manager')
            ->order_by('firstname', 'ASC')
            ->order_by('lastname',  'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Count active members for a given team.
     */
    public function count_active_members(int $team_id): int
    {
        $row = $this->db
            ->select('COUNT(*) AS cnt')
            ->from('users')
            ->where('is_active', 1)
            ->where('emp_team', $team_id)
            ->get()->row_array();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count today’s sign-offs for a given team.
     *
     * @param  int    $team_id
     * @param  string $date     e.g. 'YYYY-MM-DD'
     * @return int
     */
    public function count_today_signoffs($team_id, $date)
    {
        $sql = "
            SELECT COUNT(*) AS cnt
              FROM daily_signoff
             WHERE signoff_date = ?
               AND user_id IN (
                   SELECT id
                     FROM users
                    WHERE is_active = 1
                      AND emp_team = ?
               )";
        $row = $this->db->query($sql, [ $date, (int)$team_id ])->row_array();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Get comma-separated lead names for a team.
     * Using members' emp_teamlead -> leaders in users.
     *
     * @param  int $team_id
     * @return string|null
     */
    public function get_lead_names($team_id)
    {
        $sql = "
            SELECT COALESCE(
              GROUP_CONCAT(
                DISTINCT CONCAT(l.firstname,' ',l.lastname)
                SEPARATOR ', '
              ),
              ''
            ) AS lead_names
              FROM users AS m
              JOIN users AS l
                ON m.emp_teamlead = l.id
             WHERE m.emp_team = ?
               AND m.emp_teamlead IS NOT NULL
        ";
        $row = $this->db->query($sql, [(int)$team_id])->row_array();
        return ($row && $row['lead_names'] !== '') ? $row['lead_names'] : null;
    }

    /**
     * Get a team name by its ID.
     *
     * @param  int $team_id
     * @return string|null
     */
    public function get_team_name($team_id)
    {
        return $this->db
            ->select('name')
            ->where('id', (int)$team_id)
            ->get('teams')
            ->row('name');
    }

    /**
     * Get users not already in the given team (only active).
     *
     * @param int $team_id
     * @return array
     */
    public function get_available_users($team_id)
    {
        $team_id = (int)$team_id; // not used for filtering now, but kept for consistency
    
        return $this->db->select('u.id, u.firstname, u.lastname, u.email, u.user_role, u.emp_team, t.name AS current_team_name')
                        ->from('users u')
                        ->join('teams t', 't.id = u.emp_team', 'left')
                        ->where('u.is_active', 1)
                        ->where('LOWER(u.user_role) !=', 'admin')
                        ->order_by('u.firstname', 'ASC')
                        ->order_by('u.lastname', 'ASC')
                        ->get()
                        ->result_array();
    }

    /**
     * Assign selected users to a team.
     *
     * @param int   $team_id
     * @param array $user_ids
     * @return bool
     */
    public function add_users_to_team($team_id, $user_ids = [])
    {
        if (empty($user_ids)) return false;
        $this->db->where_in('id', array_map('intval', $user_ids));
        return $this->db->update('users', ['emp_team' => (int)$team_id]);
    }

    /**
     * Get team lead user record by team ID
     *
     * @param int $team_id
     * @return array|null
     */
    public function get_team_lead($team_id)
    {
        $sql = "
            SELECT l.*
              FROM users AS m
              JOIN users AS l ON m.emp_teamlead = l.id
             WHERE m.emp_team = ?
               AND m.emp_teamlead IS NOT NULL
             LIMIT 1
        ";
        $query = $this->db->query($sql, [(int)$team_id]);
        return $query->row_array();
    }

    // Add this method (near get_team_lead / get_lead_names)
    public function get_team_leads($team_id)
    {
        $sql = "
            SELECT DISTINCT
                l.id,
                l.firstname,
                l.lastname,
                l.email,
                l.profile_image,
                l.emp_title AS emp_title_id,
                p.title     AS emp_title_name
            FROM users AS m
            JOIN users AS l
              ON m.emp_teamlead = l.id
            LEFT JOIN hrm_positions AS p
              ON p.id = l.emp_title
            WHERE m.emp_team = ?
              AND m.is_active = 1
              AND m.emp_teamlead IS NOT NULL
              AND l.emp_team = ?        -- ensure lead is on this team
              AND l.is_active = 1
        ";
        return $this->db->query($sql, [(int)$team_id, (int)$team_id])->result_array();
    }



    /**
     * Return a user's current team_id (0 if none).
     */
    public function get_user_team_id(int $user_id): int
    {
        $row = $this->db->select('emp_team')->from('users')->where('id', $user_id)->get()->row_array();
        return (int)($row['emp_team'] ?? 0);
    }

    /**
     * Simple team list for dropdowns (id, name) ordered by name.
     */
    public function get_all_teams_brief(): array
    {
        return $this->db->select('id, name')->from('teams')->order_by('name', 'ASC')->get()->result_array();
    }

    /**
     * Lightweight team user list (for header counts, badges).
     */
    public function get_team_users_brief(int $team_id): array
    {
        return $this->db->select('id, firstname, lastname, email, user_role, profile_image, emp_id, emp_title')
                        ->from('users')
                        ->where('is_active', 1)
                        ->where('emp_team', $team_id)
                        ->order_by('firstname', 'ASC')
                        ->order_by('lastname', 'ASC')
                        ->get()->result_array();
    }

    /**
     * Team lead display name (or null).
     */
    public function get_team_lead_name_only(int $team_id): ?string
    {
        $lead = $this->get_team_lead($team_id);
        if (!$lead) return null;
        $name = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? ''));
        return $name !== '' ? $name : null;
    }

    /* =========================================================
     * GUIDES FEATURE — teams_guides CRUD (all logic here)
     * Table: teams_guides(id, team_id, title, body, is_pinned, created_by, created_at, updated_at)
     * ========================================================= */

    private function _guides_table(): string { return 'teams_guides'; }

    public function guide_list_for_team(int $team_id, int $limit = 200, int $offset = 0): array
    {
        return $this->db->select('g.*, u.firstname, u.lastname, u.profile_image')
                        ->from($this->_guides_table().' g')
                        ->join('users u', 'u.id = g.created_by', 'left')
                        ->where('g.team_id', $team_id)
                        ->order_by('g.is_pinned', 'DESC')
                        ->order_by('g.created_at', 'DESC')
                        ->limit($limit, $offset)
                        ->get()->result_array();
    }

    public function guide_get(int $id): ?array
    {
        $row = $this->db->where('id', $id)->get($this->_guides_table())->row_array();
        return $row ?: null;
    }

    public function guide_create(int $team_id, string $title, string $body, int $created_by, int $is_pinned = 0, $files): int
    {
        $payload = [
            'team_id'    => $team_id,
            'title'      => $title,
            'body'       => $body,
            'is_pinned'  => $is_pinned ? 1 : 0,
            'files'      => !empty($files) ? json_encode($files) : null,
            'created_by' => $created_by,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($this->_guides_table(), $payload);
        return (int)$this->db->insert_id();
    }

    public function guide_update(int $id, array $data): bool
    {
        if (!$data) return false;
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->_guides_table(), $data);
    }

    public function guide_toggle_pin(int $id, int $is_pinned): bool
    {
        return $this->guide_update($id, ['is_pinned' => $is_pinned ? 1 : 0]);
    }

    public function guide_delete(int $id): bool
    {
        return $this->db->where('id', $id)->delete($this->_guides_table());
    }



    /**
     * Return IDs of active users in a team.
     * @return int[]
     */
    public function get_team_member_ids(int $team_id): array
    {
        $rows = $this->db->select('id')
                         ->from('users')
                         ->where('is_active', 1)
                         ->where('emp_team', $team_id)
                         ->get()->result_array();
        return array_map('intval', array_column($rows, 'id'));
    }
    
//////////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Get members of a team, filtered by visible roles for the viewer.
     *
     * @param int        $team_id
     * @param array|null $visible_roles  null = no filter (see all)
     * @return array
     */
    public function get_team_members_scoped(int $team_id, ?array $visible_roles): array
    {
        $this->db->select('u.id, u.firstname, u.lastname, u.email, u.user_role,
                           u.emp_id, u.emp_title, u.emp_team, u.emp_teamlead,
                           u.emp_reporting, u.profile_image,
                           p.title AS emp_title_name')
                 ->from('users u')
                 ->join('hrm_positions p', 'p.id = u.emp_title', 'left')
                 ->where('u.is_active', 1)
                 ->where('u.emp_team', $team_id);
    
        // Exclude the "other" roles always
        $this->db->where_not_in(
            'LOWER(u.user_role)',
            array_map('strtolower', HIERARCHY_EXCLUDED)
        );
    
        // Scope by visible roles if provided
        if ($visible_roles !== null) {
            $this->db->where_in('LOWER(u.user_role)', array_map('strtolower', $visible_roles));
        }
    
        return $this->db
            ->order_by('u.firstname', 'ASC')
            ->order_by('u.lastname',  'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Get ALL team members for a manager-level viewer scoped to
     * teams within a given department.
     *
     * @param int        $dept_id
     * @param array|null $visible_roles
     * @return array  Rows include team_id, team_name alongside user fields.
     */
    public function get_department_members_scoped(int $dept_id, ?array $visible_roles): array
    {
        $this->db->select('u.id, u.firstname, u.lastname, u.email, u.user_role,
                           u.emp_id, u.emp_title, u.emp_team, u.emp_teamlead,
                           u.emp_reporting, u.profile_image,
                           p.title AS emp_title_name,
                           t.name  AS team_name,
                           t.id    AS team_id')
                 ->from('users u')
                 ->join('teams t',         't.id = u.emp_team',   'inner')
                 ->join('departments d',   'd.id = t.department_id', 'inner')
                 ->join('hrm_positions p', 'p.id = u.emp_title',  'left')
                 ->where('u.is_active', 1)
                 ->where('d.id', $dept_id);
    
        $this->db->where_not_in(
            'LOWER(u.user_role)',
            array_map('strtolower', HIERARCHY_EXCLUDED)
        );
    
        if ($visible_roles !== null) {
            $this->db->where_in('LOWER(u.user_role)', array_map('strtolower', $visible_roles));
        }
    
        return $this->db
            ->order_by('t.name',      'ASC')
            ->order_by('u.firstname', 'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Get ALL active team members across ALL teams,
     * for superadmin / director views.
     *
     * @return array  Rows include team_name, department_name.
     */
    public function get_all_members_global(): array
    {
        return $this->db
            ->select('u.id, u.firstname, u.lastname, u.email, u.user_role,
                      u.emp_id, u.emp_title, u.emp_team, u.emp_teamlead,
                      u.emp_reporting, u.profile_image,
                      p.title  AS emp_title_name,
                      t.name   AS team_name,
                      t.id     AS team_id,
                      d.name   AS department_name')
            ->from('users u')
            ->join('teams t',         't.id = u.emp_team',      'left')
            ->join('departments d',   'd.id = t.department_id', 'left')
            ->join('hrm_positions p', 'p.id = u.emp_title',     'left')
            ->where('u.is_active', 1)
            ->where_not_in(
                'LOWER(u.user_role)',
                array_map('strtolower', HIERARCHY_EXCLUDED)
            )
            ->order_by('d.name',      'ASC')
            ->order_by('t.name',      'ASC')
            ->order_by('u.firstname', 'ASC')
            ->get()
            ->result_array();
    }
    
    /**
     * Get the department_id for a user (via their team).
     */
    public function get_user_dept_id(int $user_id): int
    {
        $sql = "SELECT t.department_id
                  FROM users u
                  JOIN teams t ON t.id = u.emp_team
                 WHERE u.id = ?
                 LIMIT 1";
        $row = $this->db->query($sql, [$user_id])->row_array();
        return (int)($row['department_id'] ?? 0);
    }


    
}
