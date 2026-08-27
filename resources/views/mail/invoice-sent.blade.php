<x-mail::message>
{{ $body }}

<x-mail::button :url="$payUrl">
Pay invoice
</x-mail::button>

Thanks,<br>
{{ $invoice->billingEntity->legal_name }}
</x-mail::message>
