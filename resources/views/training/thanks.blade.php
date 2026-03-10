<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4">
    <div class="max-w-2xl mx-auto">
        @if($showBanner ?? true)
            <div class="rounded-lg bg-amber-100 border border-amber-300 p-4 mb-6 text-amber-900 text-center text-sm font-medium">
                Authorized security awareness exercise.
            </div>
        @endif
        <div class="rounded-lg bg-white p-8 shadow text-center">
            <h1 class="text-2xl font-bold mb-4">Thank you</h1>
            <p class="text-slate-600">You have completed this awareness step. Remember to report suspicious emails using the Report Phish button in Gmail.</p>
        </div>
    </div>
</body>
</html>
