<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPhishingSimulationJob;
use App\Models\PhishingAttack;
use App\Models\PhishingCampaign;
use App\Models\PhishingCampaignTarget;
use App\Models\PhishingEvent;
use App\Models\PhishingMessage;
use App\Models\PhishingTemplate;
use App\Services\AuditService;
use App\Services\PhishingCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        protected PhishingCampaignService $campaignService,
        protected AuditService $audit
    ) {}

    public function index(Request $request): View
    {
        $campaigns = PhishingCampaign::with('template', 'creator')
            ->when($request->input('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('create', PhishingCampaign::class);
        $templates = PhishingTemplate::where('active', true)->get();
        $attacks = PhishingAttack::where('active', true)->orderBy('difficulty_rating')->orderBy('name')->get();
        $tenant = \App\Models\Tenant::current();
        $directorySyncEnabled = $tenant && $tenant->directory_sync_enabled;
        return view('admin.campaigns.create', compact('templates', 'attacks', 'directorySyncEnabled'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PhishingCampaign::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'exists:phishing_templates,id'],
            'target_type' => ['required', 'in:user,group,ou,csv'],
            'target_identifier' => ['nullable', 'string'],
            'display_name' => ['nullable', 'string'],
            'workspace_groups' => ['nullable', 'array'],
            'workspace_groups.*' => ['string', 'email'],
            'workspace_ous' => ['nullable', 'array'],
            'workspace_ous.*' => ['string', 'max:500'],
            'attack_ids' => ['nullable', 'array'],
            'attack_ids.*' => ['exists:phishing_attacks,id'],
            'window_start' => ['nullable', 'date'],
            'window_end' => ['nullable', 'date', 'after_or_equal:window_start'],
            'emails_per_recipient' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $workspaceGroups = array_filter(array_map('trim', $validated['workspace_groups'] ?? []));
        $workspaceOus = array_filter(array_map('trim', $validated['workspace_ous'] ?? []));
        $useWorkspace = ($validated['target_type'] === 'group' || $validated['target_type'] === 'ou') && (count($workspaceGroups) > 0 || count($workspaceOus) > 0);
        if (! $useWorkspace && empty($validated['target_identifier'])) {
            $request->validate(['target_identifier' => ['required', 'string']]);
        }
        if (($validated['window_start'] ?? null) xor ($validated['window_end'] ?? null)) {
            $validated['window_start'] = null;
            $validated['window_end'] = null;
        }

        $campaign = PhishingCampaign::create([
            'tenant_id' => \App\Models\Tenant::currentId(),
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
            'status' => 'draft',
            'created_by' => auth()->id(),
            'window_start' => $validated['window_start'] ?? null,
            'window_end' => $validated['window_end'] ?? null,
            'emails_per_recipient' => $validated['emails_per_recipient'] ?? 1,
        ]);

        if ($useWorkspace) {
            foreach ($workspaceGroups as $email) {
                if ($email !== '') {
                    PhishingCampaignTarget::create([
                        'campaign_id' => $campaign->id,
                        'target_type' => 'group',
                        'target_identifier' => strtolower($email),
                        'display_name' => null,
                    ]);
                }
            }
            foreach ($workspaceOus as $path) {
                if ($path !== '') {
                    PhishingCampaignTarget::create([
                        'campaign_id' => $campaign->id,
                        'target_type' => 'ou',
                        'target_identifier' => $path,
                        'display_name' => null,
                    ]);
                }
            }
        } else {
            PhishingCampaignTarget::create([
                'campaign_id' => $campaign->id,
                'target_type' => $validated['target_type'],
                'target_identifier' => $validated['target_identifier'] ?? '',
                'display_name' => $validated['display_name'] ?? null,
            ]);
        }

        $attackIds = PhishingAttack::whereIn('id', $request->input('attack_ids', []))->pluck('id')->toArray();
        $campaign->attacks()->sync($attackIds);

        $this->audit->log('campaign_created', $campaign);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function show(PhishingCampaign $campaign): View
    {
        $this->authorize('view', $campaign);
        $campaign->load('template', 'targets', 'messages.events', 'messages.attack');
        $messageIds = $campaign->messages->pluck('id');
        $activities = \App\Models\PhishingEvent::whereIn('message_id', $messageIds)
            ->with(['message' => fn ($q) => $q->with('attack')])
            ->orderBy('occurred_at', 'desc')
            ->get();
        return view('admin.campaigns.show', compact('campaign', 'activities'));
    }

    public function edit(PhishingCampaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $templates = PhishingTemplate::where('active', true)->get();
        $attacks = PhishingAttack::where('active', true)->orderBy('difficulty_rating')->orderBy('name')->get();
        $campaign->load('attacks', 'targets');
        $canEditTargets = $campaign->messages()->count() === 0;
        return view('admin.campaigns.edit', compact('campaign', 'templates', 'attacks', 'canEditTargets'));
    }

    public function update(Request $request, PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);
        $canEditTargets = $campaign->messages()->count() === 0;
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'exists:phishing_templates,id'],
            'attack_ids' => ['nullable', 'array'],
            'attack_ids.*' => ['exists:phishing_attacks,id'],
            'window_start' => ['nullable', 'date'],
            'window_end' => ['nullable', 'date', 'after_or_equal:window_start'],
            'emails_per_recipient' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
        if ($canEditTargets) {
            $rules['target_type'] = ['required', 'in:user,group,ou,csv'];
            $rules['target_identifier'] = ['required', 'string'];
            $rules['display_name'] = ['nullable', 'string'];
        }
        $validated = $request->validate($rules);
        if (($validated['window_start'] ?? null) xor ($validated['window_end'] ?? null)) {
            $validated['window_start'] = null;
            $validated['window_end'] = null;
        }
        $old = $campaign->only(['name', 'template_id', 'window_start', 'window_end', 'emails_per_recipient']);
        $campaign->update([
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
            'window_start' => $validated['window_start'] ?? null,
            'window_end' => $validated['window_end'] ?? null,
            'emails_per_recipient' => $validated['emails_per_recipient'] ?? 1,
        ]);
        $attackIds = PhishingAttack::whereIn('id', $request->input('attack_ids', []))->pluck('id')->toArray();
        $campaign->attacks()->sync($attackIds);
        if ($canEditTargets) {
            $campaign->targets()->delete();
            PhishingCampaignTarget::create([
                'campaign_id' => $campaign->id,
                'target_type' => $validated['target_type'],
                'target_identifier' => $validated['target_identifier'],
                'display_name' => $validated['display_name'] ?? null,
            ]);
        }
        $this->audit->log('campaign_updated', $campaign, $old, $validated);
        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function destroy(PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);
        $campaign->delete();
        $this->audit->log('campaign_deleted', null, ['id' => $campaign->id]);
        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function approve(Request $request, PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('approve', $campaign);
        $request->validate(['approval_notes' => ['nullable', 'string']]);
        $campaign->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);
        $this->audit->log('campaign_approved', $campaign);
        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign approved.');
    }

    public function launch(PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('launch', $campaign);
        $result = $this->campaignService->launchCampaign($campaign);
        if ($result['ok']) {
            $msg = "Campaign launched. Recipients: {$result['accepted']}, Rejected: {$result['rejected']}.";
            if (! empty($result['scheduled_over_window'])) {
                $msg .= " {$result['total_messages']} message(s) will be sent over the selected window (run scheduler: php artisan phishing:send-scheduled).";
            }
            return redirect()->route('admin.campaigns.show', $campaign)->with('success', $msg);
        }
        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('error', $result['error'] ?? 'Launch failed.');
    }

    public function cancel(PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('cancel', $campaign);
        $this->campaignService->cancelCampaign($campaign);
        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign cancelled. You can edit it (including targets), re-approve, and launch again.');
    }

    /**
     * Requeue failed messages for this campaign so they are sent again.
     */
    public function retryFailed(PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);
        $messages = PhishingMessage::where('campaign_id', $campaign->id)->where('status', 'failed')->get();
        if ($messages->isEmpty()) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('info', 'No failed messages to retry.');
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
        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Requeued ' . $messages->count() . ' failed message(s). They will be sent when the queue worker runs.');
    }
}
