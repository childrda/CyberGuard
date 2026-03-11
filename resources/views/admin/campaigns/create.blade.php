@extends('layouts.app')

@section('title', 'New campaign')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">New campaign</h1>
    <p class="text-slate-400">Create a phishing simulation campaign</p>
</div>

<form method="post" action="{{ route('admin.campaigns.store') }}" class="max-w-6xl space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-slate-300">Name</label>
        <input type="text" name="name" value="{{ old('name') }}" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
        @error('name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Template</label>
        <select name="template_id" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            @foreach($templates as $t)
                <option value="{{ $t->id }}" @selected(old('template_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
        @error('template_id')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Attack templates (optional)</label>
        <p class="text-sm text-slate-400 mb-2">Select one or more to mix phishing content per recipient. If none selected, the template above is used for everyone.</p>
        <div class="mt-1 space-y-2 max-h-48 overflow-y-auto rounded border border-slate-600 bg-slate-800 p-3">
            @forelse($attacks as $a)
                <label class="flex items-center gap-3 py-1 min-h-[2rem]">
                    <input type="checkbox" name="attack_ids[]" value="{{ $a->id }}" {{ in_array($a->id, old('attack_ids', [])) ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                    <span class="text-slate-200">{{ $a->name }}</span>
                    <span class="text-xs text-slate-500 shrink-0">({{ $a->difficulty_rating }} – {{ $a->difficultyLabel() }})</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No attack templates. <a href="{{ route('admin.attacks.create') }}" class="text-blue-400 hover:underline">Create some</a> in Attack library.</p>
            @endforelse
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-300">Target type</label>
        <select name="target_type" id="target_type" required
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            <option value="user" @selected(old('target_type') === 'user')>User (email)</option>
            <option value="group" @selected(old('target_type') === 'group')>Group (Google Workspace)</option>
            <option value="ou" @selected(old('target_type') === 'ou')>OU (organizational unit)</option>
            <option value="csv" @selected(old('target_type') === 'csv')>CSV</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Group/OU: search workspace when directory sync is enabled. Otherwise enter a single address or path.</p>
        @if(!empty($directorySyncEnabled))
            <p class="mt-1 text-sm text-blue-300">Directory integration is on — choose Group or OU above to search and select user groups from your workspace.</p>
        @else
            <p class="mt-1 text-sm text-amber-300">To search and select groups like in the image, enable directory sync in <a href="{{ route('admin.settings.index') }}" class="underline">Settings</a> → Edit tenant → Directory integration.</p>
        @endif
    </div>

    {{-- Single target (user / csv or group/ou when directory sync off) --}}
    <div id="target_single_wrap">
        <label class="block text-sm font-medium text-slate-300">Target (email or group address)</label>
        <input type="text" name="target_identifier" id="target_identifier" value="{{ old('target_identifier') }}"
            class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500"
            placeholder="user@yourdomain.com or group@yourdomain.com">
        @error('target_identifier')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        <div>
            <label class="block text-sm font-medium text-slate-300 mt-2">Display name (optional)</label>
            <input type="text" name="display_name" value="{{ old('display_name') }}"
                class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 placeholder-slate-500">
        </div>
    </div>

    {{-- Two-panel workspace (directory integration) when directory sync on and type group/ou --}}
    <div id="target_workspace_wrap" class="hidden rounded-lg border border-slate-600 bg-slate-800/50 p-4">
        <div class="flex gap-4 border-b border-slate-600 mb-3">
            <button type="button" id="tab_selected_users" class="pb-2 text-sm font-medium border-b-2 border-blue-500 text-blue-400">Selected users (<span id="tab_selected_count">0</span>)</button>
            <button type="button" id="tab_conflicts" class="pb-2 text-sm font-medium border-b-2 border-transparent text-slate-400 hover:text-slate-300">Conflicts (0)</button>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="space-y-2 min-w-0">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="workspace_select_all" class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                    <label for="workspace_select_all" class="text-sm font-medium text-slate-300">User groups</label>
                </div>
                <input type="text" id="workspace_search" placeholder="Search groups…"
                    class="w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 text-sm placeholder-slate-500">
                <div id="workspace_list" class="max-h-[28rem] overflow-y-auto rounded border border-slate-600 bg-slate-800 p-2 space-y-1"></div>
                <button type="button" id="workspace_upload_csv" class="rounded bg-blue-600 hover:bg-blue-500 px-3 py-1.5 text-sm text-white">Upload CSV</button>
            </div>
            <div class="rounded border border-slate-600 bg-slate-800/50 min-h-[12rem] flex flex-col min-w-0">
                <div id="workspace_emails_placeholder" class="flex-1 flex items-center justify-center text-slate-500 p-4 text-center font-medium">Select a user group to begin</div>
                <div id="workspace_emails_panel" class="hidden flex flex-col flex-1 min-h-0">
                    <div class="flex items-center gap-2 p-2 border-b border-slate-600">
                        <input type="checkbox" id="emails_select_all" class="rounded border-slate-500 bg-slate-700 text-blue-500 focus:ring-blue-500">
                        <span class="text-sm text-slate-400">Select all</span>
                        <span id="workspace_emails_count" class="text-sm text-slate-500 ml-auto"></span>
                    </div>
                    <div id="workspace_emails_list" class="flex-1 overflow-y-auto p-2 space-y-1 min-h-[20rem] max-h-[28rem]"></div>
                </div>
            </div>
        </div>
        <div id="workspace_hidden_inputs"></div>
    </div>
    <div class="rounded-lg border border-slate-600 bg-slate-800/50 p-4">
        <h3 class="text-sm font-semibold text-slate-200 mb-2">Send window (optional)</h3>
        <p class="text-sm text-slate-400 mb-3">Set a date range to spread emails over time. Leave blank to send one email per recipient immediately.</p>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-300">From date</label>
                <input type="date" name="window_start" value="{{ old('window_start') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300">To date</label>
                <input type="date" name="window_end" value="{{ old('window_end') }}"
                    class="mt-1 w-full rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100 [color-scheme:dark]">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-sm font-medium text-slate-300">Emails per recipient (during window)</label>
            <input type="number" name="emails_per_recipient" value="{{ old('emails_per_recipient', 1) }}" min="1" max="50"
                class="mt-1 w-24 rounded border border-slate-600 bg-slate-800 px-3 py-2 text-slate-100">
            <p class="text-xs text-slate-500 mt-1">Only used when a send window is set.</p>
        </div>
    </div>
    <button type="submit" class="rounded bg-blue-600 hover:bg-blue-500 px-4 py-2 text-white">Create campaign</button>
</form>

@if(!empty($directorySyncEnabled))
<script>
(function () {
    const directorySyncEnabled = {{ $directorySyncEnabled ? 'true' : 'false' }};
    const routes = {
        groups: @json(route('admin.workspace.groups')),
        ous: @json(route('admin.workspace.ous')),
        resolve: @json(route('admin.workspace.resolve')),
    };

    const targetType = document.getElementById('target_type');
    const targetSingleWrap = document.getElementById('target_single_wrap');
    const targetWorkspaceWrap = document.getElementById('target_workspace_wrap');
    const targetIdentifier = document.getElementById('target_identifier');
    const workspaceSelectAll = document.getElementById('workspace_select_all');
    const workspaceSearch = document.getElementById('workspace_search');
    const workspaceList = document.getElementById('workspace_list');
    const workspaceHiddenInputs = document.getElementById('workspace_hidden_inputs');
    const workspaceEmailsPlaceholder = document.getElementById('workspace_emails_placeholder');
    const workspaceEmailsPanel = document.getElementById('workspace_emails_panel');
    const workspaceEmailsList = document.getElementById('workspace_emails_list');
    const emailsSelectAll = document.getElementById('emails_select_all');
    const workspaceEmailsCount = document.getElementById('workspace_emails_count');

    let allItems = []; // { type: 'group'|'ou', emailOrPath: string, name: string }
    let selectedGroupOu = new Set(); // keys: type:emailOrPath
    let resolvedEmails = []; // { email, name }
    let selectedEmails = new Set();

    function showWorkspaceOrSingle() {
        const isGroupOu = targetType.value === 'group' || targetType.value === 'ou';
        const showWorkspace = directorySyncEnabled && isGroupOu;
        targetSingleWrap.classList.toggle('hidden', showWorkspace);
        targetWorkspaceWrap.classList.toggle('hidden', !showWorkspace);
        if (showWorkspace) {
            targetIdentifier.removeAttribute('required');
            targetIdentifier.value = '';
            loadWorkspaceItems();
        } else {
            targetIdentifier.toggleAttribute('required', true);
        }
    }

    function loadWorkspaceItems() {
        Promise.all([
            fetch(routes.groups).then(r => r.json()),
            fetch(routes.ous).then(r => r.json()),
        ]).then(([groupsRes, ousRes]) => {
            const groups = (groupsRes.groups || []).map(g => ({ type: 'group', emailOrPath: g.email, name: g.name }));
            const ous = (ousRes.ous || []).map(o => ({ type: 'ou', emailOrPath: o.path, name: o.name }));
            allItems = [...groups, ...ous];
            renderWorkspaceList();
        }).catch(() => {
            allItems = [];
            renderWorkspaceList();
        });
    }

    function getFilteredItems() {
        const search = workspaceSearch.value.trim().toLowerCase();
        if (!search) return allItems;
        return allItems.filter(function(item) {
            const name = (item.name || '').toLowerCase();
            const emailOrPath = (item.emailOrPath || '').toLowerCase();
            return name.includes(search) || emailOrPath.includes(search);
        });
    }

    function renderWorkspaceList() {
        const filtered = getFilteredItems();
        const key = (item) => item.type + ':' + item.emailOrPath;
        workspaceList.innerHTML = filtered.map(item => {
            const k = key(item);
            const checked = selectedGroupOu.has(k) ? 'checked' : '';
            return '<label class="flex items-center gap-2 py-1 cursor-pointer min-w-0"><input type="checkbox" class="workspace-item shrink-0 rounded border-slate-500 bg-slate-700 text-blue-500" data-key="' + k.replace(/"/g, '&quot;') + '" ' + checked + '><span class="text-slate-200 text-sm break-words">' + (item.name || item.emailOrPath) + '</span></label>';
        }).join('') || '<p class="text-slate-500 text-sm">No groups or OUs match your search.</p>';

        workspaceList.querySelectorAll('.workspace-item').forEach(cb => {
            cb.addEventListener('change', function() {
                const k = this.dataset.key;
                if (this.checked) selectedGroupOu.add(k); else selectedGroupOu.delete(k);
                updateWorkspaceSelectAll();
                resolveAndShowEmails();
            });
        });
        updateWorkspaceSelectAll();
    }

    function updateWorkspaceSelectAll() {
        const filtered = getFilteredItems();
        const key = (item) => item.type + ':' + item.emailOrPath;
        const allKeys = new Set(filtered.map(key));
        const checked = allKeys.size > 0 && [...allKeys].every(k => selectedGroupOu.has(k));
        workspaceSelectAll.checked = checked;
        workspaceSelectAll.indeterminate = allKeys.size > 0 && selectedGroupOu.size > 0 && selectedGroupOu.size < allKeys.size;
    }

    workspaceSelectAll.addEventListener('change', function() {
        const filtered = getFilteredItems();
        const key = (item) => item.type + ':' + item.emailOrPath;
        filtered.forEach(item => {
            if (this.checked) selectedGroupOu.add(key(item)); else selectedGroupOu.delete(key(item));
        });
        renderWorkspaceList();
        resolveAndShowEmails();
    });

    workspaceSearch.addEventListener('input', function() {
        renderWorkspaceList();
    });

    function resolveAndShowEmails() {
        if (selectedGroupOu.size === 0) {
            workspaceEmailsPlaceholder.classList.remove('hidden');
            workspaceEmailsPanel.classList.add('hidden');
            resolvedEmails = [];
            var tabCountEl = document.getElementById('tab_selected_count');
            if (tabCountEl) tabCountEl.textContent = '0';
            return;
        }
        const groupEmails = [];
        const ouPaths = [];
        selectedGroupOu.forEach(k => {
            const idx = k.indexOf(':');
            const type = idx >= 0 ? k.substring(0, idx) : '';
            const val = idx >= 0 ? k.substring(idx + 1) : k;
            if (type === 'group') groupEmails.push(val);
            else if (type === 'ou') ouPaths.push(val);
        });

        fetch(routes.resolve, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' },
            body: JSON.stringify({ group_emails: groupEmails, ou_paths: ouPaths }),
        }).then(r => r.json()).then(data => {
            resolvedEmails = data.emails || [];
            workspaceEmailsPlaceholder.classList.add('hidden');
            workspaceEmailsPanel.classList.remove('hidden');
            workspaceEmailsCount.textContent = resolvedEmails.length + ' email(s)';
            var tabCountEl = document.getElementById('tab_selected_count');
            if (tabCountEl) tabCountEl.textContent = resolvedEmails.length;
            selectedEmails = new Set(resolvedEmails.map(e => e.email));
            renderEmailsList();
        }).catch(() => {
            resolvedEmails = [];
            workspaceEmailsPanel.classList.add('hidden');
            workspaceEmailsPlaceholder.classList.remove('hidden');
            workspaceEmailsPlaceholder.textContent = 'Failed to load emails.';
        });
    }

    function renderEmailsList() {
        const anyUnselected = resolvedEmails.some(e => !selectedEmails.has(e.email));
        emailsSelectAll.checked = resolvedEmails.length > 0 && !anyUnselected;
        emailsSelectAll.indeterminate = selectedEmails.size > 0 && selectedEmails.size < resolvedEmails.length;

        workspaceEmailsList.innerHTML = resolvedEmails.map(r => {
            const checked = selectedEmails.has(r.email) ? 'checked' : '';
            return '<label class="flex items-center gap-2 py-1 cursor-pointer"><input type="checkbox" class="email-item rounded border-slate-500 bg-slate-700 text-blue-500" data-email="' + r.email.replace(/"/g, '&quot;') + '" ' + checked + '><span class="text-slate-200 text-sm truncate">' + (r.email) + '</span></label>';
        }).join('');

        workspaceEmailsList.querySelectorAll('.email-item').forEach(cb => {
            cb.addEventListener('change', function() {
                const email = this.dataset.email;
                if (this.checked) selectedEmails.add(email); else selectedEmails.delete(email);
                var tabCountEl = document.getElementById('tab_selected_count');
                if (tabCountEl) tabCountEl.textContent = selectedEmails.size;
                renderEmailsList();
            });
        });
        var tabCountEl = document.getElementById('tab_selected_count');
        if (tabCountEl) tabCountEl.textContent = selectedEmails.size;
    }

    emailsSelectAll.addEventListener('change', function() {
        resolvedEmails.forEach(r => {
            if (this.checked) selectedEmails.add(r.email); else selectedEmails.delete(r.email);
        });
        var tabCountEl = document.getElementById('tab_selected_count');
        if (tabCountEl) tabCountEl.textContent = selectedEmails.size;
        renderEmailsList();
    });

    document.querySelector('form').addEventListener('submit', function(ev) {
        const isWorkspace = directorySyncEnabled && (targetType.value === 'group' || targetType.value === 'ou');
        if (isWorkspace) {
            if (selectedGroupOu.size === 0) {
                ev.preventDefault();
                alert('Select at least one user group or OU.');
                return;
            }
            workspaceHiddenInputs.innerHTML = '';
            selectedGroupOu.forEach(k => {
                const idx = k.indexOf(':');
                const type = idx >= 0 ? k.substring(0, idx) : '';
                const val = idx >= 0 ? k.substring(idx + 1) : k;
                const name = type === 'group' ? 'workspace_groups[]' : 'workspace_ous[]';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = val;
                workspaceHiddenInputs.appendChild(input);
            });
        }
    });

    targetType.addEventListener('change', showWorkspaceOrSingle);
    showWorkspaceOrSingle();
})();
</script>
@endif
@endsection
