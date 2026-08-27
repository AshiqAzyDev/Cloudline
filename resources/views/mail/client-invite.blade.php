<x-mail::message>
Hi {{ $user->name }},

You've been invited to the Cloudline Billing client portal. Set your password to view and pay invoices.

<x-mail::button :url="$url">
Set password
</x-mail::button>

This link expires in 7 days.
</x-mail::message>
