<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/cyberguard-logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    @if(config('phishing.simulation_enabled'))
        <div class="bg-amber-500 text-amber-900 text-center py-1 text-sm font-medium">Authorized security awareness testing only. Simulation sending is ENABLED.</div>
    @else
        <div class="bg-slate-200 text-slate-700 text-center py-1 text-sm">Simulation sending is disabled.</div>
    @endif

    <div class="flex min-h-screen">
        {{-- Left navigation --}}
        <aside class="w-56 bg-white border-r border-slate-200 flex flex-col">
            <div class="p-4 border-b border-slate-200 flex items-center gap-2">
                <img src="{{ asset('images/cyberguard-logo.png') }}" alt="CyberGuard" class="h-8 w-auto" />
                <span class="font-semibold">{{ config('app.name') }}</span>
            </div>
            <nav class="p-2 flex-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Dashboard</a>
                <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Reports</a>
                <a href="{{ route('admin.remediation.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.remediation.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Remediation</a>
                <a href="{{ route('admin.campaigns.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.campaigns.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Campaigns</a>
                <a href="{{ route('admin.attacks.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.attacks.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Attack library</a>
                <a href="{{ route('admin.leaderboard.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.leaderboard.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Leaderboard</a>
                <a href="{{ route('admin.audit.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.audit.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Audit Logs</a>
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50' }}">Settings</a>
            </nav>
            {{-- Tenant switcher --}}
            @php
                $currentTenant = \App\Models\Tenant::current();
                $user = auth()->user();
                $tenants = \App\Models\Tenant::where('active', true)->orderBy('name')->get();
                $switchableTenants = $user?->isPlatformAdmin() ? $tenants : $tenants->where('id', $user?->tenant_id);
            @endphp
            @if($switchableTenants->isNotEmpty())
                <div class="p-2 border-t border-slate-200">
                    <form method="post" action="{{ route('admin.tenant.switch') }}" class="space-y-1">
                        @csrf
                        <label class="block text-xs font-medium text-slate-500">Tenant</label>
                        <select name="tenant_id" onchange="this.form.submit()" class="w-full rounded border-slate-300 text-sm">
                            @foreach($switchableTenants as $t)
                                <option value="{{ $t->id }}" @selected($currentTenant && $currentTenant->id === $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-end px-6 gap-4">
                <span class="text-sm text-slate-500">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-slate-600 hover:text-slate-900">Logout</button>
                </form>
            </header>

            <main class="flex-1 p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4 text-red-800 text-sm">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
