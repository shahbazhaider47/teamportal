<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Returns numeric weight of a role. 0 if unknown or excluded.
 */
if (!function_exists('hierarchy_weight')) {
    function hierarchy_weight(string $role): int {
        $role = strtolower(trim($role));
        return HIERARCHY_WEIGHTS[$role] ?? 0;
    }
}

/**
 * Returns true if role is excluded from all hierarchy logic.
 */
if (!function_exists('hierarchy_is_excluded')) {
    function hierarchy_is_excluded(string $role): bool {
        return in_array(strtolower(trim($role)), HIERARCHY_EXCLUDED, true);
    }
}

/**
 * Returns scope string for a role: 'global'|'dept'|'team'|'self'
 */
if (!function_exists('hierarchy_scope')) {
    function hierarchy_scope(string $role): string {
        $role = strtolower(trim($role));
        return HIERARCHY_SCOPE[$role] ?? 'self';
    }
}

/**
 * Returns true if roleA is strictly senior to roleB.
 */
if (!function_exists('hierarchy_is_senior')) {
    function hierarchy_is_senior(string $roleA, string $roleB): bool {
        return hierarchy_weight($roleA) > hierarchy_weight($roleB);
    }
}

/**
 * Returns true if actor role can VIEW records belonging to target role.
 */
if (!function_exists('hierarchy_can_view')) {
    function hierarchy_can_view(string $actorRole, string $targetRole): bool {
        $actorRole  = strtolower(trim($actorRole));
        $targetRole = strtolower(trim($targetRole));
        if (hierarchy_is_excluded($targetRole)) return false;
        $allowed = HIERARCHY_CAN_VIEW[$actorRole] ?? [];
        return in_array($targetRole, $allowed, true);
    }
}

/**
 * Returns true if actor role can MANAGE (approve/evaluate/edit) target role.
 */
if (!function_exists('hierarchy_can_manage')) {
    function hierarchy_can_manage(string $actorRole, string $targetRole): bool {
        $actorRole  = strtolower(trim($actorRole));
        $targetRole = strtolower(trim($targetRole));
        if (hierarchy_is_excluded($targetRole)) return false;
        $allowed = HIERARCHY_CAN_MANAGE[$actorRole] ?? [];
        return in_array($targetRole, $allowed, true);
    }
}

/**
 * Returns list of roles this role can VIEW.
 * null = no filter (see all).
 */
if (!function_exists('hierarchy_visible_roles')) {
    function hierarchy_visible_roles(string $role): ?array {
        $role = strtolower(trim($role));
        $scope = hierarchy_scope($role);
        if ($scope === 'global') return null; // see everything
        return HIERARCHY_CAN_VIEW[$role] ?? [];
    }
}

/**
 * Returns list of roles this role can MANAGE.
 */
if (!function_exists('hierarchy_manageable_roles')) {
    function hierarchy_manageable_roles(string $role): array {
        $role = strtolower(trim($role));
        return HIERARCHY_CAN_MANAGE[$role] ?? [];
    }
}

/**
 * Human-readable scope label for UI headers.
 */
if (!function_exists('hierarchy_scope_label')) {
    function hierarchy_scope_label(string $role): string {
        return match(strtolower(trim($role))) {
            'superadmin', 'director' => 'All teams',
            'manager'                => 'All teams in your department',
            'teamlead'               => 'Your team',
            default                  => 'Your team members',
        };
    }
}

// ── Backward-compat wrappers ─────────────────────────────────────────────────
// Any existing code calling teams_role_* functions keeps working unchanged.

if (!function_exists('teams_role_weight')) {
    function teams_role_weight(string $role): int {
        return hierarchy_weight($role);
    }
}

if (!function_exists('teams_role_excluded')) {
    function teams_role_excluded(string $role): bool {
        return hierarchy_is_excluded($role);
    }
}

if (!function_exists('teams_visible_roles')) {
    function teams_visible_roles(string $role): ?array {
        return hierarchy_visible_roles($role);
    }
}

if (!function_exists('teams_scope_label')) {
    function teams_scope_label(string $role): string {
        return hierarchy_scope_label($role);
    }
}