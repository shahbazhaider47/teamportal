<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Subscriptions Module - Install Script (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

// Respect dbprefix for all table/constraint names
$subsTbl   = $db->dbprefix('subscriptions');
$paysTbl   = $db->dbprefix('subscription_payments');
$catsTbl   = $db->dbprefix('subscription_categories');
$usersTbl  = $db->dbprefix('users');
$pmTbl     = $db->dbprefix('payment_methods');

// ---------------------------
// 1) subscriptions
// ---------------------------
if (!$db->table_exists('subscriptions')) {
    $sql = "
    CREATE TABLE `{$subsTbl}` (
      `id`                    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `title`                 VARCHAR(255) NOT NULL,
      `category_id`           INT(10) UNSIGNED DEFAULT NULL,
      `vendor`                VARCHAR(191) DEFAULT NULL,
      `vendor_url`            VARCHAR(255) DEFAULT NULL,
      `account_email`         VARCHAR(191) DEFAULT NULL,
      `account_phone`         VARCHAR(50) DEFAULT NULL,
      `account_password`      VARCHAR(255) DEFAULT NULL COMMENT 'Store HASH only',
      `account_password_enc`  TEXT DEFAULT NULL,
      `tfa_status`            TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=disabled, 1=enabled',
      `tfa_source`            VARCHAR(50) DEFAULT NULL COMMENT 'authenticator|sms|email|other',
      `subscription_type`     ENUM('recurring','one-time','lifetime') DEFAULT 'recurring',
      `payment_cycle`         VARCHAR(50) DEFAULT NULL COMMENT 'monthly|quarterly|annually|custom',
      `cycle_days`            INT(10) UNSIGNED DEFAULT NULL COMMENT 'Used when payment_cycle=custom',
      `start_date`            DATE DEFAULT NULL,
      `next_renewal_date`     DATE DEFAULT NULL,
      `end_date`              DATE DEFAULT NULL,
      `reminder_days_before`  INT(11) DEFAULT 7 COMMENT 'Alert lead-time',
      `grace_days`            INT(11) DEFAULT 0 COMMENT 'Renewal grace period',
      `auto_renew`            TINYINT(1) DEFAULT 0,
      `amount`                DECIMAL(15,2) DEFAULT 0.00,
      `currency`              VARCHAR(10) DEFAULT 'USD',
      `seats`                 INT(10) UNSIGNED DEFAULT NULL COMMENT 'For SaaS licensing',
      `license_key`           VARCHAR(191) DEFAULT NULL COMMENT 'For software/licenses',
      `payment_method_id`     INT(10) UNSIGNED DEFAULT NULL,
      `assigned_to`           INT(10) UNSIGNED DEFAULT NULL COMMENT 'Owner/user responsible',
      `status`                ENUM('active','expired','cancelled','trial') DEFAULT 'active',
      `notes`                 TEXT DEFAULT NULL,
      `meta`                  TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
      `last_payment_date`     DATE DEFAULT NULL,
      `created_by`            INT(10) UNSIGNED DEFAULT NULL,
      `created_at`            DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at`            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `backup_codes`          LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array or newline-separated',
      PRIMARY KEY (`id`),
      KEY `idx_subs_category`      (`category_id`),
      KEY `idx_subs_vendor`        (`vendor`),
      KEY `idx_subs_next_renewal`  (`next_renewal_date`),
      KEY `idx_subs_status`        (`status`),
      KEY `idx_subs_assigned_to`   (`assigned_to`),
      KEY `idx_subs_payment_method`(`payment_method_id`),
      KEY `idx_subs_created_by`    (`created_by`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

// ---------------------------
// 2) subscription_payments
// ---------------------------
if (!$db->table_exists('subscription_payments')) {
    $sql = "
    CREATE TABLE `{$paysTbl}` (
      `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `subscription_id`  INT(10) UNSIGNED NOT NULL,
      `payment_date`     DATE DEFAULT NULL,
      `amount`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `currency`         VARCHAR(10) NOT NULL DEFAULT 'USD',
      `method`           VARCHAR(100) DEFAULT NULL,
      `transaction_id`   VARCHAR(191) DEFAULT NULL,
      `receipt_file`     VARCHAR(255) DEFAULT NULL,
      `notes`            TEXT DEFAULT NULL,
      `created_by`       INT(10) UNSIGNED DEFAULT NULL,
      `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_subpay_subscription` (`subscription_id`),
      KEY `idx_subpay_date`         (`subscription_id`,`payment_date`),
      KEY `idx_subpay_txn`          (`transaction_id`),
      KEY `idx_subpay_created_by`   (`created_by`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

// ---------------------------
// 3) subscription_categories
// ---------------------------
if (!$db->table_exists('subscription_categories')) {
    $sql = "
    CREATE TABLE `{$catsTbl}` (
      `id`    INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name`  VARCHAR(100) NOT NULL,
      `color` VARCHAR(20) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_category_name` (`name`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);

    // Seed baseline categories
    $db->insert_batch('subscription_categories', [
        ['name' => 'Software', 'color' => '0d6efd'],
        ['name' => 'Hosting',  'color' => '198754'],
        ['name' => 'Utilities','color' => '6c757d'],
    ]);
}

// ---------------------------
// 4) Add Foreign Keys if dependencies exist
//    (Do this AFTER table creation to avoid creation-time failures.)
// ---------------------------
try {
    // subscriptions → users (assigned_to, created_by)
    if ($db->table_exists('users')) {
        // assigned_to
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_subs_assigned_to_users`
            FOREIGN KEY (`assigned_to`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
        // created_by
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_subs_created_by_users`
            FOREIGN KEY (`created_by`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    // subscriptions → payment_methods (payment_method_id)
    if ($db->table_exists('payment_methods')) {
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_subs_payment_method`
            FOREIGN KEY (`payment_method_id`) REFERENCES `{$pmTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    // subscription_payments → subscriptions + users
    if ($db->table_exists('subscriptions')) {
        $db->query("
            ALTER TABLE `{$paysTbl}`
            ADD CONSTRAINT `fk_sp_subscription`
            FOREIGN KEY (`subscription_id`) REFERENCES `{$subsTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$paysTbl}`
            ADD CONSTRAINT `fk_sp_created_by_users`
            FOREIGN KEY (`created_by`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) {
    // Most likely constraints already exist or storage engine/host limitations.
    // Swallow to keep install idempotent.
}

