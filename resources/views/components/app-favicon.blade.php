@php
    $faviconUrl = \App\Support\Branding::faviconUrl();
@endphp

@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
@endif
