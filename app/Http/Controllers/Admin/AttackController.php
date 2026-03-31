<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\PhishingAttack;
use App\Models\SystemLog;
use App\Services\GmailSimulationMailer;
use App\Services\PlaceholderReplacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttackController extends Controller
{
    public function index(Request $request): View
    {
        $allowedPerPage = [10, 20, 40, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = PhishingAttack::query()
            ->when($request->input('difficulty'), fn ($q) => $q->where('difficulty_rating', $request->difficulty))
            ->when($request->input('active'), fn ($q) => $q->where('active', $request->active === '1'));
        $attacks = $query->orderBy('difficulty_rating')->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());
        return view('admin.attacks.index', compact('attacks', 'perPage', 'allowedPerPage'));
    }

    public function create(): View
    {
        $this->authorize('create', PhishingAttack::class);
        $landingPages = LandingPage::all();
        return view('admin.attacks.create', compact('landingPages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PhishingAttack::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:500'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'difficulty_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'landing_page_type' => ['nullable', 'string', 'in:training,credential_capture'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ]);
        $validated['tenant_id'] = \App\Models\Tenant::currentId();
        $validated['tags'] = isset($validated['tags']) && $validated['tags'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $validated['tags']))))
            : null;
        $validated['active'] = $request->boolean('active', true);
        $validated['times_sent'] = 0;
        $validated['times_clicked'] = 0;
        PhishingAttack::create($validated);
        return redirect()->route('admin.attacks.index')->with('success', 'Attack template created.');
    }

    public function show(PhishingAttack $attack): View
    {
        $this->authorize('view', $attack);
        $attack->load('trainingPage');
        return view('admin.attacks.show', compact('attack'));
    }

    public function edit(PhishingAttack $attack): View
    {
        $this->authorize('update', $attack);
        $landingPages = LandingPage::all();
        return view('admin.attacks.edit', compact('attack', 'landingPages'));
    }

    public function update(Request $request, PhishingAttack $attack): RedirectResponse
    {
        $this->authorize('update', $attack);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:500'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'difficulty_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'landing_page_type' => ['nullable', 'string', 'in:training,credential_capture'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ]);
        $validated['active'] = $request->boolean('active', true);
        $validated['tags'] = isset($validated['tags']) && $validated['tags'] !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $validated['tags']))))
            : null;
        $attack->update($validated);
        return redirect()->route('admin.attacks.show', $attack)->with('success', 'Attack template updated.');
    }

    public function destroy(PhishingAttack $attack): RedirectResponse
    {
        $this->authorize('delete', $attack);
        $attack->delete();
        return redirect()->route('admin.attacks.index')->with('success', 'Attack template deleted.');
    }

    /**
     * Preview attack with sample placeholder data (no send).
     */
    public function preview(PhishingAttack $attack): View
    {
        $this->authorize('view', $attack);
        $placeholders = app(PlaceholderReplacementService::class);
        $context = $placeholders->sampleContext();
        $subject = $placeholders->replace($attack->subject, $context);
        $htmlBody = $placeholders->replace($attack->html_body, $context);
        $textBody = $attack->text_body ? $placeholders->replace($attack->text_body, $context) : null;
        return view('admin.attacks.preview', [
            'attack' => $attack,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
            'sample_context' => $context,
        ]);
    }

    /**
     * Send a test email to an approved address (allowed domain).
     */
    public function sendTest(Request $request, PhishingAttack $attack): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $attack);
        $validated = $request->validate([
            'to' => ['required', 'email'],
        ]);
        $to = $validated['to'];
        $tenant = \App\Models\Tenant::current();
        $allowedDomains = $tenant ? $tenant->getAllowedDomainsList() : [];
        if (empty($allowedDomains)) {
            return response()->json(['message' => 'No allowed domains configured for this tenant.'], 422);
        }
        $domainGuard = app(\App\Services\DomainGuardService::class);
        if (! $domainGuard->isAllowed($to, $allowedDomains)) {
            return response()->json(['message' => 'Test emails can only be sent to allowed domains. Add this address to tenant allowed domains or use an approved internal address.'], 422);
        }
        $token = 'test-'.uniqid();
        $placeholders = app(PlaceholderReplacementService::class);
        $context = $placeholders->sampleContext($token);
        $subject = $placeholders->replace($attack->subject, $context);
        $htmlBody = $placeholders->replace($attack->html_body, $context);
        $textBody = $attack->text_body ? $placeholders->replace($attack->text_body, $context) : strip_tags($htmlBody);
        $mailer = app(GmailSimulationMailer::class);
        $htmlBody = $mailer->injectTrackingIntoBody($htmlBody, $token);
        try {
            $mailer->send(
                to: $to,
                subject: $subject,
                htmlBody: $htmlBody,
                textBody: $textBody,
                fromName: $attack->from_name,
                fromEmail: $attack->from_email,
                replyTo: $attack->reply_to
            );
            return $request->wantsJson()
                ? response()->json(['message' => 'Test email sent to '.$to])
                : redirect()->route('admin.attacks.show', $attack)->with('success', 'Test email sent to '.$to);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $userMessage = $message;
            if (str_contains($message, '421') || str_contains($message, 'Try again later') || str_contains($message, 'Expected response code')) {
                $userMessage = 'Google SMTP temporarily rejected the connection (421 Try again later). Wait a few minutes and try again. '
                    .'If it persists, check your Google Workspace SMTP relay settings and allowlisted IPs: https://support.google.com/a/answer/3221692';
            }
            SystemLog::log('mail_failed', 'Test attack email failed: '.$message, [
                'attack_id' => $attack->id,
                'attack_name' => $attack->name,
                'to' => $to,
                'exception' => get_class($e),
            ]);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Send failed: '.$userMessage], 500);
            }
            return redirect()->back()->with('error', 'Send failed: '.$userMessage);
        }
    }

    /**
     * Validate attack content; return warnings (empty subject, missing placeholders, etc.).
     */
    public function validateContent(PhishingAttack $attack): JsonResponse
    {
        $this->authorize('view', $attack);
        $warnings = [];
        if (trim($attack->subject ?? '') === '') {
            $warnings[] = ['type' => 'empty_subject', 'message' => 'Subject is empty.'];
        }
        $placeholders = PlaceholderReplacementService::supportedPlaceholders();
        foreach ($placeholders as $p) {
            $tag = '{{'.$p.'}}';
            if (stripos($attack->html_body ?? '', $tag) !== false || stripos($attack->subject ?? '', $tag) !== false) {
                // Present in content – OK
            }
        }
        // Check for unreplaced placeholder-like strings (optional)
        if (preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $attack->subject.' '.$attack->html_body, $m)) {
            $unknown = array_diff(array_map('strtolower', array_unique($m[1])), array_map('strtolower', $placeholders));
            if (! empty($unknown)) {
                $warnings[] = ['type' => 'unknown_placeholder', 'message' => 'Unknown placeholders: '.implode(', ', array_unique($unknown)).'. Supported: '.implode(', ', $placeholders)];
            }
        }
        return response()->json(['valid' => empty($warnings), 'warnings' => $warnings]);
    }
}
