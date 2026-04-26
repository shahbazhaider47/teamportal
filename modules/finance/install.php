<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Finance Module – Install Script
 * Idempotent, prefix-aware. All tables use soft deletes, consistent audit
 * columns, and currency/exchange-rate support for multi-currency workflows.
 */

$CI = isset($CI) ? $CI : get_instance();
$CI->load->database();
$db = $CI->db;

$engine  = 'InnoDB';
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';

/* -----------------------------------------------------------
 * 1) fin_bank_accounts
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_bank_accounts');
if (!$db->table_exists('fin_bank_accounts')) {

    $sql = "
    CREATE TABLE `{$tbl}` (
      `id`                   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

      `account_name`         VARCHAR(100) NOT NULL,
      `bank_name`            VARCHAR(100) NOT NULL,
      `account_number`       VARCHAR(50)  NOT NULL,

      `account_type`         ENUM(
                               'checking',
                               'savings',
                               'current',
                               'business',
                               'credit_card',
                               'digital_wallet',
                               'other'
                             ) NOT NULL DEFAULT 'checking',

      `bank_code`            VARCHAR(50)  DEFAULT NULL
                               COMMENT 'SWIFT, Routing, IFSC, Sort Code, etc.',

      `bank_code_type`       ENUM(
                               'swift',
                               'routing',
                               'ifsc',
                               'sort_code',
                               'bsb',
                               'iban',
                               'other'
                             ) DEFAULT NULL,

      `country`              VARCHAR(50)  DEFAULT 'United States',
      `currency`             VARCHAR(10)  DEFAULT 'USD',

      `account_holder`       VARCHAR(150) NOT NULL,

      `holder_type`          ENUM(
                               'individual',
                               'company',
                               'joint'
                             ) NOT NULL DEFAULT 'individual',

      `opening_balance`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
      `opening_balance_date` DATE          DEFAULT NULL
                               COMMENT 'Date the opening balance was set',
      `current_balance`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,

      `branch`               VARCHAR(100) DEFAULT NULL,
      `contact_phone`        VARCHAR(30)  DEFAULT NULL,
      `notes`                TEXT         DEFAULT NULL,

      `status`               ENUM(
                               'active',
                               'inactive',
                               'closed'
                             ) NOT NULL DEFAULT 'active',

      `is_primary`           TINYINT(1)   NOT NULL DEFAULT 0,
      `is_default`           TINYINT(1)   NOT NULL DEFAULT 0
                               COMMENT 'Default for payments/receipts',

      `created_by`           BIGINT UNSIGNED DEFAULT NULL,
      `updated_by`           BIGINT UNSIGNED DEFAULT NULL,
      `deleted_by`           BIGINT UNSIGNED DEFAULT NULL,

      `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`           DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      `deleted_at`           DATETIME DEFAULT NULL,

      PRIMARY KEY (`id`),
      KEY `idx_fba_status`        (`status`),
      KEY `idx_fba_account_type`  (`account_type`),
      KEY `idx_fba_is_primary`    (`is_primary`),
      KEY `idx_fba_is_default`    (`is_default`),
      KEY `idx_fba_bank_name`     (`bank_name`),
      KEY `idx_fba_deleted_at`    (`deleted_at`)
    )
    ENGINE={$engine}
    DEFAULT CHARSET={$charset}
    COLLATE={$collate}
    COMMENT='Finance bank accounts for payments, receipts, payroll, and reconciliation';
    ";

    $db->query($sql);
}


/* -----------------------------------------------------------
 * 2) fin_invoices
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_invoices');
if (!$db->table_exists('fin_invoices')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `invoice_number`  VARCHAR(50)  NOT NULL,
  `client_id`       BIGINT UNSIGNED NOT NULL,
  `contract_id`     BIGINT UNSIGNED DEFAULT NULL,
  `proposal_id`     BIGINT UNSIGNED DEFAULT NULL,
  `po_number`       VARCHAR(100) DEFAULT NULL
                      COMMENT 'Client purchase order reference',

  `invoice_date`    DATE NOT NULL,
  `due_date`        DATE DEFAULT NULL,

  `currency`        VARCHAR(10)  NOT NULL DEFAULT 'USD',
  `exchange_rate`   DECIMAL(10,6) NOT NULL DEFAULT 1.000000
                      COMMENT 'Rate vs base currency at time of invoice',

  `subtotal`        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate`        DECIMAL(5,2)  NOT NULL DEFAULT 0.00
                      COMMENT 'Primary tax rate %. Line-level rates stored on items.',
  `tax_amount`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `balance_due`     DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `status`          ENUM('draft','sent','viewed','partial','paid','overdue','cancelled')
                    NOT NULL DEFAULT 'draft',

  `notes`           TEXT DEFAULT NULL,
  `terms`           TEXT DEFAULT NULL
                      COMMENT 'Payment terms shown on the invoice',

  `sent_at`         DATETIME DEFAULT NULL
                      COMMENT 'Timestamp when invoice was emailed to client',
  `viewed_at`       DATETIME DEFAULT NULL
                      COMMENT 'Timestamp when client first opened the invoice',

  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`      BIGINT UNSIGNED DEFAULT NULL,
  `deleted_by`      BIGINT UNSIGNED DEFAULT NULL,

  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  KEY `idx_client`     (`client_id`),
  KEY `idx_status`     (`status`),
  KEY `idx_due_date`   (`due_date`),
  KEY `idx_deleted_at` (`deleted_at`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Client invoices with multi-currency and tax support';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 3) fin_invoice_items
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_invoice_items');
if (!$db->table_exists('fin_invoice_items')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`      BIGINT UNSIGNED NOT NULL,

  `sort_order`      SMALLINT UNSIGNED NOT NULL DEFAULT 0
                      COMMENT 'Display order of line items on the invoice',

  `item_name`       VARCHAR(255) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `unit`            VARCHAR(50)  DEFAULT NULL
                      COMMENT 'e.g. hrs, days, units, months',

  `quantity`        DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `discount_type`   ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `tax_rate`        DECIMAL(5,2)  NOT NULL DEFAULT 0.00
                      COMMENT 'Per-line tax rate %',
  `tax_amount`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `line_total`      DECIMAL(14,2) NOT NULL DEFAULT 0.00
                      COMMENT 'After discount, before tax',

  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_invoice`    (`invoice_id`),
  KEY `idx_sort_order` (`invoice_id`, `sort_order`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Line items belonging to an invoice';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 4) fin_credit_notes
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_credit_notes');
if (!$db->table_exists('fin_credit_notes')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `credit_number` VARCHAR(50)  NOT NULL,
  `invoice_id`    BIGINT UNSIGNED NOT NULL,
  `client_id`     BIGINT UNSIGNED NOT NULL,

  `issue_date`    DATE NOT NULL DEFAULT (CURRENT_DATE),
  `amount`        DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `reason`        VARCHAR(255) DEFAULT NULL,
  `notes`         TEXT DEFAULT NULL,

  `status`        ENUM('draft','issued','applied','voided')
                  NOT NULL DEFAULT 'draft',

  `created_by`    BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`    BIGINT UNSIGNED DEFAULT NULL,
  `deleted_by`    BIGINT UNSIGNED DEFAULT NULL,

  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_credit_number` (`credit_number`),
  KEY `idx_invoice`    (`invoice_id`),
  KEY `idx_client`     (`client_id`),
  KEY `idx_status`     (`status`),
  KEY `idx_deleted_at` (`deleted_at`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Credit notes issued against invoices';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 5) fin_payments
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_payments');
if (!$db->table_exists('fin_payments')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `client_id`          BIGINT UNSIGNED NOT NULL,
  `payment_method_id`  INT UNSIGNED    DEFAULT NULL,
  `bank_account_id`    INT UNSIGNED    DEFAULT NULL,

  `payment_date`       DATE NOT NULL,
  `reference_no`       VARCHAR(100) DEFAULT NULL,

  `currency`           VARCHAR(10)   NOT NULL DEFAULT 'USD',
  `exchange_rate`      DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
  `amount`             DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `payment_mode`       ENUM(
                         'cash',
                         'check',
                         'ach',
                         'wire',
                         'credit_card',
                         'digital_wallet',
                         'other'
                       ) NOT NULL DEFAULT 'ach',

  `status`             ENUM('pending','completed','failed','refunded','voided')
                       NOT NULL DEFAULT 'completed',

  `notes`              TEXT DEFAULT NULL,

  `created_by`         BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`         BIGINT UNSIGNED DEFAULT NULL,
  `deleted_by`         BIGINT UNSIGNED DEFAULT NULL,

  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`         DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_client`       (`client_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_status`       (`status`),
  KEY `idx_deleted_at`   (`deleted_at`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Client payments received, with mode and status tracking';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 6) fin_payment_allocations
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_payment_allocations');
if (!$db->table_exists('fin_payment_allocations')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `payment_id`       BIGINT UNSIGNED NOT NULL,
  `invoice_id`       BIGINT UNSIGNED NOT NULL,

  `allocated_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `allocated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_payment` (`payment_id`),
  KEY `idx_invoice` (`invoice_id`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Maps how a payment is split across one or more invoices';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 7) fin_expense_categories
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_expense_categories');
if (!$db->table_exists('fin_expense_categories')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`     INT UNSIGNED DEFAULT NULL
                    COMMENT 'Self-reference for sub-categories',

  `category_name` VARCHAR(150) NOT NULL,
  `description`   VARCHAR(255) DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,

  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Expense categories with optional parent for sub-category hierarchy';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 8) fin_expenses
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_expenses');
if (!$db->table_exists('fin_expenses')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `category_id`     INT UNSIGNED    DEFAULT NULL,
  `bank_account_id` INT UNSIGNED    DEFAULT NULL,
  `client_id`       BIGINT UNSIGNED DEFAULT NULL
                      COMMENT 'Set when expense is billable to a specific client',

  `expense_date`    DATE NOT NULL,

  `vendor_name`     VARCHAR(150) DEFAULT NULL,
  `currency`        VARCHAR(10)  NOT NULL DEFAULT 'USD',
  `exchange_rate`   DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
  `amount`          DECIMAL(14,2) NOT NULL,
  `tax_amount`      DECIMAL(14,2) NOT NULL DEFAULT 0.00,

  `description`     TEXT         DEFAULT NULL,
  `reference_no`    VARCHAR(100) DEFAULT NULL,
  `receipt_file`    VARCHAR(255) DEFAULT NULL
                      COMMENT 'Path or filename of the uploaded receipt',

  `is_billable`     TINYINT(1)   NOT NULL DEFAULT 0
                      COMMENT '1 = can be passed through to client invoice',

  `status`          ENUM('pending','approved','rejected','reimbursed')
                    NOT NULL DEFAULT 'pending',

  `approved_by`     BIGINT UNSIGNED DEFAULT NULL,
  `approved_at`     DATETIME DEFAULT NULL,

  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`      BIGINT UNSIGNED DEFAULT NULL,
  `deleted_by`      BIGINT UNSIGNED DEFAULT NULL,

  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_category`   (`category_id`),
  KEY `idx_client`     (`client_id`),
  KEY `idx_status`     (`status`),
  KEY `idx_deleted_at` (`deleted_at`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Business expenses with approval workflow and billable flag';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 9) fin_transactions
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_transactions');
if (!$db->table_exists('fin_transactions')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `transaction_type` ENUM('income','expense','transfer','adjustment','refund')
                     NOT NULL,

  `direction`        ENUM('debit','credit') NOT NULL
                       COMMENT 'Explicit direction for unambiguous ledger entries',

  `bank_account_id`  INT UNSIGNED    DEFAULT NULL,

  `invoice_id`       BIGINT UNSIGNED DEFAULT NULL,
  `payment_id`       BIGINT UNSIGNED DEFAULT NULL,
  `expense_id`       BIGINT UNSIGNED DEFAULT NULL,

  `currency`         VARCHAR(10)   NOT NULL DEFAULT 'USD',
  `exchange_rate`    DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
  `amount`           DECIMAL(14,2) NOT NULL,

  `transaction_date` DATE NOT NULL,

  `reference_no`     VARCHAR(100) DEFAULT NULL,
  `notes`            TEXT         DEFAULT NULL,

  `reconciled`       TINYINT(1)   NOT NULL DEFAULT 0,
  `reconciled_at`    DATETIME     DEFAULT NULL,

  `created_by`       BIGINT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_type`        (`transaction_type`),
  KEY `idx_bank`        (`bank_account_id`),
  KEY `idx_date`        (`transaction_date`),
  KEY `idx_reconciled`  (`reconciled`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='General ledger transactions linked to invoices, payments, and expenses';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 10) fin_bank_transactions
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_bank_transactions');
if (!$db->table_exists('fin_bank_transactions')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `bank_account_id`        INT UNSIGNED NOT NULL,

  `transaction_date`       DATE         NOT NULL,
  `description`            VARCHAR(255) DEFAULT NULL,

  `transaction_type`       ENUM('debit','credit') DEFAULT NULL
                             COMMENT 'Direction as reported by the bank',

  `debit`                  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `credit`                 DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `balance`                DECIMAL(14,2) DEFAULT NULL
                             COMMENT 'Running balance as per bank statement',

  `reference_no`           VARCHAR(100) DEFAULT NULL,

  `status`                 ENUM('unmatched','matched','ignored')
                           NOT NULL DEFAULT 'unmatched',

  `matched_transaction_id` BIGINT UNSIGNED DEFAULT NULL
                             COMMENT 'FK to fin_transactions once reconciled',

  `imported_via`           ENUM('manual','csv','api','ofx') DEFAULT 'manual',

  `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_bank`   (`bank_account_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date`   (`transaction_date`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Raw bank statement lines imported for reconciliation matching';
";
$db->query($sql);
}


/* -----------------------------------------------------------
 * 11) fin_reconciliations
 * ----------------------------------------------------------- */
$tbl = $db->dbprefix('fin_reconciliations');
if (!$db->table_exists('fin_reconciliations')) {

$sql = "
CREATE TABLE `{$tbl}` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  `bank_account_id` INT UNSIGNED NOT NULL,

  `start_date`      DATE NOT NULL,
  `end_date`        DATE NOT NULL,

  `opening_balance` DECIMAL(14,2) DEFAULT NULL,
  `closing_balance` DECIMAL(14,2) DEFAULT NULL,
  `difference`      DECIMAL(14,2) DEFAULT NULL
                      COMMENT 'Closing balance minus expected balance; 0.00 = balanced',

  `status`          ENUM('draft','completed') NOT NULL DEFAULT 'draft',

  `notes`           TEXT     DEFAULT NULL,
  `completed_at`    DATETIME DEFAULT NULL,

  `created_by`      BIGINT UNSIGNED DEFAULT NULL,
  `updated_by`      BIGINT UNSIGNED DEFAULT NULL,

  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_bank`   (`bank_account_id`),
  KEY `idx_status` (`status`)
)
ENGINE={$engine}
DEFAULT CHARSET={$charset}
COLLATE={$collate}
COMMENT='Bank reconciliation periods tracking opening/closing balance and status';
";
$db->query($sql);
}