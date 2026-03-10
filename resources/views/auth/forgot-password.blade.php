<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
        <h1 class="text-2xl font-bold mb-6">Forgot password</h1>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded border-slate-300">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if(session('status'))<p class="text-sm text-green-600">{{ session('status') }}</p>@endif
            <button type="submit" class="w-full rounded bg-slate-800 py-2 text-white">Send reset link</button>
        </form>
        <p class="mt-4 text-sm"><a href="{{ route('login') }}" class="hover:underline">Back to login</a></p>
    </div>
</body>
</html>
