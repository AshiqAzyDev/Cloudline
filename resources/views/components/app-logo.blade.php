@props([
    'markClass' => 'flex h-6 w-6 items-center justify-center rounded-md bg-accent text-[11px] font-bold text-white',
    'textClass' => 'font-display text-[13px] font-semibold tracking-tight',
    'imgClass' => 'h-6 max-w-[120px] object-contain object-left',
    'showText' => true,
])

@php
    $logoUrl = \App\Support\Branding::logoUrl();
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo" class="{{ $imgClass }}">
    @else
        <div class="{{ $markClass }}">C</div>
    @endif
    @if ($showText && ! $logoUrl)
        <div class="{{ $textClass }}">Cloudline</div>
    @endif
</div>
