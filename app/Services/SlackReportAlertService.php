<?php

namespace App\Services;

use App\Models\ReportedMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            Log::warning('Slack chat.update failed; posting new message', [
                'reported_message_id' => $reported->id,
                'error' => $error,
                'response_metadata' => $updateResponse['response_metadata'] ?? null,
            ]);
            if (! in_array($error, ['message_not_found', 'channel_not_found', 'invalid_blocks', 'cant_update_message'], true)) {
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

        $result = $response->json() ?: [];
        if (($result['ok'] ?? false) && ! empty($result['warning'])) {
            Log::info('Slack API warning', [
                'method' => $method,
                'warning' => $result['warning'],
            ]);
        }

        return $result;
    }

    private function buildSummaryText(ReportedMessage $reported): string
    {
        $flags = $this->userActionFlags($reported);
        $tags = [];
        if ($flags['clicked_link']) {
            $tags[] = 'CLICKED LINK';
        }
        if ($flags['entered_info']) {
            $tags[] = 'ENTERED INFO';
        }
        if ($flags['entered_password']) {
            $tags[] = 'ENTERED PASSWORD';
        }
        if ($reported->reporter_mailbox_cleared_at) {
            $tags[] = 'RECALLED';
        }
        if ($reported->remediation_via_google_admin) {
            $tags[] = 'GOOGLE ADMIN REMOVAL';
        }
        $tagSuffix = $tags !== [] ? ' ['.implode('] [', $tags).']' : '';

        return sprintf(
            '[%s] %s reported by %s: %s%s',
            strtoupper($reported->report_type ?: 'phish'),
            $this->statusLabel($reported),
            $reported->reporter_email ?: 'unknown',
            $reported->subject ?: '(no subject)',
            $tagSuffix
        );
    }

    private function buildBlocks(ReportedMessage $reported): array
    {
        $flags = $this->userActionFlags($reported);
        $status = $this->statusLabel($reported);
        $statusEmoji = $this->statusEmoji($reported);
        $adminUrl = rtrim((string) config('app.url'), '/').'/admin/reports/'.$reported->id;

        $clickedText = $flags['clicked_link'] ? ':warning: *Yes*' : 'No';
        $enteredInfoText = $flags['entered_info'] ? ':warning: *Yes*' : 'No';
        if ($flags['entered_password']) {
            $enteredInfoText .= "\n_(password also reported)_";
        }

        $mailboxText = $reported->reporter_mailbox_cleared_at
            ? ':white_check_mark: *Recalled* — '.$reported->reporter_mailbox_cleared_at->toDateTimeString().' UTC'
            : '_Not yet_';

        $domainWideText = $reported->remediation_via_google_admin
            ? ':gear: *Google Admin* investigation tool\n_(CyberGuard did not bulk-remove)_'
            : '_Not flagged_';

        // One section, max 10 fields (Slack limit). Risk/remediation rows first so they stay visible.
        // Sanitize From/Subject/Reporter — raw `<email>` and `&` break mrkdwn and can make chat.update reject blocks.
        $allFields = [
            ['type' => 'mrkdwn', 'text' => "*Clicked a link*\n{$clickedText}"],
            ['type' => 'mrkdwn', 'text' => "*Entered sensitive info*\n{$enteredInfoText}"],
            ['type' => 'mrkdwn', 'text' => "*Reporter mailbox*\n{$mailboxText}"],
            ['type' => 'mrkdwn', 'text' => "*Domain-wide removal*\n{$domainWideText}"],
            ['type' => 'mrkdwn', 'text' => "*Status*\n{$statusEmoji} {$status}"],
            ['type' => 'mrkdwn', 'text' => "*Type*\n".strtoupper((string) ($reported->report_type ?: 'phish'))],
            ['type' => 'mrkdwn', 'text' => "*Reporter*\n".$this->slackSafeMrkdwnValue($reported->reporter_email)],
            ['type' => 'mrkdwn', 'text' => "*From*\n".$this->slackSafeMrkdwnValue($reported->from_address)],
            ['type' => 'mrkdwn', 'text' => "*Subject*\n".$this->slackSafeMrkdwnValue($reported->subject)],
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
        ];

        if ($flags['clicked_link'] || $flags['entered_info'] || $flags['entered_password']) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*:rotating_light: High priority*\n".$this->highRiskCalloutText($flags),
                ],
            ];
        }

        $blocks[] = [
            'type' => 'section',
            'fields' => $allFields,
        ];
        $blocks[] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => 'Open in CyberGuard', 'emoji' => true],
                    'url' => $adminUrl,
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

    /**
     * @param  array{clicked_link: bool, entered_info: bool, entered_password: bool}  $flags
     */
    private function highRiskCalloutText(array $flags): string
    {
        $parts = [];
        if ($flags['clicked_link']) {
            $parts[] = 'Reporter *clicked a link*.';
        }
        if ($flags['entered_info']) {
            $parts[] = 'Reporter *entered sensitive information*.';
        }
        if ($flags['entered_password']) {
            $parts[] = 'Reporter *entered a password*.';
        }

        return 'Treat as a potential compromise: '.implode(' ', $parts);
    }

    /**
     * @return array{clicked_link: bool, entered_info: bool, entered_password: bool}
     */
    private function userActionFlags(ReportedMessage $reported): array
    {
        $tokens = $this->normalizeUserActionTokens($reported->user_actions);

        $clicked = false;
        $enteredInfo = false;
        $enteredPassword = false;

        foreach ($tokens as $t) {
            if ($this->tokenMeansClickedLink($t)) {
                $clicked = true;
            }
            if ($this->tokenMeansEnteredInfo($t)) {
                $enteredInfo = true;
            }
            if ($this->tokenMeansEnteredPassword($t)) {
                $enteredPassword = true;
            }
        }

        return [
            'clicked_link' => $clicked,
            'entered_info' => $enteredInfo,
            'entered_password' => $enteredPassword,
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeUserActionTokens(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeUserActionTokens($decoded);
            }

            return [strtolower(trim($raw))];
        }
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $out[] = strtolower(trim($value));
            } elseif (is_bool($value) && $value === true && is_string($key) && trim($key) !== '') {
                $out[] = strtolower(trim($key));
            } elseif (is_array($value)) {
                foreach ($this->normalizeUserActionTokens($value) as $t) {
                    $out[] = $t;
                }
            }
        }

        return array_values(array_unique($out));
    }

    private function tokenMeansClickedLink(string $t): bool
    {
        if (in_array($t, ['clicked_link', 'clicked', 'click', 'i clicked the link'], true)) {
            return true;
        }

        return $t === 'clicked link' || str_contains($t, 'clicked_link');
    }

    private function tokenMeansEnteredInfo(string $t): bool
    {
        if (in_array($t, ['entered_info', 'entered_information', 'i entered information'], true)) {
            return true;
        }

        return str_contains($t, 'entered_info')
            || ($t !== 'entered_password' && str_contains($t, 'entered') && (str_contains($t, 'info') || str_contains($t, 'information')));
    }

    private function tokenMeansEnteredPassword(string $t): bool
    {
        return in_array($t, ['entered_password', 'password'], true) || str_contains($t, 'entered_password');
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

    /**
     * Neutralize characters that break Slack mrkdwn / Block Kit (e.g. Name ‹email› uses angle brackets).
     */
    private function slackSafeMrkdwnValue(?string $s, int $max = 900): string
    {
        if ($s === null || trim($s) === '') {
            return '—';
        }
        $s = mb_substr($s, 0, $max);

        // Fullwidth / lookalikes stop * _ ` & < > from triggering formatting or links
        return str_replace(
            ['\\', '&', '<', '>', '*', '_', '`'],
            ['＼', '＆', '‹', '›', '＊', '＿', '｀'],
            $s
        );
    }
}
