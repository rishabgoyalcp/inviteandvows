<?php

namespace App\Controllers;

use App\Libraries\Coupons;
use App\Libraries\InviteService;
use App\Libraries\OrderService;
use App\Libraries\Payments\Payments;
use App\Libraries\Region;
use CodeIgniter\Exceptions\PageNotFoundException;

class Checkout extends BaseController
{
    private function owned(string $token): array
    {
        $inv = db_connect()->table('invites')->where('edit_token', $token)->get()->getRowArray();
        $own = $inv && (
            (session()->get('user_id') && (int) $inv['user_id'] === (int) session()->get('user_id'))
            || in_array($token, (array) session()->get('drafts'), true)
        );
        if (! $own) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    private function missingRequired(array $inv): array
    {
        $content = json_decode($inv['content'] ?? '{}', true) ?: [];
        $missing = [];
        foreach (InviteService::fieldsFor((int) $inv['template_id']) as $f) {
            if ($f['is_required'] && trim((string) ($content[$f['field_key']] ?? '')) === '') {
                $missing[] = i18n($f['labels'], $f['field_key']);
            }
        }

        return $missing;
    }

    private function format(array $inv): array
    {
        $tf = db_connect()->table('template_formats')->where(['template_id' => $inv['template_id'], 'region_id' => Region::current()['id'], 'format' => $inv['format'] ?: 'website', 'is_active' => 1])->get()->getRowArray();
        if (! $tf) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $tf;
    }

    public function show(string $token)
    {
        $inv = $this->owned($token);
        if ($inv['status'] === 'published') {
            return redirect()->to('/dashboard');
        }
        $missing = $this->missingRequired($inv);
        if ($missing) {
            return redirect()->to('/build/' . $token)->with('error', lang('Site.fillRequired') . ' ' . implode(', ', $missing));
        }
        if (! session()->get('user_id')) {
            return view('checkout/account', ['action' => '/checkout/' . $token . '/account', 'error' => session()->getFlashdata('error')]);
        }
        $region = Region::current();
        $tf     = $this->format($inv);
        if (! $inv['user_id']) {
            db_connect()->table('invites')->where('id', $inv['id'])->update(['user_id' => session()->get('user_id')]);
        }
        $mine = Coupons::availableFor((int) session()->get('user_id'));

        return view('checkout/summary', [
            'action' => '/checkout/' . $token . '/pay', 'name' => i18n(db_connect()->table('templates')->where('id', $inv['template_id'])->get()->getRow('names'), ''),
            'price' => money((int) $tf['price_minor'], $region['currency']), 'coupon' => $mine[0]['code'] ?? '', 'error' => session()->getFlashdata('error'),
        ]);
    }

    /** Creates the order (applying a coupon) and shows the payment page. */
    public function pay(string $token)
    {
        $inv = $this->owned($token);
        if ($inv['status'] === 'published' || ! session()->get('user_id') || $this->missingRequired($inv)) {
            return redirect()->to('/checkout/' . $token);
        }
        $region = Region::current();
        $uid    = (int) session()->get('user_id');
        $res    = OrderService::create($uid, (int) $inv['id'], $this->format($inv), (string) $this->request->getPost('coupon'), $region['currency']);
        if (isset($res['error'])) {
            return redirect()->to('/checkout/' . $token)->with('error', lang($res['error']));
        }
        $out = OrderService::pay($res['order_id'], $res['amount'], $region['currency'], $region['payment_driver'], 'inv' . $inv['id'] . '-' . $res['order_id'], '/checkout/' . $token . '/verify', '/checkout/' . $token . '/simulate');
        if (isset($out['error'])) {
            return redirect()->to('/build/' . $token)->with('error', lang($out['error']));
        }

        return isset($out['free']) ? redirect()->to('/dashboard')->with('ok', lang('Site.published')) : view($out['view'], $out['data']);
    }

    /** Sign in or create a customer account at checkout. */
    public function account(string $token)
    {
        $this->owned($token);
        $err = \App\Libraries\Accounts::loginOrCreate((string) $this->request->getPost('email'), (string) $this->request->getPost('password'), trim((string) $this->request->getPost('name')));

        return $err ? redirect()->to('/checkout/' . $token)->with('error', lang($err)) : redirect()->to('/checkout/' . $token);
    }

    /** Browser return from Razorpay checkout. The webhook does the same job, whichever arrives first wins. */
    public function verify(string $token)
    {
        $inv = $this->owned($token);
        $oid = (string) $this->request->getPost('razorpay_order_id');
        $pid = (string) $this->request->getPost('razorpay_payment_id');
        $sig = (string) $this->request->getPost('razorpay_signature');

        $pay = db_connect()->table('payments p')->select('p.*, o.invite_id')->join('orders o', 'o.id = p.order_id')
            ->where('p.provider_ref', $oid)->where('o.invite_id', $inv['id'])->get()->getRowArray();
        $driver = Payments::for(Region::current()['payment_driver']);
        if (! $pay || ! $driver->verifyPayment($oid, $pid, $sig)) {
            return redirect()->to('/build/' . $token)->with('error', lang('Site.paymentFailed'));
        }
        InviteService::markPaid((int) $pay['order_id'], $oid, $pid);

        return redirect()->to('/dashboard')->with('ok', lang('Site.published'));
    }

    /** Developer-only fake payment, available when no gateway keys exist. */
    public function simulate(string $token)
    {
        if (ENVIRONMENT !== 'development') {
            throw PageNotFoundException::forPageNotFound();
        }
        $inv = $this->owned($token);
        $ref = (string) $this->request->getPost('ref');
        $pay = db_connect()->table('payments p')->select('p.*')->join('orders o', 'o.id = p.order_id')
            ->where('p.provider_ref', $ref)->where('o.invite_id', $inv['id'])->get()->getRowArray();
        if (! $pay || ! str_starts_with($ref, 'sim_')) {
            throw PageNotFoundException::forPageNotFound();
        }
        InviteService::markPaid((int) $pay['order_id'], $ref, 'sim_payment');

        return redirect()->to('/dashboard')->with('ok', lang('Site.published'));
    }
}
