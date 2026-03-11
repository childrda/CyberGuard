# Mail / Password Reset Troubleshooting

## Why didn’t I get the password reset email?

By default, CyberGuard uses the **log** mail driver. No email is actually sent to your inbox; the message is written to a log file so you can develop without SMTP.

### 1. Check the log (when using `MAIL_MAILER=log`)

With the default config, “sent” mail is written to:

- **`storage/logs/laravel.log`** (Laravel’s default log)

Search for lines containing the reset link (e.g. `password/reset`) or your email. You can copy the reset URL from the log and open it in the browser to set your password.

### 2. Send real email (SMTP)

To have password reset (and “add user to tenant”) emails delivered to real inboxes:

1. **Edit `.env`** (copy from `.env.example` if needed).

2. **Switch to SMTP** and set your provider’s values, for example:

   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

   Or use a single URL (if your driver supports it):

   ```env
   MAIL_MAILER=smtp
   MAIL_URL=smtp://user:password@smtp.example.com:587
   ```

3. **Restart the app** (e.g. restart `php artisan serve` or your queue workers) so the new config is loaded.

4. **Try “Forgot password” again** with your email.

### 3. Common SMTP setups

| Use case | Example |
|----------|--------|
| **Local testing** | [Mailtrap](https://mailtrap.io), [Mailpit](https://github.com/axllent/mailpit) – captures mail without sending to real addresses |
| **Real delivery** | Your provider’s SMTP (Gmail, SendGrid, Amazon SES, Office 365, etc.) – use their host, port, and credentials |

### 4. Gmail (if you use it)

- Use [App Passwords](https://support.google.com/accounts/answer/185833) if you have 2FA.
- Typical settings: `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`, and your Gmail address + app password.

### 5. Still not receiving?

- Check **spam/junk**.
- Confirm the email you request is the same as the user’s `email` in the database.
- Check `storage/logs/laravel.log` for mail or queue errors.
- If you use queues, ensure **queue workers** are running (`php artisan queue:work`) when mail is queued.
