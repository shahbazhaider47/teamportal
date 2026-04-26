<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Support policy / role hierarchy for the Support module (HMVC).
 * Highest → lowest:
 *   0 Super Admin
 *   1 Department Head (of ticket's department)
 *   2 Assignee
 *   3 Requester
 *   4 Watcher
 *   5 Other staff (per staff_can)
 *   6 Others (no access)
 *
 * Maps settings + permissions to actionable capability checks.
 * Uses only the finalized settings list you provided.
 */
class Support_policy
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

    public function is_department_head(int $uid, int $departmentId): bool
    {
        if (function_exists('is_department_head')) {
            return (bool) is_department_head($uid, $departmentId);
        }
        if (method_exists($this->CI->users, 'is_department_head')) {
            return (bool) $this->CI->users->is_department_head($uid, $departmentId);
        }
        return false;
    }

    public function is_assignee(array $ticket, int $uid): bool
    {
        return !empty($ticket['assignee_id']) && (int)$ticket['assignee_id'] === $uid;
    }

    public function is_requester(array $ticket, int $uid): bool
    {
        return !empty($ticket['requester_id']) && (int)$ticket['requester_id'] === $uid;
    }

    public function is_watcher(array $ticket, int $uid): bool
    {
        $ws = $ticket['watchers'] ?? [];
        if (is_string($ws)) { $ws = json_decode($ws, true) ?: []; }
        return in_array($uid, array_map('intval', $ws), true);
    }

    public function same_department(int $uid, int $departmentId): bool
    {
        if (function_exists('user_belongs_to_department')) {
            return (bool) user_belongs_to_department($uid, $departmentId);
        }
        if (method_exists($this->CI->users, 'belongs_to_department')) {
            return (bool) $this->CI->users->belongs_to_department($uid, $departmentId);
        }
        return false;
    }

    /* ---------------------------------
     * Settings helpers (final set)
     * --------------------------------- */
    public function yes(string $key, string $default = 'yes'): bool
    {
        return (get_setting($key, $default) === 'yes');
    }

    /** requester|assignee|both */
    public function watchers_who_can(): string
    {
        return (string) get_setting('support_user_added_watchers', 'both');
    }

    /** Public URL (on/off) */
    public function public_url_enabled(): bool
    {
        return $this->yes('support_ticket_public_url', 'no'); // final setting is yes|no
    }

    /* ---------------------------------
     * Global capability checks (no ticket)
     * --------------------------------- */
    public function can_list(int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if (function_exists('staff_can')) {
            return staff_can('view_global', 'support') || staff_can('view_own', 'support');
        }
        return false;
    }

    public function can_create(int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        return (function_exists('staff_can') ? staff_can('create', 'support') : false);
    }

    /* ---------------------------------
     * Ticket-scoped capability checks
     * --------------------------------- */
    public function can_view(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if (function_exists('staff_can') && staff_can('view_global', 'support')) return true;

        // Department Head of ticket dept always can view
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;

        // "Own" = requester or assignee (and optionally watcher)
        if (function_exists('staff_can') && staff_can('view_own', 'support')) {
            if ($this->is_requester($ticket, $uid)) return true;
            if ($this->is_assignee($ticket, $uid))  return true;
            // We do allow watchers to see the ticket (read-only)
            if ($this->is_watcher($ticket, $uid))   return true;
        }

        // Fallback for staff in same department (commonly desired)
        if ($this->same_department($uid, (int)$ticket['department_id'])) {
            // Only if they have view_own at minimum
            return (function_exists('staff_can') && staff_can('view_own', 'support'));
        }

        return false;
    }

    public function can_see_internal_notes(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;
        if ($this->is_assignee($ticket, $uid)) return true;
        // Optional fine-grained permission
        return (function_exists('staff_can') ? staff_can('edit', 'support') : false);
    }

    /** Reply/post a message visible to requester; watchers cannot post. */
    public function can_post_message(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_assignee($ticket, $uid))  return true;
        if ($this->is_requester($ticket, $uid)) return true;
        return (function_exists('staff_can') ? staff_can('edit', 'support') : false);
    }

    /** Add internal note (never visible to requester) */
    public function can_add_note(array $ticket, int $uid): bool
    {
        return $this->can_see_internal_notes($ticket, $uid);
    }

    public function can_edit(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;
        if ($this->is_assignee($ticket, $uid)) return true;
        return (function_exists('staff_can') ? staff_can('edit', 'support') : false);
    }

    public function can_assign(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;
        return (function_exists('staff_can') ? staff_can('assign', 'support') : false);
    }

    public function can_change_status(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;
        if ($this->is_assignee($ticket, $uid)) return true;
        return (function_exists('staff_can') ? staff_can('edit', 'support') : false);
    }

    public function can_delete(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        return (function_exists('staff_can') ? staff_can('delete', 'support') : false);
    }

    public function can_manage_watchers(array $ticket, int $uid): bool
    {
        if ($this->is_super_admin($uid)) return true;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return true;

        $who   = $this->watchers_who_can(); // requester|assignee|both
        $isReq = $this->is_requester($ticket, $uid);
        $isAss = $this->is_assignee($ticket, $uid);

        if ($who === 'requester') return $isReq;
        if ($who === 'assignee')  return $isAss;
        if ($who === 'both')      return ($isReq || $isAss);

        // Fallback if misconfigured
        return (function_exists('staff_can') ? staff_can('edit', 'support') : false);
    }

    /** Only assignee may generate a public link, and only if setting enabled. */
    public function can_generate_public_link(array $ticket, int $uid): bool
    {
        if (!$this->public_url_enabled()) return false;
        if ($this->is_super_admin($uid)) return true; // optional: keep super admin override
        return $this->is_assignee($ticket, $uid);
    }

    /**
     * Lower value == stronger role (used in views to gate sections)
     */
    public function role_rank(array $ticket, int $uid): int
    {
        if ($this->is_super_admin($uid)) return 0;
        if ($this->is_department_head($uid, (int)$ticket['department_id'])) return 1;
        if ($this->is_assignee($ticket, $uid)) return 2;
        if ($this->is_requester($ticket, $uid)) return 3;
        if ($this->is_watcher($ticket, $uid)) return 4;
        return 5;
    }
}
