<?php

namespace App\Controllers;

use App\Libraries\Accounts;
use App\Libraries\InviteService;
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

        return view('buy/confirm', ['tf' => $tf, 'amount' => money((int) $tf['price_minor'], Region::current()['currency'])]);
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
        $driver = Payments::for($region['payment_driver']);
        $db     = db_connect();
        $now    = date('Y-m-d H:i:s');
        $uid    = (int) session()->get('user_id');

        $db->table('orders')->insert([
            'user_id' => $uid, 'invite_id' => null, 'template_format_id' => $tf['id'], 'currency' => $region['currency'],
            'amount_minor' => $tf['price_minor'], 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $orderId = (int) $db->insertID();
        $pay     = ['order_id' => $orderId, 'driver' => $driver->name(), 'status' => 'created', 'created_at' => $now, 'updated_at' => $now];
        $amount  = money((int) $tf['price_minor'], $region['currency']);

        if ($driver->name() === 'razorpay' && ! $driver->isConfigured()) {
            if (ENVIRONMENT !== 'development') {
                throw new \RuntimeException('Payment gateway is not configured.');
            }
            $ref = 'sim_' . bin2hex(random_bytes(6));
            $db->table('payments')->insert($pay + ['provider_ref' => $ref]);

            return view('checkout/simulate', ['action' => '/buy/order/' . $orderId . '/simulate', 'ref' => $ref, 'amount' => $amount]);
        }
        try {
            $data = $driver->createOrder((int) $tf['price_minor'], $region['currency'], 'buy' . $orderId);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
            $db->table('orders')->where('id', $orderId)->update(['status' => 'failed']);

            return redirect()->to('/buy/' . $id)->with('error', lang('Site.paymentUnavailable'));
        }
        $db->table('payments')->insert($pay + ['provider_ref' => $data['provider_ref']]);
        $user = $db->table('users')->where('id', $uid)->get()->getRowArray();

        return view('checkout/razorpay', ['action' => '/buy/order/' . $orderId . '/verify', 'data' => $data, 'user' => $user, 'amount' => $amount]);
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
