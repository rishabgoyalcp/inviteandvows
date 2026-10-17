<?php

namespace App\Controllers;

class Referral extends BaseController
{
    /** /r/CODE remembers who sent the visitor; the welcome coupon is created when they sign up. */
    public function land(string $code)
    {
        $code = strtoupper($code);
        if (db_connect()->table('users')->where('referral_code', $code)->countAllResults() > 0) {
            session()->set('ref', $code);
        }

        return redirect()->to('/templates');
    }
}
