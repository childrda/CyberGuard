# Report Phish – Gmail Add-on

This Google Workspace Add-on lets users report suspicious emails from Gmail. Reports are sent to your CyberGuard Laravel backend.

## Prerequisites

- Google Workspace account (admin)
- CyberGuard backend deployed with `PHISHING_WEBHOOK_SECRET` and `GMAIL_REPORT_ADDON_ENABLED=true`

## Deployment (admin-managed, domain-only)

### 1. Create the Apps Script project

1. Go to [script.google.com](https://script.google.com).
2. New project.
3. Replace `Code.gs` with the contents of this folder's `Code.gs`.
4. Replace `appsscript.json` with this folder's `appsscript.json` (use View > Show manifest file to see it).

### 2. Set script properties

1. File > Project properties > Script properties.
2. Add:
   - `WEBHOOK_URL`: Your backend URL, e.g. `https://cyberguard.yourdomain.com/api/webhook/report`
   - `WEBHOOK_SECRET`: Same value as `PHISHING_WEBHOOK_SECRET` in your Laravel `.env`

### 3. Deploy as add-on

1. Deploy > Test deployments > Install.
2. Or: Publish as internal/private app (only your domain):
   - Deploy > New deployment.
   - Type: Add-on.
   - Description: "Report Phish for security awareness".
   - Under "Execute as" choose your admin account; under "Who has access" choose "Only users in my domain" (or your org unit).
   - Deploy and note the deployment ID.

### 4. Install for the domain

- **Test deployment**: Only for test users you add in the test deployment.
- **Internal app**: In Google Admin (admin.google.com) go to Apps > Google Workspace Marketplace apps > Add app > From another domain, and enter the deployment link, or push the add-on to OUs as needed.

## Webhook payload

The add-on sends a `POST` request to your webhook with:

- `report_type`: `phish`, `spam`, or `safe`
- `gmail_message_id`, `gmail_thread_id`
- `subject`, `from`, `from_address`, `from_display`, `to`, `date`, `snippet`
- `headers`: object of header name → value
- `reporter_email`: set when Gmail profile is available

The request includes header `X-Phish-Signature: sha256=<hmac_sha256(body, WEBHOOK_SECRET)>`. Your backend must verify this (see `ReportWebhookController`).

## Optional: user actions dialog

To collect "I clicked a link", "I entered my password", etc., add a card that shows after "Report Phish" with multiple choice buttons and append the selection to the payload as `user_actions: ['clicked_link', 'entered_password']` before calling the webhook. Extend `Code.gs` and your Laravel webhook to accept and store `user_actions`.
