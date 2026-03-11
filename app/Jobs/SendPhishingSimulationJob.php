<?php

namespace App\Jobs;

use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Services\GmailSimulationMailer;
use App\Services\PlaceholderReplacementService;
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

    public function handle(GmailSimulationMailer $mailer, PlaceholderReplacementService $placeholders): void
    {
        if (! config('phishing.simulation_enabled', false)) {
            return;
        }

        $msg = $this->message;
        $msg->load('campaign.template', 'attack');

        $attack = $msg->attack;
        $template = $msg->campaign->template;
        if ($attack) {
            $subject = $attack->subject;
            $htmlBody = $attack->html_body;
            $textBody = $attack->text_body ?? strip_tags($attack->html_body);
            $fromName = $attack->from_name ?? $template?->sender_name;
            $fromEmail = $attack->from_email ?? $template?->sender_email;
            $replyTo = $attack->reply_to ?? $template?->reply_to;
        } else {
            $subject = $template->subject;
            $htmlBody = $template->html_body;
            $textBody = $template->text_body;
            $fromName = $template->sender_name;
            $fromEmail = $template->sender_email;
            $replyTo = $template->reply_to;
        }

        $context = $placeholders->contextForMessage($msg);
        $subject = $placeholders->replace($subject, $context);
        $htmlBody = $placeholders->replace($htmlBody, $context);
        if ($textBody !== null) {
            $textBody = $placeholders->replace($textBody, $context);
        }

        $body = $mailer->injectTrackingIntoBody($htmlBody, $msg->tracking_token);

        try {
            $result = $mailer->send(
                to: $msg->recipient_email,
                subject: $subject,
                htmlBody: $body,
                textBody: $textBody,
                fromName: $fromName,
                fromEmail: $fromEmail,
                replyTo: $replyTo
            );

            $msg->update([
                'status' => 'sent',
                'sent_at' => now(),
                'message_id' => $result['message_id'] ?? null,
            ]);
            if ($attack) {
                $attack->increment('times_sent');
            }
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
