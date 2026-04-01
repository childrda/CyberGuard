<?php

/**
 * Phishing awareness platform configuration.
 * Authorized internal security awareness testing only.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Phishing simulation enabled
    |--------------------------------------------------------------------------
    | When false, no simulation emails are sent. Safe default for development.
    */
    'simulation_enabled' => env('PHISHING_SIMULATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Gmail Report Add-on enabled
    |--------------------------------------------------------------------------
    */
    'gmail_report_addon_enabled' => env('GMAIL_REPORT_ADDON_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Gmail removal (trash phishing from mailboxes)
    |--------------------------------------------------------------------------
    | When true and Google credentials are set, analysts can remove confirmed
    | real phishing from reporter's mailbox and optionally domain-wide.
    */
    'gmail_removal_enabled' => env('PHISHING_GMAIL_REMOVAL_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Queue name for external integrations (Slack report alerts)
    |--------------------------------------------------------------------------
    */
    'slack_queue' => env('PHISHING_SLACK_QUEUE', 'notifications'),

    /*
    |--------------------------------------------------------------------------
    | Allowed sender domains (fallback only)
    |--------------------------------------------------------------------------
    | Prefer tenant-level allowed_domains (per tenant in Settings). This is only
    | used if a tenant has no allowed_domains set (e.g. legacy). Leave empty
    | to require configuring allowed domains per tenant.
    */
    'allowed_target_domains' => array_filter(explode(',', env('PHISHING_ALLOWED_DOMAINS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Blocked target domains (high-risk / never send)
    |--------------------------------------------------------------------------
    | Simulation emails are never sent to these domains, even if in allowed list.
    | Defaults include consumer email and common government TLDs. Override via
    | PHISHING_BLOCKED_DOMAINS (comma-separated) or leave empty to use defaults.
    */
    'blocked_target_domains' => array_filter(
        explode(',', env('PHISHING_BLOCKED_DOMAINS', 'gmail.com,googlemail.com,yahoo.com,yahoo.co.uk,outlook.com,hotmail.com,hotmail.co.uk,live.com,icloud.com,me.com,mac.com,aol.com,mail.com,protonmail.com,proton.me,.gov,.gov.uk,.mil'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Default from domain
    |--------------------------------------------------------------------------
    */
    'default_from_domain' => env('PHISHING_FROM_DOMAIN', env('MAIL_FROM_DOMAIN', 'example.com')),

    /*
    |--------------------------------------------------------------------------
    | Webhook secret for Gmail add-on
    |--------------------------------------------------------------------------
    | Shared secret to verify incoming report payloads. Encrypt in production.
    */
    'webhook_secret' => env('PHISHING_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Google API (Gmail removal, future Directory)
    |--------------------------------------------------------------------------
    | Path to service account JSON. Domain-wide delegation must be enabled
    | for Gmail API and Admin SDK (Directory) in Google Cloud Console.
    */
    'google_credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'google_domain' => env('GOOGLE_WORKSPACE_DOMAIN', ''),
    'google_admin_user' => env('GOOGLE_ADMIN_USER', ''),

    /*
    |--------------------------------------------------------------------------
    | Public URL for links and images in sent emails
    |--------------------------------------------------------------------------
    | When your app is not publicly reachable (e.g. dev box on internal IP),
    | set this to a URL that recipients can reach: ngrok/Cloudflare Tunnel URL,
    | or a staging server. All tracking links, tracking pixel, and asset URLs
    | in outgoing emails will use this base. Fallback: APP_URL.
    */
    'public_url' => env('PHISHING_PUBLIC_URL', config('app.url')),

    /*
    |--------------------------------------------------------------------------
    | Tracking link route prefix
    |--------------------------------------------------------------------------
    */
    'tracking_prefix' => env('PHISHING_TRACKING_PREFIX', 't'),

    /*
    |--------------------------------------------------------------------------
    | Open tracking
    |--------------------------------------------------------------------------
    | Best-effort only; not authoritative.
    */
    'open_tracking_enabled' => env('PHISHING_OPEN_TRACKING', true),

    /*
    |--------------------------------------------------------------------------
    | Credential capture (training only)
    |--------------------------------------------------------------------------
    | We never store real passwords. Only record that a submission occurred.
    */
    'credential_capture_placeholder' => 'submitted',

    /*
    |--------------------------------------------------------------------------
    | Scoring (gamification point deltas)
    |--------------------------------------------------------------------------
    | Vision-aligned defaults: reward reporting and training; small penalty for click/submit.
    */
    'scoring' => [
        'simulation_reported' => (int) env('PHISHING_SCORE_SIMULATION_REPORTED', 50),
        'reported_phish' => (int) env('PHISHING_SCORE_REPORTED_PHISH', 50),
        'training_completed' => (int) env('PHISHING_SCORE_TRAINING_COMPLETED', 100),
        'clicked' => (int) env('PHISHING_SCORE_CLICKED', -10),
        'submitted' => (int) env('PHISHING_SCORE_SUBMITTED', -25),
    ],
];
