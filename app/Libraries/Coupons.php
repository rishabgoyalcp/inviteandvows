<?php

namespace App\Libraries;

class Coupons
{
    /**
     * Checks a coupon for this customer and price. Returns [discountMinor, errorLangKey|null].
     * A coupon may never leave a payable amount under the gateway minimum (1 unit), so those are refused.
     */
    public static function check(string $code, int $userId, string $format, int $priceMinor, string $currency): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [0, null];
        }
        $db = db_connect();
        $c  = $db->table('coupons')->where('code', $code)->where('is_active', 1)->get()->getRowArray();
        if (! $c || ($c['expires_at'] && strtotime($c['expires_at']) < time()) || ($c['user_id'] && (int) $c['user_id'] !== $userId)) {
            return [0, 'Site.couponInvalid'];
        }
        if ($c['formats'] && ! in_array($format, explode(',', $c['formats']), true)) {
            return [0, 'Site.couponFormat'];
        }
        if ($c['type'] === 'fixed' && $c['currency'] !== $currency) {
            return [0, 'Site.couponInvalid'];
        }
        $used = $db->table('orders')->where('coupon_code', $code)->where('status', 'paid');
        if ($c['max_uses'] > 0 && (clone $used)->countAllResults() >= $c['max_uses']) {
            return [0, 'Site.couponUsedUp'];
        }
        if ((clone $used)->where('user_id', $userId)->countAllResults() > 0) {
            return [0, 'Site.couponUsedUp']; // one use per customer
        }
        $discount = $c['type'] === 'percent' ? (int) floor($priceMinor * min(100, $c['value']) / 100) : min($priceMinor, (int) $c['value']);
        $final    = $priceMinor - $discount;
        if ($final > 0 && $final < 100) {
            return [0, 'Site.couponInvalid'];
        }

        return [$discount, null];
    }

    /** Unused coupons reserved for this customer (welcome and referral rewards), best first. */
    public static function availableFor(int $userId): array
    {
        $db   = db_connect();
        $rows = $db->table('coupons')->where('user_id', $userId)->where('is_active', 1)->orderBy('value', 'DESC')->get()->getResultArray();

        return array_values(array_filter($rows, static fn ($c) => (! $c['expires_at'] || strtotime($c['expires_at']) > time())
            && $db->table('orders')->where(['coupon_code' => $c['code'], 'status' => 'paid'])->countAllResults() === 0));
    }

    public static function create(array $row): string
    {
        $db = db_connect();
        do {
            $code = ($row['prefix'] ?? 'IV') . '-' . strtoupper(random_code(6));
        } while ($db->table('coupons')->where('code', $code)->countAllResults() > 0);
        unset($row['prefix']);
        $now = date('Y-m-d H:i:s');
        $db->table('coupons')->insert($row + ['code' => $code, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);

        return $code;
    }
}
