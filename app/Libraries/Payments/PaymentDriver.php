<?php

namespace App\Libraries\Payments;

/** Every payment provider (Razorpay now, Stripe later) implements this. */
interface PaymentDriver
{
    public function name(): string;

    /** Create a provider order. Returns data the checkout page needs, including 'provider_ref'. */
    public function createOrder(int $amountMinor, string $currency, string $receipt): array;

    /** Verify the client-side payment result. */
    public function verifyPayment(string $providerOrderId, string $paymentId, string $signature): bool;

    /** Verify a webhook body against its signature header. */
    public function verifyWebhook(string $rawBody, string $signature): bool;
}
