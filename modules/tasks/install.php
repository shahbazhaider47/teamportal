<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tasks Module - Install Script (final)
 * -------------------------------------
 * Creates all database tables required for the Tasks module.
 * Assignee is stored on tasks table (assignee_id).
 * Followers are stored on tasks table (followers_json - JSON string).
 *
 * Removed legacy tables:
 *  - task_assigned (replaced by tasks.assignee_id)
 *  - task_followers (replaced by tasks.followers_json)
 *  - tasks_checklist_templates (not used)
 */

$CI = &get_instance();
$CI->load->dbforge();

/**
 * Helper: create table if not exists
 */
if (!function_exists('create_table_if_not_exists')) {
    function create_table_if_not_exists($table, $fields, $primary_key = 'id', $extra_keys = [])
    {
        $CI = &get_instance();
        if (!$CI->db->table_exists($table)) {
            $CI->dbforge->add_field($fields);
            if ($primary_key) {
                $CI->dbforge->add_key($primary_key, true);
            }
            foreach ($extra_keys as $key) {
                $CI->dbforge->add_key($key);
            }
            $CI->dbforge->create_table($table, true);
        }
    }
}

/* ======================================================================
   1) tasks
   - Single assignee on the record (assignee_id)
   - Followers as JSON TEXT (followers_json) e.g., "[12,34,56]"
   - Priority uses: low|normal|high|urgent
   ====================================================================== */
create_table_if_not_exists('tasks', [
    'id'                  => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
    'name'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'description'         => ['type' => 'MEDIUMTEXT', 'null' => true],

    // Align with settings: low|normal|high|urgent
    'priority'            => ['type' => "ENUM('low','normal','high','urgent')", 'default' => 'normal'],

    // Keep a broad status set; map in app layer as needed
    'status'              => ['type' => "ENUM('not_started','in_progress','review','completed','on_hold','cancelled')", 'default' => 'not_started'],

    'dateadded'           => ['type' => 'DATETIME', 'null' => false],
    'startdate'           => ['type' => 'DATE', 'null' => true],
    'duedate'             => ['type' => 'DATE', 'null' => true],
    'datefinished'        => ['type' => 'DATETIME', 'null' => true],

    // Creator & updater
    'addedfrom'           => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'updated_by'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],

    // Inline assignment & followers
    'assignee_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],   // single assignee
    'followers_json'      => ['type' => 'TEXT', 'null' => true],                       // JSON array of user IDs

    // Recurrence (kept; toggle via settings)
    'recurring'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
    'recurring_type'      => ['type' => "ENUM('day','week','month','year')", 'null' => true],
    'repeat_every'        => ['type' => 'INT', 'constraint' => 11, 'null' => true],
    'is_recurring_from'   => ['type' => 'INT', 'constraint' => 11, 'null' => true],
    'cycles'              => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
    'total_cycles'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
    'custom_recurring'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
    'last_recurring_date' => ['type' => 'DATE', 'null' => true],

    // Relations (project, customer, etc.)
    'rel_id'              => ['type' => 'INT', 'constraint' => 11, 'null' => true],
    'rel_type'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

    // Milestones / Kanban ordering
    'milestone'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
    'kanban_order'        => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
    'milestone_order'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],

    // Visibility & notifications
    'visible_to_team'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
    'deadline_notified'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
], 'id', [
    // Secondary keys for performance
    'status',
    'priority',
    'assignee_id',
    'duedate',
    'rel_id',
    'rel_type',
    'addedfrom'
]);

/* ======================================================================
   2) task_comments
   ====================================================================== */
create_table_if_not_exists('task_comments', [
    'id'        => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
    'taskid'    => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'user_id'   => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'comment'   => ['type' => 'MEDIUMTEXT', 'null' => false],
    'dateadded' => ['type' => 'DATETIME', 'null' => false],
], 'id', ['taskid', 'user_id']);

/* ======================================================================
   3) task_checklist_items
   ====================================================================== */
create_table_if_not_exists('task_checklist_items', [
    'id'            => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
    'taskid'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'description'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'finished'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
    'dateadded'     => ['type' => 'DATETIME', 'null' => false],
    'addedfrom'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'finished_from' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
    'list_order'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
    'assigned'      => ['type' => 'INT', 'constraint' => 11, 'null' => true],
], 'id', ['taskid', 'finished', 'list_order']);

/* ======================================================================
   4) task_attachments
   ====================================================================== */
create_table_if_not_exists('task_attachments', [
    'id'           => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
    'taskid'       => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'file_name'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'file_path'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'uploaded_by'  => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'uploaded_at'  => ['type' => 'DATETIME', 'null' => false],
], 'id', ['taskid', 'uploaded_by']);

/* ======================================================================
   5) task_activity
   ====================================================================== */
create_table_if_not_exists('task_activity', [
    'id'          => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
    'taskid'      => ['type' => 'INT', 'constraint' => 11, 'null' => false],
    'user_id'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
    'activity'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
    'description' => ['type' => 'TEXT', 'null' => true],
    'dateadded'   => ['type' => 'DATETIME', 'null' => false],
], 'id', ['taskid', 'user_id']);

log_message('info', '[Tasks Module] Tables created successfully (assignee/followers inlined).');
