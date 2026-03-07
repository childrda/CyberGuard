<?php

namespace App\Jobs;

use App\Models\MailboxActionLog;
use App\Models\RemediationJob;
use App\Models\RemediationJobItem;
use App\Services\GmailRemovalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRemediationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public RemediationJob $job
    ) {
        $this->onQueue('remediation');
    }

    public function handle(): void
    {
        $reported = $this->job->reportedMessage;
        $tenant = $reported->tenant;
        if (! $tenant) {
            $this->job->update(['status' => RemediationJob::STATUS_FAILED, 'failure_summary' => 'No tenant']);
            return;
        }

        config(['phishing.google_credentials_path' => $tenant->google_credentials_path]);
        config(['phishing.google_admin_user' => $tenant->google_admin_user]);
        config(['phishing.google_domain' => $tenant->domain]);
        config(['phishing.gmail_removal_enabled' => true]);

        $removal = new \App\Services\GmailRemovalService();
        $messageIdHeader = $reported->message_id_header ?? $this->extractMessageId($reported->headers);
        $users = $removal->listDomainUsers($tenant->domain);
        $dryRun = $this->job->dry_run;

        $success = 0;
        $failed = 0;
        foreach ($users as $email) {
            $item = RemediationJobItem::create([
                'remediation_job_id' => $this->job->id,
                'mailbox_email' => $email,
                'message_identifier' => $messageIdHeader,
                'status' => 'pending',
            ]);
            if ($dryRun) {
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->job->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => 'dry_run',
                    'actor_id' => $this->job->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => 'Skipped (dry run)',
                    'created_at' => now(),
                ]);
                $item->update(['status' => 'logged', 'processed_at' => now()]);
                $success++;
                continue;
            }
            $result = $removal->trashMessageByRfc822MessageId($email, $messageIdHeader ?? '');
            if ($result['ok']) {
                if (! empty($result['skipped'])) {
                    $item->update(['status' => 'skipped', 'processed_at' => now()]);
                } else {
                    $item->update(['status' => 'success', 'processed_at' => now()]);
                    $success++;
                }
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->job->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => $result['ok'] ? 'success' : 'failed',
                    'actor_id' => $this->job->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => $result['error'] ?? null,
                    'created_at' => now(),
                ]);
            } else {
                $item->update(['status' => 'failed', 'error_message' => $result['error'] ?? '', 'processed_at' => now()]);
                $failed++;
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->job->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => 'failed',
                    'actor_id' => $this->job->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => $result['error'] ?? null,
                    'created_at' => now(),
                ]);
            }
        }

        $status = $failed === 0 ? RemediationJob::STATUS_REMOVED : ($success > 0 ? RemediationJob::STATUS_PARTIALLY_FAILED : RemediationJob::STATUS_FAILED);
        $this->job->update([
            'status' => $status,
            'completed_at' => now(),
            'failure_summary' => $failed > 0 ? "{$failed} failed" : null,
        ]);
    }

    private function extractMessageId(?array $headers): ?string
    {
        if (! $headers) {
            return null;
        }
        foreach (['Message-ID', 'message-id'] as $key) {
            if (! empty($headers[$key])) {
                return is_string($headers[$key]) ? trim($headers[$key]) : null;
            }
        }
        return null;
    }
}
