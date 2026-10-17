<?php

namespace App\Libraries;

use App\Libraries\Payments\Payments;

/** Creates an order (with an optional coupon) and starts its payment. Shared by the invite checkout and the Canva buy flow. */
class OrderService
{
    /**
     * @param array $tf template_formats row (id, format, price_minor)
     * @return array{order_id?: int, amount?: int, error?: string}
     */
    public static function create(int $userId, ?int $inviteId, array $tf, string $couponCode, string $currency): array
    {
        $price = (int) $tf['price_minor'];
        [$discount, $err] = Coupons::check($couponCode, $userId, $tf['format'], $price, $currency);
        if ($err) {
            return ['error' => $err];
        }
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->table('orders')->insert([
            'user_id' => $userId, 'invite_id' => $inviteId, 'template_format_id' => $tf['id'], 'currency' => $currency,
            'amount_minor' => $price - $discount, 'discount_minor' => $discount, 'coupon_code' => $discount > 0 ? strtoupper(trim($couponCode)) : null,
            'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);

        return ['order_id' => (int) $db->insertID(), 'amount' => $price - $discount];
    }

    /**
     * Starts payment for an order. Returns one of:
     *  ['free' => true]                       nothing to pay, order already marked paid
     *  ['view' => name, 'data' => [...]]      the payment page to show
     *  ['error' => langKey]                   the gateway could not create the order
     */
    public static function pay(int $orderId, int $amount, string $currency, string $driverName, string $reference, string $verifyUrl, string $simulateUrl): array
    {
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $base = ['order_id' => $orderId, 'status' => 'created', 'created_at' => $now, 'updated_at' => $now];

        if ($amount === 0) {
            $ref = 'free_' . bin2hex(random_bytes(6));
            $db->table('payments')->insert($base + ['driver' => 'free', 'provider_ref' => $ref]);
            InviteService::markPaid($orderId, $ref, 'free');

            return ['free' => true];
        }
        $driver = Payments::for($driverName);
        $label  = money($amount, $currency);
        if ($driver->name() === 'razorpay' && ! $driver->isConfigured()) {
            // No gateway keys: only a developer machine may fake a payment.
            if (ENVIRONMENT !== 'development') {
                throw new \RuntimeException('Payment gateway is not configured.');
            }
            $ref = 'sim_' . bin2hex(random_bytes(6));
            $db->table('payments')->insert($base + ['driver' => $driver->name(), 'provider_ref' => $ref]);

            return ['view' => 'checkout/simulate', 'data' => ['action' => $simulateUrl, 'ref' => $ref, 'amount' => $label]];
        }
        try {
            $data = $driver->createOrder($amount, $currency, $reference);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
            $db->table('orders')->where('id', $orderId)->update(['status' => 'failed']);

            return ['error' => 'Site.paymentUnavailable'];
        }
        $db->table('payments')->insert($base + ['driver' => $driver->name(), 'provider_ref' => $data['provider_ref']]);
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRowArray();

        return ['view' => 'checkout/razorpay', 'data' => ['action' => $verifyUrl, 'data' => $data, 'user' => $user, 'amount' => $label]];
    }
}
