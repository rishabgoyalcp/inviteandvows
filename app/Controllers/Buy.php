<?php

namespace App\Controllers;

use App\Libraries\Accounts;
use App\Libraries\Coupons;
use App\Libraries\InviteService;
use App\Libraries\OrderService;
use App\Libraries\Payments\Payments;
use App\Libraries\Region;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Buying a ready-made product (editable Canva design or PDF). No builder: the buyer receives a Canva link after payment. */
class Buy extends BaseController
{
    private const CANVA_FORMATS = ['canva_design', 'canva_pdf'];

    private function format(int $id): array
    {
        $tf = db_connect()->table('template_formats tf')->select('tf.*, t.names AS template_names, t.slug')
            ->join('templates t', 't.id = tf.template_id')
            ->where('tf.id', $id)->where('tf.is_active', 1)->where('tf.region_id', Region::current()['id'])->where('t.is_active', 1)
            ->whereIn('tf.format', self::CANVA_FORMATS)->get()->getRowArray();
        if (! $tf || ! $tf['delivery_url']) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $tf;
    }

    public function show(int $id)
    {
        $tf = $this->format($id);
        if (! session()->get('user_id')) {
            return view('checkout/account', ['action' => '/buy/' . $id . '/account', 'error' => session()->getFlashdata('error')]);
        }
        $owned = db_connect()->table('orders')->where(['user_id' => session()->get('user_id'), 'template_format_id' => $id, 'status' => 'paid'])->countAllResults() > 0;
        if ($owned) {
            return redirect()->to('/dashboard')->with('ok', lang('Site.alreadyOwned'));
        }

        return view('buy/confirm', ['tf' => $tf, 'amount' => money((int) $tf['price_minor'], Region::current()['currency']), 'coupon' => Coupons::availableFor((int) session()->get('user_id'))[0]['code'] ?? '', 'error' => session()->getFlashdata('error')]);
    }

    public function account(int $id)
    {
        $this->format($id);
        $err = Accounts::loginOrCreate((string) $this->request->getPost('email'), (string) $this->request->getPost('password'), trim((string) $this->request->getPost('name')));

        return redirect()->to('/buy/' . $id)->with('error', $err ? lang($err) : null);
    }

    public function start(int $id)
    {
        $tf     = $this->format($id);
        $region = Region::current();
        $res    = OrderService::create((int) session()->get('user_id'), null, $tf, (string) $this->request->getPost('coupon'), $region['currency']);
        if (isset($res['error'])) {
            return redirect()->to('/buy/' . $id)->with('error', lang($res['error']));
        }
        $out = OrderService::pay($res['order_id'], $res['amount'], $region['currency'], $region['payment_driver'], 'buy' . $res['order_id'], '/buy/order/' . $res['order_id'] . '/verify', '/buy/order/' . $res['order_id'] . '/simulate');
        if (isset($out['error'])) {
            return redirect()->to('/buy/' . $id)->with('error', lang($out['error']));
        }

        return isset($out['free']) ? redirect()->to('/dashboard')->with('ok', lang('Site.purchased')) : view($out['view'], $out['data']);
    }

    private function ownOrderPayment(int $orderId, string $ref): array
    {
        $pay = db_connect()->table('payments p')->select('p.*')->join('orders o', 'o.id = p.order_id')
            ->where('o.id', $orderId)->where('o.user_id', session()->get('user_id'))->where('p.provider_ref', $ref)->get()->getRowArray();
        if (! $pay) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $pay;
    }

    public function verify(int $orderId)
    {
        $oid = (string) $this->request->getPost('razorpay_order_id');
        $pid = (string) $this->request->getPost('razorpay_payment_id');
        $pay = $this->ownOrderPayment($orderId, $oid);
        if (! Payments::for(Region::current()['payment_driver'])->verifyPayment($oid, $pid, (string) $this->request->getPost('razorpay_signature'))) {
            return redirect()->to('/dashboard')->with('error', lang('Site.paymentFailed'));
        }
        InviteService::markPaid($orderId, $oid, $pid);

        return redirect()->to('/dashboard')->with('ok', lang('Site.purchased'));
    }

    public function simulate(int $orderId)
    {
        if (ENVIRONMENT !== 'development') {
            throw PageNotFoundException::forPageNotFound();
        }
        $ref = (string) $this->request->getPost('ref');
        $this->ownOrderPayment($orderId, $ref);
        if (! str_starts_with($ref, 'sim_')) {
            throw PageNotFoundException::forPageNotFound();
        }
        InviteService::markPaid($orderId, $ref, 'sim_payment');

        return redirect()->to('/dashboard')->with('ok', lang('Site.purchased'));
    }
}
