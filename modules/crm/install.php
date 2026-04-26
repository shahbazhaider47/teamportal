<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Finance Module – Install Script
 * Bank Accounts (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

// Prefixed table
$bankTbl = $db->dbprefix('finbank_accounts');

/* -----------------------------------------------------------
 * 1) finbank_accounts
 * ----------------------------------------------------------- */
if (!$db->table_exists('finbank_accounts')) {

    $sql = "
    CREATE TABLE `{$bankTbl}` (
      `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

      `account_name`     VARCHAR(100) NOT NULL,
      `bank_name`        VARCHAR(100) NOT NULL,
      `account_number`   VARCHAR(50)  NOT NULL,

      `account_type`     ENUM(
                            'checking',
                            'savings',
                            'current',
                            'business',
                            'credit_card',
                            'digital_wallet',
                            'other'
                          ) NOT NULL DEFAULT 'checking',

      `bank_code`        VARCHAR(50) DEFAULT NULL
                          COMMENT 'SWIFT, Routing, IFSC, Sort Code, etc.',

      `bank_code_type`   ENUM(
                            'swift',
                            'routing',
                            'ifsc',
                            'sort_code',
                            'bsb',
                            'iban',
                            'other'
                          ) DEFAULT NULL,

      `country`          VARCHAR(50)  DEFAULT 'United States',
      `currency`         VARCHAR(10)  DEFAULT 'USD',

      `account_holder`   VARCHAR(150) NOT NULL,

      `holder_type`      ENUM(
                            'individual',
                            'company',
                            'joint'
                          ) NOT NULL DEFAULT 'individual',

      `current_balance`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `opening_balance`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,

      `branch`           VARCHAR(100) DEFAULT NULL,
      `contact_phone`    VARCHAR(30)  DEFAULT NULL,

      `status`           ENUM(
                            'active',
                            'inactive',
                            'closed'
                          ) NOT NULL DEFAULT 'active',

      `is_primary`       TINYINT(1) NOT NULL DEFAULT 0,
      `is_default`       TINYINT(1) NOT NULL DEFAULT 0
                          COMMENT 'Default for payments/receipts',

      `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,

      PRIMARY KEY (`id`),

      KEY `idx_fba_status`        (`status`),
      KEY `idx_fba_account_type`  (`account_type`),
      KEY `idx_fba_is_primary`    (`is_primary`),
      KEY `idx_fba_is_default`    (`is_default`),
      KEY `idx_fba_bank_name`     (`bank_name`)
    )
    ENGINE={$engine}
    DEFAULT CHARSET={$charset}
    COLLATE={$collate}
    COMMENT='Finance bank accounts for payments, receipts, payroll, and reconciliation';
    ";

    $db->query($sql);
}
