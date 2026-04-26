<?php
// File: application/models/User_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    /**
     * The table this model operates on.
     */
    protected $table             = 'users';
    protected $departments_table = 'departments';
    protected $positions_table   = 'hrm_positions';

    public function __construct()
    {
        parent::__construct();
        // DB is autoloaded via config/autoload.php
    }



    public function is_unique_combination($email, $username, $emp_id, $exclude_user_id = null)
    {
        $this->db->from('users');
        $this->db->group_start()
            ->or_where('email', $email)
            ->or_where('username', $username)
            ->or_where('emp_id', $emp_id)
            ->group_end();
    
        if ($exclude_user_id !== null) {
            $this->db->where('id !=', $exclude_user_id);
        }
    
        return $this->db->count_all_results() === 0;
    }

    /**
     * NEW: fetch all, or only active if $include_inactive = false
     *
     * @param bool $include_inactive
     * @return array
     */
    public function get_all_users(bool $include_inactive = false): array
    {
        $qb = $this->db
            ->select('
                users.*,
                teams.name AS team_name,
                d.name AS department_name,
                p.title AS position_title,
                CONCAT(teamlead.firstname, " ", teamlead.lastname) AS teamlead_name,
                CONCAT(manager.firstname, " ", manager.lastname) AS manager_name,
                CONCAT(reporter.firstname, " ", reporter.lastname) AS reporting_name
            ')
            ->from($this->table)
            ->join('teams', 'teams.id = users.emp_team', 'left')
            ->join('departments d', 'd.id = users.emp_department', 'left')
            ->join('hrm_positions p', 'p.id = users.emp_title', 'left')
            ->join('users AS teamlead', 'teamlead.id = users.emp_teamlead', 'left')
            ->join('users AS manager', 'manager.id = users.emp_manager', 'left')
            ->join('users AS reporter', 'reporter.id = users.emp_reporting', 'left')
            ->order_by('users.username', 'ASC');
    
        // Exclude superadmin (handles NULL/empty gracefully)
        $qb->where('(users.user_role IS NULL OR users.user_role != "superadmin")', null, false);
    
        if (! $include_inactive) {
            $qb->where('users.is_active', 1);
        }
    
        return $qb->get()->result_array();
    }


    /**
     * Attempt login by email + MD5 password.
     *
     * @param string $email
     * @param string $password  MD5-hashed already
     * @return array|false
     */
    public function get_user_login(string $email, string $password)
    {
        $user = $this->db
            ->select('*')
            ->from('users')
            ->where('email', $email)
            ->get()
            ->row_array();
    
    if ($user && $user['is_active'] == 1) {
        if (
            password_verify($password, $user['password']) || 
            md5($password) === $user['password'] // fallback check for old users
        ) {
            // Upgrade old password to new hash
            if (md5($password) === $user['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->where('id', $user['id'])->update('users', ['password' => $newHash]);
            }
    
            unset($user['password']);
            return $user;
        }
    }
    
    
        return false;
    }
    
    /**
     * Insert a new user into `users`.
     * Expects a fully-normalized $in payload from the controller.
     */
public function set_user(array $in) /* : bool|int */
{
    if (empty($in['username']) || empty($in['email']) || empty($in['password'])) {
        return false;
    }

    $now = date('Y-m-d H:i:s');

    $data = [
        // Auth / identity
        'username'           => trim($in['username']),
        'email'              => trim($in['email']),
        'password'           => password_hash((string)$in['password'], PASSWORD_DEFAULT),
        'password_token'     => $in['password_token'] ?? null,
        'token_expires_at'   => $in['token_expires_at'] ?? null,
        'is_active'          => isset($in['is_active']) ? (int)$in['is_active'] : 1,
        'last_login_at'      => $in['last_login_at'] ?? null,
        'created_at'         => $now,
        'updated_at'         => $now,

        // Names
        'firstname'          => $in['firstname'] ?? '',
        'initials'           => $in['initials'] ?? '',        
        'lastname'           => $in['lastname'] ?? '',
        'fullname'           => $in['fullname'] ?? null,
        'gender'             => $in['gender'] ?? null,

        // Personal & contact
        'emp_dob'            => $in['emp_dob'] ?? null,
        'emp_phone'          => $in['emp_phone'] ?? null,
        'profile_image'      => $in['profile_image'] ?? null,

        // Employment identifiers
        'emp_id'             => $in['emp_id'] ?? null,
        'emp_title'          => $in['emp_title'] ?? null,
        'emp_joining'        => $in['emp_joining'] ?? null,
        'emp_department'     => $in['emp_department'] ?? null,
        'emp_team'           => $in['emp_team'] ?? null,
        'emp_teamlead'       => $in['emp_teamlead'] ?? null,
        'emp_manager'        => $in['emp_manager'] ?? null,
        'emp_reporting'      => $in['emp_reporting'] ?? null,

        // Employment terms
        'employment_type'    => $in['employment_type'] ?? null,
        'contract_type'      => $in['contract_type'] ?? null,
        'pay_period'         => $in['pay_period'] ?? null,
        'work_location'      => $in['work_location'] ?? null,
        'office_id'          => $in['office_id'] ?? null,
        'work_shift'         => $in['work_shift'] ?? null,
        'probation_end_date' => $in['probation_end_date'] ?? null,
        'confirmation_date'  => $in['confirmation_date'] ?? null,
        'last_increment_date'=> $in['last_increment_date'] ?? null,

        // Compensation
        'joining_salary'     => $in['joining_salary'] ?? null,
        'current_salary'     => $in['current_salary'] ?? null,
        'allowances'         => $in['allowances'] ?? null,

        // HR details
        'marital_status'     => $in['marital_status'] ?? null,
        'country'            => $in['country'] ?? null,
        'state'              => $in['state'] ?? null,
        'city'               => $in['city'] ?? null,
        'address'            => $in['address'] ?? null,
        'current_address'    => $in['current_address'] ?? null,
        'national_id'        => $in['national_id'] ?? null,
        'passport_no'        => $in['passport_no'] ?? null,
        'nationality'        => $in['nationality'] ?? null,
        'tax_number'         => $in['tax_number'] ?? null,
        'insurance_policy_no'=> $in['insurance_policy_no'] ?? null,
        'eobi_no'            => $in['eobi_no'] ?? null,
        'ntn_no'             => $in['ntn_no'] ?? null, 
        'pay_method'         => $in['pay_method'] ?? null,
        'allow_payroll'      => isset($in['allow_payroll']) ? (int)$in['allow_payroll'] : 0,

        // Banking
        'bank_account_number'=> $in['bank_account_number'] ?? null,
        'bank_name'          => $in['bank_name'] ?? null,
        'bank_branch'        => $in['bank_branch'] ?? null,
        'bank_code'          => $in['bank_code'] ?? null,

        // Emergency contacts
        'emergency_contact_name'         => $in['emergency_contact_name'] ?? null,
        'emergency_contact_phone'        => $in['emergency_contact_phone'] ?? null,
        'emergency_contact_relationship' => $in['emergency_contact_relationship'] ?? null,
        'father_name'                    => $in['father_name'] ?? null,
        'mother_name'                    => $in['mother_name'] ?? null,   
        'blood_group'                    => $in['blood_group'] ?? null,   
        'qualification'                  => $in['qualification'] ?? null,   
        'religion'                       => $in['religion'] ?? null,   
        'emp_grade'                      => $in['emp_grade'] ?? null,   
        
        // Misc
        'notes'              => $in['notes'] ?? null,
        'user_role'          => isset($in['user_role']) ? strtolower($in['user_role']) : 'employee',
        'dashboard_layout'   => $in['dashboard_layout'] ?? null,
    ];

    $ok = $this->db->insert($this->table, $data);
    if (! $ok) {
        log_message('error', 'Database insert failed: ' . $this->db->error());
        return false;
    }
    
    $insertId = (int) $this->db->insert_id();
    
    return $insertId;
}

    /**
     * Get one user by PK.
     *
     * @param int $user_id
     * @return array|null
     */
    public function get_user_by_email($email)
    {
        return $this->db
            ->select('*')  // we need password field for verification
            ->where('email', $email)
            ->get('users')
            ->row_array();
    }

/**
 * Get one user by ID, returning position title under `emp_title`
 * and work shift name under `work_shift_name`.
 *
 * @param int $user_id
 * @return array|null
 */
public function get_user_by_id(int $user_id): ?array
{
    // Respect table prefixes
    $tUsers      = $this->db->dbprefix('users');
    $tDepts      = $this->db->dbprefix('departments');
    $tPositions  = $this->db->dbprefix('hrm_positions');
    $tShifts     = $this->db->dbprefix('work_shifts');

    $sql = "
        SELECT
            u.*,
            d.name  AS department_name,
            p.title AS emp_title,          -- override ID with human-readable title
            s.name  AS work_shift_name     -- resolve shift ID to name
        FROM {$tUsers} u
        LEFT JOIN {$tDepts} d
            ON d.id = u.emp_department
        LEFT JOIN {$tPositions} p
            ON p.id = u.emp_title
        LEFT JOIN {$tShifts} s
            ON s.id = u.work_shift
        WHERE u.id = ?
        LIMIT 1
    ";

    $row = $this->db->query($sql, [$user_id])->row_array();
    return $row ?: null;
}


    /**
     * Stamp the user’s last_login_at.
     *
     * @param int $user_id
     * @return bool
     */
    public function update_last_login(int $user_id): bool
    {
        return (bool)$this->db
            ->where('id', $user_id)
            ->update($this->table, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Update an existing user’s details.
     * The controller must build and pass $data (col=>val) including
     * 'profile_image' (new filename) or leave it out to keep old.
     *
     * @param int   $user_id
     * @param array $data     Column=>value pairs
     * @return bool
     */
    public function update_user(int $user_id, array $data): bool
    {
    
        // If password field is empty, drop it
        if (array_key_exists('password', $data)) {
            if ($data['password'] === '') {
                unset($data['password']);
            } elseif (!password_get_info($data['password'])['algo']) {
                // Only hash if it's NOT already hashed
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

        // Always update the timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['user_role'])) {
            $data['user_role'] = strtolower($data['user_role']);
        }

        return (bool)$this->db
            ->where('id', $user_id)
            ->update($this->table, $data);
    }

    /**
     * Delete (or deactivate) a user.
     *
     * @param int  $user_id
     * @param bool $hard_delete  If true, actually DELETE; else set is_active=0
     * @return bool
     */
    public function delete_user(int $user_id, bool $hard_delete = true): bool
    {
        if ($hard_delete) {
            return (bool)$this->db
                ->where('id', $user_id)
                ->delete($this->table);
        }
        return (bool)$this->db
            ->where('id', $user_id)
            ->update($this->table, ['is_active' => 0]);
    }

    /** ──────────────────────────────────────────────────────────────────── */

    /**
     * Teams dropdown for “Add/Edit User”
     * @return array
     */
    public function get_all_teams(): array
    {
        return $this->db
            ->select('id, name')
            ->from('teams')
            ->order_by('name','ASC')
            ->get()
            ->result_array();
    }

    /**
     * Team‐lead dropdown
     * @return array
     */
public function get_team_leads(): array
{
    return $this->db
        ->select('id, fullname, firstname AS first_name, lastname AS last_name')
        ->from($this->table)
        ->where('user_role','teamlead')
        ->where('is_active', 1)
        ->order_by('firstname','ASC')
        ->order_by('lastname','ASC')
        ->get()
        ->result_array();
}


    /**
     * Manager dropdown
     * @return array
     */
public function get_managers(): array
{
    return $this->db
        ->select('id, fullname, firstname AS first_name, lastname AS last_name')
        ->from($this->table)
        ->where('user_role','manager')
        ->where('is_active', 1)
        ->order_by('firstname','ASC')
        ->order_by('lastname','ASC')
        ->get()
        ->result_array();
}


    /**
     * Employee dropdown (reporting)
     * @return array
     */
public function get_employees(): array
{
    return $this->db
        ->select('id, fullname, firstname AS first_name, lastname AS last_name')
        ->from($this->table)
        ->where('user_role','employee')
        ->where('is_active', 1)
        ->order_by('firstname','ASC')
        ->order_by('lastname','ASC')
        ->get()
        ->result_array();
}


    /**
     * Check for unique email (excluding current user).
     *
     * @param string $email
     * @param int    $user_id
     * @return bool
     */
    public function email_exists_for_other_user(string $email, int $user_id): bool
    {
        return (bool)$this->db
            ->where('email', $email)
            ->where('id !=', $user_id)
            ->count_all_results($this->table);
    }

    /**
     * Count how many users hold a given role.
     *
     * @param string $role
     * @return int
     */
    public function count_by_role(string $role): int
    {
        return (int)$this->db
            ->where('user_role', $role)
            ->where('is_active', 1)
            ->count_all_results($this->table);
    }


/**
      * Count how many users are inactive.
     * @return int
      */
     public function count_inactive(): int
     {

        return (int) $this->db
            ->where('is_active', 0)
            ->count_all_results($this->table);
     }


public function get_by_role(string $role): array
{
    return $this->db
        ->select('id, fullname, firstname AS first_name, lastname AS last_name')
        ->from($this->table)
        ->where('LOWER(user_role)', strtolower($role))
        ->where('is_active', 1)
        ->order_by('firstname', 'ASC')
        ->order_by('lastname', 'ASC')
        ->get()
        ->result_array();
}


public function get_full_name($id)
{
    $user = $this->db->select('firstname, lastname')->from('users')->where('id', $id)->get()->row();
    return $user ? $user->firstname . ' ' . $user->lastname : 'N/A';
}


public function get_team_name($id)
{
    $team = $this->db->select('name')->from('teams')->where('id', $id)->get()->row();
    return $team ? $team->name : 'N/A';
}

public function get_users_by_team($team_id)
{
    return $this->db
        ->select('
            id,
            username,
            firstname,
            lastname,
            email,
            emp_id,
            emp_title,
            user_role,
            is_active,
            emp_teamlead,
            emp_manager,
            emp_reporting,
            profile_image,
            emp_phone
        ')
        ->from('users')
        ->where('emp_team', $team_id)
        ->where('is_active', 1)
        ->get()
        ->result_array();
}



public function get_team_lead_by_team($team_id)
{
    return $this->db
        ->select('id, firstname, lastname, profile_image')
        ->from($this->table)
        ->where('user_role', 'teamlead')
        ->where('emp_team', $team_id)
        ->limit(1)
        ->get()
        ->row_array();
}

/**
 * Get users by role (e.g. 'admin', 'employee', 'teamlead')
 *
 * @param string $role
 * @return array
 */
public function get_users_by_role(string $role): array
{
    return $this->db
        ->select('id, firstname, lastname, email, profile_image')
        ->from($this->table)
        ->where('LOWER(user_role)', strtolower($role))
        ->where('is_active', 1)
        ->order_by('firstname', 'ASC')
        ->order_by('lastname', 'ASC')
        ->get()
        ->result_array();
}


    /**
     * Get upcoming birthdays within N days (default 14)
     * Returns: array of users with profile_image, fullname, emp_dob, user_role
     */
public function get_upcoming_birthdays($days = 14)
{
    $today = date('m-d');
    $end   = date('m-d', strtotime("+{$days} days"));
    $tUsers     = $this->db->dbprefix('users');
    $tPositions = $this->db->dbprefix('hrm_positions');
    $between = "DATE_FORMAT(u.emp_dob, '%m-%d') BETWEEN ? AND ?";
    if ($today > $end) {
        $window = "({$between} OR DATE_FORMAT(u.emp_dob, '%m-%d') BETWEEN '01-01' AND ?)";
        $params = [$today, $end, $end];
    } else {
        $window = "({$between})";
        $params = [$today, $end];
    }

    $sql = "
        SELECT
            u.id,
            u.fullname,
            u.emp_dob,
            u.profile_image,
            p.title AS emp_title   -- return the position title instead of the ID
        FROM {$tUsers} u
        LEFT JOIN {$tPositions} p ON p.id = u.emp_title
        WHERE u.emp_dob IS NOT NULL
          AND u.emp_dob <> '0000-00-00'
          AND {$window}
          AND u.is_active = 1
        ORDER BY DATE_FORMAT(u.emp_dob, '%m-%d') ASC
    ";

    return $this->db->query($sql, $params)->result_array();
}

    
    
    // Count all users
    public function count_all_users()
    {
        return (int) $this->db->count_all_results($this->table);
    }
    
    // Count by status: 1=active, 0=inactive
    public function count_by_status($status)
    {
        return (int) $this->db->where('is_active', $status)->count_all_results($this->table);
    }
    
    // Get latest joiners, default 5
    public function get_recent_joiners($limit = 5)
    {
        return $this->db
            ->select('id, fullname, firstname, lastname, emp_joining, emp_department, emp_title, profile_image')
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('emp_joining', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }


 
public function count_users_grouped_by_role()
{
    $results = $this->db
        ->select('user_role, COUNT(*) as total')
        ->group_by('user_role')
        ->get('users')
        ->result();

    $counts = [];
    foreach ($results as $row) {
        $counts[$row->user_role] = $row->total;
    }
    return $counts;
}


public function get($user_id)
{
    if (!is_numeric($user_id)) {
        return null;
    }

    $this->db->where('id', $user_id);
    $query = $this->db->get('users');

    return $query->row_array(); // return as associative array
}


public function get_user_by_emp_id_and_email($emp_id, $email)
{
    return $this->db->where('emp_id', $emp_id)
                    ->where('email', $email)
                    ->get('users')
                    ->row_array();
}

public function get_user_by_token($token)
{
    return $this->db->where('password_token', $token)
                    ->where('token_expires_at >', date('Y-m-d H:i:s'))
                    ->get('users')
                    ->row_array();
}

public function update_user_pass($id, $data)
{
    $this->db->where('id', $id);
    return $this->db->update('users', $data);
}



public function get_team_members($leader_id)
{
    return $this->db
        ->select('id, CONCAT(firstname, " ", lastname) AS name')
        ->from('users')
        ->where('emp_teamlead', $leader_id)
        ->or_where('emp_manager', $leader_id)
        ->where('is_active', 1)
        ->order_by('firstname', 'ASC')
        ->get()
        ->result_array();
}


/**
 * Returns a structured overview of the current user’s team:
 * team_name, team_lead name, member_count
 */
public function get_team_overview($user_id)
{
    // Get current user’s team
    $user = $this->get($user_id);
    if (!$user || empty($user['emp_team'])) {
        return null;
    }

    $team_id = (int)$user['emp_team'];

    // Get team name
    $team_name_row = $this->db->select('name')->from('teams')->where('id', $team_id)->get()->row();
    $team_name = $team_name_row ? $team_name_row->name : 'N/A';

    // Get team lead
    $team_lead = $this->get_team_lead_by_team($team_id);
    $team_lead_name = $team_lead
        ? $team_lead['firstname'] . ' ' . $team_lead['lastname']
        : 'N/A';

    // Count members
    $members = $this->get_users_by_team($team_id);
    $member_count = is_array($members) ? count($members) : 0;

    return [
        'team_name'    => $team_name,
        'team_lead'    => $team_lead_name,
        'member_count' => $member_count,
    ];
}

// In User_model.php
public function get_all_for_dropdown()
{
    return $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active')
                    ->from('users')
                    ->order_by('firstname', 'ASC')
                    ->get()
                    ->result_array();
}


// NEW: dropdown-friendly search with active-only + optional query + limit
public function search_for_dropdown(?string $q = null, bool $only_active = true, int $limit = 50): array
{
    $qb = $this->db->select('id, firstname, lastname, fullname, username, email, user_role, is_active, emp_id, profile_image')
                   ->from('users');                   

    if ($only_active) {
        $qb->where('is_active', 1);
    }

    if (!empty($q)) {
        $q = trim($q);
        $qb->group_start()
               ->like('fullname',  $q)
               ->or_like('firstname', $q)
               ->or_like('lastname',  $q)
               ->or_like('username',  $q)
               ->or_like('email',     $q)
               ->or_like('emp_id',    $q)
           ->group_end();
    }

    if ($limit > 0) {
        $qb->limit($limit);
    }

    // Order by name-ish
    $qb->order_by('firstname', 'ASC')->order_by('lastname', 'ASC');

    return $qb->get()->result_array();
}
 



// application/models/User_model.php

public function get_map_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) return [];

    $rows = $this->db
        ->select('id, fullname, firstname, lastname, username, email, profile_image')
        ->from('users')
        ->where_in('id', $ids)
        ->get()
        ->result_array();

    $out = [];
    foreach ($rows as $r) {
        // display name preference
        $name = '';
        if (!empty($r['fullname'])) {
            $name = $r['fullname'];
        } else {
            $firstLast = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''));
            if ($firstLast !== '')      $name = $firstLast;
            elseif (!empty($r['username'])) $name = $r['username'];
            elseif (!empty($r['email']))    $name = $r['email'];
        }
        if ($name === '') $name = 'User #' . (int)$r['id'];

        $avatar = null;
        if (!empty($r['profile_image'])) {
            $avatar = base_url('uploads/users/profile/' . ltrim($r['profile_image'], '/'));
        }

        $out[(int)$r['id']] = [
            'id'     => (int)$r['id'],
            'name'   => $name,
            'avatar' => $avatar,
        ];
    }
    return $out;
}

    public function get_active_minimal_list(): array
    {
        return $this->db->select('id, fullname, firstname, lastname, profile_image, is_active, emp_department')
            ->from('users')
            ->where('is_active', 1)
            // exclude superadmin (case-insensitive); NULL roles are kept
            ->where('LOWER(COALESCE(user_role, "")) <>', 'superadmin')
            ->order_by('fullname ASC, firstname ASC')
            ->get()
            ->result_array();
    }



    public function exists_username(string $username, int $exclude_id = 0): bool
    {
        if ($username === '') return false;
        $this->db->from('users')
                 ->where('username', $username);
        if ($exclude_id > 0) {
            $this->db->where('id !=', $exclude_id);
        }
        return (int)$this->db->count_all_results() > 0;
    }
    
    
    public function exists_emp_id(?string $emp_id, int $exclude_id = 0): bool
    {
        $emp_id = trim((string)$emp_id);
        if ($emp_id === '') return false;
        $this->db->from('users')
                 ->where('emp_id', $emp_id);
        if ($exclude_id > 0) {
            $this->db->where('id !=', $exclude_id);
        }
        return (int)$this->db->count_all_results() > 0;
    }


// application/models/User_model.php

public function get_headcount_overview(int $months = 6): array
{
    $months = $months < 2 ? 2 : $months;

    $result = [
        'total'           => 0,
        'male'            => 0,
        'female'          => 0,
        'months'          => [],
        'series'          => [],
        'growth_percent'  => null,
        'compare_label'   => '',
    ];

    // ---------- Total active ----------
    $this->db->from('users');
    $this->db->where('is_active', 1);
    $result['total'] = (int)$this->db->count_all_results();

    // ---------- Male ----------
    $this->db->from('users');
    $this->db->where('is_active', 1);
    $this->db->group_start();
        $this->db->where_in('gender', ['male', 'Male', 'MALE', 'm', 'M']);
    $this->db->group_end();
    $result['male'] = (int)$this->db->count_all_results();

    // ---------- Female ----------
    $this->db->from('users');
    $this->db->where('is_active', 1);
    $this->db->group_start();
        $this->db->where_in('gender', ['female', 'Female', 'FEMALE', 'f', 'F']);
    $this->db->group_end();
    $result['female'] = (int)$this->db->count_all_results();

    // ---------- Monthly headcount trend (based on emp_joining) ----------
    $monthsLabels = [];
    $seriesData   = [];

    // We measure headcount as of the end of each month (users active and joined on/before that date)
    for ($i = $months - 1; $i >= 0; $i--) {
        $monthStart = (new DateTime("first day of -{$i} month"))->format('Y-m-01');
        $monthEnd   = (new DateTime("last day of -{$i} month"))->format('Y-m-t 23:59:59');

        $this->db->select('COUNT(*) AS total', false);
        $this->db->from('users');
        $this->db->where('is_active', 1);
        $this->db->group_start();
            $this->db->where('emp_joining IS NULL', null, false);
            $this->db->or_where('emp_joining <=', $monthEnd);
        $this->db->group_end();

        $row = $this->db->get()->row();
        $count = $row ? (int)$row->total : 0;

        $monthsLabels[] = date('M y', strtotime($monthStart));  // e.g. "Jan 25"
        $seriesData[]   = $count;
    }

    $result['months'] = $monthsLabels;
    $result['series'] = $seriesData;

    // ---------- Growth vs first month in range ----------
    if (!empty($seriesData)) {
        $first = (int)reset($seriesData);
        $last  = (int)end($seriesData);

        if ($first > 0) {
            $result['growth_percent'] = round((($last - $first) / $first) * 100);
        } else {
            $result['growth_percent'] = null;
        }

        // Comparison label: first month in the series
        $result['compare_label'] = $monthsLabels[0] ?? '';
    }

    return $result;
}


    /**
     * HR KPI strip for dashboard.
     *
     * Returns:
     * [
     *   'voluntary'   => float,  // % – currently placeholder (see TODO)
     *   'involuntary' => float,  // % – currently placeholder (see TODO)
     *   'first_year'  => float,  // % – currently placeholder (see TODO)
     *   'avg_age'     => int,    // years
     *   'avg_tenure'  => int,    // years
     *   'fte'         => int,    // full-time employees
     * ]
     */
    public function get_hr_kpi(): array
    {
        // ------------------------------------------------------------------
        // 1) Base stats from users table (active employees only)
        // ------------------------------------------------------------------
        $sql = "
            SELECT
                COUNT(*) AS total_active,

                SUM(CASE WHEN gender = 'male'   THEN 1 ELSE 0 END)   AS male_count,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END)   AS female_count,

                AVG(
                    CASE
                        WHEN emp_dob IS NOT NULL
                        THEN TIMESTAMPDIFF(YEAR, emp_dob, CURDATE())
                        ELSE NULL
                    END
                ) AS avg_age_years,

                AVG(
                    CASE
                        WHEN emp_joining IS NOT NULL
                        THEN TIMESTAMPDIFF(YEAR, emp_joining, CURDATE())
                        ELSE NULL
                    END
                ) AS avg_tenure_years
            FROM users
            WHERE is_active = 1
        ";

        $row = $this->db->query($sql)->row_array() ?: [];

        $totalActive   = (int)($row['total_active'] ?? 0);
        $avgAgeYears   = isset($row['avg_age_years']) && $row['avg_age_years'] !== null
            ? (int)round($row['avg_age_years'])
            : 0;
        $avgTenureYears = isset($row['avg_tenure_years']) && $row['avg_tenure_years'] !== null
            ? (int)round($row['avg_tenure_years'])
            : 0;

        // ------------------------------------------------------------------
        // 2) FTE (Full-Time Employees)
        //    Adjust the condition on employment_type to match your values
        //    (e.g. 'Full-time', 'full_time', etc.)
        // ------------------------------------------------------------------
        $this->db->from('users');
        $this->db->where('is_active', 1);
        // If you only want true FTEs, uncomment / adjust this line:
        // $this->db->where('employment_type', 'full_time');
        $fteCount = (int)$this->db->count_all_results();

        // ------------------------------------------------------------------
        // 3) Turnover metrics (placeholders until you wire real logic)
        //    TODO: Replace with real calculations using your termination data.
        // ------------------------------------------------------------------
        $voluntaryTurnover   = 0.0;
        $involuntaryTurnover = 0.0;
        $firstYearTurnover   = 0.0;

        return [
            'voluntary'   => $voluntaryTurnover,
            'involuntary' => $involuntaryTurnover,
            'first_year'  => $firstYearTurnover,
            'avg_age'     => $avgAgeYears,
            'avg_tenure'  => $avgTenureYears,
            'fte'         => $fteCount,
        ];
    }

    public function set_notifications_sound(int $user_id, int $value): bool
    {
        $value = $value ? 1 : 0;
    
        return (bool) $this->db
            ->where('id', $user_id)
            ->limit(1)
            ->update('users', ['notifications_sound' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
    }


    /**
     * Count employees currently on probation using ONLY the `users` table.
     * Strategy:
     *  - If `users.probation_end_date` exists: count users where today <= probation_end_date.
     *  - Else: derive probation by emp_joining + N months >= today
     *    (N from setting `probation_months`, default 3).
     */
    public function count_on_probation(): int
    {
        $today = date('Y-m-d');
    
        // Prefer a real column if present
        if ($this->db->field_exists('probation_end_date', $this->table)) {
            $this->db->from($this->table);
            $this->db->where('is_active', 1);
            // probation_end_date is today or in future, and not null
            $this->db->where('probation_end_date IS NOT NULL', null, false);
            $this->db->where('DATE(probation_end_date) >=', $today);
            return (int) $this->db->count_all_results();
        }
    
        // Fallback: derive using emp_joining + probation_months
        $months = (int) (function_exists('get_setting')
            ? get_setting('probation_months', 3)
            : 3
        );
    
        // Hard safety guard
        if ($months < 0 || $months > 24) {
            $months = 3;
        }
    
        $this->db->from($this->table);
        $this->db->where('is_active', 1);
        $this->db->where('emp_joining IS NOT NULL', null, false);
        // emp_joining + N months >= today
        $this->db->where(
            'DATE_ADD(emp_joining, INTERVAL ' . (int) $months . ' MONTH) >=',
            $today,
            false
        );
    
        return (int) $this->db->count_all_results();
    }



    /* =========================
       INACTIVE USERS + EXIT INFO
       ========================= */

    public function get_inactive_users_with_exit_info(): array
    {
        // Latest exit record per user
        $latestExitSub = "
            SELECT e.*
            FROM hrm_employee_exits e
            INNER JOIN (
                SELECT user_id, MAX(exit_date) AS max_exit_date
                FROM hrm_employee_exits
                GROUP BY user_id
            ) t ON t.user_id = e.user_id AND t.max_exit_date = e.exit_date
        ";

        $this->db->select("
            u.id               AS user_id,
            u.id               AS id,
            u.firstname,
            u.lastname,
            u.email,
            u.emp_id,
            u.emp_department,
            u.emp_title,
            u.user_role,
            u.gender,
            u.profile_image,
            u.is_active,
            u.created_at       AS user_created_at,
            u.updated_at       AS user_updated_at,

            d.name  AS department_name,
            p.title AS position_title,

            ex.id                        AS exit_record_id,
            ex.exit_type,
            ex.exit_date,
            ex.last_working_date,
            ex.exit_status,
            ex.reason,
            ex.remarks,
            ex.notice_period_served,
            ex.exit_interview_date,
            ex.exit_interview_conducted_by,
            ex.checklist_completed,
            ex.assets_returned,
            ex.final_settlement_amount,
            ex.final_settlement_date,
            ex.nda_signed,
            ex.created_by,
            ex.created_at,
            ex.updated_at
        ", false);

        $this->db->from('users u');
        $this->db->join('departments d', 'd.id = u.emp_department', 'left');
        $this->db->join('hrm_positions p', 'p.id = u.emp_title', 'left');
        $this->db->join("($latestExitSub) ex", 'ex.user_id = u.id', 'left', false);

        $this->db->where('u.is_active', 0);
        $this->db->order_by('COALESCE(ex.exit_date, u.updated_at, u.created_at)', 'DESC', false);

        return $this->db->get()->result_array();
    }

    public function get_interviewers()
    {
        $allowedRoles = ['admin', 'manager', 'director', 'teamlead'];
    
        return $this->db
            ->select('id, firstname, lastname, fullname, username, email, user_role')
            ->from('users')
            ->where('is_active', 1)
            ->where_in('user_role', $allowedRoles)
            ->order_by('fullname', 'ASC')
            ->get()
            ->result_array();
    }

    public function reactivate_user(int $user_id, array $payload): bool
    {
        // payload expected: is_rejoined, rejoin_date, rejoin_reson, updated_by
        $now = date('Y-m-d H:i:s');
    
        $this->db->trans_begin();
    
        // 1) Update users table
        $update = [
            'is_active'     => 1,
            'is_rejoined'   => (int)($payload['is_rejoined'] ?? 1),
            'rejoin_date'   => $payload['rejoin_date'] ?? null,
            'rejoin_reson'  => $payload['rejoin_reson'] ?? null,
            'updated_at'    => $now,
        ];
    
        $this->db->where('id', $user_id)->update('users', $update);
    
        // 2) Delete exit record (hard delete as per your requirement)
        $this->db->where('user_id', $user_id)->delete('hrm_employee_exits');
    
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
    
        $this->db->trans_commit();
        return true;
    }

    public function has_exit_record(int $user_id): bool
    {
        return $this->db->where('user_id', $user_id)->from('hrm_employee_exits')->count_all_results() > 0;
    }
    
}