# CyberGuard – Internal Phishing Awareness Platform

Production-ready internal phishing awareness platform for **authorized security awareness testing on your own Google Workspace domain only**.

---

## Quick start (minimal install)

If you have PHP 8.2+, Composer, and MySQL installed:

```bash
# 1. Install and configure
cd CyberGuard
composer install
cp .env.example .env
php artisan key:generate

# 2. Edit .env: set your database and a webhook secret (see examples below)
#    For local dev you can use: CACHE_STORE=file, QUEUE_CONNECTION=sync

# 3. Create database and run migrations
#    Create a DB named "cyberguard" in MySQL, then:
php artisan migrate
php artisan db:seed

# 4. Start the app
php artisan serve
```

Then open **http://localhost:8000** and log in with **admin@example.com** / **password**.

---

## What you need

| Requirement | Notes |
|-------------|--------|
| **PHP 8.2+** | With extensions: mbstring, xml, pdo_mysql, json, openssl, tokenizer |
| **Composer** | [getcomposer.org](https://getcomposer.org) |
| **MySQL 8+** | Or MariaDB 10.3+ |
| **Redis** (optional) | For production queues/cache; for local you can use `CACHE_STORE=file` and `QUEUE_CONNECTION=sync` |

---

## Step-by-step installation

### 1. Clone and install dependencies

```bash
cd CyberGuard
composer install
cp .env.example .env
php artisan key:generate
```

You should see: `Application key set successfully.`

### 2. Configure environment

Edit `.env` and set at least these (examples you can copy and adjust):

**Database (required)**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cyberguard
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

Create the database in MySQL first, for example:

```sql
CREATE DATABASE cyberguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Webhook secret (required for Gmail add-on)**

Generate a long random string and use the same value in Laravel and in Apps Script (Script properties). Example:

```env
PHISHING_WEBHOOK_SECRET=your-secret-at-least-32-characters-long
```

You can generate one with:

```bash
php -r "echo bin2hex(random_bytes(24));"
```

**Optional: run without Redis (local dev)**

```env
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Leave these as-is for development so no real emails are sent:

```env
PHISHING_SIMULATION_ENABLED=false
PHISHING_ALLOWED_DOMAINS=example.com
```

### 3. Run migrations and seed data (local / dev only)

```bash
php artisan migrate
php artisan db:seed
```

**Seeding is for local bootstrap only.** In production, `php artisan db:seed` **will throw** and refuse to run unless you set `SEEDER_ALLOW_PRODUCTION=true` in `.env` (e.g. for a one-time initial bootstrap with your own `SEEDER_DEFAULT_PASSWORD`). Never run the default seed in a real environment without that explicit opt-in and a strong password.

Expected (local): migrations run without errors; seeders create a default tenant, admin user, sample template, and campaign.

### 4. Start the application

```bash
php artisan serve
```

Open **http://localhost:8000**. You should see the login page.

### 5. First login (local dev only)

**These credentials are for local development only. They must never be used in production.** In production, create users and tenants through your own process and never rely on seeded defaults.

| Email | Password | Role | Access |
|-------|----------|------|--------|
| platform_admin@example.com | password | Superadmin | **All tenants** (tenant_id is null; can switch to any tenant) |
| admin@example.com | password | Superadmin | **Default tenant only** (example.com) |
| viewer@example.com | password | Viewer | Default tenant only |

- **Platform superadmin**: Create a user with `tenant_id = null` and role `superadmin` to allow one account to manage all tenants. The tenant switcher will list every active tenant.
- **Tenant-scoped admins**: Set `tenant_id` to a specific tenant when creating users. They can only see and switch to that tenant. Use this for per-tenant admins (e.g. staff vs student).

After login, use the **tenant switcher** in the left sidebar. Scoped users see only their tenant; platform admins see all. Default tenant for seeded tenant-scoped users is **example.com**.

### 6. (Optional) Run the queue worker

Only needed if you will send phishing simulations (e.g. after enabling `PHISHING_SIMULATION_ENABLED=true`):

```bash
php artisan queue:work --queue=phishing-send
```

---

## Gmail Report Phish add-on setup

1. **Create an Apps Script project**
   - Go to [script.google.com](https://script.google.com).
   - New project. Copy the contents of `google-addon/Code.gs` and `google-addon/appsscript.json` into the project (replace default files).

2. **Set Script properties** (File → Project properties → Script properties)

   | Name | Value | Example |
   |------|--------|--------|
   | WEBHOOK_URL | Your CyberGuard report API URL | `https://your-domain.com/api/webhook/report` or `http://localhost:8000/api/webhook/report` (for local testing with ngrok) |
   | WEBHOOK_SECRET | Same as `PHISHING_WEBHOOK_SECRET` in Laravel `.env` | `your-secret-at-least-32-characters-long` |

3. **Deploy**
   - Deploy → New deployment → Type: Add-on → Test deployment or Internal.
   - Restrict to your Google Workspace domain. See `google-addon/README.md` for details.

4. **Multi-tenant (optional)**  
   If you use multiple tenants (e.g. staff vs student domains), set the header in your add-on so the webhook knows which tenant to use:
   - In the webhook request, add: `X-Tenant-Domain: yourdomain.com` (must match a tenant’s `domain` in the database).

---

## Google Workspace deployment (production)

- Deploy Laravel to your server (PHP, MySQL, Redis recommended). Set `APP_URL` and `PHISHING_WEBHOOK_SECRET` in `.env`.
- Webhook endpoint: **POST** `/api/webhook/report`. The add-on sends JSON; the server verifies the header `X-Phish-Signature: sha256=<hmac_sha256(raw_body, PHISHING_WEBHOOK_SECRET)>`.
- **Gmail removal (remediation):** To allow trashing confirmed phishing from mailboxes, set in `.env`:
  - `PHISHING_GMAIL_REMOVAL_ENABLED=true`
  - `GOOGLE_APPLICATION_CREDENTIALS=/path/to/service-account.json`
  - `GOOGLE_WORKSPACE_DOMAIN=yourdomain.com`
  - `GOOGLE_ADMIN_USER=admin@yourdomain.com`  
  The service account needs domain-wide delegation for Gmail API and Admin SDK (Directory). Configure tenants in **Settings** (domain, credentials path, remediation policy).

---

## Features (overview)

- **Multi-tenant**: Separate tenants (e.g. staff vs student) with their own domain, credentials, webhook secret, and remediation policy. Tenant switcher in the admin sidebar.
- **Simulation campaigns**: Templates, target users/groups/CSV, send windows, approval workflow.
- **Gmail add-on**: Report Phish (with optional “I clicked the link” / “I entered information”), Report Spam, Mark Safe. Webhook matches reports to simulations and awards Shield points.
- **Remediation**: When a report is confirmed as real phishing, approve a remediation job (optional dry run), then run to trash the message across domain mailboxes. Full logging per mailbox action.
- **Shield points & leaderboard**: Points ledger and monthly leaderboard per tenant.
- **Admin UI**: Dashboard, Reports, Remediation, Campaigns, Leaderboard, Audit Logs, Settings. RBAC: superadmin, campaign_admin, analyst, viewer.

Details: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## API

The only public API endpoint is the report webhook below. The `/api` routes also include a Sanctum-protected group reserved for future internal admin tools; it is currently a placeholder with no routes.

### Webhook (for add-on)

**POST** `/api/webhook/report`

- **Headers:** `Content-Type: application/json`, `X-Phish-Signature: sha256=<hmac_sha256(raw_body, PHISHING_WEBHOOK_SECRET)`
- **Optional:** `X-Tenant-Domain: yourdomain.com` for multi-tenant

**Example body:**

```json
{
  "report_type": "phish",
  "reporter_email": "user@yourdomain.com",
  "gmail_message_id": "18c2a1b2e3d4f5g6",
  "gmail_thread_id": "...",
  "subject": "Urgent: Verify your account",
  "from": "IT Support <support@example.com>",
  "from_address": "support@example.com",
  "to_addresses": "user@yourdomain.com",
  "date": "Mon, 7 Mar 2025 12:00:00 +0000",
  "snippet": "Please click here to verify...",
  "headers": {},
  "user_actions": ["clicked_link"]
}
```

**Responses:** `200` OK with `{ "ok": true, "reported_message_id": 1, "correlation_id": "uuid" }`; `401` invalid signature; `422` validation error; `503` add-on disabled.

---

## Config reference

| Variable | Description | Example |
|----------|-------------|--------|
| PHISHING_SIMULATION_ENABLED | When `false`, no simulation emails are sent | `false` (dev) |
| GMAIL_REPORT_ADDON_ENABLED | When `false`, webhook returns 503 | `true` |
| PHISHING_ALLOWED_DOMAINS | Domains that may receive simulation emails (comma-separated) | `example.com` |
| PHISHING_WEBHOOK_SECRET | Must match Apps Script WEBHOOK_SECRET | Long random string |

---

## Tests

```bash
composer test
# or
php artisan test
```

Feature tests cover: auth and dashboard access, webhook (signature, unknown tenant, valid payload with tenant), tracking, **tenant isolation** (scoped user cannot see other tenant’s reports; middleware overrides tampered session; platform admin can use any tenant), **remediation** (report_only tenant cannot approve; dry-run approval creates job with flag; run requires approved job), **points awarding** (simulation_reported and reported_phish ledger; leaderboard sum), and **role enforcement** (viewer cannot confirm phish or approve remediation; analyst can).

---

## Logo and branding

Place your logo at `public/images/cyberguard-logo.png`. For the Gmail add-on icon, set the deployed logo URL in `google-addon/appsscript.json` (`logoUrl`).

---

## Security (production checklist)

- **Seeding.** In production, `php artisan db:seed` is blocked unless `SEEDER_ALLOW_PRODUCTION=true` in `.env`. Use that only for intentional one-time bootstrap; set `SEEDER_DEFAULT_PASSWORD` to a strong value (required when `APP_ENV=production`).
- **Never run with `APP_DEBUG=true` in production.** Set `APP_DEBUG=false` and ensure `APP_URL` is correct (used for redirect validation and links).
- **Keep `PHISHING_WEBHOOK_SECRET` strong and per-tenant.** The webhook rejects requests with an invalid HMAC; use a long random value and rotate if compromised.
- **Restrict Gmail add-on and webhook.** Deploy the add-on only to your Google Workspace domain. Put the app behind HTTPS and restrict admin routes to trusted networks/VPN if possible.
- **Credentials and logs.** Do not commit `.env`. Google service account JSON must be stored outside the web root. Production logs do not include credentials paths or full exception messages.
- **Tenant isolation.** Each tenant has its own webhook secret and data scope; do not reuse the same secret across tenants.
- **Landing page HTML.** Training/landing content from the database is sanitized (safe tags only; script and `javascript:`/`data:` URLs removed) to prevent stored XSS. Only allow trusted admins to create landing pages.
- **Redirect safety.** Tracking redirects are limited to the same host as `APP_URL`; set `APP_URL` correctly in production.

---

## License

MIT.
