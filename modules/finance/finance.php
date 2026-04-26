<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Finance
Description: Centralized financial management including invoices, payments, expenses, bank accounts, reconciliation, and financial reporting. Designed to provide full visibility and control over organizational finances.
Version: 1.0.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.3.*
Requires Modules:
Settings Icon: ti ti-currency-dollar
Settings Name: Finance
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('FINANCE_MODULE_NAME', 'finance');

define('FINANCE_MODULE_PATH', module_dir_path(FINANCE_MODULE_NAME));
define('FINANCE_MODULE_URL',  module_dir_url(FINANCE_MODULE_NAME));


// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(FINANCE_MODULE_NAME,   'finance_module_activate');
register_deactivation_hook(FINANCE_MODULE_NAME, 'finance_module_deactivate');
register_uninstall_hook(FINANCE_MODULE_NAME,    'finance_module_uninstall');


// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';

if (file_exists(FINANCE_MODULE_PATH . 'language/' . $lang . '/finance_lang.php')) {
    $CI->lang->load(FINANCE_MODULE_NAME . '/finance', $lang);
} else {
    $CI->lang->load(FINANCE_MODULE_NAME . '/finance', 'english');
}

register_language_files(FINANCE_MODULE_NAME, ['finance']);


// ─────────────────────────────────────────────────────────────
// 🧭 Sidebar Menu Items (Finance)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('app_sidebar_menu', 'finance_module_sidebar_menu');
function finance_module_sidebar_menu($menus)
{
    if (!staff_can('manage', 'finance')) {
        return $menus;
    }

    // ─────────────────────────────────────────────────────────
    // 1) DASHBOARD
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_dashboard',
        'name'       => 'Finance Dashboard',
        'icon'       => 'ti ti-layout-dashboard',
        'href'       => site_url('finance'),
        'position'   => 1,
        'collapse'   => false,
        'menu_group' => 'finance',
    ];

    // ─────────────────────────────────────────────────────────
    // 2) INVOICING
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_invoicing',
        'name'       => 'Invoicing',
        'icon'       => 'ti ti-file-invoice',
        'href'       => '#',
        'position'   => 10,
        'collapse'   => true,
        'menu_group' => 'finance',
        'children'   => [
            [
                'slug'     => 'finance_invoices',
                'name'     => 'Invoices',
                'href'     => site_url('finance/invoices'),
                'position' => 1,
            ],
            [
                'slug'     => 'finance_credit_notes',
                'name'     => 'Credit Notes',
                'href'     => site_url('finance/credit-notes'),
                'position' => 2,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    // 3) PAYMENTS
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_payments_group',
        'name'       => 'Payments',
        'icon'       => 'ti ti-credit-card',
        'href'       => '#',
        'position'   => 20,
        'collapse'   => true,
        'menu_group' => 'finance',
        'children'   => [
            [
                'slug'     => 'finance_payments',
                'name'     => 'All Payments',
                'href'     => site_url('finance/payments'),
                'position' => 1,
            ],
            [
                'slug'     => 'finance_payment_allocations',
                'name'     => 'Allocations',
                'href'     => site_url('finance/payments/allocations'),
                'position' => 2,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    // 4) EXPENSES
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_expenses_group',
        'name'       => 'Expenses',
        'icon'       => 'ti ti-receipt',
        'href'       => '#',
        'position'   => 30,
        'collapse'   => true,
        'menu_group' => 'finance',
        'children'   => [
            [
                'slug'     => 'finance_expenses',
                'name'     => 'All Expenses',
                'href'     => site_url('finance/expenses'),
                'position' => 1,
            ],
            [
                'slug'     => 'finance_expense_categories',
                'name'     => 'Categories',
                'href'     => site_url('finance/expenses/categories'),
                'position' => 2,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    // 5) BANKING
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_banking',
        'name'       => 'Banking',
        'icon'       => 'ti ti-building-bank',
        'href'       => '#',
        'position'   => 40,
        'collapse'   => true,
        'menu_group' => 'finance',
        'children'   => [
            [
                'slug'     => 'finance_bank_accounts',
                'name'     => 'Bank Accounts',
                'href'     => site_url('finance/bank-accounts'),
                'position' => 1,
            ],
            [
                'slug'     => 'finance_bank_transactions',
                'name'     => 'Bank Transactions',
                'href'     => site_url('finance/bank-transactions'),
                'position' => 2,
            ],
            [
                'slug'     => 'finance_reconciliations',
                'name'     => 'Reconciliation',
                'href'     => site_url('finance/reconciliations'),
                'position' => 3,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    // 6) PETTY CASH
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_pettycash',
        'name'       => 'Petty Cash',
        'icon'       => 'ti ti-cash',
        'href'       => site_url('finance/pettycash'),
        'position'   => 50,
        'collapse'   => false,
        'menu_group' => 'finance',
    ];

    // ─────────────────────────────────────────────────────────
    // 7) REPORTS & ANALYTICS
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_reports',
        'name'       => 'Reports & Analytics',
        'icon'       => 'ti ti-report-analytics',
        'href'       => '#',
        'position'   => 80,
        'collapse'   => true,
        'menu_group' => 'finance',
        'children'   => [
            [
                'slug'     => 'finance_reports_overview',
                'name'     => 'Overview',
                'href'     => site_url('finance/reports'),
                'position' => 1,
            ],
            [
                'slug'     => 'finance_reports_income',
                'name'     => 'Income Report',
                'href'     => site_url('finance/reports/income'),
                'position' => 2,
            ],
            [
                'slug'     => 'finance_reports_expenses',
                'name'     => 'Expense Report',
                'href'     => site_url('finance/reports/expenses'),
                'position' => 3,
            ],
            [
                'slug'     => 'finance_reports_pl',
                'name'     => 'Profit & Loss',
                'href'     => site_url('finance/reports/profit-loss'),
                'position' => 4,
            ],
            [
                'slug'     => 'finance_reports_aging',
                'name'     => 'Aging Report',
                'href'     => site_url('finance/reports/aging'),
                'position' => 5,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    // 8) FINANCE SETTINGS
    // ─────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'finance_settings',
        'name'       => 'Finance Settings',
        'icon'       => 'ti ti-settings',
        'href'       => site_url('finance/settings'),
        'position'   => 95,
        'collapse'   => false,
        'menu_group' => 'finance',
    ];

    return $menus;
}


// ─────────────────────────────────────────────────────────────
// 🔗 Module Action Link (Modules Page)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . FINANCE_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('finance/settings') . '" target="_blank">Settings</a>';
    return $actions;
});


// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall
// ─────────────────────────────────────────────────────────────
function finance_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(FINANCE_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function finance_module_deactivate()
{
    log_message('debug', '⚙️ Finance module deactivated.');
}

function finance_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(FINANCE_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        log_message('error', '❌ Finance uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}


// ─────────────────────────────────────────────────────────────
// 🔐 Finance Permissions (Advanced & Scalable)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'finance_permissions');

function finance_permissions(array $permissions): array
{
    $permissions['finance'] = [
        'name' => 'Finance',

        'actions' => [
            // Global access
            'view'              => 'View Finance Module',
            'view_global'       => 'View All Financial Data',
            'view_own'          => 'View Own Financial Records',

            // Invoices
            'invoice_create'    => 'Create Invoices',
            'invoice_edit'      => 'Edit Invoices',
            'invoice_delete'    => 'Delete Invoices',
            'invoice_send'      => 'Send Invoices',
            'invoice_void'      => 'Void / Cancel Invoices',

            // Credit Notes
            'credit_note_create' => 'Create Credit Notes',
            'credit_note_edit'   => 'Edit Credit Notes',
            'credit_note_delete' => 'Delete Credit Notes',
            'credit_note_void'   => 'Void Credit Notes',

            // Payments
            'payment_create'    => 'Record Payments',
            'payment_edit'      => 'Edit Payments',
            'payment_delete'    => 'Delete Payments',
            'payment_refund'    => 'Process Refunds',
            'payment_allocate'  => 'Allocate Payments to Invoices',

            // Expenses
            'expense_create'    => 'Create Expenses',
            'expense_edit'      => 'Edit Expenses',
            'expense_delete'    => 'Delete Expenses',
            'expense_approve'   => 'Approve Expenses',

            // Banking
            'bank_view'         => 'View Bank Accounts',
            'bank_manage'       => 'Manage Bank Accounts',
            'bank_reconcile'    => 'Reconcile Bank Statements',
            'bank_import'       => 'Import Bank Transactions',

            // Approvals & Period Control
            'approve'           => 'Approve Financial Transactions',
            'lock_period'       => 'Lock / Close Accounting Period',

            // Reports
            'report_view'       => 'View Financial Reports',
            'report_export'     => 'Export Financial Reports',

            // Settings & Administration
            'settings_manage'   => 'Manage Finance Settings',
            'coa_manage'        => 'Manage Chart of Accounts',
            'audit_view'        => 'View Audit Logs',
        ],
    ];

    return $permissions;
}