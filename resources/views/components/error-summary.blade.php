@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[12.5px] text-red-700']) }} role="alert">
        <div class="font-semibold">Please fix the following:</div>
        <ul class="mt-1 list-disc space-y-0.5 pl-5">
            @foreach ($errors->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
