# CircleWork Africa

**Find Work. Hire Talent. Build Trust.**

A freelance marketplace for the Nigerian market, built by Circle World Technologies Africa.
Clients post projects, freelancers submit proposals, both parties accept versioned contract
terms, deliverables are submitted and reviewed, and payments made directly between users are
recorded honestly.

Built with **PHP 8.1+**, **MySQL/MariaDB**, **PDO**, **Bootstrap 5** and **vanilla JavaScript**.
No Composer. No framework. No build step.

---

## Contents

1. [Requirements](#1-requirements)
2. [What the platform does and does not do](#2-what-the-platform-does-and-does-not-do)
3. [Project structure](#3-project-structure)
4. [XAMPP setup](#4-xampp-setup)
5. [Database creation and import](#5-database-creation-and-import)
6. [Environment configuration](#6-environment-configuration)
7. [Apache configuration](#7-apache-configuration)
8. [Creating the first administrator](#8-creating-the-first-administrator)
9. [Seeding categories](#9-seeding-categories)
10. [Running the application](#10-running-the-application)
11. [Testing the core workflows](#11-testing-the-core-workflows)
12. [Running the automated tests](#12-running-the-automated-tests)
13. [Email configuration](#13-email-configuration)
14. [Local Bootstrap and Bootstrap Icons](#14-local-bootstrap-and-bootstrap-icons)
15. [Backing up the database](#15-backing-up-the-database)
16. [Deploying to shared hosting](#16-deploying-to-shared-hosting)
17. [Protecting private storage](#17-protecting-private-storage)
18. [Disabling debugging in production](#18-disabling-debugging-in-production)
19. [Security summary](#19-security-summary)
20. [Troubleshooting](#20-troubleshooting)
21. [Development checklist](#21-development-checklist)

---

## 1. Requirements

| Requirement | Notes |
| --- | --- |
| PHP | 8.1 or newer (tested on 8.3). Extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `openssl`, `session`. `gd` is optional. |
| MySQL / MariaDB | MySQL 5.7+ or MariaDB 10.4+. InnoDB required. |
| Web server | Apache with `mod_rewrite` (XAMPP is fine), or PHP's built-in server for local testing. |
| Composer | **Not required and not used.** |

---

## 2. What the platform does and does not do

**Does**

- Connect clients and freelancers, with moderation before a project becomes public.
- Generate a contract from an accepted proposal, with versioned terms.
- Record each party's acceptance with their account, a timestamp, the accepted version and a SHA-256 hash of the canonical terms.
- Version deliverables, support revision requests, and keep a contract event history.
- Record direct payments between users, with evidence, confirmation and dispute tracking.
- Track reports and disputes to a documented outcome, with an audit log.
- Enforce subscription plan limits on the server.

**Does not**

- Hold funds, operate escrow, or provide a stored-value wallet.
- Move money between users or pay freelancers automatically.
- Deduct commission from freelance earnings automatically.
- Verify bank transfers, or claim to have verified a payment it did not verify.
- Recover money transferred directly between users.
- Provide government certification, official verification or guaranteed legal protection.
- Require a paid API or payment gateway for the core MVP.

---

## 3. Project structure

```
circlework/
    app/
        Controllers/        HTTP controllers (public, dashboard, Admin/)
        Core/               Framework primitives: App, Router, Database, Session, Auth, View, Env, Logger
        Helpers/            functions.php (global helpers), Validator.php
        Middleware/         Auth, Guest, Csrf, Admin, SuperAdmin
        Models/             One class per table, all queries via prepared statements
        Services/           Business workflows (auth, projects, proposals, contracts, ...)
    config/                 config.php — loads .env from the project root
    database/
        schema.sql          33 tables
        seeders/            bootstrap.php, seed_categories.php, create_initial_admin.php
        README.md
    public/                 THE WEB ROOT
        index.php           Front controller
        .htaccess           Rewrite rules and security headers
        assets/css|js       Brand stylesheet and vanilla JS
    resources/views/        layouts/, components/, pages/, auth/, projects/, ...
    routes/web.php          All route definitions
    storage/                Private: documents/, uploads/, logs/ (each with .htaccess deny)
    tests/                  Lightweight test runner and cases
    .env.example            Template — copy to .env
    .htaccess               Safety net if the project root is exposed
```

---

## 4. XAMPP setup

1. Install XAMPP and start **Apache** and **MySQL** from the control panel.
2. Place the project in the XAMPP web directory:

   ```
   C:\xampp\htdocs\circlework\        (Windows)
   /Applications/XAMPP/htdocs/circlework/   (macOS)
   /opt/lampp/htdocs/circlework/      (Linux)
   ```

3. Confirm the PHP CLI is available: `php -v` should print 8.1 or newer. If not, add
   `C:\xampp\php` to your `PATH`.

---

## 5. Database creation and import

Create the database and import the schema:

```bash
mysql -u root -p -e "CREATE DATABASE circlework CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p circlework < database/schema.sql
```

In phpMyAdmin: **New** → database name `circlework`, collation `utf8mb4_unicode_ci`, then
**Import** → choose `database/schema.sql` → **Go**.

### Optional: a dedicated database user

Do not run the application as `root` on a real deployment.

```sql
CREATE USER 'circlework_user'@'localhost' IDENTIFIED BY 'a-strong-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON circlework.* TO 'circlework_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 6. Environment configuration

Copy the template and edit it. `.env` lives in the project root, **outside** `public/`.

```bash
cp .env.example .env
```

```ini
APP_NAME="CircleWork Africa"
APP_ENV=local
APP_DEBUG=true
APP_URL="http://localhost/circlework/public"
APP_TIMEZONE="Africa/Lagos"
APP_KEY="generate-a-64-character-random-hex-string"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=circlework
DB_USERNAME=circlework_user
DB_PASSWORD="a-strong-password"

SESSION_NAME=circlework_session
SESSION_SECURE_COOKIE=0
```

Generate `APP_KEY` with:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Never commit `.env`. It is already listed in `.gitignore`.

---

## 7. Apache configuration

### Recommended: a virtual host whose document root is `public/`

```apache
<VirtualHost *:80>
    ServerName circlework.local
    DocumentRoot "C:/xampp/htdocs/circlework/public"

    <Directory "C:/xampp/htdocs/circlework/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/circlework-error.log"
    CustomLog "logs/circlework-access.log" combined
</VirtualHost>
```

Add `127.0.0.1 circlework.local` to your hosts file, then set
`APP_URL="http://circlework.local"`.

### Alternative: sub-folder under htdocs

Browse to `http://localhost/circlework/public` and set
`APP_URL="http://localhost/circlework/public"`. The router strips the base path automatically.

`mod_rewrite` must be enabled. In XAMPP, uncomment `LoadModule rewrite_module modules/mod_rewrite.so`
in `httpd.conf` and set `AllowOverride All` for the htdocs directory.

---

## 8. Creating the first administrator

Administrator accounts are **never** created through public registration.

```bash
php database/seeders/create_initial_admin.php
```

You will be prompted for an email address, a name and a password (hidden input).
Alternatively, supply everything in one line:

```bash
php database/seeders/create_initial_admin.php \
  --email=you@example.com \
  --name="Your Name" \
  --password="A-Strong-Password-123"
```

Requirements: a valid email address, a password of at least 12 characters containing a
letter and a number. The utility also promotes an existing standard account if the email
already exists.

**After deployment, delete or rename `database/seeders/create_initial_admin.php`.**
Further administrators are promoted from the **Administration → Administrator accounts**
page by a super administrator.

---

## 9. Seeding categories

```bash
php database/seeders/seed_categories.php
```

This seeds 10 categories, 57 skills, 3 subscription plans (Free, Client Pro, Freelancer Pro)
and the default site settings. It is safe to run more than once — existing records are updated
rather than duplicated.

You can also manage categories, skills and plans from the administration area.

---

## 10. Running the application

**Through XAMPP:** open `http://localhost/circlework/public` (or your virtual host).

**With PHP's built-in server** (useful for a quick check):

```bash
php -S localhost:8000 -t public public/index.php
```

Then open `http://localhost:8000` and set `APP_URL="http://localhost:8000"` in `.env`.

---

## 11. Testing the core workflows

Register two accounts, or use one account in both modes.

1. **Client** → *Post a project*. Save as draft, then **Submit for moderation**.
2. **Administrator** → *Administration → Projects* → open the pending project → **Approve**.
3. **Freelancer** → *Browse Projects* → open the project → submit a proposal with a bid and delivery estimate.
4. **Client** → *My projects* → **Proposals** → shortlist, then **Accept and create contract**.
5. Both parties → open the contract → tick the confirmation box → **Record acceptance**.
   The contract becomes **active** only after both have accepted.
6. **Freelancer** → submit a deliverable with a note and up to three attachments.
7. **Client** → **Approve deliverable**, or **Request revision** with feedback.
8. Either party → *Payment records* → record a direct payment. It starts as **Reported**.
9. The other party → **Confirm payment**, or flag it as disputed.
10. **Client** → **Mark contract completed** (requires an approved deliverable).
11. Either party → **Leave a review** (1–5 stars plus a comment).
12. Try to open another user's contract, project or message thread — you should receive 403 or 404.

---

## 12. Running the automated tests

A lightweight runner is included. It needs no Composer and no test framework.

```bash
php tests/run.php
php tests/run.php --filter=Security     # one group
php tests/run.php --no-http             # skip end-to-end HTTP tests
```

The runner creates and rebuilds a separate database named `circlework_test` from
`database/schema.sql`, loads minimal fixtures, starts a temporary PHP server on port 8123 for
end-to-end tests, and stops it afterwards. **It never touches your development database.**

The database user in `.env` needs privileges on `circlework_test`:

```sql
CREATE DATABASE IF NOT EXISTS circlework_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON circlework_test.* TO 'circlework_user'@'localhost';
FLUSH PRIVILEGES;
```

Coverage: registration and login, duplicate emails, weak passwords, unauthorised dashboard and
admin access, object-level authorisation for contracts, projects, proposals, payments and
files, duplicate proposals, competing proposal awards, dual contract acceptance, contract
version changes, revision requests, payment ownership and confirmation rules, review
eligibility, upload restrictions, CSRF, SQL injection, XSS escaping, account suspension and
audit log creation.

---

## 13. Email configuration

`MAIL_DRIVER` supports three values:

| Driver | Behaviour |
| --- | --- |
| `log` | Default. Writes messages to `storage/logs/mail.log`. Nothing leaves the server. |
| `mail` | Uses PHP `mail()`. Works only when the host provides a working mail transport. |
| `smtp` | Direct SMTP over a socket. No external library required. |

SMTP example:

```ini
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="CircleWork Africa"
MAIL_SMTP_HOST="smtp.yourdomain.com"
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME="no-reply@yourdomain.com"
MAIL_SMTP_PASSWORD="your-smtp-password"
MAIL_SMTP_ENCRYPTION=tls
MAIL_SMTP_AUTH=1
```

SMTP credentials live in `.env`, are never committed and are never rendered in the browser.
Email is used for email verification, password reset and optional notifications.

---

## 14. Local Bootstrap and Bootstrap Icons

The layout loads local assets when present and falls back to a CDN otherwise.

To install them locally (recommended for offline use):

```
public/assets/vendor/bootstrap/css/bootstrap.min.css
public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js
public/assets/vendor/bootstrap-icons/bootstrap-icons.css
public/assets/vendor/bootstrap-icons/fonts/...
```

Download from the official Bootstrap and Bootstrap Icons releases and copy the files into
those paths. The helpers `bootstrap_css()`, `bootstrap_js()` and `bootstrap_icons()` in
`app/Helpers/functions.php` switch automatically once the files exist.

---

## 15. Backing up the database

```bash
mysqldump -u root -p --single-transaction --routines --triggers circlework > circlework-$(date +%F).sql
```

Restore:

```bash
mysql -u root -p circlework < circlework-YYYY-MM-DD.sql
```

Also back up `storage/documents/` and `storage/uploads/`, which hold private deliverables,
payment evidence, avatars and portfolio images. Those files are not in the database.

---

## 16. Deploying to shared hosting

1. Upload the project so that `public/` becomes the document root. Many hosts let you point
a domain at a sub-folder — use that so `app/`, `config/`, `database/`, `routes/` and
`storage/` are outside the web root.
2. If you cannot move the document root, rely on the root `.htaccess` (it denies `app/`,
   `config/`, `database/`, `resources/`, `routes/`, `storage/`, `tests/` and `.env`).
   Verify this with `curl -I https://yourdomain.com/.env` — it must return 403 or 404.
3. Create the database and import `database/schema.sql` through the host's phpMyAdmin.
4. Create `.env` with production values. Set `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL` to the real HTTPS URL and `SESSION_SECURE_COOKIE=1`.
5. Run the seeders, then **delete `database/seeders/create_initial_admin.php`**.
6. Ensure `storage/` is writable by PHP (usually `0755` for directories, `0640` for files).
7. Enable HTTPS and confirm the session cookie is sent with the `Secure` flag.

---

## 17. Protecting private storage

`storage/` must never be web-accessible.

- Each storage directory ships with an `.htaccess` containing `Require all denied`.
- Uploaded files are stored with randomised names (`YYYY/MM/<40 hex chars>.<ext>`) and are
  served only through permission-checked endpoints:
  - `/deliverable-files/{id}` — contract participants and administrators only.
  - `/payment-evidence/{id}` — payment parties and administrators only.
  - `/media/avatar/{path}` and `/media/portfolio/{path}` — public images, with the path
    resolved inside the uploads directory and the MIME type re-checked before sending.
- Deliverable and evidence downloads re-check the base directory with `realpath()` on every
  request, so path traversal is rejected.
- PHP execution in upload directories is prevented both by the `.htaccess` deny rules and by
  the upload allow-list, which refuses `.php`, `.phtml`, `.phar`, `.sh`, `.exe`, `.html`, `.svg`
  and similar extensions regardless of configuration.

If your host ignores `.htaccess`, move `storage/` outside the web root and update the
`storage` paths in `config/config.php`.

---

## 18. Disabling debugging in production

```ini
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=1
```

With `APP_DEBUG=false`:

- PHP notices, warnings and stack traces are never rendered in the browser.
- Users see the friendly `errors/500` page; AJAX endpoints receive a generic JSON error.
- Full detail is written to `storage/logs/app-YYYY-MM-DD.log` (outside the web root).
- `Strict-Transport-Security` is sent when `APP_ENV=production`.

Also ensure `display_errors` stays off in `php.ini` as a second layer of defence.

---

## 19. Security summary

| Control | Implementation |
| --- | --- |
| SQL injection | Every query uses PDO prepared statements; `ORDER BY` fragments are whitelisted. |
| Passwords | `password_hash()` / `password_verify()` with automatic rehashing. |
| CSRF | `CsrfMiddleware` on every state-changing route; tokens compared with `hash_equals()`. |
| XSS | All output escaped through the `e()` helper; JSON-encoded values are escaped too. |
| Sessions | `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode, ID regenerated on login. |
| Login throttling | Failed attempts stored per email and IP; lockout after `LOGIN_MAX_ATTEMPTS`. |
| Password reset | Random 32-byte token; only a SHA-256 hash is stored; single use; 60-minute expiry. |
| Email verification | Random 32-byte token; hashed at rest; single use; 48-hour expiry. |
| Authorisation | Role middleware plus object-level checks on every private resource. |
| Uploads | Content, MIME, extension and size validated; random server-side names; stored privately. |
| Path traversal | `realpath()` containment checks before any file read or delete. |
| Safe redirects | `Response::safeUrl()` blocks protocol-relative and external redirects. |
| Errors | Generic public messages; detail only in protected logs. |
| Audit trail | Sensitive actions recorded with actor, role, IP, user agent and timestamp. |
| Transactions | Proposal award, contract acceptance, versioning, deliverable submission and subscription activation. |
| Secrets | `.env` outside the web root, git-ignored; no credentials in code or the database. |

---

## 20. Troubleshooting

| Symptom | Cause and fix |
| --- | --- |
| Blank page or 500 | Check `storage/logs/app-*.log`. Temporarily set `APP_DEBUG=true` to see detail locally. |
| "Unable to connect to the database" | Wrong `DB_*` values, or MySQL is not running. Verify with `mysql -u user -p -e "SELECT 1"`. |
| Styles missing | `APP_URL` is wrong, so asset URLs 404. Set it to the real base URL. |
| "Page not found" on every route | `mod_rewrite` is disabled or `AllowOverride` is not `All`. |
| Session lost after login | Cookie path/domain mismatch. `APP_URL` and the browser URL must agree. |
| "Your session has expired" on a form | The CSRF token expired. Reload the page and resubmit. |
| Upload rejected | Check the extension, size limit (`UPLOAD_MAX_BYTES`) and PHP `upload_max_filesize` / `post_max_size`. |
| Permission denied writing logs | Make `storage/` and its subdirectories writable by the web server user. |
| Admin area returns 403 | The account role is `user`. Promote it with the CLI utility or from the administrators page. |
| Tests cannot create `circlework_test` | Grant privileges on `circlework_test.*` to the database user. |

---

## 21. Development checklist

**Phase 1 — Foundation** ✅ Workspace inspection, folder structure, front controller, router, PDO factory, 33-table schema, shared public and dashboard layouts, working homepage.

**Phase 2 — Authentication** ✅ Registration, login, logout, email verification, password reset, sessions, profiles, permission middleware.

**Phase 3 — Marketplace** ✅ Categories and skills, project creation, moderation, public listings, search and filters, freelancer profiles, portfolio, proposals.

**Phase 4 — Contracts** ✅ Proposal acceptance, contract creation, versioning, dual acceptance, event history, project status transitions.

**Phase 5 — Deliverables and communication** ✅ Deliverable submission, private downloads, revision requests, messaging, notifications.

**Phase 6 — Payments** ✅ Direct payment tracking, evidence, status history, disputes, permission checks.

**Phase 7 — Administration** ✅ Dashboard, user moderation, project moderation, categories and skills, contracts, payments, reports, disputes, subscriptions, settings, audit logs.

**Phase 8 — Revenue** ✅ Subscription plans, manual payment verification, featured listings, server-side feature limits, expiry, clear premium indicators.

**Phase 9 — Quality assurance** ✅ 80 automated tests passing, PHP syntax checks clean, permissions and critical workflows exercised, README and database documentation complete.

### Known limitations

- No payment gateway is integrated. Subscription and featured payments are verified manually by an administrator, and the interface says so plainly.
- Email delivery depends on the host. The default `log` driver writes to `storage/logs/mail.log`.
- Bootstrap and Bootstrap Icons load from a CDN unless local assets are installed (see section 14).
- The platform does not provide legal advice or guarantee that a contract is legally enforceable.

---

## Licence

See `LICENSE`. © Circle World Technologies Africa.
