<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\PhishingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = PhishingTemplate::with('creator')->latest()->paginate(15);
        return view('admin.templates.index', compact('templates'));
    }

    public function create(): View
    {
        $this->authorize('create', PhishingTemplate::class);
        $landingPages = LandingPage::where('active', true)->get();
        return view('admin.templates.create', compact('landingPages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PhishingTemplate::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'sender_email' => ['nullable', 'email'],
            'reply_to' => ['nullable', 'email'],
            'landing_page_type' => ['required', 'in:training,credential_capture,custom'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'difficulty' => ['required', 'in:low,medium,high'],
            'tags' => ['nullable', 'array'],
        ]);
        $validated['tenant_id'] = \App\Models\Tenant::currentId();
        $validated['created_by'] = auth()->id();
        $validated['active'] = true;
        PhishingTemplate::create($validated);
        return redirect()->route('admin.templates.index')->with('success', 'Template created.');
    }

    public function show(PhishingTemplate $template): View
    {
        $this->authorize('view', $template);
        $template->load('campaigns');
        return view('admin.templates.show', compact('template'));
    }

    public function edit(PhishingTemplate $template): View
    {
        $this->authorize('update', $template);
        $landingPages = LandingPage::where('active', true)->get();
        return view('admin.templates.edit', compact('template', 'landingPages'));
    }

    public function update(Request $request, PhishingTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'sender_name' => ['nullable', 'string'],
            'sender_email' => ['nullable', 'email'],
            'reply_to' => ['nullable', 'email'],
            'landing_page_type' => ['required', 'in:training,credential_capture,custom'],
            'training_page_id' => ['nullable', 'exists:landing_pages,id'],
            'difficulty' => ['required', 'in:low,medium,high'],
            'tags' => ['nullable', 'array'],
            'active' => ['boolean'],
        ]);
        $template->update($validated);
        return redirect()->route('admin.templates.index')->with('success', 'Template updated.');
    }

    public function destroy(PhishingTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);
        $template->delete();
        return redirect()->route('admin.templates.index')->with('success', 'Template deleted.');
    }
}
