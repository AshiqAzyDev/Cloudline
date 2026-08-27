<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceEventType;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PayController extends Controller
{
    public function show(string $token, StripeCheckoutService $stripe)
    {
        $invoice = Invoice::query()
            ->with(['items', 'client', 'billingEntity'])
            ->where('pay_token', $token)
            ->firstOrFail();

        $invoice->recordEvent(InvoiceEventType::Viewed);

        $clientSecret = null;
        $checkoutError = null;
        $bankOnly = $stripe->isBankOnlyCurrency($invoice->currency);

        if ($invoice->isPayable() && ! $bankOnly) {
            try {
                $clientSecret = $stripe->createEmbeddedSession($invoice->fresh(['client', 'billingEntity']));
                if ($stripe->isConfigured() && ! $clientSecret) {
                    $checkoutError = 'Online card payment is temporarily unavailable. Please pay by bank transfer using the details below.';
                }
            } catch (\Throwable) {
                $checkoutError = 'Online card payment is temporarily unavailable. Please pay by bank transfer using the details below.';
            }
        }

        return view('pay.show', [
            'invoice' => $invoice->fresh(['items', 'client', 'billingEntity']),
            'clientSecret' => $clientSecret,
            'publishableKey' => config('stripe.key'),
            'checkoutError' => $checkoutError,
            'bankOnly' => $bankOnly,
        ]);
    }

    public function reportBankPayment(string $token, InvoiceService $invoices)
    {
        $invoice = Invoice::query()
            ->where('pay_token', $token)
            ->firstOrFail();

        try {
            $invoices->reportBankPayment($invoice);
        } catch (ValidationException $e) {
            return redirect()
                ->route('pay.show', $token)
                ->with('error', collect($e->errors())->flatten()->first() ?: $e->getMessage());
        }

        return redirect()
            ->route('pay.show', $token)
            ->with('success', 'Thanks — we\'ll verify your bank transfer and mark the invoice paid once the payment arrives.');
    }

    public function complete(string $token, Request $request, StripeCheckoutService $stripe)
    {
        $invoice = Invoice::query()
            ->with(['items', 'client', 'billingEntity'])
            ->where('pay_token', $token)
            ->firstOrFail();

        if ($request->string('session_id')->isNotEmpty() && $stripe->isConfigured()) {
            try {
                $session = $stripe->retrieveSession($request->string('session_id'));
                if ($session->payment_status === 'paid' && $invoice->isPayable()) {
                    $invoice->refresh();
                }
            } catch (\Throwable) {
                // Ignore — webhook will still settle the invoice.
            }
        }

        return view('pay.show', [
            'invoice' => $invoice->fresh(['items', 'client', 'billingEntity']),
            'clientSecret' => null,
            'publishableKey' => config('stripe.key'),
            'checkoutError' => null,
            'bankOnly' => $stripe->isBankOnlyCurrency($invoice->currency),
        ]);
    }

    public function pdf(string $token, InvoicePdfService $pdf)
    {
        $invoice = Invoice::query()
            ->with(['items', 'client', 'billingEntity'])
            ->where('pay_token', $token)
            ->firstOrFail();

        return $pdf->download($invoice);
    }
}
