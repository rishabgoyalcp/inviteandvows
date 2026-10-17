<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $regions = [
            ['code' => 'in',  'domain' => 'inviteandvows.in',  'currency' => 'INR', 'default_locale' => 'en', 'payment_driver' => 'razorpay'],
            ['code' => 'com', 'domain' => 'inviteandvows.com', 'currency' => 'USD', 'default_locale' => 'en', 'payment_driver' => 'razorpay'],
        ];
        foreach ($regions as $r) {
            $this->upsert('regions', ['code' => $r['code']], $r + ['is_active' => 1, 'created_at' => $now]);
        }

        // [code, name, native, rtl, active]. Only English and Hindi are active at launch.
        $languages = [
            ['en', 'English', 'English', 0, 1], ['hi', 'Hindi', 'हिन्दी', 0, 1],
            ['bn', 'Bengali', 'বাংলা', 0, 0], ['mr', 'Marathi', 'मराठी', 0, 0], ['te', 'Telugu', 'తెలుగు', 0, 0],
            ['ta', 'Tamil', 'தமிழ்', 0, 0], ['gu', 'Gujarati', 'ગુજરાતી', 0, 0], ['kn', 'Kannada', 'ಕನ್ನಡ', 0, 0],
            ['ml', 'Malayalam', 'മലയാളം', 0, 0], ['pa', 'Punjabi', 'ਪੰਜਾਬੀ', 0, 0], ['or', 'Odia', 'ଓଡ଼ିଆ', 0, 0],
            ['ur', 'Urdu', 'اردو', 1, 0], ['as', 'Assamese', 'অসমীয়া', 0, 0],
            ['es', 'Spanish', 'Español', 0, 0], ['fr', 'French', 'Français', 0, 0], ['de', 'German', 'Deutsch', 0, 0],
            ['pt', 'Portuguese', 'Português', 0, 0], ['ar', 'Arabic', 'العربية', 1, 0],
        ];
        foreach ($languages as [$code, $name, $native, $rtl, $active]) {
            $this->upsert('languages', ['code' => $code], ['code' => $code, 'name' => $name, 'native' => $native, 'is_rtl' => $rtl, 'is_active' => $active, 'created_at' => $now]);
        }

        // Which languages each region offers, in display order.
        $offer = [
            'in'  => ['en', 'hi', 'bn', 'mr', 'te', 'ta', 'gu', 'kn', 'ml', 'pa', 'or', 'ur', 'as'],
            'com' => ['en', 'es', 'fr', 'de', 'pt', 'ar'],
        ];
        foreach ($offer as $region => $codes) {
            $rid = $this->idOf('regions', 'code', $region);
            foreach ($codes as $i => $code) {
                $this->upsert('region_languages', ['region_id' => $rid, 'language_id' => $this->idOf('languages', 'code', $code)], ['sort' => $i]);
            }
        }

        $types = [
            ['wedding', ['en' => 'Wedding', 'hi' => 'शादी']],
            ['birthday', ['en' => 'Birthday', 'hi' => 'जन्मदिन']],
            ['baby-shower', ['en' => 'Baby Shower', 'hi' => 'गोद भराई']],
        ];
        foreach ($types as $i => [$slug, $names]) {
            $this->upsert('event_types', ['slug' => $slug], ['names' => json_encode($names, JSON_UNESCAPED_UNICODE), 'sort' => $i, 'is_active' => 1, 'created_at' => $now]);
        }

        $email = env('admin.email');
        $pass  = env('admin.password');
        if ($email && $pass) {
            $this->upsert('users', ['email' => $email], ['name' => 'Admin', 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => 'admin', 'is_active' => 1, 'created_at' => $now]);
        }
    }

    private function idOf(string $table, string $col, string $val): int
    {
        return (int) $this->db->table($table)->where($col, $val)->get()->getRow()->id;
    }

    /** Insert when no row matches $where; otherwise leave existing rows untouched. */
    private function upsert(string $table, array $where, array $data): void
    {
        if ($this->db->table($table)->where($where)->countAllResults() === 0) {
            $this->db->table($table)->insert($where + $data);
        }
    }
}
