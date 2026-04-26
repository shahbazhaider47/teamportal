-- Devices configured by the admin
CREATE TABLE IF NOT EXISTS biometric_devices (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  ip_address      VARCHAR(64)  NOT NULL,
  port            INT UNSIGNED  NOT NULL DEFAULT 4370,
  comm_key        VARCHAR(64)   NULL,          -- if your model uses it
  device_sn       VARCHAR(100)  NULL,          -- optional device serial
  timezone        VARCHAR(64)   NULL,          -- e.g., Asia/Karachi
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  last_seen_at    DATETIME      NULL,
  last_fetch_at   DATETIME      NULL,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_device_ip_port (ip_address, port)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Raw biometric punches (immutable staging)
CREATE TABLE IF NOT EXISTS biometric_raw_logs (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id       INT UNSIGNED NOT NULL,
  device_user_id  VARCHAR(64)  NOT NULL,
  punch_time      DATETIME     NOT NULL,
  punch_type      ENUM('in','out','break','other') NULL, -- optional classification
  status_code     INT NULL,     -- device status/verify code if available
  work_code       VARCHAR(64) NULL,
  verified        TINYINT(1) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_dedup (device_id, device_user_id, punch_time),
  KEY idx_device_time (device_id, punch_time),
  CONSTRAINT fk_brl_device FOREIGN KEY (device_id) REFERENCES biometric_devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mapping between device users and app users
CREATE TABLE IF NOT EXISTS biometric_user_map (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id       INT UNSIGNED NOT NULL,
  device_user_id  VARCHAR(64)  NOT NULL,
  user_id         INT UNSIGNED NOT NULL,  -- refs users.id
  user_code       VARCHAR(64) NULL,       -- optional human code/emp_id on device
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mapping (device_id, device_user_id),
  KEY idx_user (user_id),
  CONSTRAINT fk_bum_device FOREIGN KEY (device_id) REFERENCES biometric_devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import jobs (audit + troubleshooting)
CREATE TABLE IF NOT EXISTS biometric_import_jobs (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id       INT UNSIGNED NOT NULL,
  requested_by    INT UNSIGNED NULL,   -- users.id
  started_at      DATETIME NULL,
  ended_at        DATETIME NULL,
  status          ENUM('queued','running','success','failed','partial') NOT NULL DEFAULT 'queued',
  range_from      DATETIME NULL,       -- requested window
  range_to        DATETIME NULL,
  total_pulls     INT UNSIGNED DEFAULT 0,
  inserted        INT UNSIGNED DEFAULT 0,
  skipped         INT UNSIGNED DEFAULT 0,
  notes           TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bij_device FOREIGN KEY (device_id) REFERENCES biometric_devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--/ Phase 2 — Settings (system_settings keys) --/
biometric_enabled                                 yes|no (default: no)
biometric_default_device_id                       (int|null)
biometric_duplicate_window_seconds                int (default: 60)
biometric_grace_minutes                           int (default: 5)
biometric_late_after_minutes                      int (default: 10)   -- beyond scheduled start + grace
biometric_early_leave_before_minutes              int (default: 10)   -- before scheduled end - grace
biometric_cron_token                              random 32+ chars
biometric_default_shift_start                     09:00
biometric_default_shift_end                       18:00
biometric_timezone                                Asia/Karachi




--/ Cron Hooks 

*/15 * * * * /usr/bin/php /path/to/index.php attendance/biometric run_scheduled >/dev/null 2>&1

