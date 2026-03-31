<?php

namespace App\Services;

use App\Models\ReportedMessage;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\ModifyMessageRequest;
use Google\Service\Directory;
use Illuminate\Support\Facades\Log;

/**
 * Trash phishing messages from Gmail using a service account with domain-wide delegation.
 * Used after an analyst confirms a report as real phishing.
 */
class GmailRemovalService
{
    protected ?GoogleClient $client = null;
    protected ?string $lastError = null;

    public function __construct()
    {
        if (! config('phishing.gmail_removal_enabled') || ! config('phishing.google_credentials_path')) {
            $this->lastError = 'Gmail removal disabled or credentials path missing in config.';
            return;
        }
        $path = config('phishing.google_credentials_path');
        if (! is_file($path)) {
            $this->lastError = 'Credentials file not found or not readable: '.$path;
            Log::warning('GmailRemovalService: '.$this->lastError);
            return;
        }
        try {
            $this->client = new GoogleClient;
            $this->client->setAuthConfig($path);
            $this->client->setScopes([
                Gmail::GMAIL_MODIFY,
                Gmail::GMAIL_READONLY,
                Directory::ADMIN_DIRECTORY_USER_READONLY,
            ]);
            $this->client->setSubject(null); // set per-request when impersonating
        } catch (\Throwable $e) {
            $this->lastError = 'Failed to initialize Google client: '.$e->getMessage();
            Log::error('GmailRemovalService: '.$this->lastError);
            $this->client = null;
        }
    }

    /**
     * Trash the reported message in the reporter's mailbox.
     */
    public function removeFromUserMailbox(ReportedMessage $reported): array
    {
        if (! $this->client) {
            $this->lastError = 'Gmail removal not configured.';
            return ['ok' => false, 'error' => 'Gmail removal not configured.'];
        }
        if (! $reported->gmail_message_id || ! $reported->reporter_email) {
            return ['ok' => false, 'error' => 'Missing message id or reporter email.'];
        }

        try {
            $this->client->setSubject($reported->reporter_email);
            $gmail = new Gmail($this->client);
            $gmail->users_messages->trash('me', $reported->gmail_message_id);

            // Verify labels after trash action. In some edge cases Gmail accepts
            // the call but message remains in INBOX due to thread/label state.
            $msg = $gmail->users_messages->get('me', $reported->gmail_message_id, ['format' => 'minimal']);
            $labels = $msg->getLabelIds() ?: [];
            if (in_array('INBOX', $labels, true)) {
                // Fallback: force remove INBOX and add TRASH labels explicitly.
                $mod = new ModifyMessageRequest([
                    'removeLabelIds' => ['INBOX'],
                    'addLabelIds' => ['TRASH'],
                ]);
                $gmail->users_messages->modify('me', $reported->gmail_message_id, $mod);
                $msg = $gmail->users_messages->get('me', $reported->gmail_message_id, ['format' => 'minimal']);
                $labels = $msg->getLabelIds() ?: [];
            }

            if (in_array('INBOX', $labels, true)) {
                return ['ok' => false, 'error' => 'Message still has INBOX label after remediation.'];
            }

            return ['ok' => true, 'message' => 'Message removed from reporter inbox.'];
        } catch (\Throwable $e) {
            Log::warning('GmailRemovalService removeFromUserMailbox failed: '.$e->getMessage());
            return ['ok' => false, 'error' => 'Operation failed: '.$e->getMessage()];
        }
    }

    /**
     * Find the same message (by Message-ID header) in all domain users' mailboxes and trash it.
     */
    public function removeFromAllMailboxes(ReportedMessage $reported): array
    {
        if (! $this->client) {
            $this->lastError = 'Gmail removal not configured.';
            return ['ok' => false, 'error' => 'Gmail removal not configured.'];
        }

        $messageIdHeader = $reported->message_id_header ?? $this->extractMessageIdFromHeaders($reported->headers);
        if (! $messageIdHeader) {
            return ['ok' => false, 'error' => 'No Message-ID header; cannot search domain-wide.'];
        }

        $domain = config('phishing.google_domain');
        if (! $domain) {
            $domain = $this->domainFromEmail($reported->reporter_email);
        }
        if (! $domain) {
            return ['ok' => false, 'error' => 'Domain could not be determined.'];
        }

        $users = $this->listDomainUsers($domain);
        if (empty($users)) {
            $extra = $this->lastError ? ' '.$this->lastError : '';
            return ['ok' => false, 'error' => 'Could not list domain users. Check Admin SDK scope and delegation.'.$extra];
        }

        $trashed = 0;
        $errors = [];
        foreach ($users as $userEmail) {
            $result = $this->trashMessageByRfc822MessageId($userEmail, $messageIdHeader);
            if ($result['ok'] && empty($result['skipped'])) {
                $trashed++;
            } elseif (! $result['ok']) {
                $errors[] = $userEmail.': '.($result['error'] ?? 'unknown');
            }
        }

        return [
            'ok' => true,
            'trashed_count' => $trashed,
            'users_checked' => count($users),
            'errors' => array_slice($errors, 0, 5),
        ];
    }

    public function trashMessageByRfc822MessageId(string $userEmail, string $rfc822MessageId): array
    {
        try {
            $this->client->setSubject($userEmail);
            $gmail = new Gmail($this->client);
            $list = $gmail->users_messages->listUsersMessages('me', [
                'q' => 'rfc822msgid:'.str_replace(['<', '>'], '', $rfc822MessageId),
                'maxResults' => 1,
            ]);
            $messages = $list->getMessages();
            if (! $messages || count($messages) === 0) {
                return ['ok' => true, 'skipped' => true];
            }
            $gmail->users_messages->trash('me', $messages[0]->getId());
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function listDomainUsers(string $domain): array
    {
        try {
            $admin = config('phishing.google_admin_user');
            if (! $admin) {
                Log::warning('GmailRemovalService: GOOGLE_ADMIN_USER not set; cannot list domain users.');
                return [];
            }
            $this->client->setSubject($admin);
            $directory = new Directory($this->client);
            $users = [];
            $pageToken = null;
            do {
                $resp = $directory->users->listUsers([
                    'domain' => $domain,
                    'maxResults' => 500,
                    'pageToken' => $pageToken,
                ]);
                foreach ($resp->getUsers() as $u) {
                    $users[] = $u->getPrimaryEmail();
                }
                $pageToken = $resp->getNextPageToken();
            } while ($pageToken);
            return $users;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('GmailRemovalService listDomainUsers failed: '.$e->getMessage(), [
                'domain' => $domain,
                'admin_user' => config('phishing.google_admin_user'),
                'credentials_path' => config('phishing.google_credentials_path'),
            ]);
            return [];
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    protected function extractMessageIdFromHeaders(?array $headers): ?string
    {
        if (! $headers) {
            return null;
        }
        foreach (['Message-ID', 'message-id'] as $key) {
            if (isset($headers[$key])) {
                return is_string($headers[$key]) ? trim($headers[$key]) : null;
            }
        }
        return null;
    }

    protected function domainFromEmail(string $email): ?string
    {
        $pos = strrpos($email, '@');
        return $pos !== false ? substr($email, $pos + 1) : null;
    }
}
