@props(['label', 'value', 'color' => '#171717', 'hint' => null])

<div class="card px-3 py-2.5">
    <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">{{ $label }}</div>
    <div class="mt-0.5 text-lg font-bold leading-tight tabular-nums" style="color: {{ $color }}">{{ $value }}</div>
    @if ($hint)
        <div class="mt-0.5 text-[11px] text-subtle">{{ $hint }}</div>
    @endif
</div>
