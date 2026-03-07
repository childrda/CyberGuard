<?php

namespace App\Http\Controllers;

use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public tracking routes: /t/{token} and /t/{token}/open.
 * Rate limited. Validates token, logs event, redirects to landing or training page.
 */
class TrackingController extends Controller
{
    public function click(Request $request, string $token)
    {
        $message = PhishingMessage::where('tracking_token', $token)->first();
        if (! $message) {
            Log::warning('Tracking click with invalid token');
            return $this->fallbackRedirect($request);
        }

        $redirectUrl = $request->query('r');
        if (! $redirectUrl || ! $this->isSafeRedirect($redirectUrl)) {
            $redirectUrl = $this->defaultLandingUrl($message);
        }

        PhishingEvent::create([
            'message_id' => $message->id,
            'event_type' => 'clicked',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('Referer'),
            'metadata' => ['redirect_to' => $redirectUrl],
            'occurred_at' => now(),
        ]);

        return redirect()->away($redirectUrl);
    }

    /**
     * Open tracking pixel. Best-effort only.
     */
    public function open(Request $request, string $token)
    {
        $message = PhishingMessage::where('tracking_token', $token)->first();
        if (! $message) {
            return response('', 204)->header('Content-Type', 'image/gif');
        }

        PhishingEvent::create([
            'message_id' => $message->id,
            'event_type' => 'opened',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'occurred_at' => now(),
        ]);

        return response('', 204)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Credential capture form submission (training only). We never store real passwords.
     */
    public function submit(Request $request, string $token)
    {
        $message = PhishingMessage::where('tracking_token', $token)->first();
        if (! $message) {
            return redirect()->route('training.thanks');
        }

        PhishingEvent::create([
            'message_id' => $message->id,
            'event_type' => 'submitted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['placeholder' => config('phishing.credential_capture_placeholder', 'submitted')],
            'occurred_at' => now(),
        ]);

        return redirect()->route('training.thanks');
    }

    private function defaultLandingUrl(PhishingMessage $message): string
    {
        $message->load('campaign.template');
        $template = $message->campaign->template;
        $landingType = $template->landing_page_type ?? 'training';
        $base = rtrim(config('app.url'), '/');

        if ($landingType === 'credential_capture') {
            return $base.'/t/'.$message->tracking_token.'/capture';
        }

        return $base.'/training/'.$message->tracking_token;
    }

    private function isSafeRedirect(?string $url): bool
    {
        if (! $url) {
            return false;
        }
        $allowed = [parse_url(config('app.url'), PHP_URL_HOST)];
        $host = parse_url($url, PHP_URL_HOST);
        return $host && in_array($host, $allowed, true);
    }

    private function fallbackRedirect(Request $request)
    {
        return redirect()->to(config('app.url').'/training/unknown');
    }
}
