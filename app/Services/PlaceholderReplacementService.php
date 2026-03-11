<?php

namespace App\Services;

use App\Models\PhishingMessage;

/**
 * Replace merge tags in attack/template subject and body.
 * Supported: {{first_name}}, {{display_name}}, {{email}}, {{recipient_name}}, {{recipient_email}},
 * {{department}}, {{ou}}, {{tracking_link}}, {{report_address}}, {{campaign_name}},
 * {{logo_url}}, {{illustration_url}}, {{message_reference}}.
 */
class PlaceholderReplacementService
{
    public function __construct(
        protected ?string $reportAddress = null
    ) {
        $this->reportAddress ??= config('mail.from.address');
    }

    /**
     * Base URL for links and images in sent emails (PHISHING_PUBLIC_URL or APP_URL).
     */
    public static function baseUrlForEmails(): string
    {
        return rtrim(config('phishing.public_url', config('app.url')), '/');
    }

    /**
     * Build replacement map for a phishing message (real send).
     */
    public function contextForMessage(PhishingMessage $message): array
    {
        $message->load('campaign');
        $campaign = $message->campaign;
        $email = $message->recipient_email ?? '';
        $name = $message->recipient_name ?? '';

        $baseUrl = static::baseUrlForEmails();
        $trackPrefix = config('phishing.tracking_prefix', 't');
        $trackingUrl = $baseUrl.'/'.$trackPrefix.'/'.$message->tracking_token;

        $firstName = $name ? trim(explode(' ', $name, 2)[0]) : '';
        $displayName = $name ?: $email;

        return [
            'first_name' => $firstName,
            'display_name' => $displayName,
            'email' => $email,
            'recipient_name' => $displayName,
            'recipient_email' => $email,
            'department' => (string) ($message->recipient_department ?? ''),
            'ou' => (string) ($message->recipient_ou ?? ''),
            'tracking_link' => $trackingUrl,
            'report_address' => $this->reportAddress,
            'campaign_name' => $campaign ? $campaign->name : '',
            'logo_url' => (string) ($message->logo_url ?? ''),
            'illustration_url' => (string) ($message->illustration_url ?? ''),
            'message_reference' => (string) ($message->message_reference ?? $message->tracking_token ?? ''),
        ];
    }

    /**
     * Sample context for preview / test (no real message).
     */
    public function sampleContext(?string $trackingToken = null): array
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $trackPrefix = config('phishing.tracking_prefix', 't');
        $token = $trackingToken ?? 'preview-token';

        return [
            'first_name' => 'Alex',
            'display_name' => 'Alex Rivera',
            'email' => 'alex.rivera@example.com',
            'recipient_name' => 'Alex Rivera',
            'recipient_email' => 'alex.rivera@example.com',
            'department' => 'Engineering',
            'ou' => 'Staff',
            'tracking_link' => $baseUrl.'/'.$trackPrefix.'/'.$token,
            'report_address' => $this->reportAddress,
            'campaign_name' => 'Q1 Security Awareness',
            'logo_url' => $baseUrl.'/storage/attack_assets/placeholder-logo.png',
            'illustration_url' => $baseUrl.'/storage/attack_assets/placeholder-illustration.png',
            'message_reference' => 'REF-'.substr($token, 0, 8),
        ];
    }

    /**
     * Replace {{key}} placeholders in a string.
     */
    public function replace(string $text, array $context): string
    {
        if ($text === '') {
            return '';
        }
        foreach ($context as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }
        return $text;
    }

    /**
     * Replace in subject, html_body, and text_body; return new array.
     * Rewrites any APP_URL in content to PHISHING_PUBLIC_URL so dev/staging links work for recipients.
     */
    public function replaceInContent(array $content, array $context): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $publicUrl = static::baseUrlForEmails();

        $out = [];
        foreach (['subject', 'html_body', 'text_body'] as $field) {
            if (isset($content[$field]) && is_string($content[$field])) {
                $text = $this->replace($content[$field], $context);
                if ($publicUrl !== $appUrl && $appUrl !== '') {
                    $text = str_replace($appUrl, $publicUrl, $text);
                }
                $out[$field] = $text;
            }
        }
        return $out;
    }

    /**
     * List of supported placeholder names (for docs / validation).
     */
    public static function supportedPlaceholders(): array
    {
        return [
            'first_name', 'display_name', 'email', 'recipient_name', 'recipient_email',
            'department', 'ou', 'tracking_link', 'report_address', 'campaign_name',
            'logo_url', 'illustration_url', 'message_reference',
        ];
    }
}
