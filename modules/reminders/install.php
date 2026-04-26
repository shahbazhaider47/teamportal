<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reminders Module Installer
 * - Idempotent (safe to re-run)
 * - Consistent charset/collation
 * - UNSIGNED keys, helpful indexes
 */

$requiredVersion = '1.0.0';
$actualVersion   = '1.0.0'; // bump when you ship schema changes

if (version_compare($actualVersion, $requiredVersion, '<')) {
    throw new Exception('Reminders module requires at least version ' . $requiredVersion);
}

$CI = isset($CI) ? $CI : get_instance();
$db = $CI->db;

// Helpers
$charset  = 'utf8mb4';
$collate  = 'utf8mb4_unicode_ci';
$engine   = 'InnoDB';

// Respect dbprefix if you’re using it
$tblReminders      = $db->dbprefix('reminders');
$tblReminderAlerts = $db->dbprefix('reminder_alerts');

/** ----------------------------------------------
 *  Create: reminders
 *  ---------------------------------------------- */
if (!$db->table_exists('reminders')) {
    $sql = "
    CREATE TABLE `{$tblReminders}` (
      `id`                INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `title`             VARCHAR(255) NOT NULL,
      `description`       TEXT DEFAULT NULL,
      `date`              DATETIME NOT NULL,
      `priority`          VARCHAR(20) DEFAULT 'medium',
      `is_recurring`      TINYINT(1) DEFAULT 0,
      `recurring_frequency` VARCHAR(20) DEFAULT NULL,
      `recurring_duration`  INT(11) DEFAULT NULL,
      `recurring_dates`     TEXT DEFAULT NULL,
      `created_by`        INT(10) UNSIGNED DEFAULT NULL,
      `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at`        DATETIME DEFAULT NULL,
      `is_completed`      TINYINT(1) DEFAULT 0,
      `completed_at`      DATETIME DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_reminders_date`        (`date`),
      KEY `idx_reminders_created_by`  (`created_by`),
      KEY `idx_reminders_is_completed`(`is_completed`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/** ----------------------------------------------
 *  Create: reminder_alerts
 *  ---------------------------------------------- */
if (!$db->table_exists('reminder_alerts')) {
    $sql = "
    CREATE TABLE `{$tblReminderAlerts}` (
      `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`         INT(10) UNSIGNED NOT NULL,
      `reminder_id`     INT(10) UNSIGNED NOT NULL,
      `occurrence_at`   DATETIME NOT NULL,
      `alert_type`      ENUM('30','5') NOT NULL,
      `delivered_at`    DATETIME DEFAULT NULL,
      `acknowledged_at` DATETIME DEFAULT NULL,
      `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_alert` (`user_id`,`reminder_id`,`occurrence_at`,`alert_type`),
      KEY `idx_alerts_user_delivered` (`user_id`,`delivered_at`),
      KEY `idx_alerts_user_ack`       (`user_id`,`acknowledged_at`),
      KEY `idx_alerts_reminder`       (`reminder_id`),
      KEY `idx_alerts_occurrence`     (`occurrence_at`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/**
 * Optional: add foreign keys (commented out for portability).
 * If your host supports it and you want referential integrity, uncomment and adjust:
 *
 * $db->query(\"ALTER TABLE `{$tblReminders}`
 *   ADD CONSTRAINT `fk_reminders_created_by_users`
 *   FOREIGN KEY (`created_by`) REFERENCES `{$db->dbprefix('users')}`(`id`)
 *   ON DELETE SET NULL ON UPDATE CASCADE\");
 *
 * $db->query(\"ALTER TABLE `{$tblReminderAlerts}`
 *   ADD CONSTRAINT `fk_alerts_user_users`
 *   FOREIGN KEY (`user_id`) REFERENCES `{$db->dbprefix('users')}`(`id`)
 *   ON DELETE CASCADE ON UPDATE CASCADE,
 *   ADD CONSTRAINT `fk_alerts_reminder_reminders`
 *   FOREIGN KEY (`reminder_id`) REFERENCES `{$tblReminders}`(`id`)
 *   ON DELETE CASCADE ON UPDATE CASCADE\");
 */

