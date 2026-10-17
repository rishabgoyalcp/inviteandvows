<?php

namespace App\Libraries;

/** Share link -> friend gets a welcome coupon -> referrer gets a reward coupon after the friend's first paid order. */
class Referrals
{
    public static function codeFor(int $userId): string
    {
        $db = db_connect();
        $u  = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (! empty($u['referral_code'])) {
            return $u['referral_code'];
        }
        do {
            $code = strtoupper(random_code(8));
        } while ($db->table('users')->where('referral_code', $code)->countAllResults() > 0);
        $db->table('users')->where('id', $userId)->update(['referral_code' => $code]);

        return $code;
    }

    /** Called when a new customer account is created. */
    public static function onSignup(int $newUserId): void
    {
        $code = session()->get('ref');
        $pct  = (int) env('referral.friendPercent', 10);
        if (! is_string($code) || $code === '') {
            return;
        }
        $db  = db_connect();
        $ref = $db->table('users')->where('referral_code', $code)->get()->getRowArray();
        if (! $ref || (int) $ref['id'] === $newUserId) {
            return;
        }
        $db->table('users')->where('id', $newUserId)->update(['referred_by' => $ref['id']]);
        if ($pct > 0) {
            Coupons::create(['prefix' => 'WELCOME', 'type' => 'percent', 'value' => min(100, $pct), 'max_uses' => 1, 'user_id' => $newUserId, 'source' => 'referral',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))]);
        }
    }

    /** Called once when an order becomes paid. Rewards the referrer on the friend's first paid order only. */
    public static function onPaid(array $order): void
    {
        $pct = (int) env('referral.rewardPercent', 10);
        $db  = db_connect();
        $u   = $db->table('users')->where('id', $order['user_id'])->get()->getRowArray();
        if ($pct <= 0 || ! $u || ! $u['referred_by']) {
            return;
        }
        $paid = $db->table('orders')->where(['user_id' => $u['id'], 'status' => 'paid'])->countAllResults();
        $done = $db->table('coupons')->where(['source' => 'referral', 'ref_user_id' => $u['id']])->countAllResults();
        if ($paid === 1 && $done === 0) {
            Coupons::create(['prefix' => 'THANKS', 'type' => 'percent', 'value' => min(100, $pct), 'max_uses' => 1, 'user_id' => $u['referred_by'], 'source' => 'referral',
                'ref_user_id' => $u['id'], 'expires_at' => date('Y-m-d H:i:s', strtotime('+180 days'))]);
        }
    }
}
