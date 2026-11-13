# LeaveFlow — Online Leave Management System (PHP + MySQL)

A responsive, role-based Online Leave Management System based on the modules in the supplied OLMS project synopsis. This implementation uses **PHP 8.1+ and MySQL/MariaDB** instead of the synopsis's proposed Node.js/Express/MongoDB stack, as requested.

## Included modules

### Employee
- Employee self-registration and secure sign-in.
- Employee dashboard with request counts, recent applications and leave balances.
- Leave application with leave type, start/end dates and reason.
- Server-side validation, duplicate/overlapping personal leave prevention and balance checks.
- Leave status, complete history and cancellation of pending requests.
- Leave balance by leave type and calendar year.
- In-app notifications for submission, administrator decisions and pending reminders.
- Account password change.

### Administrator
- Seeded administrator account and role-protected dashboard.
- Employee directory: add, edit, search, activate and deactivate accounts.
- Leave request queue with approve/reject workflow and administrator remarks.
- Approval transaction re-checks the employee's remaining leave balance and date conflicts before deducting the approved days.
- Team overlap detection for pending/approved requests in the same department/date window.
- Basic backup-planning suggestions: active colleagues from the same department without an approved leave overlap.
- Leave policy management: add/edit/activate/deactivate leave types and configure annual quotas.
- Company holiday calendar; holidays and weekends are excluded from working-day calculations.
- Pending reminders for requests waiting more than 3 days, with a 24-hour duplicate-notification cooldown.
- In-app notifications and password change.

## Technology and requirements

- PHP 8.1 or newer
- MySQL 8.0+ or MariaDB 10.5+
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl` (normally included/enabled in XAMPP)
- Apache with PHP enabled (XAMPP/WAMP/LAMP) or PHP's built-in development server
- Modern browser

No Composer packages, JavaScript build tools, paid services or external UI libraries are required. The interface CSS and JavaScript are included locally.

## Installation (XAMPP on Windows)

1. Extract the project folder into `C:\xampp\htdocs\` so the path is `C:\xampp\htdocs\olms-php\`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`.
4. Select the **Import** tab and import `olms-php/database/olms.sql`. The script creates the `olms` database and its tables. Import it into a fresh/empty database; it is not a migration script for an existing OLMS database.
5. Open `olms-php/config/config.php` and set the database host, database name, username and password. The defaults are for a standard local XAMPP installation (`127.0.0.1`, database `olms`, user `root`, empty password).
6. Open `http://localhost/olms-php/` in your browser.
7. Sign in as administrator using the initial credentials below, then immediately change the password from **Account** in the top navigation.

### Initial administrator account

| Field | Value |
|---|---|
| Email | `admin@olms.local` |
| Initial password | `Admin@123` |

This is a known demo credential. **Change it immediately after the first sign-in**, and do not expose this installation publicly while the default password is active.

Employees can register from the home page. Administrators can also create employee accounts from **Employees → Add employee**.

## Alternative: PHP built-in development server

After configuring and importing the database, open a terminal in the project folder and run:

```bash
php -S 127.0.0.1:8000
```

Then visit `http://127.0.0.1:8000/`. This is for local development/testing only, not production deployment.

## Leave calculation and workflow rules

These rules make the supplied feature list executable. Adjust the annual quotas and holiday calendar to match the organisation's actual policy.

1. Working days are Monday–Friday, excluding dates entered under **Holidays**. Weekend/holiday-only ranges are rejected because they contain zero chargeable days.
2. A single leave request must stay within one calendar year. This keeps the request's annual balance allocation unambiguous.
3. Employees cannot submit a new request that overlaps their own pending or approved request.
4. Pending requests reserve balance during new-request validation; only approved requests increase the official `used_days` balance.
5. Team overlaps from other employees are flagged for administrator review, not automatically rejected. The administrator sees overlapping requests and same-department backup suggestions.
6. Only pending requests can be approved/rejected. Rejection requires an administrator remark. Approved leave is deducted within a database transaction.
7. A reminder is created when a request has been pending for more than 3 days. The system avoids creating the same request/recipient reminder more than once per 24 hours.

The SQL seeds example leave types (Casual 12, Sick 12, Earned 18 days). These are **sample values**, not statutory or universally applicable entitlements. Change them under **Leave policies** before real use.

## Reminder scheduling

The administrator dashboard checks for stale pending requests when opened. For scheduled processing, run the CLI script daily:

**Linux cron example (9:00 AM daily):**

```cron
0 9 * * * /usr/bin/php /absolute/path/to/olms-php/cron/send_reminders.php
```

**Windows Task Scheduler:** create a daily task that runs `php.exe` with the argument `C:\xampp\htdocs\olms-php\cron\send_reminders.php`.

The script is CLI-only and creates in-app notifications. Email/SMS delivery is not implemented in this version; the synopsis lists those as future enhancements.

## Database structure

- `users` — employee and administrator accounts
- `leave_types` — configurable leave categories and annual quotas
- `user_leave_balances` — per-user, per-leave-type, per-year allocations and used days
- `leave_requests` — applications, statuses, review details and date ranges
- `holidays` — non-working dates excluded from leave-day calculation
- `notifications` — in-app reminders and workflow updates
- `activity_logs` — basic account and administrative activity events

## Security and deployment notes

- Passwords are stored using PHP `password_hash()` and checked with `password_verify()`.
- Database access uses PDO prepared statements; role checks protect employee and administrator pages.
- State-changing forms use CSRF tokens; session IDs are regenerated at sign-in and password changes.
- Output is HTML-escaped by default in views.
- Database credentials are isolated in `config/config.php`; restrict access to that file at the server level.
- Use HTTPS, strong unique administrator passwords, backups, least-privilege database credentials and current PHP/MySQL versions for any real deployment.
- This is an academic/demo project baseline. Conduct organisation-specific security review, backup/restore testing and load testing before production use.

## Test checklist (aligned to the OLMS synopsis)

| Test | Action | Expected result |
|---|---|---|
| TC01 | Register with valid employee details | Employee account and current-year leave balances are created |
| TC02 | Sign in with valid employee credentials | Employee dashboard opens |
| TC03 | Sign in with incorrect credentials | Error is displayed; session is not created |
| TC04 | Submit a valid leave request | Request is saved as Pending; notifications are created |
| TC05 | Submit missing/invalid dates or reason | Validation errors are displayed; request is not saved |
| TC06 | Open leave history | Correct request status/history is displayed |
| TC07 | Administrator approves a pending request | Status becomes Approved; balance used days increase once |
| TC08 | Administrator rejects a pending request with a remark | Status becomes Rejected; employee is notified |
| TC09 | Submit overlapping personal/team dates | Personal overlap is blocked; team overlap is flagged for admin |
| TC10 | Open leave balance | Allocated, used, pending and remaining days are shown |
| TC11 | Run reminder worker for stale requests | In-app pending reminder is created with cooldown |

These are test cases, not a claim that the app has been executed against your own server/database. See `TESTING.md` for a manual verification sequence.
