<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify email - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
        <h1 class="text-2xl font-bold mb-6">Verify your email</h1>
        <p class="text-slate-600 mb-4">Thanks for signing up. Please verify your email by clicking the link we sent.</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="rounded bg-slate-800 px-4 py-2 text-white">Resend verification email</button>
        </form>
        @if(session('status') === 'verification-link-sent')<p class="mt-4 text-green-600">Verification link sent.</p>@endif
    </div>
</body>
</html>
