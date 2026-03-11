<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Send simulation emails via Gmail API (service account with domain-wide delegation)
 * or fallback to Laravel Mail / SMTP. For dev, logs only when simulation disabled.
 * Embeds img src that point to app storage as inline attachments so images display
 * even when the app is not publicly reachable (e.g. dev box).
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
        // Embed our-storage images inline so they display without a public URL; then send with Base64 HTML.
        try {
            $this->sendWithBase64Html($to, $subject, $htmlBody, $textBody ?? strip_tags($htmlBody), $envelopeFrom, $displayName, $replyTo);
            return ['message_id' => 'local-'.uniqid()];
        } catch (\Throwable $e) {
            Log::error('GmailSimulationMailer send failed');
            throw $e;
        }
    }

    /**
     * Find img src URLs that point to our app's storage and return [ url => absolute file path ].
     * Enables embedding those images inline so they display when the app is not publicly reachable.
     */
    private function collectStorageImageUrls(string $html): array
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $publicUrl = PlaceholderReplacementService::baseUrlForEmails();
        $storagePathPrefix = '/storage/';
        $urlToPath = [];

        if (! preg_match_all('/<img\s[^>]*\ssrc=["\']([^"\']+)["\']/i', $html, $m)) {
            return [];
        }

        foreach ($m[1] as $src) {
            $src = trim($src);
            if ($src === '' || str_starts_with($src, 'data:') || str_starts_with($src, 'cid:')) {
                continue;
            }

            $pathOnly = $src;
            if (str_contains($src, '://')) {
                $parsed = parse_url($src);
                $host = $parsed['host'] ?? '';
                $pathOnly = $parsed['path'] ?? '';
                $allowedHosts = [
                    parse_url($baseUrl, PHP_URL_HOST),
                    parse_url($publicUrl, PHP_URL_HOST),
                ];
                if (! in_array($host, $allowedHosts, true)) {
                    continue;
                }
            }

            if (! str_starts_with($pathOnly, $storagePathPrefix)) {
                continue;
            }

            $relativePath = substr($pathOnly, strlen($storagePathPrefix));
            $absolutePath = storage_path('app/public/'.$relativePath);
            if (is_file($absolutePath)) {
                $urlToPath[$src] = $absolutePath;
            }
        }

        return $urlToPath;
    }

    /**
     * Inject tracking pixel and replace links with signed tracking URLs.
     */
    public function injectTrackingIntoBody(string $htmlBody, string $trackingToken): string
    {
        $baseUrl = \App\Services\PlaceholderReplacementService::baseUrlForEmails();
        $trackPrefix = config('phishing.tracking_prefix', 't');
        $trackUrl = $baseUrl.'/'.$trackPrefix.'/'.$trackingToken;

        // Replace href="http... with tracking redirect (skip links that are already our tracking URL)
        $trackUrl = $baseUrl.'/'.$trackPrefix.'/'.$trackingToken;
        $htmlBody = preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']/i',
            function ($m) use ($baseUrl, $trackPrefix, $trackingToken, $trackUrl) {
                $attrs = $m[1];
                $originalUrl = $m[2];
                if (str_starts_with($originalUrl, $trackUrl) || $originalUrl === $trackUrl) {
                    return $m[0];
                }
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

    /**
     * Send using Symfony Mime Email. Embeds img src that point to app storage as inline
     * attachments (cid:) so images display when the app is not publicly reachable.
     * HTML part is Base64 so quoted-printable cannot fold in the middle of URLs.
     */
    private function sendWithBase64Html(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $envelopeFrom,
        string $displayName,
        ?string $replyTo
    ): void {
        $mailer = \Illuminate\Support\Facades\Mail::mailer();
        $transport = method_exists($mailer, 'getSymfonyTransport') ? $mailer->getSymfonyTransport() : null;

        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($envelopeFrom, $displayName))
            ->to($to)
            ->subject($subject);

        // Embed our-storage images inline only when using Symfony transport (so cid: parts are sent).
        if ($transport) {
            $urlToPath = $this->collectStorageImageUrls($htmlBody);
            foreach ($urlToPath as $url => $absolutePath) {
                $data = file_get_contents($absolutePath);
                if ($data === false) {
                    continue;
                }
                $name = basename($absolutePath);
                $mimeType = $this->mimeTypeForPath($absolutePath);
                $cid = $email->embed($data, $name, $mimeType);
                $htmlBody = str_replace($url, $cid, $htmlBody);
            }
        }

        $htmlPart = new \Symfony\Component\Mime\Part\TextPart($htmlBody, 'utf-8', 'html', 'base64');
        $email->setBody($htmlPart);

        if ($replyTo) {
            $email->replyTo($replyTo);
        }

        if ($transport) {
            $transport->send($email);
        } else {
            \Illuminate\Support\Facades\Mail::raw($textBody, function ($message) use ($to, $subject, $htmlBody, $displayName, $envelopeFrom, $replyTo) {
                $message->to($to)->subject($subject)->from($envelopeFrom, $displayName)->html($htmlBody);
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
            });
        }
    }

    private function mimeTypeForPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
    }
}
