<?php

namespace App\Controllers;

class Pages extends BaseController
{
    public function show(string $page)
    {
        $titles = ['privacy' => 'Privacy policy', 'terms' => 'Terms of service', 'refunds' => 'Refund and cancellation policy', 'contact' => 'Contact us'];
        // Business details come from .env so the same pages serve both domains.
        $biz = [
            'name'    => env('business.name', 'Invite & Vows'), 'email' => env('business.email', 'support@inviteandvows.in'),
            'phone'   => env('business.phone', ''), 'address' => env('business.address', ''), 'gstin' => env('business.gstin', ''),
        ];

        return view('pages/' . $page, ['title' => $titles[$page] . ' · ' . lang('Site.brand'), 'description' => $titles[$page], 'biz' => $biz, 'updated' => '17 October 2026']);
    }
}
