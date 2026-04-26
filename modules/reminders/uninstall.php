<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reminders Module - Uninstall Script (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$tables = [
    $db->dbprefix('reminder_alerts'),
    $db->dbprefix('reminders'),
];

$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $t) { $db->query("DROP TABLE IF EXISTS `{$t}`"); }
$db->query('SET FOREIGN_KEY_CHECKS=1');
