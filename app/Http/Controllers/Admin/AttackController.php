<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\PhishingAttack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttackController extends Controller
{
    public function index(Request $request): View
    {
        $query = PhishingAttack::query()
            ->when($request->input('difficulty'), fn ($q) => $q->where('difficulty_rating', $request->difficulty))
            ->when($request->input('active'), fn ($q) => $q->where('active', $request->active === '1'));
        $attacks = $query->orderBy('difficulty_rating')->orderBy('name')->paginate(20);
        return view('admin.attacks.index', compact('attacks'));
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
            'subject' => ['required', 'string', 'max:500'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'difficulty_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'landing_page_type' => ['nullable', 'string', 'in:training,credential_capture'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'active' => ['boolean'],
        ]);
        $validated['tenant_id'] = \App\Models\Tenant::currentId();
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
            'subject' => ['required', 'string', 'max:500'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'difficulty_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'landing_page_type' => ['nullable', 'string', 'in:training,credential_capture'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'active' => ['boolean'],
        ]);
        $validated['active'] = $request->boolean('active', true);
        $attack->update($validated);
        return redirect()->route('admin.attacks.show', $attack)->with('success', 'Attack template updated.');
    }

    public function destroy(PhishingAttack $attack): RedirectResponse
    {
        $this->authorize('delete', $attack);
        $attack->delete();
        return redirect()->route('admin.attacks.index')->with('success', 'Attack template deleted.');
    }
}
