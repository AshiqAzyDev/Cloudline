<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Cloudline Billing' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-5 py-10">
        <a href="{{ route('login') }}" class="mb-6 flex items-center justify-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-[12px] font-bold text-white">C</div>
            <div class="font-display text-[1.05rem] font-semibold tracking-tight">Cloudline</div>
        </a>
        <div class="card card-pad !p-5">
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>
</html>
