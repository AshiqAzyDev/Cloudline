@props(['status'])

@php
    $enum = $status instanceof \App\Enums\InvoiceStatus ? $status : \App\Enums\InvoiceStatus::from($status);
@endphp

<span class="inline-flex whitespace-nowrap rounded-md px-1.5 py-0.5 text-[10.5px] font-semibold" style="background: {{ $enum->background() }}; color: {{ $enum->color() }}">
    {{ $enum->label() }}
</span>
