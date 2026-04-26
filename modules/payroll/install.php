<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Payroll Module - Install Script (idempotent, prefix-aware)
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

// Prefixed table names
$usersTbl   = $db->dbprefix('users');

$advTbl     = $db->dbprefix('payroll_advances');
$arrTbl     = $db->dbprefix('payroll_arrears');
$detTbl     = $db->dbprefix('payroll_details');
$incTbl     = $db->dbprefix('payroll_increments');
$loanTbl    = $db->dbprefix('payroll_loans');
$pfAcctTbl  = $db->dbprefix('payroll_pf_accounts');
$pfTxnTbl   = $db->dbprefix('payroll_pf_transactions');

/* -----------------------------------------------------------
 * 1) payroll_advances
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_advances')) {
    $sql = "
    CREATE TABLE `{$advTbl}` (
      `id`            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`       INT(10)  UNSIGNED NOT NULL,
      `amount`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `paid`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `balance`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
      `requested_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `approved_at`   DATETIME DEFAULT NULL,
      `approved_by`   INT(10) UNSIGNED DEFAULT NULL,
      `status`        ENUM('requested','approved','scheduled','paid','canceled') NOT NULL DEFAULT 'requested',
      `notes`         TEXT DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_adv_user` (`user_id`),
      KEY `idx_adv_status` (`status`),
      KEY `idx_adv_requested_at` (`requested_at`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 2) payroll_arrears
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_arrears')) {
    $sql = "
    CREATE TABLE `{$arrTbl}` (
      `id`             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`        INT(10)  UNSIGNED NOT NULL,
      `arrears_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `reason`         VARCHAR(255) DEFAULT NULL,
      `source`         VARCHAR(100) DEFAULT NULL,
      `paid_on`        DATE DEFAULT NULL,
      `status`         ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
      `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_arr_user` (`user_id`),
      KEY `idx_arr_status` (`status`),
      KEY `idx_arr_paid_on` (`paid_on`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 3) payroll_details (payslips)
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_details')) {
    $sql = "
    CREATE TABLE `{$detTbl}` (
      `id`                          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`                     INT(10)  UNSIGNED NOT NULL,
      `pay_period`                  ENUM('monthly','semi-monthly','biweekly','weekly','daily','ad-hoc') DEFAULT 'monthly',
      `period_start`                DATE DEFAULT NULL,
      `period_end`                  DATE DEFAULT NULL,
      `pay_date`                    DATE DEFAULT NULL,
      `run_id`                      BIGINT(20) UNSIGNED DEFAULT NULL,
      `payslip_number`              VARCHAR(50) DEFAULT NULL,

      `basic_salary`                DECIMAL(12,2) DEFAULT 0.00,
      `allowances_total`            DECIMAL(12,2) DEFAULT 0.00,
      `allowances_breakdown_json`   LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                                     CHECK (JSON_VALID(`allowances_breakdown_json`)),
      `monthly_input_deductions_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                                     CHECK (JSON_VALID(`monthly_input_deductions_json`)),
      `deductions_total`            DECIMAL(12,2) DEFAULT 0.00,

      `overtime_hours`              DECIMAL(7,2)  DEFAULT 0.00,
      `overtime_amount`             DECIMAL(12,2) DEFAULT 0.00,
      `arrears_amount`              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `bonus_amount`                DECIMAL(12,2) DEFAULT 0.00,
      `commission_amount`           DECIMAL(12,2) DEFAULT 0.00,
      `other_earnings`              DECIMAL(12,2) DEFAULT 0.00,

      `leave_unpaid_days`           DECIMAL(7,2)  DEFAULT 0.00,
      `leave_deduction`             DECIMAL(12,2) DEFAULT 0.00,

      `taxable_income`              DECIMAL(12,2) DEFAULT 0.00,
      `tax_amount`                  DECIMAL(12,2) DEFAULT 0.00,

      `pf_wage_base`                DECIMAL(12,2) DEFAULT NULL,
      `pf_employee`                 DECIMAL(12,2) DEFAULT 0.00,
      `pf_employer`                 DECIMAL(12,2) DEFAULT 0.00,
      `pf_deduction`                DECIMAL(12,2) DEFAULT 0.00,
      `pf_txn_id`                   BIGINT(20) UNSIGNED DEFAULT NULL,

      `gross_pay`                   DECIMAL(12,2) DEFAULT 0.00,
      `employer_cost`               DECIMAL(12,2) DEFAULT 0.00,
      `net_pay`                     DECIMAL(12,2) DEFAULT 0.00,

      `loan_total_deduction`        DECIMAL(12,2) DEFAULT 0.00,
      `advance_total_deduction`     DECIMAL(12,2) DEFAULT 0.00,
      `loan_deductions_json`        LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                                     CHECK (JSON_VALID(`loan_deductions_json`)),
      `advance_deductions_json`     LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
                                     CHECK (JSON_VALID(`advance_deductions_json`)),

      `payment_method`              INT(11) DEFAULT NULL,
      `payment_ref`                 VARCHAR(100) DEFAULT NULL,
      `posted_at`                   DATETIME DEFAULT NULL,
      `posted_by`                   INT(10) UNSIGNED DEFAULT NULL,
      `paid_at`                     DATETIME DEFAULT NULL,
      `paid_by`                     INT(10) UNSIGNED DEFAULT NULL,

      `status`                      ENUM('Active','In-Active') DEFAULT 'Active',
      `status_run`                  ENUM('Open','Processed','Posted','Paid','Void') DEFAULT 'Open',
      `is_locked`                   TINYINT(1) DEFAULT 0,

      `cost_center_id`              INT(11) DEFAULT NULL,
      `department_id`               INT(11) DEFAULT NULL,
      `team_id`                     INT(11) DEFAULT NULL,

      `notes`                       TEXT DEFAULT NULL,
      `created_at`                  DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`                  DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

      PRIMARY KEY (`id`),
      KEY `idx_pd_user_period` (`user_id`,`period_start`,`period_end`),
      KEY `idx_pd_run` (`run_id`),
      KEY `idx_pd_status_run` (`status_run`),
      KEY `idx_pd_pay_date` (`pay_date`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 4) payroll_increments
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_increments')) {
    $sql = "
    CREATE TABLE `{$incTbl}` (
      `id`               INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`          INT(10) UNSIGNED NOT NULL,
      `increment_date`   DATE NOT NULL,
      `increment_type`   ENUM('amount','percent') DEFAULT 'amount',
      `increment_value`  DECIMAL(10,2) NOT NULL,
      `previous_salary`  DECIMAL(10,2) NOT NULL,
      `raised_amount`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `new_salary`       DECIMAL(10,2) NOT NULL,
      `increment_cycle`  ENUM('annual','bi-annual','quarterly','monthly','one-time','other') DEFAULT 'annual',
      `remarks`          VARCHAR(255) DEFAULT NULL,
      `status`           ENUM('pending','approved','rejected','hold') NOT NULL DEFAULT 'pending',
      `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `approved_by`      INT(10) UNSIGNED DEFAULT NULL,
      `created_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_pi_user_date` (`user_id`,`increment_date`),
      KEY `idx_pi_status` (`status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 5) payroll_loans
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_loans')) {
    $sql = "
    CREATE TABLE `{$loanTbl}` (
      `id`                  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`             INT(10) UNSIGNED NOT NULL,
      `loan_taken`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `payback_type`        ENUM('monthly','quarterly','from_salary','custom') NOT NULL DEFAULT 'monthly',
      `total_installments`  INT(10) UNSIGNED NOT NULL DEFAULT 0,
      `monthly_installment` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `current_installment` INT(10) UNSIGNED NOT NULL DEFAULT 0,
      `total_paid`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `balance`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `start_date`          DATE DEFAULT NULL,
      `end_date`            DATE DEFAULT NULL,
      `status`              ENUM('requested','active','paid','defaulted','cancelled') NOT NULL DEFAULT 'requested',
      `notes`               TEXT DEFAULT NULL,
      `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_pl_user_status` (`user_id`,`status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 6) payroll_pf_accounts
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_pf_accounts')) {
    $sql = "
    CREATE TABLE `{$pfAcctTbl}` (
      `id`                          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id`                     INT(10) UNSIGNED NOT NULL,
      `uan_number`                  VARCHAR(20) DEFAULT NULL,
      `pf_member_id`                VARCHAR(30) DEFAULT NULL,
      `current_balance`             DECIMAL(12,2) DEFAULT 0.00,
      `employee_contribution_rate`  DECIMAL(5,2)  DEFAULT 12.00,
      `employer_contribution_rate`  DECIMAL(5,2)  DEFAULT 12.00,
      `wage_base_ceiling`           DECIMAL(12,2) DEFAULT NULL,
      `opened_at`                   DATE DEFAULT NULL,
      `closed_at`                   DATE DEFAULT NULL,
      `nominee_name`                VARCHAR(100) DEFAULT NULL,
      `nominee_relation`            VARCHAR(50) DEFAULT NULL,
      `nominee_share_percent`       DECIMAL(5,2) DEFAULT 100.00,
      `account_status`              ENUM('active','closed','transferred') DEFAULT 'active',
      `created_at`                  DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`                  DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_pfa_user` (`user_id`),
      KEY `idx_pfa_status` (`account_status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 7) payroll_pf_transactions
 * ----------------------------------------------------------- */
if (!$db->table_exists('payroll_pf_transactions')) {
    $sql = "
    CREATE TABLE `{$pfTxnTbl}` (
      `id`                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `pf_account_id`      BIGINT(20) UNSIGNED NOT NULL,
      `transaction_type`   ENUM('contribution','withdrawal','interest','opening_balance','adjustment','transfer_in','transfer_out') NOT NULL,
      `amount`             DECIMAL(12,2) NOT NULL,
      `employee_share`     DECIMAL(12,2) DEFAULT 0.00,
      `employer_share`     DECIMAL(12,2) DEFAULT 0.00,
      `interest_rate`      DECIMAL(5,2)  DEFAULT NULL,
      `txn_date`           DATE DEFAULT NULL,
      `financial_year`     VARCHAR(9) DEFAULT NULL,
      `reference_id`       VARCHAR(50) DEFAULT NULL,
      `reference_module`   ENUM('payroll','manual','import') DEFAULT 'manual',
      `status`             ENUM('pending','processed','failed','reversed') DEFAULT 'processed',
      `posted_by`          INT(10) UNSIGNED DEFAULT NULL,
      `notes`              TEXT DEFAULT NULL,
      `created_at`         DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_pft_acct_date` (`pf_account_id`,`txn_date`),
      KEY `idx_pft_type_status` (`transaction_type`,`status`)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};
    ";
    $db->query($sql);
}

/* -----------------------------------------------------------
 * 8) Foreign Keys (added conditionally; safe on re-runs)
 * ----------------------------------------------------------- */
try {
    if ($db->table_exists('users')) {
        // Advances
        if ($db->table_exists('payroll_advances')) {
            $db->query("ALTER TABLE `{$advTbl}`
                ADD CONSTRAINT `fk_payroll_adv_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
            $db->query("ALTER TABLE `{$advTbl}`
                ADD CONSTRAINT `fk_payroll_adv_approved_by`
                FOREIGN KEY (`approved_by`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE");
        }
        // Arrears
        if ($db->table_exists('payroll_arrears')) {
            $db->query("ALTER TABLE `{$arrTbl}`
                ADD CONSTRAINT `fk_payroll_arr_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
        }
        // Details
        if ($db->table_exists('payroll_details')) {
            $db->query("ALTER TABLE `{$detTbl}`
                ADD CONSTRAINT `fk_payroll_det_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
            $db->query("ALTER TABLE `{$detTbl}`
                ADD CONSTRAINT `fk_payroll_det_posted_by`
                FOREIGN KEY (`posted_by`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE");
            $db->query("ALTER TABLE `{$detTbl}`
                ADD CONSTRAINT `fk_payroll_det_paid_by`
                FOREIGN KEY (`paid_by`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE");
        }
        // Increments
        if ($db->table_exists('payroll_increments')) {
            $db->query("ALTER TABLE `{$incTbl}`
                ADD CONSTRAINT `fk_payroll_inc_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
            $db->query("ALTER TABLE `{$incTbl}`
                ADD CONSTRAINT `fk_payroll_inc_approved_by`
                FOREIGN KEY (`approved_by`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE");
        }
        // Loans
        if ($db->table_exists('payroll_loans')) {
            $db->query("ALTER TABLE `{$loanTbl}`
                ADD CONSTRAINT `fk_payroll_loan_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
        }
        // PF Accounts
        if ($db->table_exists('payroll_pf_accounts')) {
            $db->query("ALTER TABLE `{$pfAcctTbl}`
                ADD CONSTRAINT `fk_pfa_user`
                FOREIGN KEY (`user_id`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE");
        }
        // PF Txns (posted_by ties to users)
        if ($db->table_exists('payroll_pf_transactions')) {
            $db->query("ALTER TABLE `{$pfTxnTbl}`
                ADD CONSTRAINT `fk_pft_posted_by`
                FOREIGN KEY (`posted_by`) REFERENCES `{$usersTbl}`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE");
        }
    }

    // PF Txns → PF Accounts
    if ($db->table_exists('payroll_pf_transactions') && $db->table_exists('payroll_pf_accounts')) {
        $db->query("ALTER TABLE `{$pfTxnTbl}`
            ADD CONSTRAINT `fk_pft_pf_account`
            FOREIGN KEY (`pf_account_id`) REFERENCES `{$pfAcctTbl}`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE");
    }
} catch (Exception $e) {
    // Likely duplicates / engine limitations. Ignore to keep installer idempotent.
}

