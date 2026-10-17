<?php

namespace App\Libraries\Payments;

class RazorpayDriver implements PaymentDriver
{
    public function name(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return env('razorpay.keyId') && env('razorpay.keySecret');
    }

    public function createOrder(int $amountMinor, string $currency, string $receipt): array
    {
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_USERPWD        => env('razorpay.keyId') . ':' . env('razorpay.keySecret'),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode(['amount' => $amountMinor, 'currency' => $currency, 'receipt' => $receipt]),
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $body, true);
        if ($status !== 200 || empty($data['id'])) {
            throw new \RuntimeException('Razorpay order failed: ' . ($data['error']['description'] ?? "HTTP {$status}"));
        }

        return ['provider_ref' => $data['id'], 'key_id' => env('razorpay.keyId'), 'amount' => $amountMinor, 'currency' => $currency];
    }

    public function verifyPayment(string $providerOrderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $providerOrderId . '|' . $paymentId, (string) env('razorpay.keySecret'));

        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $rawBody, string $signature): bool
    {
        $secret = (string) env('razorpay.webhookSecret');
        if ($secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
