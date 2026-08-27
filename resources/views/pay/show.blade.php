<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay {{ $invoice->displayNumber() }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($clientSecret)
        <script src="https://js.stripe.com/v3/"></script>
    @endif
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div class="flex min-h-screen flex-col items-center px-5 py-10">
        <div class="mb-5 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-accent text-[12px] font-bold text-white">C</div>
            <div class="font-display text-[15px] font-semibold tracking-tight">Cloudline</div>
        </div>

        <div class="card w-full max-w-[420px] card-pad !p-5">
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-ink text-[11px] font-bold text-white">{{ $invoice->billingEntity->initial() }}</div>
                <div class="text-[13px] font-semibold">{{ $invoice->billingEntity->legal_name }}</div>
            </div>
            <div class="text-[12px] text-muted">Invoice {{ $invoice->displayNumber() }}</div>
            <div class="mt-1 font-display text-[2rem] font-semibold tracking-tight">{{ $invoice->formattedTotal() }}</div>
            <div class="mt-0.5 text-[12.5px] text-muted">for {{ $invoice->client->company }}</div>

            @if (session('success'))
                <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-[12.5px] text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[12.5px] text-red-700">{{ session('error') }}</div>
            @endif

            @if ($invoice->status->value === 'paid')
                <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-3 py-3 text-center">
                    <div class="text-[13px] font-semibold text-green-700">Payment received</div>
                    <div class="mt-0.5 text-[12px] text-label">Receipt sent to {{ $invoice->client->email }}</div>
                </div>
            @elseif ($invoice->status->value === 'void')
                <div class="mt-4 rounded-md border border-line bg-canvas px-3 py-3 text-center text-[13px] text-muted">This invoice has been voided.</div>
            @elseif ($invoice->status->value === 'awaiting_verification')
                <div class="mt-4 rounded-md border border-teal-200 bg-accent-soft px-3 py-3 text-center">
                    <div class="text-[13px] font-semibold text-accent">Awaiting verification</div>
                    <div class="mt-1 text-[12px] text-label">Thanks — we’ll confirm your bank transfer and mark this invoice paid once the payment arrives.</div>
                </div>
            @else
                @if ($clientSecret && $publishableKey)
                    <div id="checkout" class="mt-4"></div>
                @elseif ($bankOnly || $checkoutError || ! $clientSecret)
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-[12.5px] text-amber-900">
                        @if ($bankOnly)
                            This invoice is paid by bank transfer (card payments are not available for {{ $invoice->currency }}).
                        @else
                            {{ $checkoutError ?? 'Please pay by bank transfer using the details below, or download the invoice PDF.' }}
                        @endif
                    </div>
                @endif

                @if ($invoice->billingEntity?->formattedBankDetails() !== '' && $invoice->isPayable())
                    <div class="mt-4 rounded-md border border-line bg-canvas px-3 py-3">
                        <div class="section-label !mb-2">Bank transfer</div>
                        <pre class="whitespace-pre-wrap font-sans text-[12.5px] leading-5 text-label">{{ $invoice->billingEntity->formattedBankDetails() }}</pre>
                    </div>
                    <form method="POST" action="{{ route('pay.bank', $invoice->pay_token) }}" class="mt-3">
                        @csrf
                        <button class="btn btn-secondary w-full">I’ve paid by bank transfer</button>
                    </form>
                @endif

                <a href="{{ route('pay.pdf', $invoice->pay_token) }}" class="btn btn-ghost mt-2 w-full">Download invoice PDF</a>
            @endif

            <div class="mt-4 text-center text-[11px] text-subtle">Secure payment · Powered by Stripe</div>
        </div>
    </div>

    @if ($clientSecret && $publishableKey)
        <script>
            (async () => {
                const stripe = Stripe(@json($publishableKey));
                const checkout = await stripe.initEmbeddedCheckout({ clientSecret: @json($clientSecret) });
                checkout.mount('#checkout');
            })();
        </script>
    @endif
</body>
</html>
