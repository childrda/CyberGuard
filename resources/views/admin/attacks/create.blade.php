@extends('layouts.app')

@section('title', 'New attack template')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">New attack template</h1>
    <p class="text-slate-400">Add a phishing message variant to the library; attach to campaigns to mix content per recipient.</p>
</div>

@php
    $difficultyLabels = [1 => 'Obvious', 2 => 'Easy to spot', 3 => 'Moderate', 4 => 'Convincing', 5 => 'Very realistic'];
@endphp
<form method="post" action="{{ route('admin.attacks.store') }}" class="max-w-2xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-300">Name (internal)</label>
        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="e.g. Google account deactivation">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Description (what this phishing message mimics)</label>
        <textarea name="description" rows="2" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="For analysts: e.g. Mimics Google account deactivation scare">{{ old('description') }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Category (optional)</label>
        <input type="text" name="category" value="{{ old('category') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="e.g. Account security">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Subject</label>
        <input type="text" name="subject" value="{{ old('subject') }}" required class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        @error('subject')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-300">From name</label>
            <input type="text" name="from_name" value="{{ old('from_name') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="e.g. Google">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300">From email</label>
            <input type="email" name="from_email" value="{{ old('from_email') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="noreply@example.com">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Reply-To (optional)</label>
        <input type="email" name="reply_to" value="{{ old('reply_to') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">HTML body</label>
        @php
            $placeholderList = implode(', ', array_map(fn ($p) => '{{' . $p . '}}', \App\Services\PlaceholderReplacementService::supportedPlaceholders()));
        @endphp
        <span class="ml-2 text-slate-500 text-sm">Placeholders: {{ $placeholderList }}</span>
        <div class="mt-1 flex gap-2">
            <textarea id="html_body" name="html_body" rows="10" required class="flex-1 rounded border border-slate-600 bg-slate-800 px-3 py-2 font-mono text-sm text-slate-100 placeholder-slate-500">{{ old('html_body') }}</textarea>
            <button type="button" id="btn-insert-image" class="rounded border border-slate-500 bg-slate-700 px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 h-fit">Insert image</button>
        </div>
        @error('html_body')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Plain text body (optional)</label>
        <textarea name="text_body" rows="4" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">{{ old('text_body') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Difficulty rating (1 = obvious, 5 = very realistic)</label>
        <select name="difficulty_rating" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            @foreach($difficultyLabels as $i => $label)
                <option value="{{ $i }}" @selected(old('difficulty_rating', 3) == $i)>{{ $i }} – {{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Landing page type (after click)</label>
        <select name="landing_page_type" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            <option value="training" @selected(old('landing_page_type', 'training') === 'training')>Training</option>
            <option value="credential_capture" @selected(old('landing_page_type') === 'credential_capture')>Credential capture (training only)</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Training page (optional)</label>
        <select name="training_page_id" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
            <option value="">— None —</option>
            @foreach($landingPages as $lp)
                <option value="{{ $lp->id }}" @selected(old('training_page_id') == $lp->id)>{{ $lp->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Tags (comma-separated, optional)</label>
        <input type="text" name="tags" value="{{ old('tags') }}" class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500" placeholder="account, security, policy">
    </div>
    <div>
        <label class="inline-flex items-center"><input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }} class="rounded border-slate-500 bg-slate-700 text-blue-500"> Active (available for campaigns)</label>
    </div>
    <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Create attack template</button>
</form>

<div id="asset-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="fixed inset-0 bg-black/70" id="asset-modal-backdrop"></div>
    <div class="fixed inset-4 md:inset-10 bg-slate-800 rounded-lg shadow-xl border border-slate-600 flex flex-col max-w-4xl mx-auto">
        <div class="p-4 border-b border-slate-600 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">Insert image</h2>
            <button type="button" id="asset-modal-close" class="text-slate-400 hover:text-white">&times;</button>
        </div>
        <div class="p-4 border-b border-slate-600">
            <input type="file" id="asset-upload-input" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="text-slate-300 text-sm">
        </div>
        <div id="asset-list" class="p-4 flex-1 overflow-auto grid grid-cols-2 sm:grid-cols-4 gap-3">
            <p class="col-span-full text-slate-500">Loading…</p>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    var modal = document.getElementById('asset-modal');
    var openBtn = document.getElementById('btn-insert-image');
    var listEl = document.getElementById('asset-list');
    var uploadInput = document.getElementById('asset-upload-input');
    var htmlBodyTextarea = document.getElementById('html_body');
    var indexUrl = '{{ route('admin.attack-assets.index') }}';
    var storeUrl = '{{ route('admin.attack-assets.store') }}';
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function openModal() { modal.classList.remove('hidden'); loadAssets(); }
    function closeModal() { modal.classList.add('hidden'); }
    openBtn && openBtn.addEventListener('click', openModal);
    document.getElementById('asset-modal-close') && document.getElementById('asset-modal-close').addEventListener('click', closeModal);
    document.getElementById('asset-modal-backdrop') && document.getElementById('asset-modal-backdrop').addEventListener('click', closeModal);
    function insertAtCursor(url) {
        if (!htmlBodyTextarea) return;
        var start = htmlBodyTextarea.selectionStart, end = htmlBodyTextarea.selectionEnd;
        var before = htmlBodyTextarea.value.substring(0, start), after = htmlBodyTextarea.value.substring(end);
        var insert = '<img src="' + url + '" alt="">';
        htmlBodyTextarea.value = before + insert + after;
        htmlBodyTextarea.selectionStart = htmlBodyTextarea.selectionEnd = start + insert.length;
        htmlBodyTextarea.focus();
        closeModal();
    }
    function loadAssets(page) {
        page = page || 1;
        listEl.innerHTML = '<p class="col-span-full text-slate-500">Loading…</p>';
        fetch(indexUrl + '?page=' + page).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.data || data.data.length === 0) { listEl.innerHTML = '<p class="col-span-full text-slate-500">No images. Upload one above.</p>'; return; }
            listEl.innerHTML = data.data.map(function(a) {
                return '<div class="border border-slate-600 rounded p-2 cursor-pointer hover:bg-slate-700" data-url="' + (a.url || '').replace(/"/g, '&quot;') + '">' +
                    '<img src="' + (a.url || '') + '" alt="" class="w-full h-20 object-contain bg-slate-700 rounded">' +
                    '<p class="text-xs text-slate-400 truncate mt-1">' + (a.original_name || '') + '</p>' +
                    '<button type="button" class="mt-1 text-xs text-blue-400">Insert URL</button></div>';
            }).join('');
            listEl.querySelectorAll('[data-url]').forEach(function(el) { el.addEventListener('click', function() { var u = el.getAttribute('data-url'); if (u) insertAtCursor(u); }); });
        });
    }
    uploadInput && uploadInput.addEventListener('change', function() {
        var file = this.files && this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', csrf);
        listEl.innerHTML = '<p class="col-span-full text-slate-500">Uploading…</p>';
        fetch(storeUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) { if (data.url) insertAtCursor(data.url); uploadInput.value = ''; loadAssets(); })
            .catch(function() { loadAssets(); });
    });
})();
</script>
@endpush
@endsection
