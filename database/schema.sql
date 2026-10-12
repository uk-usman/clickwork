-- ===========================================================================
-- CircleWork Africa — MySQL / MariaDB schema
-- Engine: InnoDB | Charset: utf8mb4 | Timestamps stored in UTC
--
-- Import with:
--   mysql -u root -p circlework < database/schema.sql
-- or through phpMyAdmin > Import.
--
-- Money columns use DECIMAL (never FLOAT/DOUBLE).
-- Financial and contract records are never cascade-deleted.
-- ===========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `admin_announcements`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `dispute_messages`;
DROP TABLE IF EXISTS `disputes`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `subscription_payments`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `subscription_plans`;
DROP TABLE IF EXISTS `featured_listings`;
DROP TABLE IF EXISTS `payment_evidence`;
DROP TABLE IF EXISTS `payment_records`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `deliverable_files`;
DROP TABLE IF EXISTS `deliverables`;
DROP TABLE IF EXISTS `contract_events`;
DROP TABLE IF EXISTS `contract_acceptances`;
DROP TABLE IF EXISTS `contract_versions`;
DROP TABLE IF EXISTS `contracts`;
DROP TABLE IF EXISTS `proposals`;
DROP TABLE IF EXISTS `project_skills`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `portfolio_items`;
DROP TABLE IF EXISTS `user_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `email_verification_tokens`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------------
-- 1. users
-- ---------------------------------------------------------------------------
CREATE TABLE `users` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(150)    NOT NULL,
    `email`              VARCHAR(190)    NOT NULL,
    `password_hash`      VARCHAR(255)    NOT NULL,
    `role`               ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user',
    `status`             ENUM('active','pending','suspended','closed') NOT NULL DEFAULT 'active',
    `default_mode`       ENUM('client','freelancer') NOT NULL DEFAULT 'client',
    `phone`              VARCHAR(30)     DEFAULT NULL,
    `email_verified_at`  DATETIME        DEFAULT NULL,
    `last_login_at`      DATETIME        DEFAULT NULL,
    `last_login_ip`      VARCHAR(45)     DEFAULT NULL,
    `suspended_at`       DATETIME        DEFAULT NULL,
    `suspension_reason`  VARCHAR(500)    DEFAULT NULL,
    `closed_at`          DATETIME        DEFAULT NULL,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. user_profiles
-- ---------------------------------------------------------------------------
CREATE TABLE `user_profiles` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`              BIGINT UNSIGNED NOT NULL,
    `headline`             VARCHAR(180)    DEFAULT NULL,
    `bio`                  TEXT            DEFAULT NULL,
    `avatar_path`          VARCHAR(255)    DEFAULT NULL,
    `country`              VARCHAR(80)     NOT NULL DEFAULT 'Nigeria',
    `state`                VARCHAR(80)     DEFAULT NULL,
    `city`                 VARCHAR(80)     DEFAULT NULL,
    `timezone`             VARCHAR(64)     NOT NULL DEFAULT 'Africa/Lagos',
    `website`              VARCHAR(255)    DEFAULT NULL,
    -- Client / business information
    `company_name`         VARCHAR(180)    DEFAULT NULL,
    `company_registered`   TINYINT(1)      NOT NULL DEFAULT 0,
    `company_registration_number` VARCHAR(60) DEFAULT NULL,
    `company_size`         VARCHAR(40)     DEFAULT NULL,
    `company_industry`     VARCHAR(120)    DEFAULT NULL,
    `company_description`  TEXT            DEFAULT NULL,
    -- Freelancer information
    `freelancer_title`     VARCHAR(150)    DEFAULT NULL,
    `freelancer_overview`  TEXT            DEFAULT NULL,
    `experience_years`     TINYINT UNSIGNED DEFAULT NULL,
    `hourly_rate`          DECIMAL(14,2)   DEFAULT NULL,
    `rate_currency`        CHAR(3)         NOT NULL DEFAULT 'NGN',
    `availability`         ENUM('available','part_time','busy','unavailable') NOT NULL DEFAULT 'available',
    `languages`            VARCHAR(255)    DEFAULT NULL,
    `profile_visibility`   ENUM('public','private') NOT NULL DEFAULT 'public',
    `rating_average`       DECIMAL(3,2)    NOT NULL DEFAULT 0.00,
    `rating_count`         INT UNSIGNED    NOT NULL DEFAULT 0,
    `completed_contracts`  INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_profiles_user` (`user_id`),
    KEY `idx_profiles_visibility` (`profile_visibility`),
    KEY `idx_profiles_city_state` (`state`, `city`),
    CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. categories
-- ---------------------------------------------------------------------------
CREATE TABLE `categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120)    NOT NULL,
    `slug`        VARCHAR(140)    NOT NULL,
    `description` VARCHAR(500)    DEFAULT NULL,
    `icon`        VARCHAR(60)     NOT NULL DEFAULT 'bi-briefcase',
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order`  INT             NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`),
    KEY `idx_categories_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. skills
-- ---------------------------------------------------------------------------
CREATE TABLE `skills` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED DEFAULT NULL,
    `name`        VARCHAR(120)    NOT NULL,
    `slug`        VARCHAR(140)    NOT NULL,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_skills_slug` (`slug`),
    KEY `idx_skills_category` (`category_id`),
    KEY `idx_skills_active` (`is_active`),
    CONSTRAINT `fk_skills_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. user_skills
-- ---------------------------------------------------------------------------
CREATE TABLE `user_skills` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          BIGINT UNSIGNED NOT NULL,
    `skill_id`         BIGINT UNSIGNED NOT NULL,
    `proficiency`      ENUM('beginner','intermediate','expert') NOT NULL DEFAULT 'intermediate',
    `years_experience` TINYINT UNSIGNED DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_skills` (`user_id`, `skill_id`),
    KEY `idx_user_skills_skill` (`skill_id`),
    CONSTRAINT `fk_user_skills_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_skills_skill` FOREIGN KEY (`skill_id`)
        REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. portfolio_items
-- ---------------------------------------------------------------------------
CREATE TABLE `portfolio_items` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `category_id`  BIGINT UNSIGNED DEFAULT NULL,
    `title`        VARCHAR(180)    NOT NULL,
    `description`  TEXT            DEFAULT NULL,
    `project_url`  VARCHAR(255)    DEFAULT NULL,
    `image_path`   VARCHAR(255)    DEFAULT NULL,
    `completed_on` DATE            DEFAULT NULL,
    `is_visible`   TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order`   INT             NOT NULL DEFAULT 0,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_portfolio_user` (`user_id`, `is_visible`),
    KEY `idx_portfolio_category` (`category_id`),
    CONSTRAINT `fk_portfolio_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_portfolio_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. projects
-- ---------------------------------------------------------------------------
CREATE TABLE `projects` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`            VARCHAR(30)     NOT NULL,
    `client_id`            BIGINT UNSIGNED NOT NULL,
    `category_id`          BIGINT UNSIGNED DEFAULT NULL,
    `title`                VARCHAR(200)    NOT NULL,
    `slug`                 VARCHAR(220)    NOT NULL,
    `description`          MEDIUMTEXT      NOT NULL,
    `required_skills_text` VARCHAR(500)    DEFAULT NULL,
    `budget_type`          ENUM('fixed','hourly') NOT NULL DEFAULT 'fixed',
    `budget_min`           DECIMAL(14,2)   DEFAULT NULL,
    `budget_max`           DECIMAL(14,2)   DEFAULT NULL,
    `currency`             CHAR(3)         NOT NULL DEFAULT 'NGN',
    `deadline`             DATE            DEFAULT NULL,
    `expected_deliverables` TEXT           DEFAULT NULL,
    `visibility`           ENUM('public','private') NOT NULL DEFAULT 'public',
    `status`               ENUM('draft','pending','published','rejected','in_progress','completed','cancelled','closed')
                           NOT NULL DEFAULT 'draft',
    `is_featured`          TINYINT(1)      NOT NULL DEFAULT 0,
    `featured_until`       DATETIME        DEFAULT NULL,
    `moderation_note`      VARCHAR(500)    DEFAULT NULL,
    `moderated_by`         BIGINT UNSIGNED DEFAULT NULL,
    `moderated_at`         DATETIME        DEFAULT NULL,
    `published_at`         DATETIME        DEFAULT NULL,
    `proposal_count`       INT UNSIGNED    NOT NULL DEFAULT 0,
    `views_count`          INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_projects_reference` (`reference`),
    UNIQUE KEY `uq_projects_slug` (`slug`),
    KEY `idx_projects_status_published` (`status`, `published_at`),
    KEY `idx_projects_client` (`client_id`, `status`),
    KEY `idx_projects_category` (`category_id`, `status`),
    KEY `idx_projects_featured` (`is_featured`, `featured_until`),
    KEY `idx_projects_budget` (`budget_type`, `budget_min`, `budget_max`),
    KEY `idx_projects_deadline` (`deadline`),
    FULLTEXT KEY `ft_projects_search` (`title`, `description`),
    CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_projects_category` FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_projects_moderator` FOREIGN KEY (`moderated_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8. project_skills
-- ---------------------------------------------------------------------------
CREATE TABLE `project_skills` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` BIGINT UNSIGNED NOT NULL,
    `skill_id`   BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_project_skills` (`project_id`, `skill_id`),
    KEY `idx_project_skills_skill` (`skill_id`),
    CONSTRAINT `fk_project_skills_project` FOREIGN KEY (`project_id`)
        REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_project_skills_skill` FOREIGN KEY (`skill_id`)
        REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. proposals
-- ---------------------------------------------------------------------------
CREATE TABLE `proposals` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`      VARCHAR(30)     NOT NULL,
    `project_id`     BIGINT UNSIGNED NOT NULL,
    `freelancer_id`  BIGINT UNSIGNED NOT NULL,
    `cover_letter`   MEDIUMTEXT      NOT NULL,
    `bid_amount`     DECIMAL(14,2)   NOT NULL,
    `currency`       CHAR(3)         NOT NULL DEFAULT 'NGN',
    `estimated_days` SMALLINT UNSIGNED NOT NULL,
    `status`         ENUM('submitted','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
    `client_note`    VARCHAR(500)    DEFAULT NULL,
    `submitted_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `decided_at`     DATETIME        DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_proposals_reference` (`reference`),
    UNIQUE KEY `uq_proposals_project_freelancer` (`project_id`, `freelancer_id`),
    KEY `idx_proposals_project_status` (`project_id`, `status`),
    KEY `idx_proposals_freelancer` (`freelancer_id`, `status`),
    CONSTRAINT `fk_proposals_project` FOREIGN KEY (`project_id`)
        REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_proposals_freelancer` FOREIGN KEY (`freelancer_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 10. contracts
-- ---------------------------------------------------------------------------
CREATE TABLE `contracts` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`          VARCHAR(30)     NOT NULL,
    `project_id`         BIGINT UNSIGNED NOT NULL,
    `proposal_id`        BIGINT UNSIGNED NOT NULL,
    `client_id`          BIGINT UNSIGNED NOT NULL,
    `freelancer_id`      BIGINT UNSIGNED NOT NULL,
    `title`              VARCHAR(200)    NOT NULL,
    `scope`              MEDIUMTEXT      NOT NULL,
    `deliverables`       MEDIUMTEXT      NOT NULL,
    `amount`             DECIMAL(14,2)   NOT NULL,
    `currency`           CHAR(3)         NOT NULL DEFAULT 'NGN',
    `deadline`           DATE            DEFAULT NULL,
    `revision_terms`     VARCHAR(500)    NOT NULL DEFAULT 'Two rounds of reasonable revisions included.',
    `payment_terms`      VARCHAR(500)    NOT NULL DEFAULT 'Direct payment agreed between client and freelancer.',
    `cancellation_terms` VARCHAR(500)    NOT NULL DEFAULT 'Either party may request cancellation before work starts.',
    `dispute_terms`      VARCHAR(500)    NOT NULL DEFAULT 'Disputes are recorded on the platform and reviewed by an administrator.',
    `current_version`    INT UNSIGNED    NOT NULL DEFAULT 1,
    `status`             ENUM('awaiting_acceptance','active','in_progress','deliverable_submitted','revision_requested','completed','cancelled','disputed')
                         NOT NULL DEFAULT 'awaiting_acceptance',
    `client_accepted_at`     DATETIME    DEFAULT NULL,
    `freelancer_accepted_at` DATETIME    DEFAULT NULL,
    `activated_at`       DATETIME        DEFAULT NULL,
    `completed_at`       DATETIME        DEFAULT NULL,
    `cancelled_at`       DATETIME        DEFAULT NULL,
    `cancelled_by`       BIGINT UNSIGNED DEFAULT NULL,
    `cancellation_reason` VARCHAR(500)   DEFAULT NULL,
    `created_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contracts_reference` (`reference`),
    UNIQUE KEY `uq_contracts_proposal` (`proposal_id`),
    KEY `idx_contracts_client` (`client_id`, `status`),
    KEY `idx_contracts_freelancer` (`freelancer_id`, `status`),
    KEY `idx_contracts_project` (`project_id`),
    KEY `idx_contracts_status` (`status`),
    CONSTRAINT `fk_contracts_project` FOREIGN KEY (`project_id`)
        REFERENCES `projects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_contracts_proposal` FOREIGN KEY (`proposal_id`)
        REFERENCES `proposals` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_contracts_client` FOREIGN KEY (`client_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_contracts_freelancer` FOREIGN KEY (`freelancer_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 11. contract_versions  (immutable snapshots of accepted terms)
-- ---------------------------------------------------------------------------
CREATE TABLE `contract_versions` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id`    BIGINT UNSIGNED NOT NULL,
    `version`        INT UNSIGNED    NOT NULL,
    `terms_snapshot` MEDIUMTEXT      NOT NULL,
    `terms_hash`     CHAR(64)        NOT NULL,
    `change_summary` VARCHAR(500)    DEFAULT NULL,
    `created_by`     BIGINT UNSIGNED DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contract_versions` (`contract_id`, `version`),
    KEY `idx_contract_versions_hash` (`terms_hash`),
    CONSTRAINT `fk_contract_versions_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_contract_versions_user` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 12. contract_acceptances
-- ---------------------------------------------------------------------------
CREATE TABLE `contract_acceptances` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id`       BIGINT UNSIGNED NOT NULL,
    `contract_version_id` BIGINT UNSIGNED NOT NULL,
    `version`           INT UNSIGNED    NOT NULL,
    `user_id`           BIGINT UNSIGNED NOT NULL,
    `party_role`        ENUM('client','freelancer') NOT NULL,
    `terms_hash`        CHAR(64)        NOT NULL,
    `accepted_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_address`        VARCHAR(45)     DEFAULT NULL,
    `user_agent`        VARCHAR(255)    DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contract_acceptance` (`contract_id`, `version`, `user_id`),
    KEY `idx_acceptances_version` (`contract_version_id`),
    CONSTRAINT `fk_acceptances_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_acceptances_version` FOREIGN KEY (`contract_version_id`)
        REFERENCES `contract_versions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_acceptances_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 13. contract_events
-- ---------------------------------------------------------------------------
CREATE TABLE `contract_events` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id` BIGINT UNSIGNED NOT NULL,
    `actor_id`    BIGINT UNSIGNED DEFAULT NULL,
    `event_type`  VARCHAR(60)     NOT NULL,
    `description` VARCHAR(500)    NOT NULL,
    `metadata`    JSON            DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contract_events_contract` (`contract_id`, `created_at`),
    KEY `idx_contract_events_type` (`event_type`),
    CONSTRAINT `fk_contract_events_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_contract_events_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 14. deliverables
-- ---------------------------------------------------------------------------
CREATE TABLE `deliverables` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id`   BIGINT UNSIGNED NOT NULL,
    `submitted_by`  BIGINT UNSIGNED NOT NULL,
    `version`       INT UNSIGNED    NOT NULL DEFAULT 1,
    `message`       TEXT            DEFAULT NULL,
    `status`        ENUM('submitted','approved','revision_requested','superseded') NOT NULL DEFAULT 'submitted',
    `reviewed_by`   BIGINT UNSIGNED DEFAULT NULL,
    `reviewed_at`   DATETIME        DEFAULT NULL,
    `review_note`   VARCHAR(1000)   DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_deliverables_contract_version` (`contract_id`, `version`),
    KEY `idx_deliverables_status` (`status`),
    CONSTRAINT `fk_deliverables_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_deliverables_submitter` FOREIGN KEY (`submitted_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_deliverables_reviewer` FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 15. deliverable_files  (private storage only)
-- ---------------------------------------------------------------------------
CREATE TABLE `deliverable_files` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `deliverable_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_by`    BIGINT UNSIGNED NOT NULL,
    `original_name`  VARCHAR(255)    NOT NULL,
    `stored_name`    VARCHAR(255)    NOT NULL,
    `mime_type`      VARCHAR(120)    NOT NULL,
    `size_bytes`     BIGINT UNSIGNED NOT NULL,
    `checksum_sha256` CHAR(64)       NOT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_deliverable_files_stored` (`stored_name`),
    KEY `idx_deliverable_files_deliverable` (`deliverable_id`),
    CONSTRAINT `fk_deliverable_files_deliverable` FOREIGN KEY (`deliverable_id`)
        REFERENCES `deliverables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_deliverable_files_user` FOREIGN KEY (`uploaded_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 16. messages  (project / contract conversation threads)
-- ---------------------------------------------------------------------------
CREATE TABLE `messages` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id`  BIGINT UNSIGNED DEFAULT NULL,
    `project_id`   BIGINT UNSIGNED DEFAULT NULL,
    `sender_id`    BIGINT UNSIGNED NOT NULL,
    `recipient_id` BIGINT UNSIGNED NOT NULL,
    `body`         TEXT            NOT NULL,
    `read_at`      DATETIME        DEFAULT NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_messages_contract` (`contract_id`, `created_at`),
    KEY `idx_messages_project` (`project_id`, `created_at`),
    KEY `idx_messages_recipient_unread` (`recipient_id`, `read_at`),
    KEY `idx_messages_sender` (`sender_id`),
    CONSTRAINT `fk_messages_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_messages_project` FOREIGN KEY (`project_id`)
        REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 17. payment_records  (direct payments between users — never held by us)
-- ---------------------------------------------------------------------------
CREATE TABLE `payment_records` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`       VARCHAR(30)     NOT NULL,
    `contract_id`     BIGINT UNSIGNED NOT NULL,
    `payer_id`        BIGINT UNSIGNED NOT NULL,
    `payee_id`        BIGINT UNSIGNED NOT NULL,
    `amount`          DECIMAL(14,2)   NOT NULL,
    `currency`        CHAR(3)         NOT NULL DEFAULT 'NGN',
    `method`          ENUM('bank_transfer','cash','mobile_money','card_transfer','other') NOT NULL DEFAULT 'bank_transfer',
    `payer_reference` VARCHAR(120)    DEFAULT NULL,
    `paid_on`         DATE            DEFAULT NULL,
    `status`          ENUM('reported','confirmed','disputed','reversed') NOT NULL DEFAULT 'reported',
    `note`            VARCHAR(500)    DEFAULT NULL,
    `reported_by`     BIGINT UNSIGNED NOT NULL,
    `confirmed_by`    BIGINT UNSIGNED DEFAULT NULL,
    `confirmed_at`    DATETIME        DEFAULT NULL,
    `disputed_by`     BIGINT UNSIGNED DEFAULT NULL,
    `disputed_at`     DATETIME        DEFAULT NULL,
    `dispute_reason`  VARCHAR(500)    DEFAULT NULL,
    `admin_note`      VARCHAR(500)    DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_records_reference` (`reference`),
    KEY `idx_payment_records_contract` (`contract_id`, `status`),
    KEY `idx_payment_records_status` (`status`, `created_at`),
    KEY `idx_payment_records_payer` (`payer_id`),
    KEY `idx_payment_records_payee` (`payee_id`),
    CONSTRAINT `fk_payment_records_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_records_payer` FOREIGN KEY (`payer_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_records_payee` FOREIGN KEY (`payee_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_records_reporter` FOREIGN KEY (`reported_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_records_confirmer` FOREIGN KEY (`confirmed_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 18. payment_evidence  (private storage only)
-- ---------------------------------------------------------------------------
CREATE TABLE `payment_evidence` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_record_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_by`       BIGINT UNSIGNED NOT NULL,
    `original_name`     VARCHAR(255)    NOT NULL,
    `stored_name`       VARCHAR(255)    NOT NULL,
    `mime_type`         VARCHAR(120)    NOT NULL,
    `size_bytes`        BIGINT UNSIGNED NOT NULL,
    `checksum_sha256`   CHAR(64)        NOT NULL,
    `note`              VARCHAR(255)    DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payment_evidence_stored` (`stored_name`),
    KEY `idx_payment_evidence_record` (`payment_record_id`),
    CONSTRAINT `fk_payment_evidence_record` FOREIGN KEY (`payment_record_id`)
        REFERENCES `payment_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_evidence_user` FOREIGN KEY (`uploaded_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 19. subscription_plans
-- ---------------------------------------------------------------------------
CREATE TABLE `subscription_plans` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`           VARCHAR(60)     NOT NULL,
    `name`           VARCHAR(120)    NOT NULL,
    `audience`       ENUM('client','freelancer','both') NOT NULL DEFAULT 'both',
    `price`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `currency`       CHAR(3)         NOT NULL DEFAULT 'NGN',
    `billing_period` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    `description`    VARCHAR(500)    DEFAULT NULL,
    `features`       JSON            DEFAULT NULL,
    `limits`         JSON            DEFAULT NULL,
    `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `is_free`        TINYINT(1)      NOT NULL DEFAULT 0,
    `sort_order`     INT             NOT NULL DEFAULT 0,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subscription_plans_code` (`code`),
    KEY `idx_plans_audience_active` (`audience`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 20. subscriptions
-- ---------------------------------------------------------------------------
CREATE TABLE `subscriptions` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          BIGINT UNSIGNED NOT NULL,
    `plan_id`          BIGINT UNSIGNED NOT NULL,
    `status`           ENUM('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
    `started_at`       DATETIME        DEFAULT NULL,
    `expires_at`       DATETIME        DEFAULT NULL,
    `cancelled_at`     DATETIME        DEFAULT NULL,
    `verified_by`      BIGINT UNSIGNED DEFAULT NULL,
    `verification_note` VARCHAR(500)   DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_subscriptions_user_status` (`user_id`, `status`),
    KEY `idx_subscriptions_expiry` (`status`, `expires_at`),
    CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`)
        REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_subscriptions_verifier` FOREIGN KEY (`verified_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 21. subscription_payments  (manually verified — never automatic)
-- ---------------------------------------------------------------------------
CREATE TABLE `subscription_payments` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id`   BIGINT UNSIGNED NOT NULL,
    `user_id`           BIGINT UNSIGNED NOT NULL,
    `plan_id`           BIGINT UNSIGNED NOT NULL,
    `amount`            DECIMAL(12,2)   NOT NULL,
    `currency`          CHAR(3)         NOT NULL DEFAULT 'NGN',
    `method`            ENUM('bank_transfer','cash','mobile_money','card_transfer','other') NOT NULL DEFAULT 'bank_transfer',
    `payer_reference`   VARCHAR(120)    DEFAULT NULL,
    `paid_at`           DATE            DEFAULT NULL,
    `status`            ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    `recorded_by`       BIGINT UNSIGNED NOT NULL,
    `verified_by`       BIGINT UNSIGNED DEFAULT NULL,
    `verified_at`       DATETIME        DEFAULT NULL,
    `verification_note` VARCHAR(500)    DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sub_payments_subscription` (`subscription_id`),
    KEY `idx_sub_payments_status` (`status`, `created_at`),
    CONSTRAINT `fk_sub_payments_subscription` FOREIGN KEY (`subscription_id`)
        REFERENCES `subscriptions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_payments_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_payments_plan` FOREIGN KEY (`plan_id`)
        REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_payments_recorder` FOREIGN KEY (`recorded_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_payments_verifier` FOREIGN KEY (`verified_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 21b. featured_listings  (paid featured project placement)
-- ---------------------------------------------------------------------------
CREATE TABLE `featured_listings` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id`     BIGINT UNSIGNED NOT NULL,
    `purchased_by`   BIGINT UNSIGNED NOT NULL,
    `amount`         DECIMAL(12,2)   NOT NULL,
    `currency`       CHAR(3)         NOT NULL DEFAULT 'NGN',
    `duration_days`  SMALLINT UNSIGNED NOT NULL,
    `starts_at`      DATETIME        DEFAULT NULL,
    `ends_at`        DATETIME        DEFAULT NULL,
    `status`         ENUM('pending','active','expired','rejected') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('bank_transfer','cash','mobile_money','card_transfer','other') NOT NULL DEFAULT 'bank_transfer',
    `payer_reference` VARCHAR(120)   DEFAULT NULL,
    `recorded_by`    BIGINT UNSIGNED NOT NULL,
    `verified_by`    BIGINT UNSIGNED DEFAULT NULL,
    `verified_at`    DATETIME        DEFAULT NULL,
    `admin_note`     VARCHAR(500)    DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_featured_project` (`project_id`, `status`),
    KEY `idx_featured_window` (`status`, `ends_at`),
    CONSTRAINT `fk_featured_project` FOREIGN KEY (`project_id`)
        REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_featured_user` FOREIGN KEY (`purchased_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_featured_recorder` FOREIGN KEY (`recorded_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_featured_verifier` FOREIGN KEY (`verified_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 22. reviews
-- ---------------------------------------------------------------------------
CREATE TABLE `reviews` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contract_id`     BIGINT UNSIGNED NOT NULL,
    `reviewer_id`     BIGINT UNSIGNED NOT NULL,
    `reviewee_id`     BIGINT UNSIGNED NOT NULL,
    `reviewer_role`   ENUM('client','freelancer') NOT NULL,
    `rating`          TINYINT UNSIGNED NOT NULL,
    `comment`         VARCHAR(2000)   DEFAULT NULL,
    `status`          ENUM('published','hidden','removed') NOT NULL DEFAULT 'published',
    `moderation_note` VARCHAR(500)    DEFAULT NULL,
    `moderated_by`    BIGINT UNSIGNED DEFAULT NULL,
    `moderated_at`    DATETIME        DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reviews_contract_reviewer` (`contract_id`, `reviewer_id`),
    KEY `idx_reviews_reviewee_status` (`reviewee_id`, `status`),
    KEY `idx_reviews_status` (`status`),
    CONSTRAINT `fk_reviews_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_reviewee` FOREIGN KEY (`reviewee_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_moderator` FOREIGN KEY (`moderated_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Note: 'reviewer must not equal reviewee' is enforced in the application layer
-- (ReviewService) because MariaDB rejects cross-column CHECK expressions.

-- ---------------------------------------------------------------------------
-- 23. notifications
-- ---------------------------------------------------------------------------
CREATE TABLE `notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `type`       VARCHAR(60)     NOT NULL,
    `title`      VARCHAR(180)    NOT NULL,
    `body`       VARCHAR(500)    DEFAULT NULL,
    `link`       VARCHAR(255)    DEFAULT NULL,
    `data`       JSON            DEFAULT NULL,
    `read_at`    DATETIME        DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_unread` (`user_id`, `read_at`, `created_at`),
    KEY `idx_notifications_type` (`type`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 24. reports
-- ---------------------------------------------------------------------------
CREATE TABLE `reports` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporter_id`     BIGINT UNSIGNED NOT NULL,
    `reportable_type` ENUM('user','project','review','message','payment','contract') NOT NULL,
    `reportable_id`   BIGINT UNSIGNED NOT NULL,
    `reason`          ENUM('spam','fraud','abuse','misleading','payment_issue','other') NOT NULL,
    `details`         VARCHAR(1500)   DEFAULT NULL,
    `status`          ENUM('open','in_review','resolved','dismissed') NOT NULL DEFAULT 'open',
    `assigned_to`     BIGINT UNSIGNED DEFAULT NULL,
    `resolution_note` VARCHAR(1000)   DEFAULT NULL,
    `resolved_by`     BIGINT UNSIGNED DEFAULT NULL,
    `resolved_at`     DATETIME        DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reports_status` (`status`, `created_at`),
    KEY `idx_reports_target` (`reportable_type`, `reportable_id`),
    KEY `idx_reports_reporter` (`reporter_id`),
    CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_assignee` FOREIGN KEY (`assigned_to`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_resolver` FOREIGN KEY (`resolved_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 25. disputes
-- ---------------------------------------------------------------------------
CREATE TABLE `disputes` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`       VARCHAR(30)     NOT NULL,
    `contract_id`     BIGINT UNSIGNED NOT NULL,
    `opened_by`       BIGINT UNSIGNED NOT NULL,
    `against_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `subject`         VARCHAR(200)    NOT NULL,
    `reason`          VARCHAR(2000)   NOT NULL,
    `status`          ENUM('open','in_review','resolved','closed') NOT NULL DEFAULT 'open',
    `resolution_note` VARCHAR(1500)   DEFAULT NULL,
    `resolved_by`     BIGINT UNSIGNED DEFAULT NULL,
    `resolved_at`     DATETIME        DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_disputes_reference` (`reference`),
    KEY `idx_disputes_status` (`status`, `created_at`),
    KEY `idx_disputes_contract` (`contract_id`),
    CONSTRAINT `fk_disputes_contract` FOREIGN KEY (`contract_id`)
        REFERENCES `contracts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_disputes_opener` FOREIGN KEY (`opened_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_disputes_against` FOREIGN KEY (`against_user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_disputes_resolver` FOREIGN KEY (`resolved_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 26. dispute_messages
-- ---------------------------------------------------------------------------
CREATE TABLE `dispute_messages` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dispute_id`  BIGINT UNSIGNED NOT NULL,
    `sender_id`   BIGINT UNSIGNED NOT NULL,
    `body`        VARCHAR(2000)   NOT NULL,
    `is_internal` TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dispute_messages_dispute` (`dispute_id`, `created_at`),
    CONSTRAINT `fk_dispute_messages_dispute` FOREIGN KEY (`dispute_id`)
        REFERENCES `disputes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dispute_messages_sender` FOREIGN KEY (`sender_id`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 27. site_settings
-- ---------------------------------------------------------------------------
CREATE TABLE `site_settings` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100)    NOT NULL,
    `setting_value` TEXT            DEFAULT NULL,
    `setting_group` VARCHAR(60)     NOT NULL DEFAULT 'general',
    `setting_type`  ENUM('string','text','boolean','integer','url','email') NOT NULL DEFAULT 'string',
    `is_public`     TINYINT(1)      NOT NULL DEFAULT 0,
    `updated_by`    BIGINT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_site_settings_key` (`setting_key`),
    KEY `idx_site_settings_group` (`setting_group`),
    CONSTRAINT `fk_site_settings_user` FOREIGN KEY (`updated_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 28. admin_announcements
-- ---------------------------------------------------------------------------
CREATE TABLE `admin_announcements` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(180)    NOT NULL,
    `body`         TEXT            NOT NULL,
    `audience`     ENUM('all','clients','freelancers','admins') NOT NULL DEFAULT 'all',
    `is_published` TINYINT(1)      NOT NULL DEFAULT 0,
    `published_at` DATETIME        DEFAULT NULL,
    `expires_at`   DATETIME        DEFAULT NULL,
    `created_by`   BIGINT UNSIGNED NOT NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_announcements_published` (`is_published`, `published_at`),
    KEY `idx_announcements_audience` (`audience`),
    CONSTRAINT `fk_announcements_creator` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 29. audit_logs
-- ---------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id`    BIGINT UNSIGNED DEFAULT NULL,
    `actor_role`  VARCHAR(30)     DEFAULT NULL,
    `action`      VARCHAR(80)     NOT NULL,
    `entity_type` VARCHAR(60)     DEFAULT NULL,
    `entity_id`   BIGINT UNSIGNED DEFAULT NULL,
    `description` VARCHAR(500)    NOT NULL,
    `metadata`    JSON            DEFAULT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `user_agent`  VARCHAR(255)    DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_actor` (`actor_id`, `created_at`),
    KEY `idx_audit_action` (`action`, `created_at`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 30. password_reset_tokens  (only a hash is stored, never the raw token)
-- ---------------------------------------------------------------------------
CREATE TABLE `password_reset_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64)        NOT NULL,
    `expires_at` DATETIME        NOT NULL,
    `used_at`    DATETIME        DEFAULT NULL,
    `ip_address` VARCHAR(45)     DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_reset_hash` (`token_hash`),
    KEY `idx_password_reset_user` (`user_id`, `used_at`),
    CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 31. email_verification_tokens
-- ---------------------------------------------------------------------------
CREATE TABLE `email_verification_tokens` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64)        NOT NULL,
    `expires_at` DATETIME        NOT NULL,
    `used_at`    DATETIME        DEFAULT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email_verification_hash` (`token_hash`),
    KEY `idx_email_verification_user` (`user_id`, `used_at`),
    CONSTRAINT `fk_email_verification_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 32. login_attempts  (server-side throttling; supports audit)
-- ---------------------------------------------------------------------------
CREATE TABLE `login_attempts` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`       VARCHAR(190)    NOT NULL,
    `ip_address`  VARCHAR(45)     NOT NULL,
    `successful`  TINYINT(1)      NOT NULL DEFAULT 0,
    `attempted_at` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_email` (`email`, `attempted_at`),
    KEY `idx_login_attempts_ip` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
