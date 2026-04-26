<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance Module - Install Script (idempotent, prefix-aware)
 */

$requiredVersion = '1.0.0';
$actualVersion   = '1.0.0';
if (version_compare($actualVersion, $requiredVersion, '<')) {
    throw new Exception('Attendance module requires at least version ' . $requiredVersion);
}

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

// Prefixed table names
$usersTbl      = $db->dbprefix('users');
$attendanceTbl = $db->dbprefix('attendance');
$uleavesTbl    = $db->dbprefix('user_leaves');
$devicesTbl    = $db->dbprefix('biometric_devices');
$jobsTbl       = $db->dbprefix('biometric_import_jobs');
$rawTbl        = $db->dbprefix('biometric_raw_logs');
$mapTbl        = $db->dbprefix('biometric_user_map');

/* -----------------------------------------------------------
 * 1) attendance (composite PK: user_id + attendance_date)
 * ----------------------------------------------------------- */
if (!$db->table_exists('attendance')) {
    $sql = "
    CREATE TABLE `{$attendanceTbl}` (
      `user_id`         INT(10) UNSIGNED NOT NULL,
      `attendance_date` DATE NOT NULL,
      `status`          CHAR(1) DEFAULT NULL COMMENT 'P=Present, A=Absent, L=Leave, H=Holiday, etc.',
      `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`user_id`,`attendance_date`),
      KEY `idx_attendance_date` (`attendance_date`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 2) user_leaves
 * ----------------------------------------------------------- */
if (!$db->table_exists('user_leaves')) {
    $sql = "
    CREATE TABLE `{$uleavesTbl}` (
      `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`          INT(10) UNSIGNED NOT NULL,
      `start_date`       DATE NOT NULL,
      `end_date`         DATE NOT NULL,
      `leave_type`       VARCHAR(50) NOT NULL,
      `leave_days`       DECIMAL(4,2) DEFAULT 0.00,
      `leave_notes`      TEXT DEFAULT NULL,
      `status`           ENUM('pending','approved','hold','rejected') DEFAULT 'pending',
      `approver_id`      INT(10) UNSIGNED DEFAULT NULL,
      `approved_at`      DATETIME DEFAULT NULL,
      `seen_by_user`     TINYINT(1) DEFAULT 0,
      `notified_admin`   TINYINT(1) DEFAULT 0,
      `notified_lead`    TINYINT(1) DEFAULT 0,
      `leave_attachment` VARCHAR(255) DEFAULT NULL,
      `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_ul_user_dates` (`user_id`,`start_date`,`end_date`),
      KEY `idx_ul_status` (`status`),
      KEY `idx_ul_approver` (`approver_id`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 3) biometric_devices
 * ----------------------------------------------------------- */
if (!$db->table_exists('biometric_devices')) {
    $sql = "
    CREATE TABLE `{$devicesTbl}` (
      `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `name`         VARCHAR(100) NOT NULL,
      `ip_address`   VARCHAR(64) NOT NULL,
      `port`         INT(10) UNSIGNED NOT NULL DEFAULT 4370,
      `comm_key`     VARCHAR(64) DEFAULT NULL,
      `device_sn`    VARCHAR(100) DEFAULT NULL,
      `timezone`     VARCHAR(64) DEFAULT NULL,
      `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
      `last_seen_at` DATETIME DEFAULT NULL,
      `last_fetch_at` DATETIME DEFAULT NULL,
      `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`   DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_device_ip_port` (`ip_address`,`port`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 4) biometric_import_jobs
 * ----------------------------------------------------------- */
if (!$db->table_exists('biometric_import_jobs')) {
    $sql = "
    CREATE TABLE `{$jobsTbl}` (
      `id`           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `device_id`    INT(10) UNSIGNED NOT NULL,
      `requested_by` INT(10) UNSIGNED DEFAULT NULL,
      `started_at`   DATETIME DEFAULT NULL,
      `ended_at`     DATETIME DEFAULT NULL,
      `status`       ENUM('queued','running','success','failed','partial') NOT NULL DEFAULT 'queued',
      `range_from`   DATETIME DEFAULT NULL,
      `range_to`     DATETIME DEFAULT NULL,
      `total_pulls`  INT(10) UNSIGNED DEFAULT 0,
      `inserted`     INT(10) UNSIGNED DEFAULT 0,
      `skipped`      INT(10) UNSIGNED DEFAULT 0,
      `notes`        TEXT DEFAULT NULL,
      `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_bij_device` (`device_id`),
      KEY `idx_bij_status` (`status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 5) biometric_raw_logs
 * ----------------------------------------------------------- */
if (!$db->table_exists('biometric_raw_logs')) {
    $sql = "
    CREATE TABLE `{$rawTbl}` (
      `id`             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `device_id`      INT(10) UNSIGNED NOT NULL,
      `device_user_id` VARCHAR(64) NOT NULL,
      `punch_time`     DATETIME NOT NULL,
      `punch_type`     ENUM('in','out','break','other') DEFAULT NULL,
      `status_code`    INT(11) DEFAULT NULL,
      `work_code`      VARCHAR(64) DEFAULT NULL,
      `verified`       TINYINT(1) DEFAULT NULL,
      `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_brl_dedup` (`device_id`,`device_user_id`,`punch_time`),
      KEY `idx_brl_device_time` (`device_id`,`punch_time`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 6) biometric_user_map
 * ----------------------------------------------------------- */
if (!$db->table_exists('biometric_user_map')) {
    $sql = "
    CREATE TABLE `{$mapTbl}` (
      `id`             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `device_id`      INT(10) UNSIGNED NOT NULL,
      `device_user_id` VARCHAR(64) NOT NULL,
      `user_id`        INT(10) UNSIGNED NOT NULL,
      `user_code`      VARCHAR(64) DEFAULT NULL,
      `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_bum_mapping` (`device_id`,`device_user_id`),
      KEY `idx_bum_user` (`user_id`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 7) Foreign Keys (added conditionally, safe on re-runs)
 * ----------------------------------------------------------- */
try {
    // attendance → users
    if ($db->table_exists('attendance') && $db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$attendanceTbl}`
            ADD CONSTRAINT `fk_attendance_user`
            FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore if exists or unsupported */ }

try {
    // import_jobs → devices, users
    if ($db->table_exists('biometric_import_jobs') && $db->table_exists('biometric_devices')) {
        $db->query("
            ALTER TABLE `{$jobsTbl}`
            ADD CONSTRAINT `fk_bij_device`
            FOREIGN KEY (`device_id`) REFERENCES `{$devicesTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('biometric_import_jobs') && $db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$jobsTbl}`
            ADD CONSTRAINT `fk_bij_requested_by_users`
            FOREIGN KEY (`requested_by`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

try {
    // raw_logs → devices
    if ($db->table_exists('biometric_raw_logs') && $db->table_exists('biometric_devices')) {
        $db->query("
            ALTER TABLE `{$rawTbl}`
            ADD CONSTRAINT `fk_brl_device`
            FOREIGN KEY (`device_id`) REFERENCES `{$devicesTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

try {
    // user_map → devices, users
    if ($db->table_exists('biometric_user_map') && $db->table_exists('biometric_devices')) {
        $db->query("
            ALTER TABLE `{$mapTbl}`
            ADD CONSTRAINT `fk_bum_device`
            FOREIGN KEY (`device_id`) REFERENCES `{$devicesTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('biometric_user_map') && $db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$mapTbl}`
            ADD CONSTRAINT `fk_bum_user`
            FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

try {
    // OPTIONAL: user_leaves → users (requester, approver)
    if ($db->table_exists('user_leaves') && $db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$uleavesTbl}`
            ADD CONSTRAINT `fk_ul_user`
            FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
        $db->query("
            ALTER TABLE `{$uleavesTbl}`
            ADD CONSTRAINT `fk_ul_approver`
            FOREIGN KEY (`approver_id`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

