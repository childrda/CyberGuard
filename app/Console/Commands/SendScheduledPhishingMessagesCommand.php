<?php

namespace App\Console\Commands;

use App\Jobs\SendPhishingSimulationJob;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use Illuminate\Console\Command;

class SendScheduledPhishingMessagesCommand extends Command
{
    protected $signature = 'phishing:send-scheduled
                            {--batch=50 : Max number of messages to process per run}';

    protected $description = 'Send phishing messages that are scheduled for now or earlier (campaign window).';

    public function handle(): int
    {
        $batch = (int) $this->option('batch');
        $batch = $batch > 0 && $batch <= 500 ? $batch : 50;

        $messages = PhishingMessage::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($batch)
            ->get();

        if ($messages->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($messages as $msg) {
            $msg->update(['status' => 'queued', 'queued_at' => now()]);
            PhishingEvent::create([
                'message_id' => $msg->id,
                'event_type' => 'queued',
                'occurred_at' => now(),
            ]);
            SendPhishingSimulationJob::dispatch($msg);
        }

        $this->info('Dispatched ' . $messages->count() . ' scheduled message(s).');
        return self::SUCCESS;
    }
}
