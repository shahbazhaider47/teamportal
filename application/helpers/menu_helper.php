<?php defined('BASEPATH') OR exit('No direct script access allowed');


    if (!function_exists('is_admin')) {
        function is_admin()
        {
            $CI = &get_instance();
            $role = $CI->session->userdata('user_role');
            
            return $role === 'admin';
        }
    }


    if (!function_exists('get_header_app_icons')) {
        function get_header_app_icons()
        {
            $icons = [];
    
            // Add system default icons (always visible)
            //$icons[] = [
            //    'slug'  => 'chat',
            //    'href'  => site_url('chat'),
            //    'icon'  => 'ti ti-brand-hipchat',
            //    'position' => 5,
            //];
    
            // 🧩 Let modules inject more icons
            return hooks()->apply_filters('header_app_icons', $icons);
        }
    }
    
    
    if (! function_exists('get_menu_items')) {
        function get_menu_items(): array
        {
            $CI = &get_instance();
            if (! isset($CI->menu_items) || ! is_array($CI->menu_items)) {
                return [];
            }
            uasort($CI->menu_items, function($a, $b) {
                return $a['position'] <=> $b['position'];
            });
    
            foreach ($CI->menu_items as &$menu) {
                if (! empty($menu['children']) && is_array($menu['children'])) {
                    uasort($menu['children'], function($a, $b) {
                        return $a['position'] <=> $b['position'];
                    });
                }
            }
            return $CI->menu_items;
        }
    }

    if (!function_exists('add_profile_menu_item')) {
        function add_profile_menu_item($slug, $item) {
            if (!isset($GLOBALS['__profile_menu_items'])) {
                $GLOBALS['__profile_menu_items'] = [];
            }
    
            $GLOBALS['__profile_menu_items'][$slug] = $item;
        }
    }
    
    
    if (!function_exists('get_profile_menu_items')) {
        function get_profile_menu_items() {
            $items = isset($GLOBALS['__profile_menu_items']) ? $GLOBALS['__profile_menu_items'] : [];
            $items = hooks()->apply_filters('profile_menu_items', $items);
    
            foreach ($items as &$item) {
                if (!isset($item['position'])) {
                    $item['position'] = 1000;
                }
            }
            unset($item);
    
            uasort($items, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });
    
            return $items;
        }
    }



if (!function_exists('get_complete_sidebar_menu')) {
    function get_complete_sidebar_menu(string $group = 'main')
    {
        $CI = &get_instance();

        if (!function_exists('hooks')) {
            $CI->load->helper('misc');
        }

        // Core items
        $core_items = get_menu_items();

        // Hooked items (modules)
        $hooked_items = hooks()->apply_filters('app_sidebar_menu', []);
        if (!is_array($hooked_items)) {
            $hooked_items = [];
        }

        // Feature filtering (keep your existing behavior)
        if (isset($CI->features) && is_array($CI->features)) {
            $hooked_items = array_filter($hooked_items, function ($item) use ($CI) {
                $slug          = $item['slug'] ?? null;
                $onlyIfFeature = $item['feature_only'] ?? false;
                return !$onlyIfFeature || ($slug && isset($CI->features[$slug]));
            });
        }

        // Normalize group on hooked menus (default group = main)
        foreach ($hooked_items as &$it) {
            if (!is_array($it)) {
                $it = [];
                continue;
            }

            if (empty($it['menu_group'])) {
                $it['menu_group'] = 'main';
            }

            if (!empty($it['children']) && is_array($it['children'])) {
                foreach ($it['children'] as &$c) {
                    if (!is_array($c)) {
                        $c = [];
                        continue;
                    }
                    if (empty($c['menu_group'])) {
                        // children inherit parent group by default
                        $c['menu_group'] = $it['menu_group'];
                    }
                }
                unset($c);
            }
        }
        unset($it);

        /**
         * ✅ NEW RULES
         * - main sidebar should NOT show module-group menus (crm/finance/etc.)
         * - main sidebar should show:
         *      core items + hooked items where menu_group = 'main'
         * - crm sidebar should show:
         *      only hooked items where menu_group = 'crm' (core excluded)
         */

        // ✅ If main: exclude non-main groups (crm/finance/etc.)
        if ($group === 'main') {
            $hooked_items = array_values(array_filter($hooked_items, function ($item) {
                if (!is_array($item)) return false;
                return (($item['menu_group'] ?? 'main') === 'main');
            }));
        }

        // ✅ If not main: return ONLY that group (hooked only)
        if ($group !== 'main') {
            $hooked_items = array_values(array_filter($hooked_items, function ($item) use ($group) {
                return is_array($item) && (($item['menu_group'] ?? 'main') === $group);
            }));

            $merged = [];

            foreach ($hooked_items as $item) {
                if (!is_array($item)) continue;

                $slug = $item['slug'] ?? uniqid('hooked_');

                if (!isset($merged[$slug])) {
                    $merged[$slug] = $item;
                } else {
                    // Merge children if repeated slug comes from multiple hooks
                    if (!empty($item['children']) && is_array($item['children'])) {
                        if (!isset($merged[$slug]['children'])) {
                            $merged[$slug]['children'] = [];
                        }

                        foreach ($item['children'] as $child) {
                            if (!is_array($child)) continue;
                            $childSlug = $child['slug'] ?? uniqid('child_');
                            $merged[$slug]['children'][$childSlug] = $child;
                        }

                        $merged[$slug]['collapse'] = true;
                    }
                }
            }

            uasort($merged, fn($a, $b) => ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999));

            foreach ($merged as &$item) {
                if (!empty($item['children']) && is_array($item['children'])) {
                    uasort($item['children'], fn($a, $b) => ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999));
                }
            }
            unset($item);

            return $merged;
        }

        // ===== MAIN sidebar (core + only main-group hooked) =====
        $merged = [];

        foreach ($core_items as $slug => $item) {
            $merged[$slug] = $item;
        }

        foreach ($hooked_items as $item) {
            if (!is_array($item)) continue;

            $slug = $item['slug'] ?? uniqid('hooked_');

            if (!isset($merged[$slug])) {
                $merged[$slug] = $item;
            } else {
                if (!empty($item['children']) && is_array($item['children'])) {
                    if (!isset($merged[$slug]['children'])) {
                        $merged[$slug]['children'] = [];
                    }

                    foreach ($item['children'] as $child) {
                        if (!is_array($child)) continue;

                        $childSlug = $child['slug'] ?? uniqid('child_');
                        $merged[$slug]['children'][$childSlug] = $child;
                    }

                    $merged[$slug]['collapse'] = true;
                }
            }
        }

        uasort($merged, fn($a, $b) => ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999));

        foreach ($merged as &$item) {
            if (!empty($item['children']) && is_array($item['children'])) {
                uasort($item['children'], fn($a, $b) => ($a['position'] ?? 9999) <=> ($b['position'] ?? 9999));
            }
        }
        unset($item);

        return $merged;
    }
}

if (!function_exists('register_core_menu_items')) {
    function register_core_menu_items()
    {
        $CI =& get_instance();
        $role = strtolower($CI->session->userdata('user_role'));

        add_menu_item('dashboard', [
            'name'     => _l('nav_dashboard'),
            'href'     => site_url('dashboard'),
            'icon'     => 'ti ti-home',
            'position' => 1,
        ]);
        
        if (staff_can('view_global', 'users')) {
            add_menu_item('hrm', [
                'name'     => 'HRM',
                'href'     => site_url('users'),
                'icon'     => 'ti ti-users',
                'position' => 2,
                'collapse' => true,
                'children' => [
                    [
                        'slug'     => 'staff',
                        'name'     => 'Staff List',
                        'href'     => site_url('users'),
                        'position' => 1,
                    ],
                    [
                        'slug'     => 'manage_staff',
                        'name'     => 'Manage Staff',
                        'href'     => site_url('users/manage_users'),
                        'position' => 2,
                    ],
                    [
                        'slug'     => 'contracts',
                        'name'     => 'Contracts',
                        'href'     => site_url('contracts'),
                        'position' => 3,
                    ], 
                    [
                        'slug'     => 'user_documents',
                        'name'     => 'Documents',
                        'href'     => site_url('users/documents'),
                        'position' => 4,
                    ], 
                    [
                        'slug'     => 'user_allowances',
                        'name'     => 'Allowances',
                        'href'     => site_url('users/allowances'),
                        'position' => 5,
                    ],
                ], 
            ]);
        }

        if (staff_can('view_global', 'teams')) {
            add_menu_item('teams', [
                'name'     => _l('nav_teams'),
                'href'     => site_url('teams'),
                'icon'     => 'ti ti-sitemap',
                'position' => 8,
            ]);
        }
        
        if (staff_can('view_own', 'teams')) {
            add_menu_item('my_team', [
                'name'     => _l('nav_myteams'),
                'href'     => site_url('teams/my_team'),
                'icon'     => 'ti ti-users',
                'position' => 10,
            ]);
        }

        if (staff_can('view_global', 'attendance')) {
            add_menu_item('attendance', [
                'name'     => 'Attendance',
                'href'     => site_url('attendance'),
                'icon'     => 'ti ti-calendar',
                'position' => 11,
            ]);
        }
        
        if (staff_can('view_own', 'attendance')) {
            add_menu_item('my_attendance', [
                'name'     => 'My Attendance',
                'href'     => site_url('attendance/my_attendance'),
                'icon'     => 'ti ti-calendar',
                'position' => 12,
            ]);
        }
        
        if (staff_can('view', 'subscriptions')) {
            add_menu_item('subscriptions', [
                'name'     => 'Subscriptions',
                'href'     => site_url('subscriptions'),
                'icon'     => 'ti ti-receipt-2',
                'position' => 45,
            ]);
        }

        if (staff_can('view_global', 'evaluations')) {
            add_menu_item('evaluations', [
                'name'     => 'Evaluations',
                'href'     => site_url('evaluations'),
                'icon'     => 'ti ti-wallet',
                'position' => 81,
            ]);
        }
        

        if (staff_can('view_own', 'evaluations')) {
            add_menu_item('my_evaluations', [
                'name'     => 'My Evaluations',
                'href'     => site_url('evaluations/my'),
                'icon'     => 'ti ti-wallet',
                'position' => 82,
            ]);
        }
        
        if (staff_can('view_global', 'vault') || staff_can('view_own', 'vault')) {
            add_menu_item('login_vault', [
                'name'     => 'Logins Vault',
                'href'     => site_url('login_vault'),
                'icon'     => 'ti ti-wallet',
                'position' => 95,
            ]);
        }
        
        if (staff_can('view', 'assets')) {
            add_menu_item('asset', [
                'name'     => _l('nav_assets'),
                'href'     => site_url('asset'),
                'icon'     => 'ti ti-packages',
                'position' => 97,
            ]);
        }
        
        if (staff_can('view_global', 'utilities')) {
            add_menu_item('utilities', [
                'name'     => _l('nav_utilities'),
                'href'     => site_url('utilities'),
                'icon'     => 'ti ti-keyframes',
                'position' => 98,
            ]);
        }    
    
        if (staff_can('viewsystem', 'general')) {
            add_menu_item('settings', [
                'name'     => _l('nav_settings'),
                'href'     => site_url('settings'),
                'icon'     => 'ti ti-settings',
                'position' => 99,
            ]);
            
        }
        
        if (staff_can('view_global', 'support') || staff_can('view_own', 'support')) {
            add_menu_item('support', [
                'name'     => 'Support',
                'href'     => site_url('support'),
                'icon'     => 'ti ti-headset',
                'position' => 100,
            ]);
            
        }
        
        add_profile_menu_item('profile', [
            'name'     => _l('nav_profile'),
            'href'     => site_url('users/profile'),
            'icon'     => 'ti ti-user',
            'position' => 1,
        ]);
        
        
        add_profile_menu_item('settings', [
            'name'     => _l('nav_settings'),
            'href'     => site_url('users/settings'),
            'icon'     => 'ti ti-settings',
            'position' => 3,
        ]);

        if (staff_can('manage_permissions', 'general')) {
            add_profile_menu_item('permissions', [
                'name'     => 'Permissions',
                'href'     => site_url('settings/manage_permissions'),
                'icon'     => 'ti ti-shield-lock',
                'position' => 10,
            ]);
        }
        
        add_profile_menu_item('divider1', [
            'type'     => 'divider',
            'position' => 80,
        ]);


        add_profile_menu_item('logout', [
            'name'     => _l('nav_logout'),
            'href'     => site_url('authentication/logout'),
            'icon'     => 'ti ti-logout',
            'position' => 9999,
            'class'    => 'text-danger',
        ]);


    }
}

if (!function_exists('add_reports_menu_group')) {
    function add_reports_menu_group(string $group_slug, array $group)
    {
        if (!isset($GLOBALS['__reports_menu']) || !is_array($GLOBALS['__reports_menu'])) {
            $GLOBALS['__reports_menu'] = [];
        }

        $group['slug']     = $group_slug;
        $group['position'] = isset($group['position']) ? (int) $group['position'] : 100;
        $group['items']    = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];

        if (!isset($GLOBALS['__reports_menu'][$group_slug])) {
            $GLOBALS['__reports_menu'][$group_slug] = $group;
        }
    }
}

if (!function_exists('add_reports_menu_item')) {
    function add_reports_menu_item(string $group_slug, array $item)
    {
        if (
            !isset($GLOBALS['__reports_menu'][$group_slug]) ||
            !is_array($GLOBALS['__reports_menu'][$group_slug])
        ) {
            return;
        }

        $item['position'] = isset($item['position']) ? (int) $item['position'] : 100;

        $GLOBALS['__reports_menu'][$group_slug]['items'][] = $item;
    }
}

if (!function_exists('get_reports_menu')) {
    function get_reports_menu(): array
    {
        static $initialized = false;

        if (!$initialized) {
            if (function_exists('hooks')) {
                hooks()->do_action('app_init_reports_menu');
            }
            $initialized = true;
        }

        $menu = $GLOBALS['__reports_menu'] ?? [];

        if (function_exists('hooks')) {
            $menu = hooks()->apply_filters('app_reports_menu', $menu);
        }

        uasort($menu, function ($a, $b) {
            return ($a['position'] ?? 999) <=> ($b['position'] ?? 999);
        });

        foreach ($menu as &$group) {
            if (!empty($group['items']) && is_array($group['items'])) {
                usort($group['items'], function ($a, $b) {
                    return ($a['position'] ?? 999) <=> ($b['position'] ?? 999);
                });
            }
        }
        unset($group);

        return $menu;
    }
}

if (function_exists('hooks')) {
    hooks()->add_action('app_init_reports_menu', function () {

        add_reports_menu_group('users', [
            'label'    => 'Users',
            'icon'     => 'ti ti-users',
            'position' => 10,
        ]);

        add_reports_menu_item('users', [
            'label'    => 'All Users',
            'href'     => site_url('reports/users/all/report_view'),
            'icon'     => 'ti ti-arrow-badge-right',
            'position' => 1,
        ]);

        add_reports_menu_item('users', [
            'label'    => 'Full Profile',
            'href'     => site_url('reports/users/profile/report_view'),
            'icon'     => 'ti ti-arrow-badge-right',
            'position' => 2,
        ]);

        add_reports_menu_item('users', [
            'label'    => 'Inactive Users',
            'href'     => site_url('reports/users/inactive/report_view'),
            'icon'     => 'ti ti-arrow-badge-right',
            'position' => 3,
        ]);

        add_reports_menu_item('users', [
            'label'    => 'New Hires',
            'href'     => site_url('reports/users/new_hires/report_view'),
            'icon'     => 'ti ti-arrow-badge-right',
            'class'    => 'text-primary fw-semibold',
            'position' => 100,
        ]);

    });
}

if (! function_exists('add_menu_item')) {
    function add_menu_item(string $slug, array $item)
    {
        $CI = &get_instance();
        if (! isset($CI->menu_items) || ! is_array($CI->menu_items)) {
            $CI->menu_items = [];
        }
        $defaults = [
            'name'     => '',
            'href'     => '#',
            'icon'     => '',
            'position' => 0,
            'badge'    => null,
            'collapse' => false,
            'children' => [],
        ];
        $CI->menu_items[$slug] = array_merge($defaults, $CI->menu_items[$slug] ?? [], $item);
    }
}

if (! function_exists('add_menu_child_item')) {
    function add_menu_child_item(string $parentSlug, string $slug, array $item)
    {
        $CI = &get_instance();
        if (! isset($CI->menu_items[$parentSlug])) {
            $CI->menu_items[$parentSlug] = [
                'name'     => '',
                'href'     => '#',
                'icon'     => '',
                'position' => 0,
                'badge'    => null,
                'collapse' => true,
                'children' => [],
            ];
        }
        $defaults = [
            'name'     => '',
            'href'     => '#',
            'icon'     => '',
            'position' => 0,
            'badge'    => null,
        ];
        $CI->menu_items[$parentSlug]['collapse'] = true;
        $CI->menu_items[$parentSlug]['children'][$slug] = array_merge($defaults, $item);
    }
}

if (!function_exists('role_policy')) {
    function role_policy(string $action, array $role): bool
    {
        $roleName = strtolower((string) ($role['role_name'] ?? ''));
        $protectedRoles = [
            'superadmin',
            'director',
            'manager',
            'teamlead',
            'employee',
            'officeboy',  
            'sweeper',
            'other',
        ];

        if (in_array($roleName, $protectedRoles, true)) {
            return false;
        }

        return true;
    }
}