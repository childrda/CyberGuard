<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PhishingMessage;
use App\Models\PhishingEvent;
use App\Models\TrainingCompletion;
use App\Services\Gamification\PointsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Show training/landing pages after click or report. Includes simulation banner for admin review.
 */
class TrainingViewController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $message = PhishingMessage::where('tracking_token', $token)->first();
        $content = $this->defaultTrainingContent();
        $showBanner = true;
        $indicators = $this->defaultIndicators();

        if ($message) {
            $message->load('campaign.template');
            $template = $message->campaign->template;
            if ($template->training_page_id) {
                $page = LandingPage::find($template->training_page_id);
                if ($page && ! empty($page->html_content)) {
                    $content = $this->sanitizeTrainingHtml($page->html_content);
                }
            }
            $indicators = $this->indicatorsForMessage($message);
            $this->recordTrainingCompletedOnce($message);
        }

        return view('training.show', [
            'content' => $content,
            'showBanner' => $showBanner,
            'token' => $token,
            'indicators' => $indicators,
        ]);
    }

    public function thanks(): View
    {
        return view('training.thanks', ['showBanner' => true]);
    }

    public function capture(string $token): View
    {
        $message = PhishingMessage::where('tracking_token', $token)->first();
        if (! $message) {
            return view('training.show', [
                'content' => $this->defaultTrainingContent(),
                'showBanner' => true,
                'token' => $token,
            ]);
        }

        return view('training.capture', [
            'token' => $token,
            'actionUrl' => url('/t/'.$token.'/submit'),
        ]);
    }

    private function defaultTrainingContent(): string
    {
        return '<h1>This was a simulated phishing exercise</h1>
        <p>You clicked a link in a training email. No real data was collected.</p>
        <p>Learn to spot phishing: check sender address, hover links before clicking, and report suspicious messages.</p>
        <p><a href="'.url('/training/thanks').'">Continue</a></p>';
    }

    /**
     * Safe, generic phishing indicators to highlight (no user/sender data).
     */
    private function defaultIndicators(): array
    {
        return [
            'Suspicious or unexpected links',
            'Sender address that doesn’t match the organization',
            'Urgent or threatening language',
            'Requests for passwords or personal information',
        ];
    }

    /**
     * Build indicators for this simulation (safe labels only; no PII).
     */
    private function indicatorsForMessage(PhishingMessage $message): array
    {
        $indicators = $this->defaultIndicators();
        $campaign = $message->campaign;
        $template = $campaign->template ?? null;
        if ($template && $template->subject) {
            array_unshift($indicators, 'Subject line designed to prompt quick action');
        }
        return array_slice(array_unique($indicators), 0, 6);
    }

    /**
     * Record training viewed and award points once per message (idempotent).
     */
    private function recordTrainingCompletedOnce(PhishingMessage $message): void
    {
        $existing = TrainingCompletion::where('phishing_message_id', $message->id)->exists();
        if ($existing) {
            return;
        }
        $campaign = $message->campaign;
        $tenantId = $campaign->tenant_id ?? null;
        if ($tenantId === null) {
            return;
        }

        TrainingCompletion::create([
            'phishing_message_id' => $message->id,
            'completed_at' => now(),
        ]);
        PhishingEvent::create([
            'message_id' => $message->id,
            'event_type' => 'training_viewed',
            'occurred_at' => now(),
        ]);
        $points = config('phishing.scoring.training_completed', 100);
        if ($points > 0) {
            app(PointsService::class)->award(
                $tenantId,
                $message->recipient_email,
                'training_completed',
                $points,
                ['reason' => 'Completed training', 'campaign_id' => $campaign->id]
            );
        }
    }

    /**
     * Sanitize HTML from landing pages to prevent stored XSS. Allows only safe tags;
     * strips script, iframe, object, and javascript:/data: in href/src.
     */
    private function sanitizeTrainingHtml(string $html): string
    {
        $allowed = '<p><a><h1><h2><h3><h4><ul><ol><li><strong><em><b><i><u><br><hr><div><span>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s+(href|src)\s*=\s*["\'][^"\']*javascript:[^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+(href|src)\s*=\s*["\'][^"\']*data:[^"\']*["\']/i', '', $html);
        return $html;
    }
}
