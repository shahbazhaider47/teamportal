<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team Chat — install.php
 * Runs once on module activation.
 * Creates all required chat tables if they do not already exist.
 */

$CI = &get_instance();
$db = $CI->db;
$forge = $CI->load->dbforge(null, true);

// ─────────────────────────────────────────────────────────────
// TABLE 1: chat_conversations
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_conversations')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'type' => [
            'type'       => 'ENUM',
            'constraint' => ['direct', 'group', 'channel'],
            'default'    => 'direct',
        ],
        'name' => [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ],
        'slug' => [
            'type'       => 'VARCHAR',
            'constraint' => 160,
            'null'       => true,
        ],
        'description' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'team_id' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'department_id' => [
            'type'       => 'INT',
            'constraint' => 11,
            'null'       => true,
        ],
        'avatar' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
        ],
        'created_by' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'is_archived' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'is_read_only' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'last_message_id' => [
            'type'       => 'BIGINT',
            'constraint' => 20,
            'unsigned'   => true,
            'null'       => true,
        ],
        'last_activity_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'created_at' => [
            'type'    => 'DATETIME',
            'null'    => false,
            'default' => '1000-01-01 00:00:00',
        ],
        'updated_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key('type');
    $forge->add_key('team_id');
    $forge->add_key('department_id');
    $forge->add_key('last_activity_at');
    $forge->add_key('is_archived');
    $forge->create_table('chat_conversations', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    log_message('debug', '✅ chat_conversations table created.');
}

// ─────────────────────────────────────────────────────────────
// TABLE 2: chat_members
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_members')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'conversation_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'user_id' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
        ],
        'role' => [
            'type'       => 'ENUM',
            'constraint' => ['owner', 'admin', 'member'],
            'default'    => 'member',
        ],
        'is_muted' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'notify_on_mention' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 1,
        ],
        'last_read_message_id' => [
            'type'       => 'BIGINT',
            'constraint' => 20,
            'unsigned'   => true,
            'null'       => true,
            'default'    => null,
        ],
        'last_read_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'added_by' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'joined_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'left_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key(['conversation_id', 'user_id']); // composite unique
    $forge->add_key('user_id');
    $forge->add_key('conversation_id');
    $forge->create_table('chat_members', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    // Add unique constraint manually
    $db->query('ALTER TABLE `chat_members` ADD UNIQUE KEY `uq_member` (`conversation_id`, `user_id`)');

    log_message('debug', '✅ chat_members table created.');
}

// ─────────────────────────────────────────────────────────────
// TABLE 3: chat_messages
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_messages')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'conversation_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'sender_id' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
        ],
        'parent_id' => [
            'type'       => 'BIGINT',
            'constraint' => 20,
            'unsigned'   => true,
            'null'       => true,
            'default'    => null,
        ],
        'thread_reply_count' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'default'    => 0,
        ],
        'type' => [
            'type'       => 'ENUM',
            'constraint' => ['text', 'file', 'image', 'system', 'poll'],
            'default'    => 'text',
        ],
        'body' => [
            'type' => 'MEDIUMTEXT',
            'null' => true,
        ],
        'metadata' => [
            'type' => 'JSON',
            'null' => true,
        ],
        'is_edited' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'edited_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'is_deleted' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'deleted_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
        'deleted_by' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'created_at' => [
            'type'    => 'DATETIME',
            'null'    => false,
            'default' => '1000-01-01 00:00:00',
        ],
        'updated_at' => [
            'type' => 'DATETIME',
            'null' => true,
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key(['conversation_id', 'created_at']);
    $forge->add_key('sender_id');
    $forge->add_key('parent_id');
    $forge->add_key('is_deleted');
    $forge->create_table('chat_messages', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    // Full-text search on message body
    $db->query('ALTER TABLE `chat_messages` ADD FULLTEXT KEY `ft_body` (`body`)');

    log_message('debug', '✅ chat_messages table created.');
}

// ─────────────────────────────────────────────────────────────
// TABLE 4: chat_attachments
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_attachments')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'message_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
            'null'     => true,
        ],
        'conversation_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'uploader_id' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'original_name' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
        ],
        'stored_name' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
        ],
        'file_path' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
        ],
        'mime_type' => [
            'type'       => 'VARCHAR',
            'constraint' => 100,
            'null'       => true,
        ],
        'file_size' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
            'default'  => 0,
        ],
        'thumbnail_path' => [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
        ],
        'is_deleted' => [
            'type'       => 'TINYINT',
            'constraint' => 1,
            'default'    => 0,
        ],
        'created_at' => [
            'type'    => 'DATETIME',
            'null'    => false,
            'default' => '1000-01-01 00:00:00',
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key('message_id');
    $forge->add_key('conversation_id');
    $forge->add_key('uploader_id');
    $forge->create_table('chat_attachments', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    log_message('debug', '✅ chat_attachments table created.');
} else {
    $field = $db->field_data('chat_attachments');
    foreach ($field as $column) {
        if ($column->name === 'message_id' && empty($column->nullable)) {
            $db->query('ALTER TABLE `chat_attachments` MODIFY `message_id` BIGINT(20) UNSIGNED NULL');
            break;
        }
    }
}

// ─────────────────────────────────────────────────────────────
// TABLE 5: chat_reactions
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_reactions')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'message_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'user_id' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
        ],
        'emoji' => [
            'type'       => 'VARCHAR',
            'constraint' => 10,
        ],
        'created_at' => [
            'type'    => 'DATETIME',
            'null'    => false,
            'default' => '1000-01-01 00:00:00',
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key('message_id');
    $forge->add_key('user_id');
    $forge->create_table('chat_reactions', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    $db->query('ALTER TABLE `chat_reactions` ADD UNIQUE KEY `uq_reaction` (`message_id`, `user_id`, `emoji`)');

    log_message('debug', '✅ chat_reactions table created.');
}

// ─────────────────────────────────────────────────────────────
// TABLE 6: chat_pins
// ─────────────────────────────────────────────────────────────
if (!$db->table_exists('chat_pins')) {
    $forge->add_field([
        'id' => [
            'type'           => 'BIGINT',
            'constraint'     => 20,
            'unsigned'       => true,
            'auto_increment' => true,
        ],
        'conversation_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'message_id' => [
            'type'     => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
        ],
        'pinned_by' => [
            'type'       => 'INT',
            'constraint' => 10,
            'unsigned'   => true,
            'null'       => true,
        ],
        'pinned_at' => [
            'type'    => 'DATETIME',
            'null'    => false,
            'default' => '1000-01-01 00:00:00',
        ],
    ]);

    $forge->add_key('id', true);
    $forge->add_key('conversation_id');
    $forge->add_key('message_id');
    $forge->create_table('chat_pins', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

    log_message('debug', '✅ chat_pins table created.');
}

log_message('debug', '✅ Team Chat module installation complete.');