<?php

namespace App\Console\Commands;

use App\Jobs\SendPhishingSimulationJob;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use Illuminate\Console\Command;

class RetryFailedPhishingMessagesCommand extends Command
{
    protected $signature = 'phishing:retry-failed
                            {--campaign= : Campaign ID to retry (optional; default: all campaigns)}
                            {--batch=100 : Max number of failed messages to retry per run}';

    protected $description = 'Requeue and retry phishing messages that failed to send.';

    public function handle(): int
    {
        $campaignId = $this->option('campaign');
        $batch = (int) $this->option('batch');
        $batch = $batch > 0 && $batch <= 500 ? $batch : 100;

        $query = PhishingMessage::where('status', 'failed');
        if ($campaignId !== null && $campaignId !== '') {
            $query->where('campaign_id', (int) $campaignId);
        }
        $messages = $query->orderBy('id')->limit($batch)->get();

        if ($messages->isEmpty()) {
            $this->info('No failed messages to retry.');
            return self::SUCCESS;
        }

        foreach ($messages as $msg) {
            $msg->update([
                'status' => 'queued',
                'queued_at' => now(),
                'failure_reason' => null,
            ]);
            PhishingEvent::create([
                'message_id' => $msg->id,
                'event_type' => 'queued',
                'metadata' => ['retry' => true],
                'occurred_at' => now(),
            ]);
            SendPhishingSimulationJob::dispatch($msg);
        }

        $this->info('Dispatched ' . $messages->count() . ' failed message(s) for retry.');
        return self::SUCCESS;
    }
}
