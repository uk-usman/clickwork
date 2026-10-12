# Database

CircleWork Africa uses MySQL or MariaDB with InnoDB, `utf8mb4` and foreign keys.

## Files

| File | Purpose |
| --- | --- |
| `schema.sql` | Complete schema. Creates 33 tables, indexes, foreign keys and constraints. |
| `seeders/bootstrap.php` | Shared command-line bootstrap for the seeders. |
| `seeders/seed_categories.php` | Seeds 10 categories, 57 skills, 3 subscription plans and default site settings. Safe to re-run. |
| `seeders/create_initial_admin.php` | Protected command-line utility that creates or promotes the first administrator. **Remove or disable after deployment.** |

## Tables

`users`, `user_profiles`, `categories`, `skills`, `user_skills`, `portfolio_items`,
`projects`, `project_skills`, `proposals`, `contracts`, `contract_versions`,
`contract_acceptances`, `contract_events`, `deliverables`, `deliverable_files`,
`messages`, `payment_records`, `payment_evidence`, `subscription_plans`,
`subscriptions`, `subscription_payments`, `featured_listings`, `reviews`,
`notifications`, `reports`, `disputes`, `dispute_messages`, `site_settings`,
`admin_announcements`, `audit_logs`, `password_reset_tokens`,
`email_verification_tokens`, `login_attempts`.

## Conventions

- **Engine** InnoDB, **charset** `utf8mb4`, **collation** `utf8mb4_unicode_ci`.
- **Primary keys** `BIGINT UNSIGNED AUTO_INCREMENT` named `id`.
- **Timestamps** `DATETIME` in UTC. The PDO connection sets `time_zone = '+00:00'`,
  so `UTC_TIMESTAMP()` and `CURRENT_TIMESTAMP` are consistent. Times are displayed
  in `Africa/Lagos`.
- **Money** always `DECIMAL`. Never `FLOAT` or `DOUBLE`.
- **Deletes** financial and contract records are protected with `ON DELETE RESTRICT`
  and are never cascade-deleted when an account closes. Only content that belongs
  to a single user (profiles, portfolio items, notifications, tokens) cascades.
- **Uniqueness** enforced in the database for duplicate emails, duplicate proposals
  (`project_id`, `freelancer_id`), duplicate reviews (`contract_id`, `reviewer_id`),
  duplicate acceptances (`contract_id`, `version`, `user_id`) and one contract per
  proposal.

## Import

```bash
mysql -u root -p -e "CREATE DATABASE circlework CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p circlework < database/schema.sql
```

Or import `database/schema.sql` through phpMyAdmin.

## Seed

```bash
php database/seeders/seed_categories.php
php database/seeders/create_initial_admin.php --email=you@example.com --name="Your Name"
```

Run `create_initial_admin.php` without `--password` to be prompted with hidden input.
Administrator passwords must be at least 12 characters and contain a letter and a number.

## Re-running the schema

`schema.sql` starts with `DROP TABLE IF EXISTS` statements and disables foreign key
checks while it rebuilds. **Importing it destroys existing data.** Never run it against
a database that holds live records.

## Backups

```bash
mysqldump -u root -p --single-transaction --routines --triggers circlework > circlework-backup-$(date +%F).sql
```

Restore with `mysql -u root -p circlework < circlework-backup-YYYY-MM-DD.sql`.
