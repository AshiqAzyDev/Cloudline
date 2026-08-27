<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckoutService $stripe)
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        try {
            $event = $stripe->constructEvent($payload, $signature);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return response('Invalid signature', 400);
        }

        $stripe->handleEvent($event);

        return response()->json(['received' => true]);
    }
}
