<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScorePeriod;
use App\Services\Gamification\ScorePeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScorePeriodController extends Controller
{
    public function __construct(
        protected ScorePeriodService $scorePeriods
    ) {}

    public function index(): View
    {
        $tenantId = \App\Models\Tenant::currentId();
        if ($tenantId === null) {
            $periods = collect();
        } else {
            $periods = $this->scorePeriods->listForTenant($tenantId, 50);
        }

        return view('admin.score-periods.index', [
            'periods' => $periods,
        ]);
    }

    public function create(): View
    {
        return view('admin.score-periods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = \App\Models\Tenant::currentId();
        if ($tenantId === null) {
            return redirect()->route('admin.score-periods.index')->with('error', 'Select a tenant first.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $this->scorePeriods->create(
            $tenantId,
            $validated['name'],
            $validated['slug'],
            $validated['start_date'],
            $validated['end_date'],
            (bool) ($validated['is_current'] ?? false)
        );

        return redirect()->route('admin.score-periods.index')->with('success', 'Score period created.');
    }

    public function setCurrent(int $period): RedirectResponse
    {
        $tenantId = \App\Models\Tenant::currentId();
        if ($tenantId === null) {
            return redirect()->route('admin.score-periods.index')->with('error', 'Select a tenant first.');
        }

        $exists = \App\Models\ScorePeriod::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $period)
            ->exists();
        if (! $exists) {
            return redirect()->route('admin.score-periods.index')->with('error', 'Score period not found.');
        }

        $this->scorePeriods->setCurrent($tenantId, $period);
        return redirect()->route('admin.score-periods.index')->with('success', 'Current period updated.');
    }
}
