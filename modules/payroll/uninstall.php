<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Payroll Module - Uninstall Script (idempotent, prefix-aware)
 * Drops tables in reverse dependency order.
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$tables = [
    // children → parents
    $db->dbprefix('payroll_pf_transactions'),
    $db->dbprefix('payroll_pf_accounts'),
    $db->dbprefix('payroll_loans'),
    $db->dbprefix('payroll_increments'),
    $db->dbprefix('payroll_details'),
    $db->dbprefix('payroll_arrears'),
    $db->dbprefix('payroll_advances'),
];

$db->query('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $t) { $db->query("DROP TABLE IF EXISTS `{$t}`"); }
$db->query('SET FOREIGN_KEY_CHECKS=1');
