CREATE TABLE IF NOT EXISTS `#__companies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `stage` VARCHAR(32) NOT NULL DEFAULT 'C0',
  `cached_stage` VARCHAR(32) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_companies_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__company_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED NOT NULL,
  `event_type` VARCHAR(128) NOT NULL,
  `payload` JSON NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_events_company_id` (`company_id`),
  KEY `idx_company_events_event_type` (`event_type`),
  KEY `idx_company_events_company_event` (`company_id`, `event_type`),
  KEY `idx_company_events_created_at` (`created_at`),
  CONSTRAINT `fk_company_events_company` FOREIGN KEY (`company_id`) REFERENCES `#__companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoices_company_status` (`company_id`, `status`),
  CONSTRAINT `fk_invoices_company` FOREIGN KEY (`company_id`) REFERENCES `#__companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `paid_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_invoice` (`invoice_id`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__certificates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` INT UNSIGNED NOT NULL,
  `issued_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_certificates_company` (`company_id`),
  CONSTRAINT `fk_certificates_company` FOREIGN KEY (`company_id`) REFERENCES `#__companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
