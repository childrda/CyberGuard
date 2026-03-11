@extends('layouts.app')

@section('title', 'Edit ' . $attack->name)

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Edit attack template</h1>
    <p class="text-slate-400">Sent: {{ number_format($attack->times_sent) }} · Clicks: {{ number_format($attack->times_clicked) }}</p>
</div>

<form method="post" action="{{ route('admin.attacks.update', $attack) }}" class="max-w-2xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-slate-300">Name (internal)</label>
        <input type="text" name="name" value="{{ old('name', $attack->name) }}" required class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Description (what this mimics)</label>
        <textarea name="description" rows="2" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">{{ old('description', $attack->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Category (optional)</label>
        <input type="text" name="category" value="{{ old('category', $attack->category) }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="e.g. Account security">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Subject</label>
        <input type="text" name="subject" value="{{ old('subject', $attack->subject) }}" required class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        @error('subject')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-300">From name</label>
            <input type="text" name="from_name" value="{{ old('from_name', $attack->from_name) }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300">From email</label>
            <input type="email" name="from_email" value="{{ old('from_email', $attack->from_email) }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Reply-To (optional)</label>
        <input type="email" name="reply_to" value="{{ old('reply_to', $attack->reply_to) }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">HTML body</label>
        @php
            $placeholderList = implode(', ', array_map(fn ($p) => '{{' . $p . '}}', \App\Services\PlaceholderReplacementService::supportedPlaceholders()));
        @endphp
        <span class="ml-2 text-slate-500 text-sm">Use placeholders: {{ $placeholderList }}</span>
        <div class="mt-1 flex gap-2">
            <textarea id="html_body" name="html_body" rows="10" required class="flex-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 font-mono text-sm text-slate-100 placeholder-slate-500" data-insert-target="html_body">{{ old('html_body', $attack->html_body) }}</textarea>
            <div>
                <button type="button" id="btn-insert-image" class="rounded border border-slate-500 bg-slate-700 px-3 py-2 text-sm text-slate-200 hover:bg-slate-600">Insert image</button>
            </div>
        </div>
        @error('html_body')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Plain text body (optional)</label>
        <textarea name="text_body" rows="4" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">{{ old('text_body', $attack->text_body) }}</textarea>
    </div>
    @php $difficultyLabels = [1 => 'Obvious', 2 => 'Easy to spot', 3 => 'Moderate', 4 => 'Convincing', 5 => 'Very realistic']; @endphp
    <div>
        <label class="block text-sm font-medium text-slate-300">Difficulty rating (1–5)</label>
        <select name="difficulty_rating" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            @foreach($difficultyLabels as $i => $label)
                <option value="{{ $i }}" @selected(old('difficulty_rating', $attack->difficulty_rating) == $i)>{{ $i }} – {{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Landing page type</label>
        <select name="landing_page_type" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            <option value="training" @selected(old('landing_page_type', $attack->landing_page_type) === 'training')>Training</option>
            <option value="credential_capture" @selected(old('landing_page_type', $attack->landing_page_type) === 'credential_capture')>Credential capture</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Training page (optional)</label>
        <select name="training_page_id" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            <option value="">— None —</option>
            @foreach($landingPages as $lp)
                <option value="{{ $lp->id }}" @selected(old('training_page_id', $attack->training_page_id) == $lp->id)>{{ $lp->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Tags (comma-separated, optional)</label>
        <input type="text" name="tags" value="{{ old('tags', is_array($attack->tags) ? implode(', ', $attack->tags) : '') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="account, security, policy">
    </div>
    <div>
        <label class="inline-flex items-center"><input type="checkbox" name="active" value="1" {{ old('active', $attack->active) ? 'checked' : '' }} class="rounded border-slate-500 bg-slate-700 text-blue-500"> Active</label>
    </div>
    <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Update</button>
</form>

{{-- Asset manager modal --}}
<div id="asset-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="fixed inset-0 bg-black/70" id="asset-modal-backdrop"></div>
    <div class="fixed inset-4 md:inset-10 bg-slate-800 rounded-lg shadow-xl border border-slate-600 flex flex-col max-w-4xl mx-auto">
        <div class="p-4 border-b border-slate-600 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">Insert image</h2>
            <button type="button" id="asset-modal-close" class="text-slate-400 hover:text-white">&times;</button>
        </div>
        <div class="p-4 border-b border-slate-600">
            <input type="file" id="asset-upload-input" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="text-slate-300 text-sm">
            <p class="mt-1 text-xs text-slate-500">JPEG, PNG, GIF, WebP, SVG. Max 5MB.</p>
        </div>
        <div id="asset-list" class="p-4 flex-1 overflow-auto grid grid-cols-2 sm:grid-cols-4 gap-3">
            <p class="col-span-full text-slate-500">Loading…</p>
        </div>
        <div class="p-4 border-t border-slate-600 text-slate-500 text-sm">
            @php
                $imgExample = '<img src="{{url}}">';
            @endphp
            Click an image to insert its URL at the cursor in the HTML body. Or copy the URL and paste it into your template (e.g. in an <code class="bg-slate-700 px-1 rounded">{{ $imgExample }}</code>).
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const modal = document.getElementById('asset-modal');
    const backdrop = document.getElementById('asset-modal-backdrop');
    const closeBtn = document.getElementById('asset-modal-close');
    const openBtn = document.getElementById('btn-insert-image');
    const listEl = document.getElementById('asset-list');
    const uploadInput = document.getElementById('asset-upload-input');
    const htmlBodyTextarea = document.getElementById('html_body');
    const indexUrl = '{{ route('admin.attack-assets.index') }}';
    const storeUrl = '{{ route('admin.attack-assets.store') }}';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function openModal() { modal.classList.remove('hidden'); loadAssets(); }
    function closeModal() { modal.classList.add('hidden'); }
    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);

    function insertAtCursor(url) {
        if (!htmlBodyTextarea) return;
        const start = htmlBodyTextarea.selectionStart;
        const end = htmlBodyTextarea.selectionEnd;
        const before = htmlBodyTextarea.value.substring(0, start);
        const after = htmlBodyTextarea.value.substring(end);
        const insert = '<img src="' + url + '" alt="">';
        htmlBodyTextarea.value = before + insert + after;
        htmlBodyTextarea.selectionStart = htmlBodyTextarea.selectionEnd = start + insert.length;
        htmlBodyTextarea.focus();
        closeModal();
    }

    function loadAssets(page = 1) {
        listEl.innerHTML = '<p class="col-span-full text-slate-500">Loading…</p>';
        fetch(indexUrl + '?page=' + page + '&attack_id={{ $attack->id }}')
            .then(r => r.json())
            .then(data => {
                if (!data.data || data.data.length === 0) {
                    listEl.innerHTML = '<p class="col-span-full text-slate-500">No images yet. Upload one above.</p>';
                    return;
                }
                listEl.innerHTML = data.data.map(a => {
                    const img = '<img src="' + a.url + '" alt="" class="w-full h-20 object-contain bg-slate-700 rounded">';
                    return '<div class="border border-slate-600 rounded p-2 cursor-pointer hover:bg-slate-700" data-url="' + (a.url || '').replace(/"/g, '&quot;') + '" title="' + (a.original_name || '').replace(/"/g, '&quot;') + '">' + img + '<p class="text-xs text-slate-400 truncate mt-1">' + (a.original_name || '') + '</p><button type="button" class="mt-1 text-xs text-blue-400 hover:underline">Insert URL</button></div>';
                }).join('');
                listEl.querySelectorAll('[data-url]').forEach(el => {
                    el.addEventListener('click', () => { const u = el.getAttribute('data-url'); if (u) insertAtCursor(u); });
                });
            })
            .catch(() => { listEl.innerHTML = '<p class="col-span-full text-red-400">Failed to load assets.</p>'; });
    }

    uploadInput?.addEventListener('change', function() {
        const file = this.files?.[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('attack_id', '{{ $attack->id }}');
        fd.append('_token', csrf);
        listEl.innerHTML = '<p class="col-span-full text-slate-500">Uploading…</p>';
        fetch(storeUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (data.url) { insertAtCursor(data.url); }
                uploadInput.value = '';
                loadAssets();
            })
            .catch(() => { listEl.innerHTML = '<p class="col-span-full text-red-400">Upload failed.</p>'; loadAssets(); });
    });
})();
</script>
@endpush

<p class="mt-6"><a href="{{ route('admin.attacks.show', $attack) }}" class="text-slate-400 hover:underline">← Back to attack</a></p>
@endsection
