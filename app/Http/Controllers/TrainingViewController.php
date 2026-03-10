<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\PhishingMessage;
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

        if ($message) {
            $template = $message->campaign->template;
            if ($template->training_page_id) {
                $page = LandingPage::find($template->training_page_id);
                if ($page && ! empty($page->html_content)) {
                    $content = $this->sanitizeTrainingHtml($page->html_content);
                }
            }
        }

        return view('training.show', [
            'content' => $content,
            'showBanner' => $showBanner,
            'token' => $token,
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
