<?php

namespace App\Jobs;

use App\Models\ReportedMessage;
use App\Models\SystemLog;
use App\Services\SlackReportAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncReportedMessageToSlackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(
        public int $reportedMessageId
    ) {
        $this->onQueue(config('phishing.slack_queue', 'notifications'));
    }

    public function handle(SlackReportAlertService $slack): void
    {
        $reported = ReportedMessage::withoutGlobalScope('tenant')
            ->with(['tenant', 'analyst'])
            ->find($this->reportedMessageId);

        if (! $reported) {
            return;
        }

        $slack->syncReportAlert($reported);
    }

    public function failed(\Throwable $e): void
    {
        $reported = ReportedMessage::withoutGlobalScope('tenant')->find($this->reportedMessageId);

        Log::warning('Slack sync failed for reported message', [
            'reported_message_id' => $this->reportedMessageId,
            'tenant_id' => $reported?->tenant_id,
            'error' => $e->getMessage(),
        ]);

        if ($reported?->tenant_id) {
            SystemLog::create([
                'tenant_id' => $reported->tenant_id,
                'type' => 'integration_slack_failed',
                'message' => 'Slack report sync failed.',
                'context' => [
                    'reported_message_id' => $this->reportedMessageId,
                    'error' => $e->getMessage(),
                ],
            ]);
        }
    }
}

