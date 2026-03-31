<?php

namespace App\Jobs;

use App\Models\MailboxActionLog;
use App\Models\RemediationJob;
use App\Models\RemediationJobItem;
use App\Services\GmailRemovalService;
use App\Services\RemediationPreflightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRemediationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public RemediationJob $remediationJob
    ) {
        $this->onQueue('remediation');
    }

    public function handle(): void
    {
        $reported = $this->remediationJob->reportedMessage;
        $tenant = $reported->tenant;
        if (! $tenant) {
            $this->remediationJob->update(['status' => RemediationJob::STATUS_FAILED, 'failure_summary' => 'No tenant']);
            return;
        }

        $preflight = app(RemediationPreflightService::class)->checkTenant($tenant);
        if (! $preflight['ok']) {
            $this->remediationJob->update([
                'status' => RemediationJob::STATUS_FAILED,
                'failure_summary' => $preflight['error'] ?? 'Remediation preflight failed',
                'completed_at' => now(),
            ]);
            return;
        }

        config(['phishing.google_credentials_path' => $tenant->google_credentials_path]);
        config(['phishing.google_admin_user' => $tenant->google_admin_user]);
        config(['phishing.google_domain' => $tenant->domain]);
        config(['phishing.gmail_removal_enabled' => true]);

        $removal = app(GmailRemovalService::class);
        $messageIdHeader = $reported->message_id_header ?? $this->extractMessageId($reported->headers);
        if (! $messageIdHeader) {
            $this->remediationJob->update([
                'status' => RemediationJob::STATUS_FAILED,
                'failure_summary' => 'No Message-ID header available; cannot perform domain-wide remediation.',
                'completed_at' => now(),
            ]);
            return;
        }

        $users = $removal->listDomainUsers($tenant->domain);
        if (empty($users)) {
            $this->remediationJob->update([
                'status' => RemediationJob::STATUS_FAILED,
                'failure_summary' => 'Could not list domain users. Check Admin SDK delegation/scopes and google_admin_user.',
                'completed_at' => now(),
            ]);
            return;
        }
        $dryRun = $this->remediationJob->dry_run;

        $removedCount = 0;
        $skippedCount = 0;
        $dryRunCount = 0;
        $failedCount = 0;

        foreach ($users as $email) {
            $item = RemediationJobItem::create([
                'remediation_job_id' => $this->remediationJob->id,
                'mailbox_email' => $email,
                'message_identifier' => $messageIdHeader,
                'status' => 'pending',
            ]);
            if ($dryRun) {
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->remediationJob->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => 'dry_run',
                    'actor_id' => $this->remediationJob->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => 'Skipped (dry run)',
                    'created_at' => now(),
                ]);
                $item->update(['status' => 'logged', 'processed_at' => now()]);
                $dryRunCount++;
                continue;
            }
            $result = $removal->trashMessageByRfc822MessageId($email, $messageIdHeader ?? '');
            if ($result['ok']) {
                if (! empty($result['skipped'])) {
                    $item->update(['status' => 'skipped', 'processed_at' => now()]);
                    $skippedCount++;
                } else {
                    $item->update(['status' => 'success', 'processed_at' => now()]);
                    $removedCount++;
                }
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->remediationJob->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => ! empty($result['skipped']) ? 'skipped' : 'success',
                    'actor_id' => $this->remediationJob->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => $result['error'] ?? null,
                    'created_at' => now(),
                ]);
            } else {
                $item->update(['status' => 'failed', 'error_message' => $result['error'] ?? '', 'processed_at' => now()]);
                $failedCount++;
                MailboxActionLog::create([
                    'tenant_id' => $tenant->id,
                    'remediation_job_id' => $this->remediationJob->id,
                    'remediation_job_item_id' => $item->id,
                    'mailbox_email' => $email,
                    'message_identifier' => $messageIdHeader,
                    'action_attempted' => 'trash',
                    'action_result' => 'failed',
                    'actor_id' => $this->remediationJob->approved_by,
                    'actor_type' => 'automation',
                    'api_response_summary' => $result['error'] ?? null,
                    'created_at' => now(),
                ]);
            }
        }

        $status = $this->resolveFinalStatus($dryRun, $removedCount, $skippedCount, $dryRunCount, $failedCount);
        $failureSummary = null;
        if ($failedCount > 0) {
            $failureSummary = "{$failedCount} failed";
        } elseif ($removedCount === 0 && $skippedCount > 0 && ! $dryRun) {
            $failureSummary = "No mailbox copy found to remove ({$skippedCount} skipped)";
        } elseif ($dryRun && $dryRunCount > 0) {
            $failureSummary = "Simulated: {$dryRunCount} mailboxes (no messages trashed)";
        }

        $this->remediationJob->update([
            'status' => $status,
            'completed_at' => now(),
            'failure_summary' => $failureSummary,
            'removed_count' => $removedCount,
            'skipped_count' => $skippedCount,
            'dry_run_count' => $dryRunCount,
            'failed_count' => $failedCount,
        ]);
    }

    private function resolveFinalStatus(bool $dryRun, int $removedCount, int $skippedCount, int $dryRunCount, int $failedCount): string
    {
        if ($dryRun && $dryRunCount > 0 && $removedCount === 0 && $failedCount === 0) {
            return RemediationJob::STATUS_DRY_RUN_COMPLETED;
        }
        if (! $dryRun && $removedCount === 0 && $skippedCount > 0 && $failedCount === 0) {
            return RemediationJob::STATUS_FAILED;
        }
        if ($failedCount > 0) {
            return $removedCount > 0 ? RemediationJob::STATUS_PARTIALLY_FAILED : RemediationJob::STATUS_FAILED;
        }
        return RemediationJob::STATUS_REMOVED;
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
