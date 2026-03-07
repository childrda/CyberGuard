<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhishingAttack;
use App\Models\PhishingCampaign;
use App\Models\PhishingCampaignTarget;
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
        return view('admin.campaigns.create', compact('templates', 'attacks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PhishingCampaign::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'exists:phishing_templates,id'],
            'target_type' => ['required', 'in:user,group,csv'],
            'target_identifier' => ['required', 'string'],
            'display_name' => ['nullable', 'string'],
            'attack_ids' => ['nullable', 'array'],
            'attack_ids.*' => ['exists:phishing_attacks,id'],
        ]);

        $campaign = PhishingCampaign::create([
            'tenant_id' => \App\Models\Tenant::currentId(),
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        PhishingCampaignTarget::create([
            'campaign_id' => $campaign->id,
            'target_type' => $validated['target_type'],
            'target_identifier' => $validated['target_identifier'],
            'display_name' => $validated['display_name'] ?? null,
        ]);

        $attackIds = PhishingAttack::whereIn('id', $request->input('attack_ids', []))->pluck('id')->toArray();
        $campaign->attacks()->sync($attackIds);

        $this->audit->log('campaign_created', $campaign);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign created.');
    }

    public function show(PhishingCampaign $campaign): View
    {
        $this->authorize('view', $campaign);
        $campaign->load('template', 'targets', 'messages.events');
        return view('admin.campaigns.show', compact('campaign'));
    }

    public function edit(PhishingCampaign $campaign): View
    {
        $this->authorize('update', $campaign);
        $templates = PhishingTemplate::where('active', true)->get();
        $attacks = PhishingAttack::where('active', true)->orderBy('difficulty_rating')->orderBy('name')->get();
        $campaign->load('attacks');
        return view('admin.campaigns.edit', compact('campaign', 'templates', 'attacks'));
    }

    public function update(Request $request, PhishingCampaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'exists:phishing_templates,id'],
            'attack_ids' => ['nullable', 'array'],
            'attack_ids.*' => ['exists:phishing_attacks,id'],
        ]);
        $old = $campaign->only(['name', 'template_id']);
        $campaign->update([
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
        ]);
        $attackIds = PhishingAttack::whereIn('id', $request->input('attack_ids', []))->pluck('id')->toArray();
        $campaign->attacks()->sync($attackIds);
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
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('success', "Campaign launched. Accepted: {$result['accepted']}, Rejected: {$result['rejected']}.");
        }
        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('error', $result['error'] ?? 'Launch failed.');
    }
}
