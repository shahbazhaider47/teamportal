<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CRM
Description: Centralized customer relationship management including leads, contacts, accounts/companies, deals/pipeline, activities, follow-ups, and CRM reporting. Designed to provide full visibility and control over sales operations.
Version: 1.0.0
Author: RCM Centric
Author URI: https://rcmcentric.com
Requires at least: 3.3.*
Requires Modules:
Settings Icon: ti ti-address-book
Settings Name: CRM
*/

// ─────────────────────────────────────────────────────────────
// 🔁 Define Constants
// ─────────────────────────────────────────────────────────────
define('CRM_MODULE_NAME', 'crm');

define('CRM_MODULE_PATH', module_dir_path(CRM_MODULE_NAME));
define('CRM_MODULE_URL',  module_dir_url(CRM_MODULE_NAME));


// ─────────────────────────────────────────────────────────────
// 📦 Register Lifecycle Hooks
// ─────────────────────────────────────────────────────────────
register_activation_hook(CRM_MODULE_NAME,   'crm_module_activate');
register_deactivation_hook(CRM_MODULE_NAME, 'crm_module_deactivate');
register_uninstall_hook(CRM_MODULE_NAME,    'crm_module_uninstall');

// ─────────────────────────────────────────────────────────────
// 🌐 Register Language Files
// ─────────────────────────────────────────────────────────────
$CI = &get_instance();
$lang = $CI->config->item('language') ?? 'english';

if (file_exists(CRM_MODULE_PATH . 'language/' . $lang . '/crm_lang.php')) {
    $CI->lang->load(CRM_MODULE_NAME . '/crm', $lang);
} else {
    $CI->lang->load(CRM_MODULE_NAME . '/crm', 'english');
}

register_language_files(CRM_MODULE_NAME, ['crm']);

hooks()->add_filter('app_sidebar_menu', 'crm_module_sidebar_menu');
function crm_module_sidebar_menu($menus)
{
    if (!staff_can('manage', 'crm')) {
        return $menus;
    }

    // ─────────────────────────────────────────────────────────────
    // 1) DASHBOARD
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_dashboard',
        'name'       => 'CRM Dashboard',
        'icon'       => 'ti ti-layout-dashboard',
        'href'       => site_url('crm'),
        'position'   => 1,
        'collapse'   => false,
        'menu_group' => 'crm',
    ];

    // ─────────────────────────────────────────────────────────────
    // 2) PEOPLE & ORGANIZATIONS
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_clients',
        'name'       => 'Clients',
        'icon'       => 'ti ti-address-book',
        'href'       => '#',
        'position'   => 10,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_clients_list',
                'name'     => 'All Clients',
                'href'     => site_url('crm/clients'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_groups',
                'name'     => 'Groups',
                'href'     => site_url('crm/groups'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_contracts',
                'name'     => 'Contracts',
                'href'     => site_url('crm/contracts'),
                'position' => 3,
            ],
            
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 3) PIPELINE & REVENUE
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_sales',
        'name'       => 'Leads & Pipeline',
        'icon'       => 'ti ti-git-merge',
        'href'       => '#',
        'position'   => 20,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_leads',
                'name'     => 'All Leads',
                'href'     => site_url('crm/leads'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_forecast',
                'name'     => 'Forecast',
                'href'     => site_url('crm/leads/forecast'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_quotes',
                'name'     => 'Proposals',
                'href'     => site_url('crm/proposals'),
                'position' => 4,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 4) MARKETING & CAMPAIGNS
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_marketing',
        'name'       => 'Marketing',
        'icon'       => 'ti ti-speakerphone',
        'href'       => '#',
        'position'   => 30,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_campaigns',
                'name'     => 'Campaigns',
                'href'     => site_url('crm/campaigns'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_email_sequences',
                'name'     => 'Email Sequences',
                'href'     => site_url('crm/email-sequences'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_lead_sources',
                'name'     => 'Lead Sources',
                'href'     => site_url('crm/lead-sources'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_web_forms',
                'name'     => 'Web Forms',
                'href'     => site_url('crm/web-forms'),
                'position' => 4,
            ],
            [
                'slug'     => 'crm_segments',
                'name'     => 'Segments',
                'href'     => site_url('crm/segments'),
                'position' => 5,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 5) ACTIVITY & PRODUCTIVITY
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_activity',
        'name'       => 'Activity',
        'icon'       => 'ti ti-activity',
        'href'       => '#',
        'position'   => 40,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_activities',
                'name'     => 'Activities',
                'href'     => site_url('crm/activities'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_tasks',
                'name'     => 'Tasks / Follow-ups',
                'href'     => site_url('crm/tasks'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_calendar',
                'name'     => 'Calendar',
                'href'     => site_url('crm/calendar'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_calls',
                'name'     => 'Calls Log',
                'href'     => site_url('crm/calls'),
                'position' => 4,
            ],
            [
                'slug'     => 'crm_meetings',
                'name'     => 'Meetings',
                'href'     => site_url('crm/meetings'),
                'position' => 5,
            ],
            [
                'slug'     => 'crm_notes',
                'name'     => 'Notes',
                'href'     => site_url('crm/notes'),
                'position' => 6,
            ],
            [
                'slug'     => 'crm_emails',
                'name'     => 'Emails',
                'href'     => site_url('crm/emails'),
                'position' => 7,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 6) SUPPORT & SERVICE
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_support',
        'name'       => 'Support',
        'icon'       => 'ti ti-headset',
        'href'       => '#',
        'position'   => 50,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_tickets',
                'name'     => 'Tickets',
                'href'     => site_url('crm/tickets'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_complaints',
                'name'     => 'Complaints',
                'href'     => site_url('crm/complaints'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_knowledge_base',
                'name'     => 'Knowledge Base',
                'href'     => site_url('crm/knowledge-base'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_sla',
                'name'     => 'SLA Policies',
                'href'     => site_url('crm/sla'),
                'position' => 4,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 7) DATA & TOOLS
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_tools',
        'name'       => 'Tools',
        'icon'       => 'ti ti-tool',
        'href'       => '#',
        'position'   => 60,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_import',
                'name'     => 'Import',
                'href'     => site_url('crm/import'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_export',
                'name'     => 'Export',
                'href'     => site_url('crm/export'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_dedupe',
                'name'     => 'Duplicates',
                'href'     => site_url('crm/duplicates'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_tags',
                'name'     => 'Tags',
                'href'     => site_url('crm/tags'),
                'position' => 4,
            ],
            [
                'slug'     => 'crm_custom_fields',
                'name'     => 'Custom Fields',
                'href'     => site_url('crm/custom-fields'),
                'position' => 5,
            ],
            [
                'slug'     => 'crm_automations',
                'name'     => 'Automations',
                'href'     => site_url('crm/automations'),
                'position' => 6,
            ],
            [
                'slug'     => 'crm_webhooks',
                'name'     => 'Webhooks',
                'href'     => site_url('crm/webhooks'),
                'position' => 7,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 8) REPORTS & ANALYTICS
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_reports',
        'name'       => 'Reports & Analytics',
        'icon'       => 'ti ti-report-analytics',
        'href'       => '#',
        'position'   => 80,
        'collapse'   => true,
        'menu_group' => 'crm',
        'children'   => [
            [
                'slug'     => 'crm_reports_overview',
                'name'     => 'Overview',
                'href'     => site_url('crm/reports'),
                'position' => 1,
            ],
            [
                'slug'     => 'crm_reports_leads',
                'name'     => 'Lead Reports',
                'href'     => site_url('crm/reports/rleads'),
                'position' => 2,
            ],
            [
                'slug'     => 'crm_reports_sales',
                'name'     => 'Sales Reports',
                'href'     => site_url('crm/reports/sales'),
                'position' => 3,
            ],
            [
                'slug'     => 'crm_reports_activity',
                'name'     => 'Activity Reports',
                'href'     => site_url('crm/reports/activity'),
                'position' => 4,
            ],
            [
                'slug'     => 'crm_reports_conversion',
                'name'     => 'Conversion Reports',
                'href'     => site_url('crm/reports/conversion'),
                'position' => 5,
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // 9) CRM SETTINGS
    // ─────────────────────────────────────────────────────────────
    $menus[] = [
        'slug'       => 'crm_settings',
        'name'       => 'CRM Settings',
        'icon'       => 'ti ti-settings',
        'href'       => site_url('crm/settings'),
        'position'   => 95,
        'collapse'   => false,
        'menu_group' => 'crm',
    ];

    return $menus;
}

// ─────────────────────────────────────────────────────────────
// 🔗 Module Action Link (Modules Page)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('module_' . CRM_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . base_url('crm/settings') . '" target="_blank">Settings</a>';
    return $actions;
});

// ─────────────────────────────────────────────────────────────
// ✅ Activation / Deactivation / Uninstall
// ─────────────────────────────────────────────────────────────
function crm_module_activate()
{
    $CI = &get_instance();

    try {
        include_once(CRM_MODULE_PATH . 'install.php');
    } catch (Exception $e) {
        throw $e;
    }
}

function crm_module_deactivate()
{
    log_message('debug', '⚙️ CRM module deactivated.');
}

function crm_module_uninstall()
{
    $CI = &get_instance();

    try {
        include_once(CRM_MODULE_PATH . 'uninstall.php');
    } catch (Exception $e) {
        log_message('error', '❌ CRM uninstall failed: ' . $e->getMessage());
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────
// 🔐 CRM Permissions (Advanced & Scalable)
// ─────────────────────────────────────────────────────────────
hooks()->add_filter('user_permissions', 'crm_permissions');

function crm_permissions(array $permissions): array
{
    $permissions['crm'] = [
        'name' => 'CRM',

        'actions' => [
            'view_global'        => 'View All CRM Data',
            'view_own'           => 'View Own CRM Records',

            // Clients
            'client_create'        => 'Create Clients',
            'client_edit'          => 'Edit Clients',
            'client_delete'        => 'Delete Clients',
            'client_view'          => 'View Clients',
            'client_view_global'   => 'View All Clients',
            'client_view_own'      => 'View Assigned Clients',
            'client_import'        => 'Import Clients',
            'client_export'        => 'Export Clients',
            'client_assign'        => 'Assign Clients to Staff',
            'client_merge'         => 'Merge Duplicate Clients',
            'client_archive'       => 'Archive / Deactivate Clients',
            'client_restore'       => 'Restore Archived Clients',
            'client_notes_manage'  => 'Manage Client Notes',
            'client_files_manage'  => 'Manage Client Attachments',
            'client_activity_view' => 'View Client Activity Timeline',

            // Leads
            'lead_create'        => 'Create Leads',
            'lead_edit'          => 'Edit Leads',
            'lead_delete'        => 'Delete Leads',
            'lead_import'        => 'Import Leads',
            'lead_assign'        => 'Assign Leads',
            'lead_convert'       => 'Convert Lead to Contact/Account/Deal',

            // Contacts
            'contact_create'     => 'Create Contacts',
            'contact_edit'       => 'Edit Contacts',
            'contact_delete'     => 'Delete Contacts',
            'contact_export'     => 'Export Contacts',

            // Accounts / Companies
            'account_create'     => 'Create Accounts',
            'account_edit'       => 'Edit Accounts',
            'account_delete'     => 'Delete Accounts',

            // Deals / Pipeline
            'deal_create'        => 'Create Deals',
            'deal_edit'          => 'Edit Deals',
            'deal_delete'        => 'Delete Deals',
            'deal_move_stage'    => 'Move Deals Across Stages',
            'deal_close_won'     => 'Close Deal as Won',
            'deal_close_lost'    => 'Close Deal as Lost',

            // Activities / Tasks
            'activity_create'    => 'Log Activities (calls/emails/meetings)',
            'activity_edit'      => 'Edit Activities',
            'activity_delete'    => 'Delete Activities',
            'task_create'        => 'Create Tasks / Follow-ups',
            'task_edit'          => 'Edit Tasks',
            'task_delete'        => 'Delete Tasks',
            'task_assign'        => 'Assign Tasks',

            // Reporting
            'report_view'        => 'View CRM Reports',
            'report_export'      => 'Export CRM Reports',

            // Settings / Admin
            'settings_manage'    => 'Manage CRM Settings',
            'stages_manage'      => 'Manage Pipeline Stages',
            'sources_manage'     => 'Manage Lead Sources',
            'tags_manage'        => 'Manage Tags',
            'custom_fields'      => 'Manage Custom Fields',
            'audit_view'         => 'View Audit Logs',
        ],
    ];

    return $permissions;
}