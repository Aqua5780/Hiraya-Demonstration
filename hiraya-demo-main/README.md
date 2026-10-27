# Hiraya

Hiraya is the web-based student management system for **Good Shepherd of Taguig Colleges, Inc.** It digitizes student records, enrollment, class schedules, grades, announcements, and basic payment verification.

This repository is the implementation of the system described in the SE1 project paper. The current checkpoint is **CP3 — Final QA and Documentation**.

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | Vue 3.5, TypeScript 7, Inertia.js 2 |
| Styling | Tailwind CSS 4 |
| Database | SQLite 3 for local development; MySQL-ready migrations |
| Build tool | Vite 8 |
| Testing | PHPUnit 12 |

The portable PHP runtime and Composer are installed in `SE1/.tools`, outside this application folder. The application itself is entirely inside `SE1/hiraya`. The portable PHP configuration uses `SE1/.tools/tmp` as its upload directory so announcement and CSV uploads work on Windows.

## Run the system

From this folder:

```powershell
.\start.ps1
```

Or manually:

```powershell
..\.tools\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Then open <http://127.0.0.1:8000>.

The frontend is pre-built in `public/build`. After changing Vue/TypeScript/CSS files, rebuild it with:

```powershell
npm run build
```

To reset the database and demo data:

```powershell
..\.tools\php\php.exe artisan migrate:fresh --seed
```

## Demo accounts

All demo accounts use the password **`Password123!`**.

| Role | Email |
|---|---|
| Administrator | `admin@hiraya.test` |
| Registrar | `registrar@hiraya.test` |
| Faculty | `faculty@hiraya.test` |
| Student (enrolled) | `student@hiraya.test` |
| Student (not enrolled) | `newstudent@hiraya.test` |
| Finance | `finance@hiraya.test` |

The login page lists these accounts and fills the form when one is selected. This convenience is only shown while `HIRAYA_DEMO_MODE=true`. The checked-in `.env` enables it for local demonstrations; `.env.example` defaults it to `false` so new installs are not demo mode by accident.

## What is implemented

- Laravel 13 + Vue 3.5 + TypeScript 7 + Inertia 2 foundation
- Responsive application shell with role-specific navigation
- GSTC-inspired design system (navy, gold, laurel green) using the official school seal
- Login with password hashing, validation, and rate limiting
- Three failed login attempts → five-minute account lockout
- Forgot-password flow with hashed, expiring, single-use OTP
- Account activation with single-use invitation tokens
- Role-based access control for Admin, Registrar, Faculty, Student, and Finance
- Account settings for password changes only; name and email are read-only
- **Admin:** academic year management, programs, year levels, sections, subjects, class offerings with schedule and conflict checks, global announcements with attachments, account invitations and activation resending, manual student account creation with fixed `@gstc.edu.ph` emails, student password resets, and audit logs
- **Registrar:** student search, filtering, sorting, student profile view, record editing, and CSV import with template download and row-level validation
- **Faculty:** weekly class schedule, class list and roster, grade encoding with computed final ratings, and class announcements with attachments
- **Student:** online enrollment with schedule-conflict detection, class list and roster, weekly schedule, grades, announcements, payment submission with proof, payment history, and account ledger
- **Finance:** pending payment verification, payment rejection with remarks, walk-in payment posting, student ledgers, payment history, and printable receipts
- Seeded academic year, programs, year levels, faculty, sections, subjects, class offerings, schedules, students, enrollments, grades, payments, ledger entries, announcements, and FAQs
- Working dashboards for all five roles using real seeded data
- Searchable Help & FAQ page with role-aware articles
- Audit logging for authentication, password resets, enrollment, payments, and administrative actions
- 107 passing feature tests covering authentication, lockout, OTP reset, activation, RBAC, settings, admin operations, registrar records and imports, faculty grading, student enrollment and payments, finance verification, receipts, security headers, SQL-injection text handling, dashboards, OTP attempt limits, account deactivation, duplicate payment verification, neutral login/reset responses, registrar email restrictions, unsafe storage paths, production seeding guards, student account creation, admin password changes, and name locks

## What is intentionally deferred

The Core MVP was scoped to the academic and basic finance flows first. Five paper features are **staged as dormant modules but not connected** to the running application:

- Payroll draft, maker-checker approval, and payroll register
- Transaction reversal
- Formal reports and CSV exports
- Student inbox/messaging to administrators, registrar, and faculty (finance excluded)
- Automated student account provisioning. Planned administrator workflow, not finalized: surname + account number + `@gstc.edu.ph`. Account settings intentionally cannot change the email address, and imported student records do not automatically receive login accounts.

These modules have complete migrations, models, services, controllers, and Vue pages, but **no routes, navigation links, seeders, or tests are wired**. Their migrations live in `database/migrations/deferred/`, which `php artisan migrate` does not scan.

See **[docs/DEFERRED-FEATURES.md](docs/DEFERRED-FEATURES.md)** for the file map, exact routes, navigation entries, migration commands, seed examples, tests to add, and rollback steps.

The following paper options remain deferred and have no staged code:

- Admin “view as student” / Canvas-style impersonation (deferred design option)
- Canvas LMS integration (deferred design option)
- Student-number login in addition to email (deferred design option)
- Real SMTP delivery (demo mode logs email and shows OTP/activation links locally)
- Production MySQL deployment

The data model and service layer for enrollment and payments are connected to the Student and Finance screens in this checkpoint.

## Checkpoints

| Gate | Scope | Status |
|---|---|---|
| CP0 | Foundation, schema, seed data, auth, RBAC, shell, dashboards | Complete |
| CP1 | Admin, Registrar, and Faculty modules | Complete |
| CP2 | Student and Finance flows, Help/FAQ integration | Complete |
| CP3 | Testing, polish, documentation, final walkthrough | Complete |

## Testing and final QA

Run the full suite and build before a demonstration:

```powershell
..\.tools\php\php.exe artisan test --compact
npm run build
..\.tools\php\php.exe ..\.tools\composer.phar audit --locked
npm audit --omit=dev
```

Current results:

- **107 tests passed, 752 assertions**
- PHP dependency audit: no security advisories
- Node production dependency audit: 0 vulnerabilities
- Production frontend build passes
- Browser smoke tests completed for all five roles, the login flow, the payment verification flow, the printable receipt, and mobile layouts
- Focused security review completed for authentication, authorization, IDOR, upload/download handling, SQL injection, and payment integrity. This is not a substitute for an independent penetration test.

### Final walkthrough checklist

- **Admin** — create or activate an academic year, add a section/subject, create a class offering, invite an account, publish an announcement, open the audit log.
- **Registrar** — search and sort students, open a record, edit it, download the CSV template, import records.
- **Faculty** — open the weekly schedule, view a roster, encode and save grades, post a class announcement.
- **Student** — enroll, view schedule/classes/grades/announcements, submit a payment with proof, view the ledger.
- **Finance** — verify or reject a pending payment, post a walk-in payment, view the student ledger, open and print a receipt.
- **Account settings** — confirm email is read-only and name/password changes work.
- **Mobile** — check the login page, navigation drawer, and student payment page.

Reset the demo data before presenting:

```powershell
..\.tools\php\php.exe artisan migrate:fresh --seed
```

## Production deployment

Local development uses SQLite. For the institution's deployment, use MySQL and do not run the application as the database root user.

### 1. Create the database and least-privilege accounts

Local SQLite has no database-user accounts. For MySQL production, a ready-to-run script is in [`database/mysql-least-privilege.sql`](database/mysql-least-privilege.sql). Replace the password placeholders and run it as a MySQL administrator. It creates:

- `hiraya_app` — runtime account with only `SELECT`, `INSERT`, `UPDATE`, and `DELETE`
- `hiraya_migrate` — schema changes, used only while deploying migrations
- `hiraya_backup` — read/lock privileges for `mysqldump`

Then set `.env` to use `hiraya_app` for normal operation. Use `hiraya_migrate` only while running migrations, then switch back.

Why this matters: if the runtime credentials leak, the account still cannot drop tables, alter the schema, create users, read other databases, or use MySQL server-file features. It only has the data permissions the application actually needs. Never use `root`, `GRANT ALL`, `FILE`, `SUPER`, `PROCESS`, or `GRANT OPTION` for the application. If the database is on another host, use a restricted host/IP instead of `%`, and use TLS for the connection.

### 2. Production environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hiraya
DB_USERNAME=hiraya_app
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="Hiraya"

HIRAYA_DEMO_MODE=false
```

`HIRAYA_DEMO_MODE=false` hides the demo-account picker and the on-screen OTP/activation links. `DatabaseSeeder` refuses to run when `APP_ENV=production`; create real administrator and staff accounts through a controlled provisioning step.

### 3. Deploy and migrate

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# First install only: generate the application key.
php artisan key:generate --force

# Use the migration account for this step.
php artisan migrate --force

# Switch .env back to hiraya_app, then:
php artisan optimize
```

Ensure the web server document root is the `public/` directory and that `storage/` and `bootstrap/cache/` are writable by the PHP user. Keep `.env`, `database/`, `storage/`, and backups outside the public web root.

### 4. Production security checklist

- HTTPS only, with HSTS and secure cookies
- `APP_DEBUG=false`; the application forces this in production even if `.env` is misconfigured
- Runtime database account with only `SELECT`, `INSERT`, `UPDATE`, and `DELETE`
- Separate migration, backup, and monitoring accounts
- PHP: `display_errors=Off`, `display_startup_errors=Off`, `expose_php=Off`, `allow_url_include=Off`, and disable unused process functions such as `exec`, `shell_exec`, `system`, `proc_open`, and `popen`
- Web server: directory listing off, dotfiles denied, request/header timeouts set, and `public/` as the document root
- Trusted reverse proxy configuration so rate limiting sees the real client IP
- Rate limiting and login lockout monitoring at the web server or reverse proxy
- Malware scanning for uploaded PDFs/images, such as ClamAV
- Daily database and upload backups, with a tested restore procedure
- Patch PHP, Laravel, Node, and server dependencies regularly
- Run `composer audit` and `npm audit` after dependency updates
- Keep `HIRAYA_DEMO_MODE=false`
- Restrict access to the audit log and finance screens to authorized roles
- Log, alert on, and review 403/419/500 responses
- Enable server and application log rotation

### 5. Patch management

- `.github/workflows/security-audit.yml` runs `composer audit --locked` and `npm audit --omit=dev` every Monday, on pushes, and on pull requests. It activates when the repository is pushed to GitHub.
- `.github/dependabot.yml` opens weekly Composer/npm update pull requests and monthly GitHub Actions updates.
- On Windows, schedule `scripts/security-audit.ps1` weekly with Task Scheduler if the repository is not on GitHub.
- Review and apply security updates within 7 days; apply critical fixes within 24–48 hours.
- After each update, run `php artisan test --compact`, `npm run build`, and the audit script.

### 6. Upload storage

Payment proofs and announcement attachments are stored under `storage/app/private`. They are not in the public web root. If the institution uses object storage later, keep the same download-only policy and scan uploaded files before making them available.

## Database notes

The paper’s ERD is a useful starting point but does not contain tables for several flows it describes. The implementation keeps the core entities (`roles`, `users`, `students`, `faculty_profiles`, `academic_years`, `programs`, `year_levels`, `sections`, `subjects`, `class_offerings`, `class_schedules`, `enrollments`, `enrollment_details`, `grades`, `announcements`, `announcement_sections`, `audit_logs`) and adds the missing operational tables:

- `activation_tokens` for single-use account invitations
- `fee_structures` for program/year-level assessments
- `payments` for student submissions and finance verification
- `ledger_entries` for student balances and payment history
- `faq_categories` and `faqs` for the Help module

`class_schedules` is a separate table so a class offering can meet on multiple days, which the paper’s single `schedule_id` field cannot represent. `enrollment_details` connects a student’s enrollment to offered classes, and `grades` attaches to that enrollment detail.

Money is stored as `decimal(12,2)`. Grades use `decimal(5,2)`. The final rating is the weighted midterm/final average (50/50 by default), with 75 as the passing grade.

## Security notes

- **SQL injection:** database access uses Laravel's query builder and Eloquent, which bind user input as parameters. The only raw SQL expressions in the project are fixed aggregate/order expressions with no user input. Search sort columns and directions are whitelisted. The test suite includes a search request using an SQL-injection payload to verify that it is treated as plain text.
- **XSS:** Vue escapes rendered content by default. The only `v-html` usage is for Laravel-generated pagination labels, not user content.
- **CSRF:** forms use Laravel's CSRF protection through Inertia.
- **Passwords:** hashed with Laravel's password hasher; reset OTPs are hashed and expire.
- **Account enumeration:** failed logins use one generic message; password-reset requests always return the same neutral response, and verification errors are generic. Unknown accounts still perform a dummy bcrypt check to reduce timing differences. Email delivery time can still vary.
- **Access control:** role middleware plus per-record checks for student, faculty, finance, and payment documents.
- **Sensitive account changes:** names are read-only for all accounts; only administrators can change a student login email; admins can reset student passwords from Accounts & Activation; those actions are audited.
- **Student account emails:** generated from the student's name with a fixed `@gstc.edu.ph` domain; duplicates receive a numeric suffix; only students without accounts are listed for creation.
- **Session revocation:** deactivated accounts are logged out on their next request, and pending/inactive accounts cannot sign in.
- **Password reset:** OTPs are hashed, single-use, expire after 10 minutes, are capped at five failed attempts, and reset requests/verification are rate-limited. Resetting a password does not reactivate a deactivated account.
- **Payment integrity:** verification and rejection re-check the pending state under a database row lock, preventing double posting from concurrent requests.
- **Remote code execution:** no `eval`, `exec`, `system`, `shell_exec`, `proc_open`, dynamic includes, or unserialize of user input. Uploaded files are stored outside the public root, given server-generated names, and served as attachments.
- **SSRF:** there are no user-controlled outbound HTTP requests; the application only sends mail through the configured SMTP settings. If integrations are added later, allowlist destinations and block private/link-local addresses.
- **Path traversal:** stored paths are generated server-side. Downloads are restricted to the expected storage directory and reject `..` and backslash segments.
- **Browser headers:** web responses set `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and `Permissions-Policy`. Download responses additionally set a sandbox CSP and `Cache-Control: private, no-store`.
- **Production misconfiguration:** production forces `APP_DEBUG=false` and secure session cookies; `DB::prohibitDestructiveCommands` blocks `migrate:fresh` and `db:wipe`; demo seeding refuses to run in production; `public/.htaccess` disables directory indexes and denies common dotfiles.
- **Database permissions:** production should use the least-privilege accounts in `database/mysql-least-privilege.sql`; local SQLite has no database users.
- **File uploads:** stored in private local storage, validated by size and MIME type, given server-generated storage names, and served as attachments only. Payment proofs are restricted to PDF, JPG, and PNG. Downloads set `X-Content-Type-Options: nosniff`, `Content-Security-Policy: sandbox`, and `Cache-Control: private, no-store`, so browsers do not render them inline. The file name is shown as a label, but the file opens as a download.
- **Residual risks:** there has been no independent penetration test; administrators bypass role gates by design; uploaded files are not malware-scanned; email-delivery timing is not perfectly constant; and production PHP/web-server hardening (HTTPS/HSTS, disabled functions, request timeouts, malware scanning) remains an operational responsibility.

## Project structure

```
app/
  Http/Controllers/        Role-specific controllers
  Http/Middleware/         Inertia sharing, role guard, active-account guard, security headers
  Http/Requests/           Form validation
  Models/                  Eloquent models
  Services/                Audit, OTP, enrollment, class offerings, imports, grades, payments
  Features/                Dormant deferred modules (payroll, reversal, reports, messaging, provisioning)
database/
  migrations/              Schema
  migrations/deferred/     Dormant feature migrations, not auto-run
  mysql-least-privilege.sql MySQL runtime, migration, and backup accounts
  seeders/                 Demo data
resources/
  css/app.css              Tailwind theme and component styles
  js/Components/           App shell, auth layout, modal, pagination, UI kit
  js/Pages/                Inertia pages by role
  js/lib/                  Formatting and notification helpers
  js/types/                Shared TypeScript types
routes/web.php             Authentication, dashboards, admin, registrar, faculty, student, finance, settings, Help
tests/Feature/             Auth, RBAC, settings, admin, registrar, faculty, student, finance, security, dashboard tests
.github/                   Weekly dependency audit and Dependabot updates
scripts/security-audit.ps1 Local dependency audit for Windows Task Scheduler
```

## Useful commands

```powershell
# Run the test suite
..\.tools\php\php.exe artisan test --compact

# Format PHP code
..\.tools\php\php.exe vendor\bin\pint --dirty --format agent

# List routes
..\.tools\php\php.exe artisan route:list --except-vendor

# Rebuild frontend assets
npm run build

# Audit PHP and production Node dependencies
.\scripts\security-audit.ps1
```

## Paper mapping

| Paper module | CP3 state |
|---|---|
| User and Access | Login, lockout, OTP reset, activation, RBAC |
| Admin Dashboard | Academic years, master data, class offerings, announcements, accounts, audit logs |
| Registrar Dashboard | Student search, filters, sorting, profile view, record editing, CSV import |
| Faculty Dashboard | Schedule, class list, grade encoding, class announcements |
| Student Dashboard | Enrollment, schedule, class list and roster, grades, announcements, payments |
| Finance Dashboard | Payment verification, walk-in posting, ledgers, receipts |
| Payroll, Reversal, Reports | Staged dormant modules; not connected (see `docs/DEFERRED-FEATURES.md`) |
| Messaging and Provisioning | Staged dormant modules; not connected (see `docs/DEFERRED-FEATURES.md`) |
| Help/FAQ | Searchable, role-aware articles |

## Notes on the paper

- The paper uses “SQLite v9.7” and “MySQL v3.53” in different chapters. Those version numbers are inconsistent with the real products; this project uses SQLite 3.x locally and keeps migrations compatible with MySQL.
- The paper describes student payments both as student-posted and finance-verified. The project follows the safer flow: the student submits a reference and proof, and finance verifies before the ledger is updated.
- Imported student records are academic records only. Login-account and email provisioning are still pending because the school's email format is not finalized.
- No AI/agent feature is included, matching the paper and the confirmed project scope.
