<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!class_exists('PayrollBaseModel')) {
    $base = __DIR__ . '/PayrollBaseModel.php';
    if (file_exists($base)) {
        require_once $base;
    } else {
        @require_once APPPATH . 'modules/payroll/models/PayrollBaseModel.php';
    }
}

class PayrollIncrementsModel extends PayrollBaseModel
{
    private $deptTable;              // departments table name or null
    private $posTable;               // positions table name or null

    // Users-table columns (resolved dynamically)
    private $usersDeptCol = null;    // numeric FK if present; else null
    private $usersDeptTxt = null;    // fallback text column (e.g., 'department')
    private $usersPosCol  = null;    // numeric FK if present; else null
    private $usersPosTxt  = null;    // fallback text column (e.g., 'emp_title')

    public function __construct()
    {
        parent::__construct();
        $this->resolveOrgTables();
        $this->resolveUserOrgColumns();
    }

    /* -------------------- Public API used by controller/views -------------------- */

public function users_dropdown(bool $all = false): array
{
    $this->db->from('users AS u')
             ->select('u.id, u.emp_id, u.firstname, u.lastname, u.current_salary');

    if (!$all) {
        if (method_exists($this, 'where_active_users')) {
            $this->where_active_users();
        } else {
            $this->db->where('u.is_active', 1);
        }
    }

    return $this->db->order_by('u.firstname', 'ASC')
                    ->order_by('u.lastname', 'ASC')
                    ->get()->result_array();
}

public function departments_dropdown(): array
{
    if (!$this->deptTable) { return []; }

    $fields = $this->db->list_fields($this->deptTable);

    // Resolve a display column and always alias it as "title"
    if (in_array('title', $fields, true)) {
        $sel = 'id, title AS title';
    } elseif (in_array('name', $fields, true)) {
        $sel = 'id, name AS title';
    } elseif (in_array('department_name', $fields, true)) {
        $sel = 'id, department_name AS title';
    } else {
        // Fallback: just id (no visible title)
        $sel = 'id, id AS title';
    }

    return $this->db->select($sel, false)
                    ->from($this->deptTable)
                    ->order_by('title','ASC')
                    ->get()->result_array();
}


    public function positions_dropdown(): array
    {
        if (!$this->posTable) { return []; }
        $fields = $this->db->list_fields($this->posTable);
        $nameCol = in_array('title', $fields, true) ? 'title'
                  : (in_array('name', $fields, true) ? 'name' : 'title');
        return $this->db->select('id, '.$nameCol.' AS title', false)
                        ->from($this->posTable)
                        ->order_by('title','ASC')
                        ->get()->result_array();
    }

    public function history_all(): array
    {
        $this->db->select('pi.*, u.emp_id, u.firstname, u.lastname');

        if ($this->deptTable) {
            $deptFields = $this->db->list_fields($this->deptTable);
            $dName = in_array('name',$deptFields,true) ? 'name' :
                     (in_array('title',$deptFields,true) ? 'title' :
                      (in_array('department_name',$deptFields,true) ? 'department_name' : 'name'));
        }

        if ($this->posTable) {
            $posFields = $this->db->list_fields($this->posTable);
            $pName = in_array('title',$posFields,true) ? 'title' :
                     (in_array('name',$posFields,true) ? 'name' : 'title');
            $this->db->select('p.'.$pName.' AS position_title', false);
        }

        $this->db->from('payroll_increments AS pi')
                 ->join('users AS u', 'u.id = pi.user_id', 'left');

        if ($this->deptTable) {
            $deptFields = $this->db->list_fields($this->deptTable);
            $dName = in_array('name',$deptFields,true) ? 'name' :
                     (in_array('title',$deptFields,true) ? 'title' :
                      (in_array('department_name',$deptFields,true) ? 'department_name' : 'name'));
        
            if ($this->usersDeptCol) {
                $this->db->join($this->deptTable.' AS d', 'd.id = u.'.$this->usersDeptCol, 'left');
            } elseif ($this->usersDeptTxt) {
                $this->db->join($this->deptTable.' AS d', 'd.'.$dName.' = u.'.$this->usersDeptTxt, 'left');
            }
        
            $this->db->select('d.'.$dName.' AS department_name', false);
        }


        if ($this->posTable) {
            if ($this->usersPosCol) {
                $this->db->join($this->posTable.' AS p', 'p.id = u.'.$this->usersPosCol, 'left');
            } elseif ($this->usersPosTxt) {
                $posFields = $this->db->list_fields($this->posTable);
                $pName = in_array('title',$posFields,true) ? 'title' :
                         (in_array('name',$posFields,true) ? 'name' : 'title');
                $this->db->join($this->posTable.' AS p', 'p.'.$pName.' = u.'.$this->usersPosTxt, 'left');
            }
        }

        return $this->db->order_by('pi.increment_date','DESC')
                        ->order_by('pi.id','DESC')
                        ->get()->result_array();
    }

    /**
     * Scope: all | users | department | position
     * Works whether users table has numeric FKs or only text columns.
     */
    public function users_for_scope(string $scope, array $user_ids = [], int $department_id = 0, int $position_id = 0, bool $all = false): array
    {
        $qb = $this->db->select('u.id, u.emp_id, u.firstname, u.lastname, u.current_salary')
                       ->from('users AS u');
    
        if (!$all) {
            if (method_exists($this, 'where_active_users')) {
                $this->where_active_users();
            } else {
                $this->db->where('u.is_active', 1);
            }
        }
    
        if ($scope === 'users') {
            $ids = array_map('intval', $user_ids ?: []);
            if (!$ids) { return []; }
            $qb->where_in('u.id', $ids);
    
        } elseif ($scope === 'department') {
            if ($department_id <= 0 || !$this->deptTable) { return []; }
    
            if ($this->usersDeptCol) {
                $qb->where('u.'.$this->usersDeptCol, $department_id);
            } elseif ($this->usersDeptTxt) {
                $deptFields = $this->db->list_fields($this->deptTable);
                $dName = in_array('name',$deptFields,true) ? 'name' :
                         (in_array('title',$deptFields,true) ? 'title' :
                          (in_array('department_name',$deptFields,true) ? 'department_name' : 'name'));
                $qb->join($this->deptTable.' AS d', 'd.'.$dName.' = u.'.$this->usersDeptTxt, 'left')
                   ->where('d.id', $department_id);
            } else {
                return [];
            }
    
        } elseif ($scope === 'position') {
            if ($position_id <= 0 || !$this->posTable) { return []; }
    
            if ($this->usersPosCol) {
                $qb->where('u.'.$this->usersPosCol, $position_id);
            } elseif ($this->usersPosTxt) {
                $posFields = $this->db->list_fields($this->posTable);
                $pName = in_array('title',$posFields,true) ? 'title' :
                         (in_array('name',$posFields,true) ? 'name' : 'title');
                $qb->join($this->posTable.' AS p', 'p.'.$pName.' = u.'.$this->usersPosTxt, 'left')
                   ->where('p.id', $position_id);
            } else {
                return [];
            }
    
        } elseif ($scope !== 'all') {
            return [];
        }
    
        return $qb->order_by('u.firstname', 'ASC')->get()->result_array();
    }


public function apply_bulk(array $targets, array $payload): int
{
    $type  = in_array(($payload['increment_type'] ?? 'amount'), ['amount','percent'], true)
           ? $payload['increment_type'] : 'amount';

    $value = (float)($payload['increment_value'] ?? 0);
    if ($value <= 0) { return 0; }

    $date    = $payload['increment_date']  ?? date('Y-m-d');
    $cycle   = $payload['increment_cycle'] ?? 'annual';
    $remarks = $payload['remarks']         ?? null;

    $now = $this->now();
    $inserted = 0;

    $this->db->trans_start();

    foreach ($targets as $t) {
        $uid  = (int)$t['id'];
        $prev = (float)$t['current_salary'];

        $new  = ($type === 'percent')
              ? max(0, round($prev * (1 + ($value / 100.0)), 2))
              : max(0, round($prev + $value, 2));

        $raised = round($new - $prev, 2);

        $this->db->insert('payroll_increments', [
            'user_id'         => $uid,
            'increment_date'  => $date,
            'increment_type'  => $type,
            'increment_value' => $value,
            'previous_salary' => $prev,
            'raised_amount'   => $raised,     // <-- NEW
            'new_salary'      => $new,
            'increment_cycle' => $cycle,
            'remarks'         => $remarks,
            'status'          => 'pending',
            'approved_by'     => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        $inserted += (int)($this->db->affected_rows() > 0);
    }

    $this->db->trans_complete();
    return $this->db->trans_status() ? $inserted : 0;
}



    /* -------------------- Internals -------------------- */

    private function resolveOrgTables(): void
    {
        // departments
        if ($this->db->table_exists('departments')) {
            $this->deptTable = 'departments';
        } elseif ($this->db->table_exists('emp_departments')) {
            $this->deptTable = 'emp_departments';
        } else {
            $this->deptTable = null;
        }

        // positions
        if ($this->db->table_exists('hrm_positions')) {
            $this->posTable = 'hrm_positions';
        } elseif ($this->db->table_exists('positions')) {
            $this->posTable = 'positions';
        } else {
            $this->posTable = null;
        }
    }

    private function resolveUserOrgColumns(): void
    {
        $uFields = $this->db->list_fields('users');

        // department FK candidates
        foreach (['emp_department','department_id','dept_id'] as $c) {
            if (in_array($c, $uFields, true)) { $this->usersDeptCol = $c; break; }
        }
        if (!$this->usersDeptCol) {
            foreach (['department','dept','emp_department_name'] as $c) {
                if (in_array($c, $uFields, true)) { $this->usersDeptTxt = $c; break; }
            }
        }

        // position FK candidates
        foreach (['hrm_position_id','position_id','designation_id'] as $c) {
            if (in_array($c, $uFields, true)) { $this->usersPosCol = $c; break; }
        }
        if (!$this->usersPosCol) {
            foreach (['emp_title','job_title','designation','position'] as $c) {
                if (in_array($c, $uFields, true)) { $this->usersPosTxt = $c; break; }
            }
        }
    }


public function apply_items(array $items, array $meta): int
{
    $date    = $meta['increment_date']  ?? date('Y-m-d');
    $cycle   = $meta['increment_cycle'] ?? 'annual';
    $remarks = $meta['remarks']         ?? null;

    $now = $this->now();
    $inserted = 0;

    $this->db->trans_start();

    foreach ($items as $it) {
        $uid   = (int)($it['user_id'] ?? 0);
        $type  = strtolower((string)($it['increment_type'] ?? 'amount'));
        $value = (float)($it['increment_value'] ?? 0);

        if ($uid <= 0 || !in_array($type, ['amount','percent'], true) || $value <= 0) {
            continue;
        }

        // Re-fetch current salary
        $u = $this->db->select('current_salary')->from('users')->where('id', $uid)->get()->row_array();
        $prev = (float)($u['current_salary'] ?? 0);

        $new = ($type === 'percent')
             ? max(0, round($prev * (1 + ($value / 100.0)), 2))
             : max(0, round($prev + $value, 2));

        $raised = round($new - $prev, 2);

        $this->db->insert('payroll_increments', [
            'user_id'         => $uid,
            'increment_date'  => $date,
            'increment_type'  => $type,
            'increment_value' => $value,
            'previous_salary' => $prev,
            'raised_amount'   => $raised,     // <-- NEW
            'new_salary'      => $new,
            'increment_cycle' => $cycle,
            'remarks'         => $remarks,
            'status'          => 'pending',
            'approved_by'     => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $inserted += (int)($this->db->affected_rows() > 0);
    }

    $this->db->trans_complete();
    return $this->db->trans_status() ? $inserted : 0;
}

public function delete(int $id): bool
{
    return $this->db->where('id', $id)->delete('payroll_increments');
}

public function get_increment(int $id): ?array
{
    $this->db->select('pi.*, u.emp_id, u.firstname, u.lastname, u.current_salary');

    if ($this->deptTable) {
        $deptFields = $this->db->list_fields($this->deptTable);
        $dName = in_array('name', $deptFields, true) ? 'name'
               : (in_array('title', $deptFields, true) ? 'title' : 'name');
        $this->db->select('d.' . $dName . ' AS department_name', false);
    }

    if ($this->posTable) {
        $posFields = $this->db->list_fields($this->posTable);
        $pName = in_array('title', $posFields, true) ? 'title'
               : (in_array('name', $posFields, true) ? 'name' : 'title');
        $this->db->select('p.' . $pName . ' AS position_title', false);
    }

    // Approver name
    $this->db->select('CONCAT(ab.firstname, " ", ab.lastname) AS approved_by_name', false);

    $this->db->from('payroll_increments AS pi')
             ->join('users AS u', 'u.id = pi.user_id', 'left')
             ->join('users AS ab', 'ab.id = pi.approved_by', 'left');

    if ($this->deptTable) {
        $deptFields = $this->db->list_fields($this->deptTable);
        $dName = in_array('name', $deptFields, true) ? 'name'
               : (in_array('title', $deptFields, true) ? 'title' : 'name');
        if ($this->usersDeptCol) {
            $this->db->join($this->deptTable . ' AS d', 'd.id = u.' . $this->usersDeptCol, 'left');
        } elseif ($this->usersDeptTxt) {
            $this->db->join($this->deptTable . ' AS d', 'd.' . $dName . ' = u.' . $this->usersDeptTxt, 'left');
        }
    }

    if ($this->posTable) {
        if ($this->usersPosCol) {
            $this->db->join($this->posTable . ' AS p', 'p.id = u.' . $this->usersPosCol, 'left');
        } elseif ($this->usersPosTxt) {
            $posFields = $this->db->list_fields($this->posTable);
            $pName = in_array('title', $posFields, true) ? 'title'
                   : (in_array('name', $posFields, true) ? 'name' : 'title');
            $this->db->join($this->posTable . ' AS p', 'p.' . $pName . ' = u.' . $this->usersPosTxt, 'left');
        }
    }

    $row = $this->db->where('pi.id', $id)->get()->row_array();

    return $row ?: null;
}

public function history_for_user(int $user_id, int $exclude_id = 0): array
{
    $this->db->select('pi.id, pi.increment_date, pi.increment_type, pi.increment_value,
                        pi.previous_salary, pi.raised_amount, pi.new_salary,
                        pi.increment_cycle, pi.status, pi.remarks,
                        pi.approved_at,
                        CONCAT(ab.firstname, " ", ab.lastname) AS approved_by_name', false)
             ->from('payroll_increments AS pi')
             ->join('users AS ab', 'ab.id = pi.approved_by', 'left')
             ->where('pi.user_id', $user_id);

    if ($exclude_id > 0) {
        $this->db->where('pi.id !=', $exclude_id);
    }

    return $this->db->order_by('pi.increment_date', 'DESC')
                    ->order_by('pi.id', 'DESC')
                    ->get()->result_array();
}

public function approve(int $id, int $approved_by): bool
{
    // Fetch the increment record
    $inc = $this->db
        ->where('id', $id)
        ->where('status', 'pending')
        ->get('payroll_increments')
        ->row_array();

    if (!$inc) {
        return false; // Already approved, cancelled, or doesn't exist
    }

    $now         = $this->now();
    $uid         = (int) $inc['user_id'];
    $new_salary  = (float) $inc['new_salary'];
    $raised      = (float) $inc['raised_amount'];
    $inc_type    = $inc['increment_type'];   // 'amount' or 'percent'
    $inc_value   = (float) $inc['increment_value'];
    $inc_date    = $inc['increment_date'];

    $this->db->trans_start();

    // 1. Mark increment record as approved
    $this->db->where('id', $id)->update('payroll_increments', [
        'status'      => 'approved',
        'approved_by' => $approved_by,
        'approved_at' => $now,
        'updated_at'  => $now,
    ]);

    // 2. Update the user's salary and increment tracking columns
    $user_fields = $this->db->list_fields('users');

    $update = ['current_salary' => $new_salary];

    if (in_array('last_increment_date', $user_fields, true)) {
        $update['last_increment_date'] = $inc_date;
    }
    if (in_array('increment_type', $user_fields, true)) {
        $update['increment_type'] = $inc_type;
    }
    if (in_array('increment_amount', $user_fields, true)) {
        $update['increment_amount'] = $raised;
    }
    // Some schemas use 'last_increment' or 'increment_value' instead
    if (in_array('last_increment', $user_fields, true)) {
        $update['last_increment'] = $inc_value;
    }

    $this->db->where('id', $uid)->update('users', $update);

    $this->db->trans_complete();

    return $this->db->trans_status();
}
}