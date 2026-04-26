<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('current_user_role')) {
    function current_user_role(): string
    {
        $CI = get_instance();
        $role = (string)($CI->session->userdata('user_role') ?? '');
        return strtolower(trim($role));
    }
}

if (!function_exists('is_superadmin')) {
    function is_superadmin(): bool
    {
        return current_user_role() === 'superadmin';
    }
}

if (!function_exists('is_manager')) {
    function is_manager(): bool
    {
        return current_user_role() === 'manager';
    }
}

if (!function_exists('is_teamlead')) {
    function is_teamlead(): bool
    {
        return current_user_role() === 'teamlead';
    }
}

if (!function_exists('_perm_norm')) {
    function _perm_norm($s): string {
        if (!is_string($s)) return '';
        $s = preg_replace('/\s*:\s*/', ':', $s);
        return strtolower(trim($s));
    }
}

if (!function_exists('_perm_norm_arr')) {
    function _perm_norm_arr($arr): array {
        $out = [];
        $stack = is_array($arr) ? $arr : [];
        $flat  = [];
        foreach ($stack as $item) {
            if (is_array($item)) {
                foreach ($item as $inner) {
                    if (is_string($inner)) $flat[] = $inner;
                }
            } elseif (is_string($item)) {
                $flat[] = $item;
            }
        }

        foreach ($flat as $s) {
            $ss = _perm_norm($s);
            if ($ss !== '') $out[] = $ss;
        }
        return array_values(array_unique($out));
    }
}


if (!function_exists('is_superadmin')) {
    function is_superadmin(): bool
    {
        $CI = &get_instance();
        return strtolower((string)($CI->session->userdata('user_role') ?? '')) === 'superadmin';
    }
}

if (!function_exists('staff_can')) {
    function staff_can(string $action, string $module): bool
    {
        $CI = &get_instance();

        $user_id = (int) ($CI->session->userdata('user_id') ?? 0);
        if ($user_id <= 0) return false;

        if (is_superadmin()) return true;

        $permKeyCanonical = _perm_norm($module . ':' . $action);
        $permKeyLegacy    = _perm_norm('Can: ' . $action);
        $user_grants = $CI->session->userdata('user_perm_grants');
        $user_denies = $CI->session->userdata('user_perm_denies');
        $cached_user_updated_at = $CI->session->userdata('perm_user_updated_at');
        
        static $runtimeChecked = false;
        static $cur_user_updated_at = null;

        if (!$runtimeChecked) {
            $rowU = $CI->db->select('updated_at')
                           ->where('user_id', $user_id)
                           ->limit(1)
                           ->get('user_permissions')
                           ->row_array();
            $cur_user_updated_at = $rowU['updated_at'] ?? null;
            $runtimeChecked = true;
        }

        $needsRefresh = (!is_array($user_grants) || !is_array($user_denies) ||
                         ($cur_user_updated_at !== $cached_user_updated_at));

        if ($needsRefresh) {
            $CI->load->model('User_permissions_model', 'userperms');
            $ud = $CI->userperms->get_by_user_id($user_id);

            if (($ud['_source'] ?? '') === 'empty') {
                $CI->config->load('default_user_permissions', TRUE);

                $default_grants = (array) $CI->config->item('default_user_grants', 'default_user_permissions');
                $default_denies = (array) $CI->config->item('default_user_denies',  'default_user_permissions');

                $CI->userperms->apply_defaults_if_missing($user_id, [
                    'grants' => _perm_norm_arr($default_grants),
                    'denies' => _perm_norm_arr($default_denies),
                ]);

                $ud = $CI->userperms->get_by_user_id($user_id);
            }

            $user_grants = _perm_norm_arr($ud['grants'] ?? []);
            $user_denies = _perm_norm_arr($ud['denies'] ?? []);

            $CI->session->set_userdata('user_perm_grants', $user_grants);
            $CI->session->set_userdata('user_perm_denies', $user_denies);
            $CI->session->set_userdata('perm_user_updated_at', $ud['updated_at'] ?? $cur_user_updated_at);
        } else {
            $user_grants = _perm_norm_arr($user_grants);
            $user_denies = _perm_norm_arr($user_denies);
        }

        if (in_array($permKeyCanonical, $user_denies, true) || in_array($permKeyLegacy, $user_denies, true)) {
            return false;
        }
        if (in_array($permKeyCanonical, $user_grants, true) || in_array($permKeyLegacy, $user_grants, true)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('refresh_current_user_permissions_cache')) {
    function refresh_current_user_permissions_cache(): void
    {
        $CI = &get_instance();
        $CI->session->unset_userdata('user_perm_grants');
        $CI->session->unset_userdata('user_perm_denies');
        $CI->session->unset_userdata('perm_user_updated_at');
    }
}