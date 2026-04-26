<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Staff Payroll
Description: Automates salary calculations, allowances, deductions, and more. Ensures accurate payouts, generates payslips, and syncs with attendance data for smooth payroll cycles.
Version: 1.0.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.3.*
Requires Modules:
Settings Icon: ti ti-report-money
Settings Name: Payroll
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('PAYROLL_MODULE_NAME', 'payroll');

// ─────────────────────────────────────────────────────────────
// 🎨 Register CSS/JS Assets
// ─────────────────────────────────────────────────────────────
add_module_assets(PAYROLL_MODULE_NAME, [
    'css' => ['payroll.css'],
    'js'  => ['payroll.js'],
]);

define('PAYROLL_MODULE_PATH', module_dir_path(PAYROLL_MODULE_NAME));
define('PAYROLL_MODULE_URL',  module_dir_url(PAYROLL_MODULE_NAME));

// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(PAYROLL_MODULE_NAME,   'payroll_module_activate');
register_deactivation_hook(PAYROLL_MODULE_NAME, 'payroll_module_deactivate');
register_uninstall_hook(PAYROLL_MODULE_NAME,    'payroll_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';
if (file_exists(PAYROLL_MODULE_PATH . 'language/' . $lang . '/payroll_lang.php')) {
    $CI->lang->load(PAYROLL_MODULE_NAME . '/payroll', $lang);
} else {
    $CI->lang->load(PAYROLL_MODULE_NAME . '/payroll', 'english');
}
register_language_files(PAYROLL_MODULE_NAME, ['payroll']);

// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu Items (Staff + Permission Check)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'payroll_module_sidebar_menu');
function payroll_module_sidebar_menu($menus)
{
    // Admin / Global payroll menu
    if (staff_can('view_global', 'payroll')) {
        $menus[] = [
            'slug'     => 'payroll_admin', // unique slug
            'name'     => 'Payroll',
            'icon'     => 'ti ti-report-money',
            'href'     => base_url('payroll'),
            'position' => 2,
            'collapse' => true,
            'children' => [
                [
                    'slug'     => 'payroll_admin_manage',
                    'name'     => 'Manage',
                    'href'     => site_url('payroll'),
                    'position' => 1,
                ],
                [
                    'slug'     => 'payroll_admin_increments',
                    'name'     => 'Increments',
                    'href'     => site_url('payroll/increments'),
                    'position' => 2,
                ],
                [
                    'slug'     => 'payroll_admin_loans',
                    'name'     => 'Loans',
                    'href'     => site_url('payroll/loans'),
                    'position' => 3,
                ],
                [
                    'slug'     => 'payroll_admin_advances',
                    'name'     => 'Advances',
                    'href'     => site_url('payroll/advances'),
                    'position' => 4,
                ],
                [
                    'slug'     => 'payroll_admin_arrears',
                    'name'     => 'Arrears',
                    'href'     => site_url('payroll/arrears'),
                    'position' => 5,
                ],                
                [
                    'slug'     => 'payroll_admin_pf',
                    'name'     => 'PF Accounts',
                    'href'     => site_url('payroll/pf_accounts'),
                    'position' => 6,
                ],
                [
                    'slug'     => 'payroll_admin_settlement',
                    'name'     => 'Final Settlement',
                    'href'     => site_url('payroll/final_settlement'),
                    'position' => 7,
                ],
            ],
        ];
    }

    // Self-service / My payroll menu
    if (staff_can('view_own', 'payroll')) {
        $menus[] = [
            'slug'     => 'payroll_self', // unique slug
            'name'     => 'My Payroll',
            'icon'     => 'ti ti-wallet',
            'href'     => base_url('payroll/my'),
            'position' => 3,
        ];
    }

    return $menus;
}


// ─────────────────────────────────────────────────────────────
// 🔗 Module Page Action Link (in Modules table/listing)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . PAYROLL_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('payroll/settings') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall Functions
// ─────────────────────────────────────────────────────────────
function payroll_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(PAYROLL_MODULE_PATH . 'install.php'); // creates payroll_users
    } catch (Exception $e) {
        throw $e;
    }
}

function payroll_module_deactivate()
{
    log_message('debug', '⚙️ Payroll module deactivated.');
}

function payroll_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(PAYROLL_MODULE_PATH . 'uninstall.php'); // drops payroll_users (if you keep it that way)
    } catch (Exception $e) {
        log_message('error', '❌ Payroll uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────
// 🔐 Permissions
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'payroll_permissions');
function payroll_permissions($permissions)
{
    $permissions['payroll'] = [
        'name'    => _l('permissions_payroll'),
        'actions' => [
            'view_global'       => _l('view_global'),
            'run'           => 'Run Payroll',
            'view_own'          => _l('view_own'),
            'create'            => _l('create'),
            'edit'              => _l('edit'),
            'delete'            => _l('delete'),
        ],
    ];
    return $permissions;
}

// ─────────────────────────────────────────────────────────────
// 📊 Requests Overview: register "Payroll Advances" section
// ─────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────
// 📊 Requests Overview: register Payroll sections (Advances + Loans)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('requests_sections', 'payroll_register_requests_section', 10, 2);

function payroll_register_requests_section(array $sections, array $args)
{
    // Ensure module is active
    if (function_exists('module_is_active') && !module_is_active(PAYROLL_MODULE_NAME)) {
        return $sections;
    }

    // Permissions – same as other payroll screens
    if (function_exists('staff_can')) {
        if (!staff_can('view_own', 'payroll') && !staff_can('view_global', 'payroll')) {
            return $sections;
        }
    }

    $CI =& get_instance();

    // ---------------------------------------------------------
    // 1) Payroll Advances
    // ---------------------------------------------------------
    $CI->db->from('payroll_advances');
    $total_adv = (int)$CI->db->count_all_results();

    $CI->db->from('payroll_advances');
    $CI->db->where('status', 'requested'); // mapped into "Pending"
    $pending_adv = (int)$CI->db->count_all_results();

    $CI->db->from('payroll_advances');
    $CI->db->where('status', 'approved');
    $approved_adv = (int)$CI->db->count_all_results();

    $CI->db->from('payroll_advances');
    $CI->db->where('status', 'rejected');
    $rejected_adv = (int)$CI->db->count_all_results();

    $slug_adv = 'advances';

    $sections[$slug_adv] = [
        'slug'        => $slug_adv,
        'label'       => 'Payroll Advances',
        //'description' => 'Advance salary requests submitted via the Payroll module.',
        'icon'        => 'ti ti-cash',
        'url'         => site_url('requests/' . $slug_adv),
        'module'      => PAYROLL_MODULE_NAME,
        'total'       => $total_adv,
        'pending'     => $pending_adv,
        'approved'    => $approved_adv,
        'rejected'    => $rejected_adv,
    ];

    // ---------------------------------------------------------
    // 2) Payroll Loans
    // ---------------------------------------------------------
    $CI->db->from('payroll_loans');
    $total_loans = (int)$CI->db->count_all_results();

    // Treat "requested" as pending in dashboard semantics (same mapping logic)
    $CI->db->from('payroll_loans');
    $CI->db->where('status', 'requested');
    $pending_loans = (int)$CI->db->count_all_results();

    $CI->db->from('payroll_loans');
    $CI->db->where('status', 'approved');
    $approved_loans = (int)$CI->db->count_all_results();

    $CI->db->from('payroll_loans');
    $CI->db->where('status', 'rejected');
    $rejected_loans = (int)$CI->db->count_all_results();

    $slug_loans = 'loans';

    $sections[$slug_loans] = [
        'slug'        => $slug_loans,
        'label'       => 'Payroll Loans',
        //'description' => 'Salary loan requests and repayment schedules managed in the Payroll module.',
        'icon'        => 'ti ti-pig-money',
        'url'         => site_url('requests/' . $slug_loans),
        'module'      => PAYROLL_MODULE_NAME,
        'total'       => $total_loans,
        'pending'     => $pending_loans,
        'approved'    => $approved_loans,
        'rejected'    => $rejected_loans,
    ];

    return $sections;
}


// ─────────────────────────────────────────────────────────────
// 📄 Requests Section View: detailed Payroll pages (Advances + Loans)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('requests_section_view', 'payroll_requests_section_view', 10, 2);

function payroll_requests_section_view($html, array $args)
{
    $slug = $args['slug'] ?? '';

    // Not our sections
    if ($slug !== 'advances' && $slug !== 'loans') {
        return $html;
    }

    // Module active?
    if (function_exists('module_is_active') && !module_is_active(PAYROLL_MODULE_NAME)) {
        return $html;
    }

    // Permissions
    if (function_exists('staff_can')) {
        if (!staff_can('view_own', 'payroll') && !staff_can('view_global', 'payroll')) {
            return $html; // Requests::section() will treat empty as 404
        }
    }

    $CI =& get_instance();

    $userId  = (int)($args['user_id'] ?? 0);
    $filters = is_array($args['filters'] ?? []) ? $args['filters'] : [];

    // ---------------------------------------------------------
    // Advances section
    // ---------------------------------------------------------
    if ($slug === 'advances') {
        $CI->db->select('pa.*, u.fullname, u.firstname, u.lastname');
        $CI->db->from('payroll_advances AS pa');
        $CI->db->join('users AS u', 'u.id = pa.user_id', 'left');
        $CI->db->order_by('pa.requested_at', 'DESC');

        $advances = $CI->db->get()->result_array();

        return $CI->load->view('payroll/partials/advances_requests', [
            'advances' => $advances,
            'user_id'  => $userId,
            'section'  => $args['section'] ?? [],
        ], true);
    }

    // ---------------------------------------------------------
    // Loans section
    // ---------------------------------------------------------
    if ($slug === 'loans') {
        $CI->db->select('pl.*, u.fullname, u.firstname, u.lastname');
        $CI->db->from('payroll_loans AS pl');
        $CI->db->join('users AS u', 'u.id = pl.user_id', 'left');
        $CI->db->order_by('pl.created_at', 'DESC');

        $loans = $CI->db->get()->result_array();

        return $CI->load->view('payroll/partials/loans_requests', [
            'loans'   => $loans,
            'user_id' => $userId,
            'section' => $args['section'] ?? [],
        ], true);
    }

    return $html;
}


/*
 * (Optional) If you want a Payroll tab in the user profile like your attendance module:
 *
 * hooks()->add_filter('user_profile_tabs', function ($payload) {
 *     if (is_array($payload) && array_key_exists('tabs', $payload)) {
 *         $payload['tabs'][] = '
 *           <button class="nav-link" id="payroll-tab"
 *                   data-bs-toggle="tab" data-bs-target="#payroll"
 *                   type="button" role="tab" aria-selected="false">
 *             <i class="ti ti-cash pe-1 ps-1"></i> Payroll
 *           </button>';
 *         return $payload;
 *     }
 *     $payload[] = '
 *       <button class="nav-link" id="payroll-tab"
 *               data-bs-toggle="tab" data-bs-target="#payroll"
 *               type="button" role="tab" aria-selected="false">
 *         <i class="ti ti-cash pe-1 ps-1"></i> Payroll
 *       </button>';
 *     return $payload;
 * });
 *
 * hooks()->add_filter('user_profile_tab_contents', function ($payload) {
 *     $CI = &get_instance();
 *     if (is_array($payload) && array_key_exists('contents', $payload)) {
 *         $user = $payload['user'] ?? null;
 *         $html = $CI->load->view('payroll/user_tab', ['user' => $user], true);
 *         $payload['contents'][] = '
 *           <div class="tab-pane fade" id="payroll" role="tabpanel" aria-labelledby="payroll-tab">
 *             ' . $html . '
 *           </div>';
 *         return $payload;
 *     }
 *     $html = $CI->load->view('payroll/user_tab', [], true);
 *     $payload[] = '
 *       <div class="tab-pane fade" id="payroll" role="tabpanel" aria-labelledby="payroll-tab">
 *         ' . $html . '
 *       </div>';
 *     return $payload;
 * });
 */