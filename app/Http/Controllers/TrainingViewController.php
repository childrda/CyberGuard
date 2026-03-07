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
                if ($page) {
                    $content = $page->html_content ?? $content;
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
}
