-- --------------------------------------------------------
-- Table structure for table `tblcustomfields`
-- --------------------------------------------------------

CREATE TABLE `tblcustomfields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fieldto` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(20) NOT NULL,
  `options` longtext DEFAULT NULL,
  `display_inline` tinyint(1) NOT NULL DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `only_admin` tinyint(1) NOT NULL DEFAULT 0,
  `show_on_table` tinyint(1) NOT NULL DEFAULT 0,
  `disalow_staff_to_edit` int(11) NOT NULL DEFAULT 0,
  `bs_column` int(11) NOT NULL DEFAULT 12,
  `default_value` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tblcustomfieldsvalues`
-- --------------------------------------------------------

CREATE TABLE `tblcustomfieldsvalues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relid` int(11) NOT NULL,
  `fieldid` int(11) NOT NULL,
  `fieldto` varchar(15) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `relid` (`relid`),
  KEY `fieldto` (`fieldto`),
  KEY `fieldid` (`fieldid`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  
  
-- --------------------------------------------------------
-- Table structure for table `tblloginvault`
-- --------------------------------------------------------

  CREATE TABLE `tblloginvault` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `owner_user_id`     INT(11) NOT NULL,
  `title`             VARCHAR(191) NOT NULL,
  `description`       TEXT DEFAULT NULL,
  `type`              VARCHAR(50) NOT NULL DEFAULT 'website',
  `login_url`         VARCHAR(255) DEFAULT NULL,
  `username`          VARCHAR(191) DEFAULT NULL,
  `login_email`       VARCHAR(191) DEFAULT NULL, 
  `login_phone`       VARCHAR(50)  DEFAULT NULL,
  `login_pin`         VARCHAR(20)  DEFAULT NULL, 
  `password_encrypted` TEXT NOT NULL, 
  `is_tfa`            TINYINT(1) NOT NULL DEFAULT 0,
  `tfa_secret`        VARCHAR(255) DEFAULT NULL, 
  `password_hint`     VARCHAR(255) DEFAULT NULL, 
  `permissions` ENUM('private','read','write','admin')
                   NOT NULL DEFAULT 'read',
  `created_by`        INT(11) NOT NULL,
  `updated_by`        INT(11) DEFAULT NULL,
  `created_at`        DATETIME NOT NULL,
  `updated_at`        DATETIME DEFAULT NULL,
  `deleted_at`        DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `owner_user_id` (`owner_user_id`),
  KEY `created_by`    (`created_by`),
  KEY `type`          (`type`),
  KEY `permissions`   (`permissions`),
  KEY `deleted_at`    (`deleted_at`),
  KEY `login_url`     (`login_url`(191))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tblloginvault_shares`
-- --------------------------------------------------------

CREATE TABLE `tblloginvault_shares` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `vault_id`     INT(11) NOT NULL,
  `share_type`   ENUM('user','team','department') NOT NULL,
  `share_id`     INT(11) NOT NULL,
  `permissions`  ENUM('read','write','admin') NOT NULL DEFAULT 'read',
  `created_by`   INT(11) NOT NULL,
  `created_at`   DATETIME NOT NULL,
  `revoked_at`   DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `vault_id`      (`vault_id`),
  KEY `share_lookup`  (`share_type`, `share_id`),
  UNIQUE KEY `vault_share_unique` (`vault_id`, `share_type`, `share_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
