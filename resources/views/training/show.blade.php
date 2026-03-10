<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Security awareness - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4">
    <div class="max-w-2xl mx-auto">
        @if($showBanner ?? true)
            <div class="rounded-lg bg-amber-100 border border-amber-300 p-4 mb-6 text-amber-900 text-center text-sm font-medium">
                This is an authorized security awareness exercise. No real data was collected.
            </div>
        @endif
        <div class="rounded-lg bg-white p-8 shadow prose max-w-none">
            {!! $content !!}
        </div>
        <p class="mt-6 text-center text-slate-500 text-sm"><a href="{{ url('/training/thanks') }}" class="hover:underline">Continue to completion</a></p>
    </div>
</body>
</html>
