<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks Module - Uninstall Script
 * ---------------------------------
 * Drops all tables created by the Tasks module.
 */

$CI = &get_instance();
$CI->load->dbforge();

// List of tables (drop order matters because of relationships)
$tables = [
    'task_activity',
    'task_attachments',
    'task_comments',
    'task_checklist_items',
    'task_assigned',
    'task_followers',
    'tasks_checklist_templates',
    'tasks'
];

foreach ($tables as $tbl) {
    if ($CI->db->table_exists($tbl)) {
        $CI->dbforge->drop_table($tbl, true);
    }
}

log_message('info', '[Tasks Module] All tables dropped successfully.');
