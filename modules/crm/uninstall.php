<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Finance Module – Uninstall Script
 * Drops finance-related tables (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

/*
 |------------------------------------------------------------
 | Tables to drop
 | Order: children → parents (future-safe)
 |------------------------------------------------------------
 */
$tables = [
    $db->dbprefix('finbank_accounts'),
];

/*
 |------------------------------------------------------------
 | Disable FK checks, drop tables safely
 |------------------------------------------------------------
 */
$db->query('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
    $db->query("DROP TABLE IF EXISTS `{$table}`");
}

$db->query('SET FOREIGN_KEY_CHECKS=1');
