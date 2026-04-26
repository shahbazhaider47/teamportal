<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Team Chat — uninstall.php
 * Runs once on module uninstall.
 * Drops all chat tables in reverse dependency order.
 * WARNING: This is permanent and removes all chat history.
 */

$CI    = &get_instance();
$db    = $CI->db;
$forge = $CI->load->dbforge(null, true);

// Disable foreign key checks so drops succeed regardless of FK order
$db->query('SET FOREIGN_KEY_CHECKS = 0');

// ─────────────────────────────────────────────────────────────
// Drop tables — child tables first, parent tables last
// ─────────────────────────────────────────────────────────────
$tables = [
    'chat_pins',           // depends on chat_messages, chat_conversations
    'chat_reactions',      // depends on chat_messages
    'chat_attachments',    // depends on chat_messages, chat_conversations
    'chat_members',        // depends on chat_conversations
    'chat_messages',       // depends on chat_conversations
    'chat_conversations',  // root table — drop last
];

foreach ($tables as $table) {
    if ($db->table_exists($table)) {
        $forge->drop_table($table, true);
        log_message('debug', '🗑️ Dropped table: ' . $table);
    } else {
        log_message('debug', '⚠️ Table not found (skipped): ' . $table);
    }
}

// Re-enable foreign key checks
$db->query('SET FOREIGN_KEY_CHECKS = 1');

log_message('debug', '✅ Team Chat module uninstalled successfully.');