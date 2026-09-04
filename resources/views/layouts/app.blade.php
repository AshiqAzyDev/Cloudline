<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Cloudline Billing' }}</title>
    <x-app-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div class="flex min-h-screen">
        <aside class="sticky top-0 flex h-screen w-[176px] shrink-0 flex-col border-r border-line bg-white px-2 py-3">
            <x-app-logo
                class="mb-3 px-2"
                imgClass="h-10 max-w-[152px] object-contain object-left"
                markClass="flex h-9 w-9 items-center justify-center rounded-md bg-accent text-[14px] font-bold text-white"
                textClass="font-display text-[17px] font-semibold tracking-tight"
            />
            <nav class="flex flex-col gap-0.5">
                @php
                    $item = function (string $route, string $label) {
                        $active = request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route));
                        if ($route === 'dashboard') {
                            $active = request()->routeIs('dashboard');
                        }
                        return compact('active', 'label');
                    };
                @endphp
                @foreach ([
                    ['dashboard', 'Dashboard', 'invoices.view'],
                    ['invoices.index', 'Invoices', 'invoices.view'],
                    ['invoices.create', 'New invoice', 'invoices.create'],
                    ['clients.index', 'Clients', 'clients.view'],
                    ['services.index', 'Services', 'services.view'],
                    ['reports.index', 'Reports', 'reports.view'],
                    ['settings.index', 'Settings', null],
                ] as [$route, $label, $permission])
                    @php $nav = $item($route, $label); @endphp
                    @if ($route === 'settings.index' && ! auth()->user()->can('settings.manage') && ! auth()->user()->can('users.manage'))
                        @continue
                    @endif
                    @if ($permission && ! auth()->user()->can($permission))
                        @continue
                    @endif
                    <a href="{{ route($route) }}"
                       class="rounded-md px-2.5 py-1.5 text-[12.5px] font-medium {{ $nav['active'] ? 'bg-brand-soft text-ink font-semibold' : 'text-label hover:bg-white hover:text-ink' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="mt-auto rounded-md border border-line bg-canvas p-2">
                <div class="text-[10px] font-semibold uppercase tracking-wide text-label">Stripe</div>
                <div class="mt-1 flex items-center gap-1.5">
                    <div class="h-1.5 w-1.5 rounded-full {{ config('stripe.secret') ? 'bg-accent' : 'bg-red-500' }}"></div>
                    <div class="text-[11px] text-ink">{{ config('stripe.secret') ? 'Connected' : 'Not configured' }}</div>
                </div>
            </div>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="flex h-11 items-center justify-end gap-3 border-b border-line bg-white px-4 text-[12.5px]">
                <span class="text-muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="font-semibold text-label hover:text-ink">Log out</button>
                </form>
            </header>
            <main>
                @if (session('success'))
                    <div class="page !pb-0">
                        <div class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[12.5px] font-medium text-green-800">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="page !pb-0">
                        <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[12.5px] font-medium text-red-700">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
