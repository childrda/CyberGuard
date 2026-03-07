<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4">
    <div class="max-w-md mx-auto">
        <div class="rounded-lg bg-white p-8 shadow">
            <h1 class="text-xl font-bold mb-6">Sign in</h1>
            <p class="text-slate-600 text-sm mb-4">This is a simulated login page for awareness training. Do not enter real credentials.</p>
            <form method="POST" action="{{ $actionUrl }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" class="mt-1 w-full rounded border-slate-300" placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" class="mt-1 w-full rounded border-slate-300" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full rounded bg-slate-800 py-2 text-white">Sign in</button>
            </form>
        </div>
        <div class="mt-6 rounded-lg bg-amber-100 border border-amber-300 p-4 text-amber-900 text-center text-sm">
            Authorized security awareness exercise. Submissions are logged for training only; passwords are never stored.
        </div>
    </div>
</body>
</html>
