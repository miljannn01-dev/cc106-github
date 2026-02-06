-- Grant Hub schema for XAMPP/phpMyAdmin
-- Import this file to create the database, tables, seed data, and the
-- DOST Research and Development Grant program with its requirements.

DROP DATABASE IF EXISTS `grant_hub`;
CREATE DATABASE `grant_hub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `grant_hub`;

-- Users & authentication ----------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_type` ENUM('admin','founder') NOT NULL DEFAULT 'founder',
  `company_name` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB;

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `user_type`)
VALUES
  ('System Administrator', 'admin@granthub.com', '$2y$10$.ZYUMMZnsy/DiB8vTzNUdeTaX3hd5KXI3GTCavqcOZ.S6ykl0NFdK', 'admin');

-- Application statuses ------------------------------------------------------
CREATE TABLE `application_statuses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status_key` VARCHAR(50) NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_status_key` (`status_key`)
) ENGINE=InnoDB;

INSERT INTO `application_statuses`
  (`status_key`, `label`, `description`, `sort_order`)
VALUES
  ('draft', 'Draft', 'Saved locally, not yet submitted', 10),
  ('submitted', 'Submitted', 'Waiting for initial screening', 20),
  ('under_review', 'Under Review', 'Being evaluated by DOST panel', 30),
  ('needs_revision', 'Needs Revision', 'Applicant must address revisions', 40),
  ('approved', 'Approved', 'Application approved for funding', 50),
  ('rejected', 'Rejected', 'Application did not pass evaluation', 60);

-- Grant programs & requirements --------------------------------------------
CREATE TABLE `grant_programs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `short_name` VARCHAR(150) DEFAULT NULL,
  `description` TEXT,
  `funding_agency` VARCHAR(150) DEFAULT 'DOST',
  `max_funding` DECIMAL(15,2) DEFAULT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'PHP',
  `eligibility` TEXT,
  `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
  `deadline` DATE DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_grant_slug` (`slug`),
  CONSTRAINT `fk_grant_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `grant_programs`
  (`slug`, `name`, `short_name`, `description`, `funding_agency`, `max_funding`, `eligibility`, `deadline`, `status`)
VALUES
  (
    'dost-rd-grant',
    'DOST Research and Development Grant Program',
    'DOST R&D Grant',
    'Supports startups and researchers developing science and technology solutions aligned with national priorities. Covers prototype development, pilot deployment, and commercialization readiness activities.',
    'Department of Science and Technology',
    5000000.00,
    'Philippine-registered startups, universities, and research teams with at least 60% Filipino ownership and a demonstrable R&D track record.',
    DATE_ADD(CURDATE(), INTERVAL 6 MONTH),
    'published'
  );

CREATE TABLE `grant_requirements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `grant_id` INT UNSIGNED NOT NULL,
  `requirement_code` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `requirement_type` ENUM('document','text','number','url','date') NOT NULL DEFAULT 'document',
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_requirement_code` (`requirement_code`),
  KEY `idx_requirement_grant` (`grant_id`),
  CONSTRAINT `fk_requirement_grant` FOREIGN KEY (`grant_id`) REFERENCES `grant_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `grant_requirements`
  (`grant_id`, `requirement_code`, `title`, `description`, `requirement_type`, `is_required`, `sort_order`)
SELECT
  gp.id,
  req.requirement_code,
  req.title,
  req.description,
  req.requirement_type,
  req.is_required,
  req.sort_order
FROM `grant_programs` gp
JOIN (
  SELECT 'DOST_REQ_01' AS requirement_code, 'Project Proposal Narrative' AS title,
         'Detailed problem statement, objectives, and R&D methodology.' AS description, 'document' AS requirement_type, 1 AS is_required, 10 AS sort_order
  UNION ALL SELECT 'DOST_REQ_02', 'Work Plan & Gantt Chart', 'Milestones, activities, and responsible persons for the 12-24 month project period.', 'document', 1, 20
  UNION ALL SELECT 'DOST_REQ_03', 'Line-Item Budget (LIB)', 'Breakdown of direct costs, personnel services, and equipment with justifications.', 'document', 1, 30
  UNION ALL SELECT 'DOST_REQ_04', 'Cash/ In-Kind Counterpart Commitment', 'Signed commitment letter showing counterpart resources from proponent.', 'document', 1, 40
  UNION ALL SELECT 'DOST_REQ_05', 'Business / Institutional Registration', 'SEC/DTI/CDA registration or university board resolution authorizing the project.', 'document', 1, 50
  UNION ALL SELECT 'DOST_REQ_06', 'Latest Audited Financial Statements', 'Most recent audited FS or bank certification for startups < 2 years.', 'document', 1, 60
  UNION ALL SELECT 'DOST_REQ_07', 'CV of Project Leader & Key Staff', 'Updated curriculum vitae highlighting R&D track record and experience.', 'document', 1, 70
  UNION ALL SELECT 'DOST_REQ_08', 'Intellectual Property Status Disclosure', 'Patent search report or IP declaration specifying ownership of foreground IP.', 'document', 1, 80
  UNION ALL SELECT 'DOST_REQ_09', 'Freedom to Operate / Prior Art Search', 'Evidence that the proposed solution does not infringe existing IP.', 'document', 1, 90
  UNION ALL SELECT 'DOST_REQ_10', 'Ethics Clearance / Biosafety Permit', 'Required for biomedical, agricultural, or AI solutions handling sensitive data.', 'document', 0, 100
  UNION ALL SELECT 'DOST_REQ_11', 'Community / LGU Endorsement', 'Certification of support from project deployment site or beneficiary LGU.', 'document', 0, 110
  UNION ALL SELECT 'DOST_REQ_12', 'Monitoring & Evaluation Framework', 'Key performance indicators, data-collection plan, and risk mitigation.', 'document', 1, 120
) AS req ON gp.slug = 'dost-rd-grant';

-- Applications --------------------------------------------------------------
CREATE TABLE `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_code` VARCHAR(100) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `grant_id` INT UNSIGNED NOT NULL,
  `project_title` VARCHAR(255) NOT NULL,
  `project_summary` TEXT,
  `requested_amount` DECIMAL(15,2) DEFAULT NULL,
  `status_id` INT UNSIGNED NOT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_application_code` (`application_code`),
  KEY `idx_app_user` (`user_id`),
  KEY `idx_app_grant` (`grant_id`),
  KEY `idx_app_status` (`status_id`),
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_grant` FOREIGN KEY (`grant_id`) REFERENCES `grant_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_status` FOREIGN KEY (`status_id`) REFERENCES `application_statuses` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `application_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `status_id` INT UNSIGNED NOT NULL,
  `remarks` TEXT,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_history_application` (`application_id`),
  CONSTRAINT `fk_history_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_status` FOREIGN KEY (`status_id`) REFERENCES `application_statuses` (`id`),
  CONSTRAINT `fk_history_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `application_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `requirement_id` INT UNSIGNED NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(120) DEFAULT NULL,
  `file_size` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('submitted','needs_revision','approved') NOT NULL DEFAULT 'submitted',
  `reviewer_note` TEXT,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc_application` (`application_id`),
  KEY `idx_doc_requirement` (`requirement_id`),
  CONSTRAINT `fk_doc_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `grant_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- DOST R&D detailed requirement storage ------------------------------------
CREATE TABLE `dost_application_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `program_title` VARCHAR(255) NOT NULL,
  `project_title` VARCHAR(255) NOT NULL,
  `project_leader` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `sex` VARCHAR(50) NOT NULL,
  `telephone` VARCHAR(50) DEFAULT NULL,
  `house_number` VARCHAR(50) DEFAULT NULL,
  `street_name` VARCHAR(255) DEFAULT NULL,
  `barangay` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(255) DEFAULT NULL,
  `district` VARCHAR(255) DEFAULT NULL,
  `province` VARCHAR(255) DEFAULT NULL,
  `region` VARCHAR(255) DEFAULT NULL,
  `country` VARCHAR(255) DEFAULT NULL,
  `implementing_agency` VARCHAR(255) NOT NULL,
  `cooperating_agency` VARCHAR(255) DEFAULT NULL,
  `type_of_research` VARCHAR(255) NOT NULL,
  `rd_priority_area_program` TEXT NOT NULL,
  `sustainable_development_goal` TEXT NOT NULL,
  `dost_pillars_pursued` TEXT NOT NULL,
  `dost_thematic_areas` TEXT NOT NULL,
  `dost_strategic_program` TEXT NOT NULL,
  `introduction` MEDIUMTEXT NOT NULL,
  `executive_summary` MEDIUMTEXT NOT NULL,
  `rationale_significance` MEDIUMTEXT NOT NULL,
  `scientific_basis` MEDIUMTEXT NOT NULL,
  `objectives` MEDIUMTEXT NOT NULL,
  `review_of_literature` MEDIUMTEXT NOT NULL,
  `methodology` MEDIUMTEXT NOT NULL,
  `technology_roadmap` MEDIUMTEXT NOT NULL,
  `expected_outputs` MEDIUMTEXT NOT NULL,
  `potential_outcomes` MEDIUMTEXT NOT NULL,
  `potential_impacts` MEDIUMTEXT NOT NULL,
  `target_beneficiaries` MEDIUMTEXT NOT NULL,
  `sustainability_plan` MEDIUMTEXT NOT NULL,
  `gad_score` VARCHAR(50) NOT NULL,
  `limitations` MEDIUMTEXT NOT NULL,
  `risks_assumptions` MEDIUMTEXT NOT NULL,
  `literature_cited` MEDIUMTEXT NOT NULL,
  `submitted_by` VARCHAR(255) NOT NULL,
  `endorsed_by` VARCHAR(255) DEFAULT NULL,
  `remarks` MEDIUMTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dost_application` (`application_id`),
  CONSTRAINT `fk_dost_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `dost_application_sites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `site_number` VARCHAR(50) DEFAULT NULL,
  `country` VARCHAR(255) DEFAULT NULL,
  `region` VARCHAR(255) DEFAULT NULL,
  `province` VARCHAR(255) DEFAULT NULL,
  `district` VARCHAR(255) DEFAULT NULL,
  `municipality` VARCHAR(255) DEFAULT NULL,
  `barangay` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sites_application` (`application_id`),
  CONSTRAINT `fk_sites_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `dost_personnel_requirements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `position` VARCHAR(255) NOT NULL,
  `quantity` VARCHAR(50) NOT NULL,
  `percent_time` VARCHAR(50) NOT NULL,
  `responsibility` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_personnel_application` (`application_id`),
  CONSTRAINT `fk_personnel_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `dost_budget_allocations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `year_label` VARCHAR(50) NOT NULL,
  `agency` VARCHAR(255) NOT NULL,
  `ps_dost` DECIMAL(15,2) DEFAULT 0,
  `ps_counterpart` DECIMAL(15,2) DEFAULT 0,
  `mooe_dost` DECIMAL(15,2) DEFAULT 0,
  `mooe_counterpart` DECIMAL(15,2) DEFAULT 0,
  `co_dost` DECIMAL(15,2) DEFAULT 0,
  `co_counterpart` DECIMAL(15,2) DEFAULT 0,
  `total_dost` DECIMAL(15,2) DEFAULT 0,
  `total_counterpart` DECIMAL(15,2) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_budget_application` (`application_id`),
  CONSTRAINT `fk_budget_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Convenience view tying applications to status labels ----------------------
CREATE OR REPLACE VIEW `vw_application_overview` AS
SELECT
  a.id,
  a.application_code,
  u.full_name AS applicant_name,
  u.email AS applicant_email,
  u.company_name,
  g.name AS grant_name,
  g.slug AS grant_slug,
  s.label AS status_label,
  s.status_key,
  a.project_title,
  a.requested_amount,
  a.submitted_at,
  a.updated_at
FROM applications a
JOIN users u ON a.user_id = u.id
JOIN grant_programs g ON a.grant_id = g.id
JOIN application_statuses s ON a.status_id = s.id;
