<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance Module - Uninstall Script (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$tables = [
    // biometric child tables first
    $db->dbprefix('biometric_user_map'),
    $db->dbprefix('biometric_raw_logs'),
    $db->dbprefix('biometric_import_jobs'),
    $db->dbprefix('biometric_devices'),
    // attendance core
    $db->dbprefix('user_leaves'),
    $db->dbprefix('attendance'),
];

$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $t) { $db->query("DROP TABLE IF EXISTS `{$t}`"); }
$db->query('SET FOREIGN_KEY_CHECKS=1');
