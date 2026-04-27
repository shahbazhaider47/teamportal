-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 03:33 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u993957070_teamhrm`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `sent_to` enum('all','employee','teamlead','manager') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `category_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_categories`
--

CREATE TABLE `announcement_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT '6c757d'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_dismissals`
--

CREATE TABLE `announcement_dismissals` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `dismissed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `guarantee_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_purchases`
--

CREATE TABLE `asset_purchases` (
  `id` int(11) NOT NULL,
  `purchase_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `asset_type_id` int(11) DEFAULT NULL,
  `required_quantity` int(11) NOT NULL DEFAULT 1,
  `date_required` date DEFAULT NULL,
  `cost_per_item` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(14,2) GENERATED ALWAYS AS (`required_quantity` * `cost_per_item`) STORED,
  `purchase_status` enum('Pending','Hold','Scheduled','Canceled','Purchased','Delayed') NOT NULL DEFAULT 'Pending',
  `purchase_source` varchar(255) DEFAULT NULL,
  `purchased_by` int(10) UNSIGNED DEFAULT NULL,
  `payment_user` int(10) UNSIGNED DEFAULT NULL,
  `payment_method` enum('Cash','Bank Transfer','Card','Online','Other') DEFAULT 'Cash',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_types`
--

CREATE TABLE `asset_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` char(1) DEFAULT NULL COMMENT 'P=Present, A=Absent, L=Leave, H=Holiday, etc.',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL COMMENT 'Exact event timestamp',
  `status` enum('check_in','check_out','overtime_in','overtime_out','other') NOT NULL,
  `log_type` enum('AUTO','MANUAL','CORRECTION') NOT NULL DEFAULT 'AUTO',
  `device_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `approval_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'APPROVED',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` varchar(20) NOT NULL COMMENT 'SYSTEM or user_id',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_by` int(10) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Atomic attendance event logs with approval and soft delete support';

-- --------------------------------------------------------

--
-- Table structure for table `att_leaves`
--

CREATE TABLE `att_leaves` (
  `id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `total_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_days` decimal(4,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approver_id` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `seen_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `notified_admin` tinyint(1) NOT NULL DEFAULT 0,
  `notified_lead` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL,
  `className` varchar(50) DEFAULT 'event-primary',
  `created_by` int(11) DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_attachments`
--

CREATE TABLE `chat_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `uploader_id` int(10) UNSIGNED DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('direct','group','channel') NOT NULL DEFAULT 'direct',
  `name` varchar(150) DEFAULT NULL,
  `slug` varchar(160) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_read_only` tinyint(1) NOT NULL DEFAULT 0,
  `last_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_members`
--

CREATE TABLE `chat_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `notify_on_mention` tinyint(1) NOT NULL DEFAULT 1,
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `added_by` int(10) UNSIGNED DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `thread_reply_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type` enum('text','file','image','system','poll') NOT NULL DEFAULT 'text',
  `body` mediumtext DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_edited` tinyint(1) NOT NULL DEFAULT 0,
  `edited_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_pins`
--

CREATE TABLE `chat_pins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `pinned_by` int(10) UNSIGNED DEFAULT NULL,
  `pinned_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_reactions`
--

CREATE TABLE `chat_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `emoji` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '1000-01-01 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_info`
--

CREATE TABLE `company_info` (
  `company_name` varchar(255) NOT NULL,
  `business_phone` varchar(50) DEFAULT NULL,
  `business_email` varchar(255) DEFAULT NULL,
  `company_type` varchar(100) DEFAULT NULL,
  `ntn_no` varchar(50) DEFAULT NULL,
  `light_logo` varchar(255) DEFAULT NULL,
  `dark_logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `office_id` int(10) UNSIGNED DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `website` varchar(190) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_offices`
--

CREATE TABLE `company_offices` (
  `id` int(10) UNSIGNED NOT NULL,
  `office_code` varchar(50) NOT NULL COMMENT 'Human-readable code e.g. HQ, LHR, NYC',
  `office_name` varchar(191) NOT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `currency` char(3) NOT NULL DEFAULT 'USD',
  `is_head_office` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_activity`
--

CREATE TABLE `crm_activity` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `rel_type` varchar(50) NOT NULL,
  `rel_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_clients`
--

CREATE TABLE `crm_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_code` varchar(50) NOT NULL,
  `client_group_id` int(10) UNSIGNED DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `practice_name` varchar(255) NOT NULL,
  `practice_legal_name` varchar(255) DEFAULT NULL,
  `practice_type` varchar(100) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `npi_number` varchar(50) DEFAULT NULL,
  `primary_contact_name` varchar(150) DEFAULT NULL,
  `primary_contact_title` varchar(100) DEFAULT NULL,
  `primary_email` varchar(150) DEFAULT NULL,
  `primary_phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `time_zone` varchar(50) DEFAULT NULL,
  `billing_model` enum('percentage','flat','custom') DEFAULT 'percentage',
  `rate_percent` decimal(5,2) DEFAULT NULL,
  `rate_flat` decimal(12,2) DEFAULT NULL,
  `rate_custom` text DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `invoice_frequency` enum('monthly','bi-weekly','weekly','custom') DEFAULT 'monthly',
  `services_included` text DEFAULT NULL,
  `avg_monthly_claims` int(11) DEFAULT NULL,
  `expected_monthly_collections` decimal(14,2) DEFAULT NULL,
  `account_manager` varchar(150) DEFAULT NULL,
  `client_status` enum('inactive','active','on-hold','terminated') DEFAULT 'active',
  `onboarding_date` date DEFAULT NULL,
  `offboarding_date` date DEFAULT NULL,
  `termination_reason` varchar(255) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_client_groups`
--

CREATE TABLE `crm_client_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_name` varchar(100) NOT NULL COMMENT 'Short internal group name (display)',
  `company_name` varchar(150) NOT NULL COMMENT 'Legal or registered company name',
  `tax_id` varchar(50) DEFAULT NULL COMMENT 'EIN, VAT, NTN or equivalent',
  `website` varchar(255) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL COMMENT 'ISO country',
  `fax_number` varchar(50) DEFAULT NULL,
  `contract_date` date DEFAULT NULL COMMENT 'Contract start date with partner',
  `contract_end` date DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = auto-renew on expiry',
  `next_renew` date DEFAULT NULL COMMENT 'Next renewal date',
  `last_renew` date DEFAULT NULL COMMENT 'Last renewal date',
  `contract_file` varchar(255) DEFAULT NULL COMMENT 'Path to signed contract PDF',
  `invoice_mode` enum('single','separate') NOT NULL DEFAULT 'single',
  `payment_terms` varchar(100) DEFAULT NULL COMMENT 'e.g. Net 30, Due on receipt',
  `billing_email` varchar(150) DEFAULT NULL COMMENT 'Invoices sent here if different from contact_email',
  `onboarding_status` enum('pending','in_progress','completed','on_hold') NOT NULL DEFAULT 'pending' COMMENT 'Partner onboarding stage',
  `notes` text DEFAULT NULL COMMENT 'Internal notes visible to staff only',
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_alt_phone` varchar(50) DEFAULT NULL COMMENT 'Secondary / mobile number',
  `status` enum('active','inactive','suspended','churned') NOT NULL DEFAULT 'active',
  `status_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for inactive/suspended/churned',
  `churned_at` timestamp NULL DEFAULT NULL COMMENT 'Set when status → churned',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users.id',
  `updated_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users.id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete — NULL means not deleted',
  `deleted_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → users.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Third-party / partner companies onboarding clients';

-- --------------------------------------------------------

--
-- Table structure for table `crm_contracts`
--

CREATE TABLE `crm_contracts` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `parent_contract_id` int(10) UNSIGNED DEFAULT NULL,
  `contract_title` varchar(255) NOT NULL,
  `contract_code` varchar(50) DEFAULT NULL,
  `contract_type` enum('service_agreement','nda','billing_agreement','amendment','renewal','other') DEFAULT 'service_agreement',
  `status` enum('draft','sent','signed','active','expired','terminated') DEFAULT 'draft',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `billing_model` enum('percentage','flat_fee','custom') DEFAULT NULL,
  `rate_percent` decimal(5,2) DEFAULT NULL,
  `rate_flat` decimal(10,2) DEFAULT NULL,
  `custom_rate` varchar(255) DEFAULT NULL,
  `invoice_frequency` enum('weekly','bi-weekly','monthly','quarterly','annual','custom') DEFAULT 'monthly',
  `payment_terms_days` int(11) DEFAULT 30,
  `auto_renew` tinyint(1) DEFAULT 0,
  `renewal_period` int(11) DEFAULT NULL COMMENT 'months',
  `notice_period_days` int(11) DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `signed_by_client` varchar(255) DEFAULT NULL,
  `signed_by_rcm` varchar(255) DEFAULT NULL,
  `signature_method` enum('wet','docusign','hellosign','other') DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `services_included` text DEFAULT NULL,
  `sla_terms` varchar(500) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `termination_notice_days` int(11) DEFAULT 30,
  `terminated_by` int(10) UNSIGNED DEFAULT NULL,
  `terminated_date` date DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_files`
--

CREATE TABLE `crm_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `related_type` varchar(50) NOT NULL COMMENT 'lead, client, proposal, activity, note, etc',
  `related_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_leads`
--

CREATE TABLE `crm_leads` (
  `id` int(11) NOT NULL,
  `lead_uuid` char(36) NOT NULL,
  `practice_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `alternate_phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `practice_type` enum('solo','group','multi-specialty','hospital','clinic','other') NOT NULL DEFAULT 'other',
  `specialty` varchar(100) DEFAULT NULL,
  `patient_volume_per_month` int(11) DEFAULT NULL,
  `current_billing_provider` varchar(255) DEFAULT NULL,
  `current_emr_system` varchar(255) DEFAULT NULL,
  `monthly_claim_volume` int(11) DEFAULT NULL,
  `current_billing_method` enum('in-house','outsourced','hybrid','none') NOT NULL DEFAULT 'none',
  `monthly_collections` decimal(12,2) DEFAULT NULL,
  `estimated_monthly_revenue` decimal(12,2) DEFAULT NULL,
  `estimated_setup_fee` decimal(12,2) DEFAULT NULL,
  `estimated_annual_value` decimal(12,2) DEFAULT NULL,
  `forecast_probability` tinyint(3) DEFAULT NULL,
  `forecast_category` enum('commit','best_case','pipeline','omitted') NOT NULL DEFAULT 'pipeline',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'USA',
  `lead_source` varchar(100) DEFAULT NULL,
  `lead_status` enum('new','contacted','qualified','proposal_sent','negotiation','demo_scheduled','demo_completed','contract_sent','contract_signed','lost','disqualified') NOT NULL DEFAULT 'new',
  `pipeline_stage_order` tinyint(3) NOT NULL DEFAULT 1,
  `lead_quality` enum('hot','warm','cold') NOT NULL DEFAULT 'warm',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `initial_contact_date` date DEFAULT NULL,
  `last_contact_date` datetime DEFAULT NULL,
  `next_followup_date` datetime DEFAULT NULL,
  `demo_date` datetime DEFAULT NULL,
  `proposal_date` date DEFAULT NULL,
  `expected_close_date` date DEFAULT NULL,
  `actual_close_date` date DEFAULT NULL,
  `loss_reason` varchar(255) DEFAULT NULL,
  `converted_client_id` int(11) DEFAULT NULL,
  `last_stage_changed_at` datetime DEFAULT NULL,
  `practice_needs` text DEFAULT NULL,
  `pain_points` text DEFAULT NULL,
  `decision_criteria` text DEFAULT NULL,
  `key_decision_makers` varchar(500) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `import_batch_id` varchar(50) DEFAULT NULL,
  `import_source_file` varchar(255) DEFAULT NULL,
  `import_date` timestamp NULL DEFAULT NULL,
  `is_imported` tinyint(1) NOT NULL DEFAULT 0,
  `preferred_contact_method` enum('email','phone','text','any') NOT NULL DEFAULT 'any',
  `best_time_to_contact` varchar(100) DEFAULT NULL,
  `referred_by` varchar(255) DEFAULT NULL,
  `referral_type` enum('existing_client','partner','conference','other') DEFAULT NULL,
  `data_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_notes`
--

CREATE TABLE `crm_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rel_type` varchar(50) NOT NULL COMMENT 'lead, client, proposal, contract, etc',
  `rel_id` bigint(20) UNSIGNED NOT NULL,
  `note` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = internal, 0 = visible to client (future use)',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Primary owner of the note',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_proposals`
--

CREATE TABLE `crm_proposals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proposal_number` varchar(30) NOT NULL,
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `terms_and_conditions` longtext DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` enum('weekly','bi-weekly','monthly','quarterly','annual','custom') DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `validity_days` smallint(5) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `go_live_date` date DEFAULT NULL,
  `status` enum('draft','pending_review','sent','viewed','approved','declined','expired','cancelled') NOT NULL DEFAULT 'draft',
  `status_changed_at` datetime DEFAULT NULL,
  `status_changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `forecast_category` enum('commit','best_case','pipeline','omitted') DEFAULT NULL,
  `public_token` varchar(100) DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `client_notes` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `decline_reason` varchar(500) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_proposal_items`
--

CREATE TABLE `crm_proposal_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proposal_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` enum('service','setup_fee','addon','other') NOT NULL DEFAULT 'service',
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_tags`
--

CREATE TABLE `crm_tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rel_type` varchar(50) NOT NULL COMMENT 'lead, client, proposal, contract, etc',
  `rel_id` bigint(20) UNSIGNED NOT NULL,
  `tag_name` varchar(100) NOT NULL,
  `tag_color` varchar(20) DEFAULT NULL COMMENT 'HEX or label color (optional UI use)',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cron_history`
--

CREATE TABLE `cron_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_slug` varchar(191) NOT NULL,
  `started_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL,
  `status` enum('success','failed','skipped') NOT NULL DEFAULT 'success',
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cron_tasks`
--

CREATE TABLE `cron_tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `schedule` varchar(191) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'core',
  `module_name` varchar(100) DEFAULT NULL,
  `callback` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_kpi_cache`
--

CREATE TABLE `dashboard_kpi_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` varchar(32) NOT NULL,
  `kpi_key` varchar(64) NOT NULL,
  `kpi_value` text NOT NULL,
  `refreshed_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_widget_prefs`
--

CREATE TABLE `dashboard_widget_prefs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `container_id` varchar(64) NOT NULL,
  `widget_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT json_array() CHECK (json_valid(`widget_order`)),
  `widget_visibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT json_object() CHECK (json_valid(`widget_visibility`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `hod` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `do_later_tasks`
--

CREATE TABLE `do_later_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('controller','model','view','helper','library','module','css','js','assets','sql','config','other') NOT NULL DEFAULT 'other',
  `reference` varchar(191) DEFAULT NULL COMMENT 'File, module, or feature reference',
  `code` longtext NOT NULL COMMENT 'Raw code snippet or notes',
  `status` enum('pending','in_process','completed','needs_review','blocked','obsolete') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emailtemplates`
--

CREATE TABLE `emailtemplates` (
  `emailtemplateid` int(11) NOT NULL,
  `type` longtext NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` longtext NOT NULL,
  `subject` longtext NOT NULL,
  `message` longtext NOT NULL,
  `fromname` longtext NOT NULL,
  `fromemail` varchar(100) DEFAULT NULL,
  `plaintext` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(4) NOT NULL DEFAULT 0,
  `order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_feedback_forms`
--

CREATE TABLE `employee_feedback_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency` enum('weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_departments` text DEFAULT NULL,
  `reviewers` text DEFAULT NULL,
  `notify_participants` tinyint(1) NOT NULL DEFAULT 1,
  `notify_reviewers` tinyint(1) NOT NULL DEFAULT 1,
  `form_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`form_schema`)),
  `status` enum('draft','active','closed') NOT NULL DEFAULT 'draft',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_feedback_submissions`
--

CREATE TABLE `employee_feedback_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `average_score` decimal(5,2) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_movements`
--

CREATE TABLE `employee_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `movement_type` enum('promotion','transfer','salary_change','role_change','department_change','team_change','location_change','status_change') NOT NULL,
  `from_title_id` int(11) DEFAULT NULL,
  `from_department_id` int(11) DEFAULT NULL,
  `from_team_id` int(11) DEFAULT NULL,
  `from_manager_id` int(11) DEFAULT NULL,
  `from_salary` decimal(12,2) DEFAULT NULL,
  `to_title_id` int(11) DEFAULT NULL,
  `to_department_id` int(11) DEFAULT NULL,
  `to_team_id` int(11) DEFAULT NULL,
  `to_manager_id` int(11) DEFAULT NULL,
  `to_salary` decimal(12,2) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `review_type` enum('monthly','bi-annual','annual','quarterly','probation','custom') NOT NULL,
  `review_period` varchar(50) NOT NULL,
  `review_date` date NOT NULL,
  `att_working_days` tinyint(4) DEFAULT NULL,
  `att_days_present` tinyint(4) DEFAULT NULL,
  `att_days_absent` tinyint(4) DEFAULT NULL,
  `att_late_arrivals` tinyint(4) DEFAULT NULL,
  `att_extra_hours` decimal(5,2) DEFAULT NULL,
  `att_pct` decimal(5,2) DEFAULT NULL,
  `score_attendance` decimal(5,2) DEFAULT NULL,
  `score_targets` decimal(5,2) DEFAULT NULL,
  `score_perf_metrics` varchar(20) DEFAULT NULL,
  `score_ratings` decimal(5,2) DEFAULT NULL,
  `overall_verdict` varchar(500) DEFAULT NULL,
  `employee_comments` text DEFAULT NULL,
  `supervisor_comments` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `sig_supervisor` varchar(100) DEFAULT NULL,
  `sig_supervisor_date` date DEFAULT NULL,
  `sig_employee` varchar(100) DEFAULT NULL,
  `sig_employee_date` date DEFAULT NULL,
  `sig_hr` varchar(100) DEFAULT NULL,
  `sig_hr_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eval_criteria`
--

CREATE TABLE `eval_criteria` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `criteria_type` enum('rating','pass_fail','target','attendance','phone','text') NOT NULL,
  `label` varchar(255) NOT NULL,
  `default_target_day` decimal(10,2) DEFAULT NULL,
  `default_target_month` decimal(10,2) DEFAULT NULL,
  `default_deadline` varchar(50) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eval_goals`
--

CREATE TABLE `eval_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `goal` text DEFAULT NULL,
  `training_need` text DEFAULT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eval_responses`
--

CREATE TABLE `eval_responses` (
  `id` int(10) UNSIGNED NOT NULL,
  `evaluation_id` int(10) UNSIGNED NOT NULL,
  `criteria_id` int(10) UNSIGNED NOT NULL,
  `score` tinyint(4) DEFAULT NULL,
  `pass_fail` enum('pass','fail','na') DEFAULT NULL,
  `target_day` decimal(10,2) DEFAULT NULL,
  `deadline` varchar(50) DEFAULT NULL,
  `target_month` decimal(10,2) DEFAULT NULL,
  `actual_month` decimal(10,2) DEFAULT NULL,
  `ach_pct` decimal(7,4) DEFAULT NULL,
  `target_pass_fail` enum('pass','fail') DEFAULT NULL,
  `selected_option` varchar(50) DEFAULT NULL,
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eval_sections`
--

CREATE TABLE `eval_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `section_key` varchar(80) NOT NULL,
  `section_label` varchar(150) NOT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eval_templates`
--

CREATE TABLE `eval_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `review_type` enum('monthly','bi-annual','annual','quarterly','probation','custom') NOT NULL DEFAULT 'monthly',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fin_bank_accounts`
--

CREATE TABLE `fin_bank_accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` enum('checking','savings','current','business','credit_card','digital_wallet','other') NOT NULL DEFAULT 'checking',
  `bank_code` varchar(50) DEFAULT NULL COMMENT 'SWIFT, Routing, IFSC, Sort Code, etc.',
  `bank_code_type` enum('swift','routing','ifsc','sort_code','bsb','iban','other') DEFAULT NULL,
  `country` varchar(50) DEFAULT 'United States',
  `currency` varchar(10) DEFAULT 'USD',
  `account_holder` varchar(150) NOT NULL,
  `holder_type` enum('individual','company','joint') NOT NULL DEFAULT 'individual',
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_balance_date` date DEFAULT NULL COMMENT 'Date the opening balance was set',
  `current_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `branch` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','closed') NOT NULL DEFAULT 'active',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Default for payments/receipts',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Finance bank accounts for payments, receipts, payroll, and reconciliation';

-- --------------------------------------------------------

--
-- Table structure for table `fin_bank_transactions`
--

CREATE TABLE `fin_bank_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` int(10) UNSIGNED NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `transaction_type` enum('debit','credit') DEFAULT NULL COMMENT 'Direction as reported by the bank',
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) DEFAULT NULL COMMENT 'Running balance as per bank statement',
  `reference_no` varchar(100) DEFAULT NULL,
  `status` enum('unmatched','matched','ignored') NOT NULL DEFAULT 'unmatched',
  `matched_transaction_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to fin_transactions once reconciled',
  `imported_via` enum('manual','csv','api','ofx') DEFAULT 'manual',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Raw bank statement lines imported for reconciliation matching';

-- --------------------------------------------------------

--
-- Table structure for table `fin_credit_notes`
--

CREATE TABLE `fin_credit_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `credit_number` varchar(50) NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `issue_date` date NOT NULL DEFAULT curdate(),
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','issued','applied','voided') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credit notes issued against invoices';

-- --------------------------------------------------------

--
-- Table structure for table `fin_expenses`
--

CREATE TABLE `fin_expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `bank_account_id` int(10) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Set when expense is billable to a specific client',
  `expense_date` date NOT NULL,
  `vendor_name` varchar(150) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `amount` decimal(14,2) NOT NULL,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL COMMENT 'Path or filename of the uploaded receipt',
  `is_billable` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = can be passed through to client invoice',
  `status` enum('pending','approved','rejected','reimbursed') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Business expenses with approval workflow and billable flag';

-- --------------------------------------------------------

--
-- Table structure for table `fin_expense_categories`
--

CREATE TABLE `fin_expense_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Self-reference for sub-categories',
  `category_name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Expense categories with optional parent for sub-category hierarchy';

-- --------------------------------------------------------

--
-- Table structure for table `fin_invoices`
--

CREATE TABLE `fin_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `contract_id` bigint(20) UNSIGNED DEFAULT NULL,
  `proposal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL COMMENT 'Client purchase order reference',
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Primary tax rate %. Line-level rates stored on items.',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  `balance_due` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','viewed','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL COMMENT 'Payment terms shown on the invoice',
  `sent_at` datetime DEFAULT NULL COMMENT 'Timestamp when invoice was emailed to client',
  `viewed_at` datetime DEFAULT NULL COMMENT 'Timestamp when client first opened the invoice',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Client invoices with multi-currency and tax support';

-- --------------------------------------------------------

--
-- Table structure for table `fin_invoice_items`
--

CREATE TABLE `fin_invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display order of line items on the invoice',
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL COMMENT 'e.g. hrs, days, units, months',
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Per-line tax rate %',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'After discount, before tax',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Line items belonging to an invoice';

-- --------------------------------------------------------

--
-- Table structure for table `fin_payments`
--

CREATE TABLE `fin_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_id` int(10) UNSIGNED DEFAULT NULL,
  `bank_account_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `payment_mode` enum('cash','check','ach','wire','credit_card','digital_wallet','other') NOT NULL DEFAULT 'ach',
  `status` enum('pending','completed','failed','refunded','voided') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Client payments received, with mode and status tracking';

-- --------------------------------------------------------

--
-- Table structure for table `fin_payment_allocations`
--

CREATE TABLE `fin_payment_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `allocated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maps how a payment is split across one or more invoices';

-- --------------------------------------------------------

--
-- Table structure for table `fin_reconciliations`
--

CREATE TABLE `fin_reconciliations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_account_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `opening_balance` decimal(14,2) DEFAULT NULL,
  `closing_balance` decimal(14,2) DEFAULT NULL,
  `difference` decimal(14,2) DEFAULT NULL COMMENT 'Closing balance minus expected balance; 0.00 = balanced',
  `status` enum('draft','completed') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bank reconciliation periods tracking opening/closing balance and status';

-- --------------------------------------------------------

--
-- Table structure for table `fin_transactions`
--

CREATE TABLE `fin_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type` enum('income','expense','transfer','adjustment','refund') NOT NULL,
  `direction` enum('debit','credit') NOT NULL COMMENT 'Explicit direction for unambiguous ledger entries',
  `bank_account_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expense_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(10,6) NOT NULL DEFAULT 1.000000,
  `amount` decimal(14,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `reconciled_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='General ledger transactions linked to invoices, payments, and expenses';

-- --------------------------------------------------------

--
-- Table structure for table `hrm_allowances`
--

CREATE TABLE `hrm_allowances` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `is_percentage` tinyint(1) DEFAULT 0,
  `percentage_of` enum('Base Salary','Gross Salary') DEFAULT NULL,
  `max_limit` decimal(10,2) DEFAULT NULL,
  `applicable_to` enum('All','Male','Female','Departments','Positions','Custom') DEFAULT 'All',
  `is_active` tinyint(1) DEFAULT 1,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `applicable_departments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_departments_json`)),
  `applicable_positions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_positions_json`)),
  `applicable_user_ids_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_user_ids_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hrm_documents`
--

CREATE TABLE `hrm_documents` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_scope` varchar(20) NOT NULL DEFAULT 'employee',
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `doc_type` varchar(100) NOT NULL,
  `expiry_date` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hrm_employee_exits`
--

CREATE TABLE `hrm_employee_exits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `exit_type` varchar(50) NOT NULL,
  `exit_date` date NOT NULL,
  `last_working_date` date DEFAULT NULL,
  `exit_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `accepted_by` int(10) UNSIGNED DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `accepted_remarks` text DEFAULT NULL,
  `notice_period_served` tinyint(1) DEFAULT 0,
  `exit_interview_date` date DEFAULT NULL,
  `exit_interview_conducted_by` int(11) DEFAULT NULL,
  `checklist_completed` tinyint(1) DEFAULT 0,
  `assets_returned` tinyint(1) DEFAULT 0,
  `final_settlement_amount` decimal(12,2) DEFAULT NULL,
  `final_settlement_date` date DEFAULT NULL,
  `settlement_id` int(10) UNSIGNED DEFAULT NULL,
  `nda_signed` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hrm_employee_rejoins`
--

CREATE TABLE `hrm_employee_rejoins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active (reactivated)',
  `is_rejoined` tinyint(1) NOT NULL DEFAULT 1,
  `rejoin_date` date DEFAULT NULL,
  `rejoin_reson` varchar(190) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hrm_positions`
--

CREATE TABLE `hrm_positions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `min_salary` decimal(12,2) DEFAULT NULL,
  `max_salary` decimal(12,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `type` enum('Paid','Unpaid','Compensatory','Work from Home') NOT NULL DEFAULT 'Paid',
  `limit` decimal(5,2) DEFAULT NULL,
  `unit` enum('Hours','Days') NOT NULL DEFAULT 'Days',
  `description` text DEFAULT NULL,
  `attachment_required` tinyint(1) NOT NULL DEFAULT 0,
  `based_on` enum('Calendar Days','Joining Date') NOT NULL DEFAULT 'Calendar Days',
  `employment_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `allowed_annually` decimal(5,2) DEFAULT NULL,
  `allowed_monthly` decimal(5,2) DEFAULT NULL,
  `applies_to_genders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `applies_to_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_locations`)),
  `applies_to_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_departments`)),
  `applies_to_positions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_positions`)),
  `applies_to_employees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_employees`)),
  `applies_to_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applies_to_roles`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notepad_folders`
--

CREATE TABLE `notepad_folders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notepad_notes`
--

CREATE TABLE `notepad_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `folder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` mediumtext NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `feature_key` varchar(50) NOT NULL,
  `short_text` varchar(191) NOT NULL,
  `full_text` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_pdf` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_advances`
--

CREATE TABLE `payroll_advances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('requested','approved','scheduled','paid','canceled') NOT NULL DEFAULT 'requested',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_arrears`
--

CREATE TABLE `payroll_arrears` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `arrears_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `paid_on` date DEFAULT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

CREATE TABLE `payroll_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `pay_period` enum('monthly','semi-monthly','biweekly','weekly','daily','ad-hoc') DEFAULT 'monthly',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `run_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payslip_number` varchar(50) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances_total` decimal(12,2) DEFAULT 0.00,
  `allowances_breakdown_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances_breakdown_json`)),
  `monthly_input_deductions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`monthly_input_deductions_json`)),
  `deductions_total` decimal(12,2) DEFAULT 0.00,
  `overtime_hours` decimal(7,2) DEFAULT 0.00,
  `overtime_amount` decimal(12,2) DEFAULT 0.00,
  `arrears_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bonus_amount` decimal(12,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) DEFAULT 0.00,
  `other_earnings` decimal(12,2) DEFAULT 0.00,
  `leave_unpaid_days` decimal(7,2) DEFAULT 0.00,
  `leave_deduction` decimal(12,2) DEFAULT 0.00,
  `taxable_income` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `pf_wage_base` decimal(12,2) DEFAULT NULL,
  `pf_employee` decimal(12,2) DEFAULT 0.00,
  `pf_employer` decimal(12,2) DEFAULT 0.00,
  `pf_deduction` decimal(12,2) DEFAULT 0.00,
  `pf_txn_id` bigint(20) UNSIGNED DEFAULT NULL,
  `gross_pay` decimal(12,2) DEFAULT 0.00,
  `employer_cost` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `loan_total_deduction` decimal(12,2) DEFAULT 0.00,
  `advance_total_deduction` decimal(12,2) DEFAULT 0.00,
  `loan_deductions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`loan_deductions_json`)),
  `advance_deductions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`advance_deductions_json`)),
  `payment_method` int(11) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `posted_by` int(10) UNSIGNED DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `paid_by` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('Active','In-Active') DEFAULT 'Active',
  `status_run` enum('Open','Processed','Posted','Paid','Void') DEFAULT 'Open',
  `is_locked` tinyint(1) DEFAULT 0,
  `cost_center_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_increments`
--

CREATE TABLE `payroll_increments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `increment_date` date NOT NULL,
  `increment_type` enum('amount','percent') DEFAULT 'amount',
  `increment_value` decimal(10,2) NOT NULL,
  `previous_salary` decimal(10,2) NOT NULL,
  `raised_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_salary` decimal(10,2) NOT NULL,
  `increment_cycle` enum('annual','bi-annual','quarterly','monthly','one-time','other') DEFAULT 'annual',
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','hold') NOT NULL DEFAULT 'pending',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_loans`
--

CREATE TABLE `payroll_loans` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `loan_taken` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payback_type` enum('monthly','quarterly','from_salary','custom') NOT NULL DEFAULT 'monthly',
  `total_installments` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `monthly_installment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_installment` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('requested','active','paid','defaulted','cancelled') NOT NULL DEFAULT 'requested',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_pf_accounts`
--

CREATE TABLE `payroll_pf_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `uan_number` varchar(20) DEFAULT NULL,
  `pf_member_id` varchar(30) DEFAULT NULL,
  `current_balance` decimal(12,2) DEFAULT 0.00,
  `employee_contribution_rate` decimal(5,2) DEFAULT 12.00,
  `employer_contribution_rate` decimal(5,2) DEFAULT 12.00,
  `wage_base_ceiling` decimal(12,2) DEFAULT NULL,
  `opened_at` date DEFAULT NULL,
  `closed_at` date DEFAULT NULL,
  `nominee_name` varchar(100) DEFAULT NULL,
  `nominee_relation` varchar(50) DEFAULT NULL,
  `nominee_share_percent` decimal(5,2) DEFAULT 100.00,
  `account_status` enum('active','closed','transferred') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_pf_transactions`
--

CREATE TABLE `payroll_pf_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pf_account_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type` enum('contribution','withdrawal','interest','opening_balance','adjustment','transfer_in','transfer_out') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `employee_share` decimal(12,2) DEFAULT 0.00,
  `employer_share` decimal(12,2) DEFAULT 0.00,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `txn_date` date DEFAULT NULL,
  `financial_year` varchar(9) DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `reference_module` enum('payroll','manual','import') DEFAULT 'manual',
  `status` enum('pending','processed','failed','reversed') DEFAULT 'processed',
  `posted_by` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

CREATE TABLE `public_holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('Local','Federal','Religion') NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of location IDs' CHECK (json_valid(`locations`)),
  `departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of department IDs' CHECK (json_valid(`departments`)),
  `positions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of position/designation IDs' CHECK (json_valid(`positions`)),
  `employees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of employee IDs' CHECK (json_valid(`employees`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` datetime NOT NULL,
  `priority` varchar(20) DEFAULT 'medium',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` varchar(20) DEFAULT NULL,
  `recurring_duration` int(11) DEFAULT NULL,
  `recurring_dates` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminder_alerts`
--

CREATE TABLE `reminder_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reminder_id` int(10) UNSIGNED NOT NULL,
  `occurrence_at` datetime NOT NULL,
  `alert_type` enum('30','5') NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_no` varchar(30) NOT NULL,
  `type` varchar(50) NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `priority` varchar(20) DEFAULT 'normal',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `submitted_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `signoff_forms`
--

CREATE TABLE `signoff_forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(128) NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON describing fields' CHECK (json_valid(`fields`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `signoff_points`
--

CREATE TABLE `signoff_points` (
  `id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `form_id` int(10) UNSIGNED NOT NULL,
  `points_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`points_json`)),
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `signoff_submissions`
--

CREATE TABLE `signoff_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `submission_date` date NOT NULL,
  `fields_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON user submission' CHECK (json_valid(`fields_data`)),
  `total_points` decimal(18,4) DEFAULT NULL,
  `achieved_targets` decimal(18,4) DEFAULT NULL,
  `signoff_attachment` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'submitted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `signoff_targets`
--

CREATE TABLE `signoff_targets` (
  `id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `form_id` int(10) UNSIGNED DEFAULT NULL,
  `targets_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targets_json`)),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_contracts`
--

CREATE TABLE `staff_contracts` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `contract_type` varchar(100) NOT NULL,
  `version` int(11) DEFAULT 1,
  `parent_contract_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notice_period_days` int(11) DEFAULT 30,
  `is_renewable` tinyint(1) DEFAULT 1,
  `contract_file` varchar(255) DEFAULT NULL,
  `status` enum('draft','sent','signed','expired','cancelled','renewed') NOT NULL DEFAULT 'draft',
  `sent_at` datetime DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `renew_at` datetime DEFAULT NULL,
  `last_renew_at` datetime DEFAULT NULL,
  `signed_by_user_id` int(11) UNSIGNED DEFAULT NULL,
  `sign_method` enum('manual','digital','system') DEFAULT 'manual',
  `signature_hash` varchar(255) DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `vendor` varchar(191) DEFAULT NULL,
  `vendor_url` varchar(255) DEFAULT NULL,
  `account_email` varchar(191) DEFAULT NULL,
  `account_phone` varchar(50) DEFAULT NULL,
  `account_password` varchar(255) DEFAULT NULL COMMENT 'Store HASH only',
  `account_password_enc` text DEFAULT NULL,
  `tfa_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=disabled, 1=enabled',
  `tfa_source` varchar(50) DEFAULT NULL COMMENT 'authenticator|sms|email|other',
  `subscription_type` enum('recurring','one-time','lifetime') DEFAULT 'recurring',
  `payment_cycle` varchar(50) DEFAULT NULL COMMENT 'monthly|quarterly|annually|custom',
  `cycle_days` int(10) UNSIGNED DEFAULT NULL COMMENT 'Used when payment_cycle=custom',
  `start_date` date DEFAULT NULL,
  `next_renewal_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reminder_days_before` int(11) DEFAULT 7 COMMENT 'Alert lead-time',
  `grace_days` int(11) DEFAULT 0 COMMENT 'Renewal grace period',
  `auto_renew` tinyint(1) DEFAULT 0,
  `amount` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `seats` int(10) UNSIGNED DEFAULT NULL COMMENT 'For SaaS licensing',
  `license_key` varchar(191) DEFAULT NULL COMMENT 'For software/licenses',
  `payment_method_id` int(10) UNSIGNED DEFAULT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL COMMENT 'Owner/user responsible',
  `status` enum('active','expired','cancelled','trial') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `meta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array or newline-separated'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_categories`
--

CREATE TABLE `subscription_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED NOT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `method` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_posts`
--

CREATE TABLE `support_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `type` enum('message','note') NOT NULL,
  `body` mediumtext NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(24) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `requester_id` int(10) UNSIGNED NOT NULL,
  `assignee_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('open','in_progress','waiting_user','on_hold','resolved','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `source` enum('web','email','api') NOT NULL DEFAULT 'web',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `watchers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`watchers`)),
  `first_response_due_at` datetime DEFAULT NULL,
  `resolution_due_at` datetime DEFAULT NULL,
  `first_responded_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `last_activity_at` datetime NOT NULL DEFAULT current_timestamp(),
  `files_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(191) NOT NULL,
  `group_key` varchar(100) NOT NULL DEFAULT 'system',
  `value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('not_started','in_progress','review','completed','on_hold','cancelled') NOT NULL DEFAULT 'not_started',
  `dateadded` datetime NOT NULL,
  `startdate` date DEFAULT NULL,
  `duedate` date DEFAULT NULL,
  `datefinished` datetime DEFAULT NULL,
  `addedfrom` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `assignee_id` int(11) DEFAULT NULL,
  `followers_json` text DEFAULT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_type` enum('day','week','month','year') DEFAULT NULL,
  `repeat_every` int(11) DEFAULT NULL,
  `is_recurring_from` int(11) DEFAULT NULL,
  `cycles` int(11) NOT NULL DEFAULT 0,
  `total_cycles` int(11) NOT NULL DEFAULT 0,
  `last_recurring_date` date DEFAULT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `rel_type` varchar(50) DEFAULT NULL,
  `milestone` int(11) NOT NULL DEFAULT 0,
  `kanban_order` int(11) NOT NULL DEFAULT 1,
  `milestone_order` int(11) NOT NULL DEFAULT 0,
  `visible_to_team` tinyint(1) NOT NULL DEFAULT 1,
  `deadline_notified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_activity`
--

CREATE TABLE `task_activity` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_checklist_items`
--

CREATE TABLE `task_checklist_items` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT 0,
  `dateadded` datetime NOT NULL,
  `addedfrom` int(11) NOT NULL,
  `finished_from` int(11) DEFAULT NULL,
  `list_order` int(11) NOT NULL DEFAULT 0,
  `assigned` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` mediumtext NOT NULL,
  `dateadded` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comment_replies`
--

CREATE TABLE `task_comment_replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `taskid` int(10) UNSIGNED NOT NULL,
  `comment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `reply` text NOT NULL,
  `dateadded` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblloginvault`
--

CREATE TABLE `tblloginvault` (
  `id` int(11) NOT NULL,
  `owner_user_id` int(11) NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'website',
  `login_url` varchar(255) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `login_email` varchar(191) DEFAULT NULL,
  `login_phone` varchar(50) DEFAULT NULL,
  `login_pin` varchar(20) DEFAULT NULL,
  `password_encrypted` text NOT NULL,
  `is_tfa` tinyint(1) NOT NULL DEFAULT 0,
  `tfa_secret` varchar(255) DEFAULT NULL,
  `password_hint` varchar(255) DEFAULT NULL,
  `permissions` enum('private','read','write') NOT NULL DEFAULT 'read',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblloginvault_shares`
--

CREATE TABLE `tblloginvault_shares` (
  `id` int(11) NOT NULL,
  `vault_id` int(11) NOT NULL,
  `share_type` enum('user','team','department') NOT NULL,
  `share_id` int(11) NOT NULL,
  `permissions` enum('read','write','admin') NOT NULL DEFAULT 'read',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmodules`
--

CREATE TABLE `tblmodules` (
  `id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `installed_version` varchar(50) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `teamlead_id` int(10) UNSIGNED DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams_guides`
--

CREATE TABLE `teams_guides` (
  `id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `files` longtext DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

CREATE TABLE `todos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `todo_name` varchar(255) NOT NULL,
  `rel_type` varchar(50) DEFAULT NULL,
  `rel_id` int(11) DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_rejoined` tinyint(1) DEFAULT 0,
  `rejoin_date` datetime DEFAULT NULL,
  `rejoin_reson` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `initials` varchar(10) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `fullname` varchar(200) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `emp_dob` date DEFAULT NULL,
  `emp_phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `emp_title` int(11) DEFAULT NULL,
  `emp_joining` date DEFAULT NULL,
  `emp_department` int(11) DEFAULT NULL,
  `emp_team` int(11) DEFAULT NULL,
  `emp_teamlead` int(11) DEFAULT NULL,
  `emp_manager` int(11) DEFAULT NULL,
  `emp_reporting` int(11) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT NULL,
  `contract_type` varchar(50) DEFAULT NULL,
  `pay_period` varchar(50) DEFAULT NULL,
  `work_location` varchar(150) DEFAULT NULL,
  `office_id` int(10) UNSIGNED DEFAULT NULL,
  `work_shift` bigint(20) UNSIGNED DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `last_increment_date` date DEFAULT NULL,
  `increment` decimal(10,2) DEFAULT NULL,
  `joining_salary` decimal(10,2) DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `pay_method` varchar(50) DEFAULT NULL,
  `allow_payroll` tinyint(1) DEFAULT 1,
  `allowances` text DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `current_address` varchar(255) DEFAULT NULL,
  `national_id` varchar(100) DEFAULT NULL,
  `nic_expiry` date DEFAULT NULL,
  `passport_no` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `insurance_policy_no` varchar(100) DEFAULT NULL,
  `eobi_no` varchar(50) DEFAULT NULL,
  `ntn_no` varchar(50) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `bank_code` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(100) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `blood_group` varchar(3) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `emp_grade` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `dashboard_layout` text DEFAULT NULL,
  `notifications_sound` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `grants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`grants`)),
  `denies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`denies`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_shifts`
--

CREATE TABLE `work_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `shift_type` enum('fixed','regular','flexible','off_day') NOT NULL DEFAULT 'fixed',
  `shift_start_time` time NOT NULL,
  `shift_end_time` time NOT NULL,
  `break_start_time` time DEFAULT NULL,
  `break_end_time` time DEFAULT NULL,
  `break_minutes` int(11) DEFAULT 0,
  `grace_minutes` int(11) DEFAULT 0,
  `monthly_late_minutes` int(11) DEFAULT 0,
  `overtime_after_minutes` int(11) DEFAULT 0,
  `max_overtime_minutes` int(11) DEFAULT 0,
  `overtime_type` enum('normal','weekend','holiday') DEFAULT 'normal',
  `weekly_hours` decimal(5,2) DEFAULT NULL,
  `monthly_hours` decimal(6,2) DEFAULT NULL,
  `min_time_between_punches` int(11) DEFAULT 0 COMMENT 'Minutes between punch-in/out',
  `off_days` varchar(50) DEFAULT NULL COMMENT 'CSV: sat,sun or fri',
  `is_night_shift` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_categories`
--
ALTER TABLE `announcement_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_dismissals`
--
ALTER TABLE `announcement_dismissals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_purchases`
--
ALTER TABLE `asset_purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_types`
--
ALTER TABLE `asset_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`user_id`,`attendance_date`),
  ADD KEY `idx_attendance_date` (`attendance_date`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_datetime` (`user_id`,`datetime`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_approval_status` (`approval_status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `att_leaves`
--
ALTER TABLE `att_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_dates` (`user_id`,`start_date`,`end_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_leave_type` (`leave_type_id`),
  ADD KEY `fk_approver` (`approver_id`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `last_activity_at` (`last_activity_at`),
  ADD KEY `is_archived` (`is_archived`);

--
-- Indexes for table `chat_members`
--
ALTER TABLE `chat_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_member` (`conversation_id`,`user_id`),
  ADD KEY `conversation_id_user_id` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id_created_at` (`conversation_id`,`created_at`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `is_deleted` (`is_deleted`);
ALTER TABLE `chat_messages` ADD FULLTEXT KEY `ft_body` (`body`);

--
-- Indexes for table `chat_pins`
--
ALTER TABLE `chat_pins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `chat_reactions`
--
ALTER TABLE `chat_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reaction` (`message_id`,`user_id`,`emoji`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `company_offices`
--
ALTER TABLE `company_offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_company_offices_code` (`office_code`),
  ADD KEY `idx_company_offices_active` (`is_active`),
  ADD KEY `idx_company_offices_head` (`is_head_office`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_settings_key` (`key`);

--
-- Indexes for table `crm_activity`
--
ALTER TABLE `crm_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rel` (`rel_type`,`rel_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `crm_clients`
--
ALTER TABLE `crm_clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_finclients_client_code` (`client_code`),
  ADD KEY `idx_finclients_status` (`client_status`),
  ADD KEY `idx_finclients_practice_name` (`practice_name`),
  ADD KEY `idx_finclients_group` (`client_group_id`),
  ADD KEY `idx_finclients_is_group` (`is_group`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `crm_client_groups`
--
ALTER TABLE `crm_client_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_group_name` (`group_name`),
  ADD UNIQUE KEY `uq_company_name` (`company_name`),
  ADD KEY `idx_finclient_groups_status` (`status`),
  ADD KEY `idx_onboarding_status` (`onboarding_status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_contract_end` (`contract_end`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_country` (`country`);

--
-- Indexes for table `crm_contracts`
--
ALTER TABLE `crm_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `crm_files`
--
ALTER TABLE `crm_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_related` (`related_type`,`related_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- Indexes for table `crm_leads`
--
ALTER TABLE `crm_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lead_uuid` (`lead_uuid`),
  ADD KEY `idx_lead_status` (`lead_status`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_next_followup` (`next_followup_date`),
  ADD KEY `idx_practice_name` (`practice_name`),
  ADD KEY `idx_import_batch` (`import_batch_id`),
  ADD KEY `idx_lead_quality` (`lead_quality`),
  ADD KEY `idx_is_deleted` (`is_deleted`),
  ADD KEY `idx_expected_close_date` (`expected_close_date`),
  ADD KEY `idx_forecast_category` (`forecast_category`),
  ADD KEY `idx_pipeline_stage_order` (`pipeline_stage_order`),
  ADD KEY `idx_converted_client_id` (`converted_client_id`);
ALTER TABLE `crm_leads` ADD FULLTEXT KEY `idx_search` (`practice_name`,`contact_person`,`contact_email`,`internal_notes`);

--
-- Indexes for table `crm_notes`
--
ALTER TABLE `crm_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rel` (`rel_type`,`rel_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `crm_proposals`
--
ALTER TABLE `crm_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_crm_proposals_number` (`proposal_number`),
  ADD UNIQUE KEY `uq_crm_proposals_public_token` (`public_token`),
  ADD KEY `idx_crm_proposals_lead_id` (`lead_id`),
  ADD KEY `idx_crm_proposals_created_by` (`created_by`),
  ADD KEY `idx_crm_proposals_updated_by` (`updated_by`),
  ADD KEY `idx_crm_proposals_status_changed_by` (`status_changed_by`),
  ADD KEY `idx_crm_proposals_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_crm_proposals_deleted_by` (`deleted_by`),
  ADD KEY `idx_crm_proposals_status` (`status`),
  ADD KEY `idx_crm_proposals_status_deleted` (`status`,`deleted_at`),
  ADD KEY `idx_crm_proposals_forecast_category` (`forecast_category`),
  ADD KEY `idx_crm_proposals_expires_at` (`expires_at`),
  ADD KEY `idx_crm_proposals_sent_at` (`sent_at`),
  ADD KEY `idx_crm_proposals_approved_at` (`approved_at`),
  ADD KEY `idx_crm_proposals_created_at` (`created_at`),
  ADD KEY `idx_crm_proposals_deleted_at` (`deleted_at`),
  ADD KEY `idx_crm_proposals_lead_status` (`lead_id`,`status`);

--
-- Indexes for table `crm_proposal_items`
--
ALTER TABLE `crm_proposal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_crm_proposal_items_proposal_id` (`proposal_id`),
  ADD KEY `idx_crm_proposal_items_proposal_item_type` (`proposal_id`,`item_type`);

--
-- Indexes for table `crm_tags`
--
ALTER TABLE `crm_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rel` (`rel_type`,`rel_id`),
  ADD KEY `idx_tag` (`tag_name`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `cron_history`
--
ALTER TABLE `cron_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cron_tasks`
--
ALTER TABLE `cron_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_kpi_cache`
--
ALTER TABLE `dashboard_kpi_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_key` (`role`,`kpi_key`),
  ADD KEY `idx_refreshed` (`refreshed_at`);

--
-- Indexes for table `dashboard_widget_prefs`
--
ALTER TABLE `dashboard_widget_prefs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_container` (`user_id`,`container_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `do_later_tasks`
--
ALTER TABLE `do_later_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emailtemplates`
--
ALTER TABLE `emailtemplates`
  ADD PRIMARY KEY (`emailtemplateid`);

--
-- Indexes for table `employee_feedback_forms`
--
ALTER TABLE `employee_feedback_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_frequency` (`frequency`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `employee_feedback_submissions`
--
ALTER TABLE `employee_feedback_submissions`
  ADD KEY `fk_feedback_form` (`form_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `idx_user_review` (`user_id`,`review_type`,`review_date`),
  ADD KEY `idx_team` (`team_id`,`review_date`) USING BTREE;

--
-- Indexes for table `eval_criteria`
--
ALTER TABLE `eval_criteria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `eval_goals`
--
ALTER TABLE `eval_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluation_id` (`evaluation_id`);

--
-- Indexes for table `eval_responses`
--
ALTER TABLE `eval_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eval_criteria` (`evaluation_id`,`criteria_id`),
  ADD KEY `criteria_id` (`criteria_id`);

--
-- Indexes for table `eval_sections`
--
ALTER TABLE `eval_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `eval_templates`
--
ALTER TABLE `eval_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fin_bank_accounts`
--
ALTER TABLE `fin_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fba_status` (`status`),
  ADD KEY `idx_fba_account_type` (`account_type`),
  ADD KEY `idx_fba_is_primary` (`is_primary`),
  ADD KEY `idx_fba_is_default` (`is_default`),
  ADD KEY `idx_fba_bank_name` (`bank_name`),
  ADD KEY `idx_fba_deleted_at` (`deleted_at`);

--
-- Indexes for table `fin_bank_transactions`
--
ALTER TABLE `fin_bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bank` (`bank_account_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`transaction_date`);

--
-- Indexes for table `fin_credit_notes`
--
ALTER TABLE `fin_credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_credit_number` (`credit_number`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `fin_expenses`
--
ALTER TABLE `fin_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `fin_expense_categories`
--
ALTER TABLE `fin_expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `fin_invoices`
--
ALTER TABLE `fin_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `fin_invoice_items`
--
ALTER TABLE `fin_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_sort_order` (`invoice_id`,`sort_order`);

--
-- Indexes for table `fin_payments`
--
ALTER TABLE `fin_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `fin_payment_allocations`
--
ALTER TABLE `fin_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment` (`payment_id`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Indexes for table `fin_reconciliations`
--
ALTER TABLE `fin_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bank` (`bank_account_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fin_transactions`
--
ALTER TABLE `fin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`transaction_type`),
  ADD KEY `idx_bank` (`bank_account_id`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `idx_reconciled` (`reconciled`);

--
-- Indexes for table `hrm_allowances`
--
ALTER TABLE `hrm_allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hrm_documents`
--
ALTER TABLE `hrm_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hrm_employee_exits`
--
ALTER TABLE `hrm_employee_exits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_exits_user` (`user_id`),
  ADD KEY `idx_exits_user_date` (`user_id`,`exit_date`),
  ADD KEY `idx_exits_status` (`exit_status`),
  ADD KEY `idx_exits_created_at` (`created_at`);

--
-- Indexes for table `hrm_employee_rejoins`
--
ALTER TABLE `hrm_employee_rejoins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `hrm_positions`
--
ALTER TABLE `hrm_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_leave_types_code` (`code`),
  ADD KEY `idx_leave_types_type` (`type`),
  ADD KEY `idx_leave_types_unit` (`unit`),
  ADD KEY `idx_leave_types_deleted` (`deleted_at`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notepad_folders`
--
ALTER TABLE `notepad_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_user_sort` (`user_id`,`sort_order`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `notepad_notes`
--
ALTER TABLE `notepad_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner_status` (`user_id`,`status`),
  ADD KEY `idx_owner_folder_sort` (`user_id`,`folder_id`,`is_pinned`,`sort_order`),
  ADD KEY `idx_flags` (`user_id`,`is_pinned`,`is_favorite`),
  ADD KEY `idx_deleted` (`deleted_at`),
  ADD KEY `fk_notes_folder` (`folder_id`),
  ADD KEY `idx_notes_user_status_folder_pin_updated` (`user_id`,`status`,`folder_id`,`is_pinned`,`updated_at`);
ALTER TABLE `notepad_notes` ADD FULLTEXT KEY `ft_title_content` (`title`,`content`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_advances`
--
ALTER TABLE `payroll_advances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adv_user` (`user_id`),
  ADD KEY `idx_adv_status` (`status`),
  ADD KEY `idx_adv_requested_at` (`requested_at`);

--
-- Indexes for table `payroll_arrears`
--
ALTER TABLE `payroll_arrears`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_arr_user` (`user_id`),
  ADD KEY `idx_arr_status` (`status`),
  ADD KEY `idx_arr_paid_on` (`paid_on`);

--
-- Indexes for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pd_user_period` (`user_id`,`period_start`,`period_end`),
  ADD KEY `idx_pd_run` (`run_id`),
  ADD KEY `idx_pd_status_run` (`status_run`),
  ADD KEY `idx_pd_pay_date` (`pay_date`);

--
-- Indexes for table `payroll_increments`
--
ALTER TABLE `payroll_increments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_user_date` (`user_id`,`increment_date`),
  ADD KEY `idx_pi_status` (`status`);

--
-- Indexes for table `payroll_loans`
--
ALTER TABLE `payroll_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pl_user_status` (`user_id`,`status`);

--
-- Indexes for table `payroll_pf_accounts`
--
ALTER TABLE `payroll_pf_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pfa_user` (`user_id`),
  ADD KEY `idx_pfa_status` (`account_status`);

--
-- Indexes for table `payroll_pf_transactions`
--
ALTER TABLE `payroll_pf_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pft_acct_date` (`pf_account_id`,`txn_date`),
  ADD KEY `idx_pft_type_status` (`transaction_type`,`status`);

--
-- Indexes for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_holiday_category` (`category`),
  ADD KEY `idx_holiday_dates` (`from_date`,`to_date`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reminder_alerts`
--
ALTER TABLE `reminder_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_request_no` (`request_no`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_by` (`requested_by`),
  ADD KEY `idx_assigned_to` (`assigned_to`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_role_name` (`role_name`);

--
-- Indexes for table `signoff_forms`
--
ALTER TABLE `signoff_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_signoff_forms_team` (`team_id`),
  ADD KEY `idx_signoff_forms_position` (`position_id`),
  ADD KEY `idx_signoff_forms_active` (`is_active`);

--
-- Indexes for table `signoff_points`
--
ALTER TABLE `signoff_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_signoff_points_team_form` (`team_id`,`form_id`),
  ADD KEY `idx_signoff_points_form` (`form_id`);

--
-- Indexes for table `signoff_submissions`
--
ALTER TABLE `signoff_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_signoff_form_user_date` (`form_id`,`user_id`,`submission_date`),
  ADD KEY `idx_signoff_submissions_form` (`form_id`),
  ADD KEY `idx_signoff_submissions_user` (`user_id`),
  ADD KEY `idx_signoff_submissions_team` (`team_id`),
  ADD KEY `idx_signoff_submissions_status` (`status`);

--
-- Indexes for table `signoff_targets`
--
ALTER TABLE `signoff_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_signoff_targets_team_form` (`team_id`,`form_id`),
  ADD KEY `idx_signoff_targets_window` (`start_date`,`end_date`);

--
-- Indexes for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subs_category` (`category_id`),
  ADD KEY `idx_subs_vendor` (`vendor`),
  ADD KEY `idx_subs_next_renewal` (`next_renewal_date`),
  ADD KEY `idx_subs_status` (`status`),
  ADD KEY `idx_subs_assigned_to` (`assigned_to`),
  ADD KEY `idx_subs_payment_method` (`payment_method_id`),
  ADD KEY `idx_subs_created_by` (`created_by`);

--
-- Indexes for table `subscription_categories`
--
ALTER TABLE `subscription_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_name` (`name`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subpay_subscription` (`subscription_id`),
  ADD KEY `idx_subpay_date` (`subscription_id`,`payment_date`),
  ADD KEY `idx_subpay_txn` (`transaction_id`),
  ADD KEY `idx_subpay_created_by` (`created_by`);

--
-- Indexes for table `support_posts`
--
ALTER TABLE `support_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_posts_ticket` (`ticket_id`),
  ADD KEY `idx_support_posts_author` (`author_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_support_tickets_code` (`code`),
  ADD KEY `idx_support_tickets_status` (`status`),
  ADD KEY `idx_support_tickets_dept` (`department_id`),
  ADD KEY `idx_support_tickets_assignee` (`assignee_id`),
  ADD KEY `idx_support_tickets_requester` (`requester_id`),
  ADD KEY `idx_support_tickets_last_activity` (`last_activity_at`);
ALTER TABLE `support_tickets` ADD FULLTEXT KEY `ft_support_tickets_subject` (`subject`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_system_settings_key` (`key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `assignee_id` (`assignee_id`),
  ADD KEY `duedate` (`duedate`),
  ADD KEY `rel_id` (`rel_id`),
  ADD KEY `rel_type` (`rel_type`),
  ADD KEY `addedfrom` (`addedfrom`);

--
-- Indexes for table `task_activity`
--
ALTER TABLE `task_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `task_checklist_items`
--
ALTER TABLE `task_checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`),
  ADD KEY `finished` (`finished`),
  ADD KEY `list_order` (`list_order`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taskid` (`taskid`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_comment_replies`
--
ALTER TABLE `task_comment_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_comment` (`taskid`,`comment_id`),
  ADD KEY `idx_user_date` (`user_id`,`dateadded`);

--
-- Indexes for table `tblloginvault`
--
ALTER TABLE `tblloginvault`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_user_id` (`owner_user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `type` (`type`),
  ADD KEY `permissions` (`permissions`),
  ADD KEY `deleted_at` (`deleted_at`),
  ADD KEY `login_url` (`login_url`(191));

--
-- Indexes for table `tblloginvault_shares`
--
ALTER TABLE `tblloginvault_shares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vault_share_unique` (`vault_id`,`share_type`,`share_id`),
  ADD KEY `vault_id` (`vault_id`),
  ADD KEY `share_lookup` (`share_type`,`share_id`);

--
-- Indexes for table `tblmodules`
--
ALTER TABLE `tblmodules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teams_department` (`department_id`),
  ADD KEY `idx_teams_teamlead` (`teamlead_id`),
  ADD KEY `idx_teams_manager` (`manager_id`);

--
-- Indexes for table `teams_guides`
--
ALTER TABLE `teams_guides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_team_id` (`team_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `todos`
--
ALTER TABLE `todos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_online` (`is_online`),
  ADD KEY `idx_users_office_id` (`office_id`);

--
-- Indexes for table `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_categories`
--
ALTER TABLE `announcement_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_dismissals`
--
ALTER TABLE `announcement_dismissals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_purchases`
--
ALTER TABLE `asset_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_types`
--
ALTER TABLE `asset_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `att_leaves`
--
ALTER TABLE `att_leaves`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_attachments`
--
ALTER TABLE `chat_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_members`
--
ALTER TABLE `chat_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_pins`
--
ALTER TABLE `chat_pins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_reactions`
--
ALTER TABLE `chat_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_offices`
--
ALTER TABLE `company_offices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_activity`
--
ALTER TABLE `crm_activity`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_clients`
--
ALTER TABLE `crm_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_contracts`
--
ALTER TABLE `crm_contracts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_files`
--
ALTER TABLE `crm_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_leads`
--
ALTER TABLE `crm_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_notes`
--
ALTER TABLE `crm_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_proposals`
--
ALTER TABLE `crm_proposals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_proposal_items`
--
ALTER TABLE `crm_proposal_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_tags`
--
ALTER TABLE `crm_tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cron_history`
--
ALTER TABLE `cron_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cron_tasks`
--
ALTER TABLE `cron_tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_kpi_cache`
--
ALTER TABLE `dashboard_kpi_cache`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_widget_prefs`
--
ALTER TABLE `dashboard_widget_prefs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `do_later_tasks`
--
ALTER TABLE `do_later_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emailtemplates`
--
ALTER TABLE `emailtemplates`
  MODIFY `emailtemplateid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_feedback_forms`
--
ALTER TABLE `employee_feedback_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eval_criteria`
--
ALTER TABLE `eval_criteria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eval_goals`
--
ALTER TABLE `eval_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eval_responses`
--
ALTER TABLE `eval_responses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eval_sections`
--
ALTER TABLE `eval_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eval_templates`
--
ALTER TABLE `eval_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_bank_accounts`
--
ALTER TABLE `fin_bank_accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_bank_transactions`
--
ALTER TABLE `fin_bank_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_credit_notes`
--
ALTER TABLE `fin_credit_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_expenses`
--
ALTER TABLE `fin_expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_expense_categories`
--
ALTER TABLE `fin_expense_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_invoices`
--
ALTER TABLE `fin_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_invoice_items`
--
ALTER TABLE `fin_invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_payments`
--
ALTER TABLE `fin_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_payment_allocations`
--
ALTER TABLE `fin_payment_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_reconciliations`
--
ALTER TABLE `fin_reconciliations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fin_transactions`
--
ALTER TABLE `fin_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrm_allowances`
--
ALTER TABLE `hrm_allowances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrm_documents`
--
ALTER TABLE `hrm_documents`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrm_employee_exits`
--
ALTER TABLE `hrm_employee_exits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrm_employee_rejoins`
--
ALTER TABLE `hrm_employee_rejoins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hrm_positions`
--
ALTER TABLE `hrm_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notepad_folders`
--
ALTER TABLE `notepad_folders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notepad_notes`
--
ALTER TABLE `notepad_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_advances`
--
ALTER TABLE `payroll_advances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_arrears`
--
ALTER TABLE `payroll_arrears`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_increments`
--
ALTER TABLE `payroll_increments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_loans`
--
ALTER TABLE `payroll_loans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_pf_accounts`
--
ALTER TABLE `payroll_pf_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_pf_transactions`
--
ALTER TABLE `payroll_pf_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_holidays`
--
ALTER TABLE `public_holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminder_alerts`
--
ALTER TABLE `reminder_alerts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `signoff_forms`
--
ALTER TABLE `signoff_forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `signoff_points`
--
ALTER TABLE `signoff_points`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `signoff_submissions`
--
ALTER TABLE `signoff_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `signoff_targets`
--
ALTER TABLE `signoff_targets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_categories`
--
ALTER TABLE `subscription_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_posts`
--
ALTER TABLE `support_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_activity`
--
ALTER TABLE `task_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_checklist_items`
--
ALTER TABLE `task_checklist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comment_replies`
--
ALTER TABLE `task_comment_replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblloginvault`
--
ALTER TABLE `tblloginvault`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblloginvault_shares`
--
ALTER TABLE `tblloginvault_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmodules`
--
ALTER TABLE `tblmodules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams_guides`
--
ALTER TABLE `teams_guides`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `todos`
--
ALTER TABLE `todos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_shifts`
--
ALTER TABLE `work_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `att_leaves`
--
ALTER TABLE `att_leaves`
  ADD CONSTRAINT `fk_att_leaves_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_att_leaves_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_att_leaves_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crm_activity`
--
ALTER TABLE `crm_activity`
  ADD CONSTRAINT `fk_crm_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `crm_clients`
--
ALTER TABLE `crm_clients`
  ADD CONSTRAINT `fk_finclients_group` FOREIGN KEY (`client_group_id`) REFERENCES `crm_client_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `crm_files`
--
ALTER TABLE `crm_files`
  ADD CONSTRAINT `fk_crm_files_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_files_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `crm_proposal_items`
--
ALTER TABLE `crm_proposal_items`
  ADD CONSTRAINT `fk_crm_proposal_items_proposal_id` FOREIGN KEY (`proposal_id`) REFERENCES `crm_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employee_feedback_submissions`
--
ALTER TABLE `employee_feedback_submissions`
  ADD CONSTRAINT `fk_feedback_form` FOREIGN KEY (`form_id`) REFERENCES `employee_feedback_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `eval_templates` (`id`);

--
-- Constraints for table `eval_criteria`
--
ALTER TABLE `eval_criteria`
  ADD CONSTRAINT `eval_criteria_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `eval_sections` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eval_goals`
--
ALTER TABLE `eval_goals`
  ADD CONSTRAINT `eval_goals_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eval_responses`
--
ALTER TABLE `eval_responses`
  ADD CONSTRAINT `eval_responses_ibfk_1` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eval_responses_ibfk_2` FOREIGN KEY (`criteria_id`) REFERENCES `eval_criteria` (`id`);

--
-- Constraints for table `eval_sections`
--
ALTER TABLE `eval_sections`
  ADD CONSTRAINT `eval_sections_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `eval_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hrm_employee_exits`
--
ALTER TABLE `hrm_employee_exits`
  ADD CONSTRAINT `fk_exits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hrm_employee_exits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `notepad_notes`
--
ALTER TABLE `notepad_notes`
  ADD CONSTRAINT `fk_notes_folder` FOREIGN KEY (`folder_id`) REFERENCES `notepad_folders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subs_assigned_to_users` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subs_created_by_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD CONSTRAINT `fk_sp_created_by_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `support_posts`
--
ALTER TABLE `support_posts`
  ADD CONSTRAINT `fk_support_posts_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teams_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_teams_teamlead` FOREIGN KEY (`teamlead_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teams_guides`
--
ALTER TABLE `teams_guides`
  ADD CONSTRAINT `fk_teams_guides_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_teams_guides_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
