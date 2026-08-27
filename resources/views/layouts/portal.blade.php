<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Client portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div class="mx-auto max-w-[900px] px-6 py-10">
        <header class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-[7px] bg-accent text-[14px] font-bold text-white">C</div>
                <div>
                    <div class="text-[15px] font-bold tracking-tight">Cloudline Billing</div>
                    <div class="text-xs text-muted">Client portal</div>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="text-muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="font-semibold text-label">Log out</button>
                </form>
            </div>
        </header>
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('error') }}</div>
        @endif
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
