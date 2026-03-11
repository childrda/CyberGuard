@extends('layouts.app')

@section('title', $attack->name)

@section('content')
<div class="mb-8 flex justify-between">
    <div>
        <h1 class="text-2xl font-bold text-white">{{ $attack->name }}</h1>
        <p class="text-slate-400">Difficulty: {{ $attack->difficulty_rating }} – {{ $attack->difficultyLabel() }} · Sent: {{ number_format($attack->times_sent) }} · Clicks: {{ number_format($attack->times_clicked) }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.attacks.preview', $attack) }}" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-300 hover:bg-slate-700">Preview with sample data</a>
        @can('update', $attack)
            <a href="{{ route('admin.attacks.edit', $attack) }}" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-300 hover:bg-slate-700">Edit</a>
            <button type="button" id="btn-validate" class="rounded border border-slate-600 bg-slate-800 px-4 py-2 text-slate-300 hover:bg-slate-700">Validate</button>
        @endcan
    </div>
</div>

@can('update', $attack)
<div id="test-email-box" class="rounded-lg border border-slate-600 bg-slate-800/50 p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">Send test email</h2>
    <p class="text-slate-400 text-sm mb-3">Send a test to an address in your tenant’s allowed domains.</p>
    <form id="send-test-form" action="{{ route('admin.attacks.send-test', $attack) }}" method="post" class="flex gap-2 flex-wrap items-end">
        @csrf
        <label class="flex-1 min-w-[200px]">
            <span class="block text-xs text-slate-500 mb-1">Email</span>
            <input type="email" name="to" required placeholder="you@yourdomain.com" class="w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
        </label>
        <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Send test</button>
    </form>
    <div id="send-test-message" class="mt-2 text-sm hidden" role="alert"></div>
    @if(session('success') && str_contains(session('success'), 'Test email sent'))
        <p class="mt-2 text-sm text-emerald-400">{{ session('success') }}</p>
    @endif
</div>
<div id="validate-result" class="hidden mb-6 rounded-lg border border-slate-600 bg-slate-800/50 p-4"></div>
@endcan

@if($attack->description)
<div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">Description (what this mimics)</h2>
    <p class="text-slate-200">{{ $attack->description }}</p>
</div>
@endif

<div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">Subject</h2>
    <p class="text-slate-100">{{ $attack->subject }}</p>
    @if($attack->from_name || $attack->from_email)
        <p class="mt-2 text-sm text-slate-400">From: {{ $attack->from_name }} {{ $attack->from_email ? '&lt;' . e($attack->from_email) . '&gt;' : '' }}</p>
    @endif
</div>

<div class="rounded-lg border border-slate-600 bg-slate-800/50 p-6">
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-2">HTML body (preview)</h2>
    <div class="max-w-none rounded border border-slate-600 bg-slate-800 p-4 text-sm text-slate-200">{{ Str::limit(strip_tags($attack->html_body), 800) }}</div>
</div>

<p class="mt-6"><a href="{{ route('admin.attacks.index') }}" class="text-slate-400 hover:underline">← Back to attack library</a></p>

@can('update', $attack)
@push('scripts')
<script>
(function() {
    var btn = document.getElementById('btn-validate');
    var resultEl = document.getElementById('validate-result');
    if (btn && resultEl) {
        btn.addEventListener('click', function() {
            resultEl.classList.remove('hidden');
            resultEl.innerHTML = '<p class="text-slate-400">Checking…</p>';
            fetch('{{ route('admin.attacks.validate', $attack) }}', { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.valid) {
                        resultEl.innerHTML = '<p class="text-emerald-400">No warnings. Content looks good.</p>';
                    } else {
                        resultEl.innerHTML = '<p class="text-amber-400 font-medium mb-2">Warnings:</p><ul class="list-disc list-inside text-slate-300">' +
                            data.warnings.map(function(w) { return '<li>' + (w.message || w.type) + '</li>'; }).join('') + '</ul>';
                    }
                })
                .catch(function() { resultEl.innerHTML = '<p class="text-red-400">Validation request failed.</p>'; });
        });
    }

    var sendTestForm = document.getElementById('send-test-form');
    var sendTestMessage = document.getElementById('send-test-message');
    if (sendTestForm && sendTestMessage) {
        sendTestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendTestMessage.classList.remove('hidden');
            sendTestMessage.classList.remove('text-emerald-400', 'text-red-400', 'text-slate-400');
            sendTestMessage.textContent = 'Sending…';
            sendTestMessage.classList.add('text-slate-400');

            var formData = new FormData(sendTestForm);
            var csrf = document.querySelector('input[name="_token"]');
            var headers = { 'Accept': 'application/json' };
            if (csrf) headers['X-CSRF-TOKEN'] = csrf.value;

            fetch(sendTestForm.action, {
                method: 'POST',
                body: formData,
                headers: headers
            })
            .then(function(r) {
                return r.json().then(function(data) {
                    if (r.ok) {
                        sendTestMessage.textContent = data.message || 'Test email sent.';
                        sendTestMessage.classList.remove('text-slate-400', 'text-red-400');
                        sendTestMessage.classList.add('text-emerald-400');
                    } else {
                        sendTestMessage.textContent = data.message || 'Something went wrong.';
                        sendTestMessage.classList.remove('text-slate-400', 'text-emerald-400');
                        sendTestMessage.classList.add('text-red-400');
                    }
                });
            })
            .catch(function() {
                sendTestMessage.textContent = 'Request failed. Try again.';
                sendTestMessage.classList.remove('text-slate-400', 'text-emerald-400');
                sendTestMessage.classList.add('text-red-400');
            });
        });
    }
})();
</script>
@endpush
@endcan
@endsection
