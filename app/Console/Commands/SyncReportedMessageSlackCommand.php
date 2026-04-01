<?php

namespace App\Console\Commands;

use App\Models\ReportedMessage;
use App\Services\SlackReportAlertService;
use Illuminate\Console\Command;
use Throwable;

class SyncReportedMessageSlackCommand extends Command
{
    protected $signature = 'cyberguard:sync-slack {reported_message_id : Reported message ID from /admin/reports/{id}}';

    protected $description = 'Push or update the Slack alert for a reported message immediately (bypasses the queue)';

    public function handle(SlackReportAlertService $slack): int
    {
        $id = (int) $this->argument('reported_message_id');
        $reported = ReportedMessage::withoutGlobalScope('tenant')
            ->with(['tenant', 'analyst'])
            ->find($id);

        if (! $reported) {
            $this->error("Reported message #{$id} not found.");

            return self::FAILURE;
        }

        $tenant = $reported->tenant;
        if (! $tenant || ! $tenant->slack_alerts_enabled) {
            $this->error('Slack alerts are disabled for this report\'s tenant.');

            return self::FAILURE;
        }
        if (trim((string) $tenant->slack_bot_token) === '') {
            $this->error('Tenant has no slack_bot_token configured.');

            return self::FAILURE;
        }

        $this->info('Queue name for automatic sync: '.config('phishing.slack_queue').' (workers must listen to this queue).');

        try {
            $slack->syncReportAlert($reported->fresh(['tenant', 'analyst']));
            $this->info('Slack sync completed. Check logs for "Slack chat.update succeeded" or "Slack chat.postMessage succeeded".');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
