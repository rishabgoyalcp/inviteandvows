<?php

namespace App\Controllers;

use App\Libraries\InviteService;
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
        $tf = db_connect()->table('template_formats')->where(['template_id' => $inv['template_id'], 'region_id' => Region::current()['id'], 'format' => 'website', 'is_active' => 1])->get()->getRowArray();
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
        $driver = Payments::for($region['payment_driver']);
        $db     = db_connect();
        $now    = date('Y-m-d H:i:s');

        $db->table('orders')->insert([
            'user_id' => session()->get('user_id'), 'invite_id' => $inv['id'], 'template_format_id' => $tf['id'],
            'currency' => $region['currency'], 'amount_minor' => $tf['price_minor'], 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $orderId = (int) $db->insertID();
        $pay     = ['order_id' => $orderId, 'driver' => $driver->name(), 'status' => 'created', 'created_at' => $now, 'updated_at' => $now];

        if ($driver->name() === 'razorpay' && ! $driver->isConfigured()) {
            // Razorpay keys missing: only allow a fake payment on a developer machine.
            if (ENVIRONMENT !== 'development') {
                throw new \RuntimeException('Payment gateway is not configured.');
            }
            $ref = 'sim_' . bin2hex(random_bytes(6));
            $db->table('payments')->insert($pay + ['provider_ref' => $ref]);

            return view('checkout/simulate', ['action' => '/checkout/' . $token . '/simulate', 'ref' => $ref, 'amount' => money((int) $tf['price_minor'], $region['currency'])]);
        }

        try {
            $data = $driver->createOrder((int) $tf['price_minor'], $region['currency'], 'inv' . $inv['id'] . '-' . $orderId);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
            $db->table('orders')->where('id', $orderId)->update(['status' => 'failed']);

            return redirect()->to('/build/' . $token)->with('error', lang('Site.paymentUnavailable'));
        }
        $db->table('payments')->insert($pay + ['provider_ref' => $data['provider_ref']]);
        $user = $db->table('users')->where('id', session()->get('user_id'))->get()->getRowArray();

        return view('checkout/razorpay', ['action' => '/checkout/' . $token . '/verify', 'data' => $data, 'user' => $user, 'amount' => money((int) $tf['price_minor'], $region['currency'])]);
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
