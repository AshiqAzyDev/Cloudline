<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $subject = Setting::getValue(
            'email.invoice_sent.subject',
            'Invoice {{invoice_number}} from {{entity_name}}'
        );

        return new Envelope(subject: $this->replace($subject));
    }

    public function content(): Content
    {
        $body = Setting::getValue(
            'email.invoice_sent.body',
            "Hi {{client_name}},\n\nPlease find invoice {{invoice_number}} for {{amount}} attached. You can pay online here:\n{{pay_url}}\n\nDue date: {{due_date}}\n\nThank you,\n{{entity_name}}"
        );

        return new Content(
            markdown: 'mail.invoice-sent',
            with: [
                'body' => $this->replace($body),
                'invoice' => $this->invoice,
                'payUrl' => route('pay.show', $this->invoice->pay_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => app(InvoicePdfService::class)->render($this->invoice),
                $this->invoice->displayNumber().'.pdf',
            )->withMime('application/pdf'),
        ];
    }

    private function replace(string $template): string
    {
        $invoice = $this->invoice->loadMissing(['client', 'billingEntity']);

        return strtr($template, [
            '{{invoice_number}}' => $invoice->displayNumber(),
            '{{client_name}}' => $invoice->client->contact ?: $invoice->client->company,
            '{{amount}}' => $invoice->formattedTotal(),
            '{{pay_url}}' => route('pay.show', $invoice->pay_token),
            '{{due_date}}' => $invoice->due_date->format('d M Y'),
            '{{entity_name}}' => $invoice->billingEntity->legal_name,
        ]);
    }
}
