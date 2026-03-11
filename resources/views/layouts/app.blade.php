<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cyber': {
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155',
                            600: '#475569',
                            500: '#64748b',
                        }
                    }
                }
            }
        }
    </script>
    @stack('styles')
</head>
<body class="bg-slate-900 text-slate-100 antialiased min-h-screen">
    @if(config('phishing.simulation_enabled'))
        <div class="bg-amber-500/90 text-amber-950 text-center py-1 text-sm font-medium">Authorized security awareness testing only. Simulation sending is ENABLED.</div>
    @else
        <div class="bg-slate-700 text-slate-300 text-center py-1 text-sm">Simulation sending is disabled.</div>
    @endif

    <div class="flex min-h-screen">
        {{-- Left navigation - dark --}}
        <aside class="w-60 bg-slate-900 border-r border-slate-700 flex flex-col">
            <div class="p-4 border-b border-slate-700 flex items-center gap-3">
                <img src="{{ asset('images/cyberguard-logo.png') }}" alt="CyberGuard" class="h-9 w-auto" />
                <div>
                    <span class="font-bold text-white block">{{ config('app.name') }}</span>
                    <span class="text-xs text-slate-400">Inbox Security & Phishing Defense</span>
                </div>
            </div>
            @php
                $currentTenant = \App\Models\Tenant::current();
                $user = auth()->user();
                $tenants = \App\Models\Tenant::where('active', true)->orderBy('name')->get();
                $switchableTenants = $user?->isPlatformAdmin() ? $tenants : $tenants->where('id', $user?->tenant_id);
            @endphp
            <nav class="p-2 flex-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Dashboard</a>
                <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    Reports
                    @if(isset($pendingReportsCount) && $pendingReportsCount > 0)
                        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $pendingReportsCount > 99 ? '99+' : $pendingReportsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.remediation.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.remediation.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Remediation</a>
                <a href="{{ route('admin.campaigns.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.campaigns.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Campaigns</a>
                <a href="{{ route('admin.attacks.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.attacks.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Attack library</a>
                @if($currentTenant?->gamification_enabled ?? true)
                <a href="{{ route('admin.leaderboard.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.leaderboard.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Leaderboard</a>
                <a href="{{ route('admin.score-periods.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.score-periods.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Score periods</a>
                @endif
                <a href="{{ route('admin.audit.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.audit.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Audit Logs</a>
                <a href="{{ route('admin.system-log.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.system-log.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">System log</a>
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Settings</a>
            </nav>
            {{-- Tenant switcher --}}
            @if($switchableTenants->isNotEmpty())
                <div class="p-2 border-t border-slate-700">
                    <form method="post" action="{{ route('admin.tenant.switch') }}" class="space-y-1">
                        @csrf
                        <label class="block text-xs font-medium text-slate-400">Tenant</label>
                        <select name="tenant_id" onchange="this.form.submit()" class="w-full rounded border-slate-600 bg-slate-800 text-slate-200 text-sm">
                            @foreach($switchableTenants as $t)
                                <option value="{{ $t->id }}" @selected($currentTenant && $currentTenant->id === $t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
            <div class="p-3 border-t border-slate-700">
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-full bg-slate-600 flex items-center justify-center text-sm font-medium text-slate-200">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="h-14 bg-slate-900 border-b border-slate-700 flex items-center justify-between px-6">
                <div></div>
                <div class="flex items-center gap-4">
                    <button type="button" class="relative p-2 text-slate-400 hover:text-white rounded-lg" title="Notifications" aria-label="Notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 6H9" /></svg>
                        @if(isset($pendingReportsCount) && $pendingReportsCount > 0)
                            <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
                        @endif
                    </button>
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full bg-slate-600 flex items-center justify-center text-sm font-medium text-slate-200">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                        <span class="text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                        <span class="text-xs text-slate-400">Administrator</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-slate-400 hover:text-white">Logout</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-emerald-500/20 border border-emerald-500/50 p-4 text-emerald-200 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-lg bg-red-500/20 border border-red-500/50 p-4 text-red-200 text-sm">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
