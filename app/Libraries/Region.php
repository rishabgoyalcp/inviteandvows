<?php

namespace App\Libraries;

/**
 * Resolves the market (region) from the request host, plus the languages it offers.
 * Data comes from the `regions` / `languages` tables; the fallback only applies before seeding.
 */
class Region
{
    private static ?array $current = null;
    private static array $languages = [];

    public static function current(?string $host = null): array
    {
        if (self::$current !== null) {
            return self::$current;
        }
        $host = strtolower(preg_replace('/^www\./', '', $host ?? ($_SERVER['HTTP_HOST'] ?? '')));
        $host = explode(':', $host)[0];

        $fallback = ['id' => 0, 'code' => 'in', 'domain' => $host, 'currency' => 'INR', 'default_locale' => 'en', 'payment_driver' => 'razorpay'];
        try {
            $db  = \Config\Database::connect();
            $row = $db->table('regions')->where('domain', $host)->where('is_active', 1)->get()->getRowArray();
            if (! $row) {
                $row = $db->table('regions')->where('code', env('app.defaultRegion', 'in'))->get()->getRowArray();
            }
        } catch (\Throwable) {
            $row = null;
        }

        return self::$current = $row ?: $fallback;
    }

    /** @return array<string, array> active languages offered in the current region, keyed by code */
    public static function languages(): array
    {
        $region = self::current();
        if (isset(self::$languages[$region['id']])) {
            return self::$languages[$region['id']];
        }
        $list = [];
        try {
            $rows = \Config\Database::connect()->table('region_languages rl')
                ->select('l.code, l.name, l.native, l.is_rtl')
                ->join('languages l', 'l.id = rl.language_id')
                ->where('rl.region_id', $region['id'])->where('l.is_active', 1)
                ->orderBy('rl.sort')->get()->getResultArray();
            foreach ($rows as $r) {
                $list[$r['code']] = $r;
            }
        } catch (\Throwable) {
        }
        if (! $list) {
            $list['en'] = ['code' => 'en', 'name' => 'English', 'native' => 'English', 'is_rtl' => 0];
        }

        return self::$languages[$region['id']] = $list;
    }
}
