<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks policy / role hierarchy for the Tasks module (HMVC).
 *
 * Highest → lowest:
 *   0 Super Admin
 *   1 Project Manager (of task's project) OR Team Lead (of task's team)
 *   2 Assignee
 *   3 Requester/Creator
 *   4 Follower
 *   5 Other staff (per staff_can)
 *   6 Others (no access)
 *
 * Maps settings + permissions to actionable capability checks.
 * Uses only the finalized tasks settings list you provided.
 */
class Tasks_policy
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = get_instance();
        if (!isset($this->CI->users)) {
            $this->CI->load->model('User_model', 'users');
        }
    }

    /* ---------------------------------
     * Role detection
     * --------------------------------- */
    public function is_super_admin(int $uid): bool
    {
        return function_exists('is_admin') ? (bool) is_admin($uid) : false;
    }

    public function is_project_manager(int $uid, ?int $projectId): bool
    {
        if (!$projectId) return false;
        if (function_exists('is_project_manager')) {
            return (bool) is_project_manager($uid, $projectId);
        }
        if (method_exists($this->CI->users, 'is_project_manager')) {
            return (bool) $this->CI->users->is_project_manager($uid, $projectId);
        }
        return false;
    }

    public function is_team_lead(int $uid, ?int $teamId): bool
    {
        if (!$teamId) return false;
        if (function_exists('is_team_lead')) {
            return (bool) is_team_lead($uid, $teamId);
        }
        if (method_exists($this->CI->users, 'is_team_lead')) {
            return (bool) $this->CI->users->is_team_lead($uid, $teamId);
        }
        return false;
    }

    public function is_assignee(array $task, int $uid): bool
    {
        return !empty($task['assignee_id']) && (int) $task['assignee_id'] === $uid;
    }

    /** Requester/Creator field — supports either key */
    public function is_requester(array $task, int $uid): bool
    {
        $rid = $task['requester_id'] ?? $task['creator_id'] ?? null;
        return !empty($rid) && (int) $rid === $uid;
    }

    public function is_follower(array $task, int $uid): bool
    {
        $fs = $task['followers'] ?? [];
        if (is_string($fs)) { $fs = json_decode($fs, true) ?: []; }
        return in_array($uid, array_map('intval', $fs), true);
    }

    public function same_project_member(int $uid, ?int $projectId): bool
    {
        if (!$projectId) return false;
        if (function_exists('user_belongs_to_project')) {
            return (bool) user_belongs_to_project($uid, $projectId);
        }
        if (method_exists($this->CI->users, 'belongs_to_project')) {
            return (bool) $this->CI->users->belongs_to_project($uid, $projectId);
        }
        return false;
    }

    public function same_team_member(int $uid, ?int $teamId): bool
    {
        if (!$teamId) return false;
        if (function_exists('user_belongs_to_team')) {
            return (bool) user_belongs_to_team($uid, $teamId);
        }
        if (method_exists($this->CI->users, 'belongs_to_team')) {
            return (bool) $this->CI->users->belongs_to_team($uid, $teamId);
        }
        return false;
    }

    /* ---------------------------------
     * Settings helpers (final tasks set)
     * --------------------------------- */

    protected function setting(string $key, $default = null)
    {
        // Uses your unified settings accessor
        return function_exists('get_setting') ? get_setting($key, $default) : $default;
    }

    protected function yes(string $key, string $default = 'yes'): bool
    {
        return ($this->setting($key, $default) === 'yes');
    }

    /** requester|assignee|both (who can add followers) */
    public function followers_who_can(): string
    {
        return (string) $this->setting('tasks_user_added_followers', 'both');
    }

    /** Public URL flag */
    public function public_url_enabled(): bool
    {
        return $this->yes('tasks_public_url', 'no'); // yes|no
    }

    /** Enforce checklist before marking completed */
    public function enforce_checklist_before_done(): bool
    {
        return $this->yes('tasks_enforce_checklist_before_done', 'no');
    }

    /** Block moving forward if dependencies unresolved */
    public function block_on_unresolved_dependencies(): bool
    {
        return $this->yes('tasks_block_on_unresolved_dependencies', 'yes'); // default strict
    }

    /* ---------------------------------
     * Global capability checks (no task)
     * --------------------------------- */

    public function can_list(int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if (function_exists('staff_can')) {
            return staff_can('view_global', 'tasks') || staff_can('view_own', 'tasks');
        }
        return false;
    }

    public function can_create(int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        return (function_exists('staff_can') ? staff_can('create', 'tasks') : false);
    }

    /* ---------------------------------
     * Task-scoped capability checks
     * --------------------------------- */

    public function can_view(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if (function_exists('staff_can') && staff_can('view_global', 'tasks')) return true;

        $projectId = isset($task['project_id']) ? (int)$task['project_id'] : null;
        $teamId    = isset($task['team_id'])    ? (int)$task['team_id']    : null;

        // PM/TeamLead of the linked context
        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;

        // "Own" = requester/creator, assignee, and follower (read-only)
        if (function_exists('staff_can') && staff_can('view_own', 'tasks')) {
            if ($this->is_requester($task, $uid)) return true;
            if ($this->is_assignee($task, $uid))  return true;
            if ($this->is_follower($task, $uid))  return true;
        }

        // Fallback: members of same project/team may view if at least view_own
        $sameContext = $this->same_project_member($uid, $projectId) || $this->same_team_member($uid, $teamId);
        if ($sameContext) {
            return (function_exists('staff_can') && staff_can('view_own', 'tasks'));
        }

        return false;
    }

    public function can_edit(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;
        if ($this->is_assignee($task, $uid))            return true;

        return (function_exists('staff_can') ? staff_can('edit', 'tasks') : false);
    }

    public function can_assign(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;

        return (function_exists('staff_can') ? staff_can('assign', 'tasks') : false);
    }

    public function can_change_status(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;
        if ($this->is_assignee($task, $uid))            return true;

        return (function_exists('staff_can') ? staff_can('edit', 'tasks') : false);
    }

    public function can_delete(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        return (function_exists('staff_can') ? staff_can('delete', 'tasks') : false);
    }

    public function can_manage_followers(array $task, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;

        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        // PM / Team Lead always allowed
        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;

        $who    = $this->followers_who_can(); // requester|assignee|both
        $isReq  = $this->is_requester($task, $uid);
        $isAss  = $this->is_assignee($task, $uid);

        if ($who === 'requester') return $isReq;
        if ($who === 'assignee')  return $isAss;
        if ($who === 'both')      return ($isReq || $isAss);

        // Fallback if misconfigured
        return (function_exists('staff_can') ? staff_can('edit', 'tasks') : false);
    }

    /** Only assignee (or PM/Lead) may generate a public link, and only if setting enabled. */
    public function can_generate_public_link(array $task, int $uid): bool
    {
        if (!$this->public_url_enabled()) return false;
        if ($this->is_super_admin($uid)) return true;

        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        if ($this->is_project_manager($uid, $projectId)) return true;
        if ($this->is_team_lead($uid, $teamId))         return true;

        return $this->is_assignee($task, $uid);
    }

    /* ---------------------------------
     * Policy-aware validations (used by controllers/services)
     * --------------------------------- */

    /**
     * Validate whether the task can be moved to "completed".
     * Returns [bool $ok, string $reason].
     *
     * Expected $task flags (safe defaults used if absent):
     *   - checklist_done (bool|int)
     *   - dependencies_ok (bool|int)
     */
    public function validate_completion_prereqs(array $task): array
    {
        $checklistDone  = (bool) ($task['checklist_done']  ?? false);
        $dependenciesOk = (bool) ($task['dependencies_ok'] ?? true);

        if ($this->enforce_checklist_before_done() && !$checklistDone) {
            return [false, 'Checklist items must be completed before closing the task.'];
        }

        if ($this->block_on_unresolved_dependencies() && !$dependenciesOk) {
            return [false, 'All prerequisite tasks must be resolved before completion.'];
        }

        return [true, 'OK'];
    }

    /**
     * Convenience: compute the user's effective role rank for a given task.
     * Lower value == stronger role (useful in views).
     */
    public function role_rank(array $task, int $uid): int
    {
        if ($this->is_super_admin($uid)) return 0;

        $projectId = $task['project_id'] ?? null;
        $teamId    = $task['team_id'] ?? null;

        if ($this->is_project_manager($uid, $projectId)) return 1;
        if ($this->is_team_lead($uid, $teamId))         return 1;

        if ($this->is_assignee($task, $uid))  return 2;
        if ($this->is_requester($task, $uid)) return 3;
        if ($this->is_follower($task, $uid))  return 4;

        return 5;
    }
}
