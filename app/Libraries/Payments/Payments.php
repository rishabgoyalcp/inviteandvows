<?php

namespace App\Libraries\Payments;

class Payments
{
    /** Resolve the driver named on the region row. Add new providers here. */
    public static function for(string $driver): PaymentDriver
    {
        return match ($driver) {
            'razorpay' => new RazorpayDriver(),
            default    => throw new \RuntimeException("Unknown payment driver: {$driver}"),
        };
    }
}
