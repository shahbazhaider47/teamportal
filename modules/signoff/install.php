<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Signoff Module - Install Script (idempotent, prefix-aware)
 */

$requiredVersion = '1.0.0';
$actualVersion   = '1.0.0';
if (version_compare($actualVersion, $requiredVersion, '<')) {
    throw new Exception('Signoff module requires at least version ' . $requiredVersion);
}

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

// Table names with prefix
$formsTbl   = $db->dbprefix('signoff_forms');
$pointsTbl  = $db->dbprefix('signoff_points');
$targetsTbl = $db->dbprefix('signoff_targets');
$subsTbl    = $db->dbprefix('signoff_submissions');
$teamsTbl   = $db->dbprefix('teams');
$usersTbl   = $db->dbprefix('users');
$posTbl     = $db->dbprefix('hrm_positions'); // if you map position_id to hrm_positions

/* -----------------------------------------------------------
 * 1) signoff_forms
 * ----------------------------------------------------------- */
if (!$db->table_exists('signoff_forms')) {
    $sql = "
    CREATE TABLE `{$formsTbl}` (
      `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `title`      VARCHAR(128) NOT NULL,
      `team_id`    INT(10) UNSIGNED DEFAULT NULL,
      `position_id` INT(11) DEFAULT NULL,
      `fields`     LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
                   COMMENT 'JSON describing fields' CHECK (json_valid(`fields`)),
      `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
      `created_by` INT(10) UNSIGNED DEFAULT NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_signoff_forms_team` (`team_id`),
      KEY `idx_signoff_forms_position` (`position_id`),
      KEY `idx_signoff_forms_active` (`is_active`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 2) signoff_points
 *    (per-team and/or global (team_id NULL or 0) points per form)
 * ----------------------------------------------------------- */
if (!$db->table_exists('signoff_points')) {
    $sql = "
    CREATE TABLE `{$pointsTbl}` (
      `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `team_id`      INT(10) UNSIGNED DEFAULT NULL,
      `form_id`      INT(10) UNSIGNED NOT NULL,
      `points_json`  LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
                     CHECK (json_valid(`points_json`)),
      `created_by`   INT(10) UNSIGNED DEFAULT NULL,
      `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_by`   INT(10) UNSIGNED DEFAULT NULL,
      `updated_at`   DATETIME DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_signoff_points_team_form` (`team_id`,`form_id`),
      KEY `idx_signoff_points_form` (`form_id`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 3) signoff_targets
 *    (time-bounded targets per team+form)
 * ----------------------------------------------------------- */
if (!$db->table_exists('signoff_targets')) {
    $sql = "
    CREATE TABLE `{$targetsTbl}` (
      `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `team_id`      INT(10) UNSIGNED DEFAULT NULL,
      `form_id`      INT(10) UNSIGNED DEFAULT NULL,
      `targets_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                     CHECK (json_valid(`targets_json`)),
      `start_date`   DATE NOT NULL,
      `end_date`     DATE NOT NULL,
      `created_by`   INT(10) UNSIGNED DEFAULT NULL,
      `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_by`   INT(10) UNSIGNED DEFAULT NULL,
      `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_signoff_targets_team_form` (`team_id`,`form_id`),
      KEY `idx_signoff_targets_window` (`start_date`,`end_date`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 4) signoff_submissions
 * ----------------------------------------------------------- */
if (!$db->table_exists('signoff_submissions')) {
    $sql = "
    CREATE TABLE `{$subsTbl}` (
      `id`                INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `form_id`           INT(10) UNSIGNED NOT NULL,
      `user_id`           INT(10) UNSIGNED NOT NULL,
      `team_id`           INT(10) UNSIGNED DEFAULT NULL,
      `submission_date`   DATE NOT NULL,
      `fields_data`       LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
                          COMMENT 'JSON user submission' CHECK (json_valid(`fields_data`)),
      `total_points`      DECIMAL(18,4) DEFAULT NULL,
      `achieved_targets`  DECIMAL(18,4) DEFAULT NULL,
      `signoff_attachment` VARCHAR(255) DEFAULT NULL,
      `status`            VARCHAR(32) NOT NULL DEFAULT 'submitted',
      `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `reviewed_by`       INT(10) UNSIGNED DEFAULT NULL,
      `reviewed_at`       DATETIME DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_signoff_form_user_date` (`form_id`,`user_id`,`submission_date`),
      KEY `idx_signoff_submissions_form` (`form_id`),
      KEY `idx_signoff_submissions_user` (`user_id`),
      KEY `idx_signoff_submissions_team` (`team_id`),
      KEY `idx_signoff_submissions_status` (`status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 5) Foreign keys (added conditionally and safely)
 *    We add them AFTER creation to avoid creation-time failures.
 * ----------------------------------------------------------- */
try {
    // forms → teams (team_id)
    if ($db->table_exists('signoff_forms') && $db->table_exists('teams')) {
        $db->query("
            ALTER TABLE `{$formsTbl}`
            ADD CONSTRAINT `fk_signoff_forms_team`
            FOREIGN KEY (`team_id`) REFERENCES `{$teamsTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore if already exists */ }

try {
    // points → forms, teams
    if ($db->table_exists('signoff_points') && $db->table_exists('signoff_forms')) {
        $db->query("
            ALTER TABLE `{$pointsTbl}`
            ADD CONSTRAINT `fk_signoff_points_form`
            FOREIGN KEY (`form_id`) REFERENCES `{$formsTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('signoff_points') && $db->table_exists('teams')) {
        $db->query("
            ALTER TABLE `{$pointsTbl}`
            ADD CONSTRAINT `fk_signoff_points_team`
            FOREIGN KEY (`team_id`) REFERENCES `{$teamsTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

try {
    // targets → forms, teams
    if ($db->table_exists('signoff_targets') && $db->table_exists('signoff_forms')) {
        $db->query("
            ALTER TABLE `{$targetsTbl}`
            ADD CONSTRAINT `fk_signoff_targets_form`
            FOREIGN KEY (`form_id`) REFERENCES `{$formsTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('signoff_targets') && $db->table_exists('teams')) {
        $db->query("
            ALTER TABLE `{$targetsTbl}`
            ADD CONSTRAINT `fk_signoff_targets_team`
            FOREIGN KEY (`team_id`) REFERENCES `{$teamsTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

try {
    // submissions → forms, teams, users
    if ($db->table_exists('signoff_submissions') && $db->table_exists('signoff_forms')) {
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_signoff_submissions_form`
            FOREIGN KEY (`form_id`) REFERENCES `{$formsTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('signoff_submissions') && $db->table_exists('teams')) {
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_signoff_submissions_team`
            FOREIGN KEY (`team_id`) REFERENCES `{$teamsTbl}`(`id`)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }
    if ($db->table_exists('signoff_submissions') && $db->table_exists('users')) {
        $db->query("
            ALTER TABLE `{$subsTbl}`
            ADD CONSTRAINT `fk_signoff_submissions_user`
            FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
} catch (Exception $e) { /* ignore */ }

