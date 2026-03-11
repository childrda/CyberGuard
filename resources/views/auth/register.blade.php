<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
        <div class="flex items-center justify-center gap-2 mb-6">
            <img src="{{ asset('images/cyberguard-logo.png') }}" alt="CyberGuard" class="h-10 w-auto" />
            <h1 class="text-2xl font-bold">Register</h1>
        </div>
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-slate-300">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border-slate-300">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" required class="mt-1 w-full rounded border-slate-300">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Confirm password</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded border-slate-300">
            </div>
            <button type="submit" class="w-full rounded bg-slate-800 py-2 text-white">Register</button>
        </form>
        <p class="mt-4 text-sm text-slate-600"><a href="{{ route('login') }}" class="hover:underline">Already have an account?</a></p>
    </div>
</body>
</html>
