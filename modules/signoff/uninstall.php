<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Signoff Module - Uninstall Script (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$tables = [
    // children → parents
    $db->dbprefix('signoff_submissions'),
    $db->dbprefix('signoff_targets'),
    $db->dbprefix('signoff_points'),
    $db->dbprefix('signoff_forms'),
];

$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $t) { $db->query("DROP TABLE IF EXISTS `{$t}`"); }
$db->query('SET FOREIGN_KEY_CHECKS=1');
