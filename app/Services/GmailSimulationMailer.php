<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Send simulation emails via Gmail API (service account with domain-wide delegation)
 * or fallback to Laravel Mail / SMTP. For dev, logs only when simulation disabled.
 */
class GmailSimulationMailer
{
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null,
        ?string $fromEmail = null,
        ?string $replyTo = null
    ): array {
        if (! config('phishing.simulation_enabled', false)) {
            if (config('app.env') !== 'production') {
                Log::info('Phishing simulation disabled; would send to [redacted]');
            }
            return ['message_id' => null, 'skipped' => true];
        }

        // Google Workspace SMTP relay only accepts MAIL FROM in your domain. Use the app's
        // allowed From address so relay works; show the simulation "sender" in the display name.
        $envelopeFrom = config('mail.from.address');
        $displayName = $fromName ?: config('mail.from.name');
        if ($fromEmail && $fromEmail !== $envelopeFrom) {
            $displayName = trim($displayName . ' <' . $fromEmail . '>');
        }

        // TODO: Integrate Gmail API with service account. For now use Laravel Mail.
        try {
            \Illuminate\Support\Facades\Mail::raw($textBody ?? strip_tags($htmlBody), function ($message) use ($to, $subject, $htmlBody, $displayName, $envelopeFrom, $replyTo) {
                $message->to($to)
                    ->subject($subject)
                    ->from($envelopeFrom, $displayName)
                    ->html($htmlBody);
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });
            return ['message_id' => 'local-'.uniqid()];
        } catch (\Throwable $e) {
            Log::error('GmailSimulationMailer send failed');
            throw $e;
        }
    }

    /**
     * Inject tracking pixel and replace links with signed tracking URLs.
     */
    public function injectTrackingIntoBody(string $htmlBody, string $trackingToken): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $trackPrefix = config('phishing.tracking_prefix', 't');
        $trackUrl = $baseUrl.'/'.$trackPrefix.'/'.$trackingToken;

        // Replace href="http... with tracking redirect
        $htmlBody = preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']/i',
            function ($m) use ($baseUrl, $trackPrefix, $trackingToken) {
                $attrs = $m[1];
                $originalUrl = $m[2];
                $redirect = $baseUrl.'/'.$trackPrefix.'/'.$trackingToken.'?r='.urlencode($originalUrl);
                return '<a '.$attrs.' href="'.e($redirect).'"';
            },
            $htmlBody
        );

        if (config('phishing.open_tracking_enabled', true)) {
            $pixelUrl = $baseUrl.'/'.$trackPrefix.'/'.$trackingToken.'/open';
            $pixel = '<img src="'.e($pixelUrl).'" width="1" height="1" alt="" style="display:none" />';
            // Insert before </body>
            if (stripos($htmlBody, '</body>') !== false) {
                $htmlBody = str_ireplace('</body>', $pixel."\n</body>", $htmlBody);
            } else {
                $htmlBody .= $pixel;
            }
        }

        return $htmlBody;
    }
}
