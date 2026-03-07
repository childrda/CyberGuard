<?php

namespace App\Jobs;

use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Services\GmailSimulationMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPhishingSimulationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PhishingMessage $message
    ) {
        $this->onQueue('phishing-send');
    }

    public function handle(GmailSimulationMailer $mailer): void
    {
        if (! config('phishing.simulation_enabled', false)) {
            return;
        }

        $msg = $this->message;
        $msg->load('campaign.template');

        $template = $msg->campaign->template;
        $body = $mailer->injectTrackingIntoBody($template->html_body, $msg->tracking_token);

        try {
            $result = $mailer->send(
                to: $msg->recipient_email,
                subject: $template->subject,
                htmlBody: $body,
                textBody: $template->text_body,
                fromName: $template->sender_name,
                fromEmail: $template->sender_email,
                replyTo: $template->reply_to
            );

            $msg->update([
                'status' => 'sent',
                'sent_at' => now(),
                'message_id' => $result['message_id'] ?? null,
            ]);
            PhishingEvent::create([
                'message_id' => $msg->id,
                'event_type' => 'sent',
                'metadata' => $result,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $msg->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            PhishingEvent::create([
                'message_id' => $msg->id,
                'event_type' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
                'occurred_at' => now(),
            ]);
            throw $e;
        }
    }
}
