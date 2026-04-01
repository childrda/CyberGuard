<?php

namespace App\Services;

use App\Models\ReportedMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SlackReportAlertService
{
    public function syncReportAlert(ReportedMessage $reported): void
    {
        $tenant = $reported->tenant;
        if (! $tenant || ! $tenant->slack_alerts_enabled) {
            return;
        }

        $token = trim((string) $tenant->slack_bot_token);
        if ($token === '') {
            throw new RuntimeException('Slack is enabled for tenant but slack_bot_token is not configured.');
        }

        $targetChannel = trim((string) ($tenant->slack_channel ?: 'phishing-alert'));
        if ($targetChannel === '') {
            $targetChannel = 'phishing-alert';
        }

        $payload = [
            'text' => $this->buildSummaryText($reported),
            'blocks' => $this->buildBlocks($reported),
            'unfurl_links' => false,
            'unfurl_media' => false,
        ];

        // Prefer updating the original Slack alert to keep channel noise low.
        if ($reported->slack_message_ts && $reported->slack_channel) {
            $updateResponse = $this->callSlack($token, 'chat.update', array_merge($payload, [
                'channel' => $reported->slack_channel,
                'ts' => $reported->slack_message_ts,
            ]));

            if ($updateResponse['ok'] ?? false) {
                return;
            }

            $error = (string) ($updateResponse['error'] ?? '');
            if (! in_array($error, ['message_not_found', 'channel_not_found'], true)) {
                throw new RuntimeException('Slack chat.update failed: '.$error);
            }
        }

        $postResponse = $this->callSlack($token, 'chat.postMessage', array_merge($payload, [
            'channel' => $targetChannel,
        ]));

        if (! ($postResponse['ok'] ?? false)) {
            $error = (string) ($postResponse['error'] ?? 'unknown_error');
            throw new RuntimeException('Slack chat.postMessage failed: '.$error);
        }

        $channel = (string) ($postResponse['channel'] ?? '');
        $ts = (string) ($postResponse['ts'] ?? '');
        if ($channel !== '' && $ts !== '') {
            $reported->forceFill([
                'slack_channel' => $channel,
                'slack_message_ts' => $ts,
            ])->save();
        }
    }

    private function callSlack(string $token, string $method, array $payload): array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->post("https://slack.com/api/{$method}", $payload);

        if ($response->failed()) {
            throw new RuntimeException("Slack {$method} HTTP failure: ".$response->status());
        }

        return $response->json() ?: [];
    }

    private function buildSummaryText(ReportedMessage $reported): string
    {
        return sprintf(
            '[%s] %s reported by %s: %s',
            strtoupper($reported->report_type ?: 'phish'),
            $this->statusLabel($reported),
            $reported->reporter_email ?: 'unknown',
            $reported->subject ?: '(no subject)'
        );
    }

    private function buildBlocks(ReportedMessage $reported): array
    {
        $status = $this->statusLabel($reported);
        $statusEmoji = $this->statusEmoji($reported);
        $adminUrl = rtrim((string) config('app.url'), '/').'/admin/reports/'.$reported->id;

        $fields = [
            ['type' => 'mrkdwn', 'text' => "*Status*\n{$statusEmoji} {$status}"],
            ['type' => 'mrkdwn', 'text' => "*Type*\n".strtoupper((string) ($reported->report_type ?: 'phish'))],
            ['type' => 'mrkdwn', 'text' => "*Reporter*\n".($reported->reporter_email ?: '—')],
            ['type' => 'mrkdwn', 'text' => "*From*\n".($reported->from_address ?: '—')],
            ['type' => 'mrkdwn', 'text' => "*Subject*\n".($reported->subject ?: '—')],
            ['type' => 'mrkdwn', 'text' => "*Reported at*\n".$reported->created_at->toDateTimeString().' UTC'],
        ];

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'CyberGuard report #'.$reported->id,
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'fields' => $fields,
            ],
            [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => 'Open in CyberGuard', 'emoji' => true],
                        'url' => $adminUrl,
                    ],
                ],
            ],
        ];

        if ($reported->analyst_notes) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Analyst notes*\n".mb_substr($reported->analyst_notes, 0, 900),
                ],
            ];
        }

        return $blocks;
    }

    private function statusLabel(ReportedMessage $reported): string
    {
        return match ($reported->analyst_status) {
            null, '', 'pending' => 'Under review',
            'analyst_confirmed_real' => 'Confirmed phishing',
            'analyst_confirmed_spam' => 'Confirmed spam',
            'false_positive' => 'Safe / not phishing',
            'analyst_confirmed_simulation' => 'Confirmed simulation',
            default => ucwords(str_replace('_', ' ', (string) $reported->analyst_status)),
        };
    }

    private function statusEmoji(ReportedMessage $reported): string
    {
        return match ($reported->analyst_status) {
            null, '', 'pending' => ':hourglass_flowing_sand:',
            'analyst_confirmed_real' => ':rotating_light:',
            'analyst_confirmed_spam' => ':warning:',
            'false_positive' => ':white_check_mark:',
            'analyst_confirmed_simulation' => ':large_blue_circle:',
            default => ':information_source:',
        };
    }
}

