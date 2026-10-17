<?php

namespace App\Controllers;

use App\Libraries\InviteService;
use App\Libraries\Payments\RazorpayDriver;

class Webhooks extends BaseController
{
    public function razorpay()
    {
        $body = (string) $this->request->getBody();
        $sig  = (string) $this->request->getHeaderLine('X-Razorpay-Signature');
        if (! (new RazorpayDriver())->verifyWebhook($body, $sig)) {
            return $this->response->setStatusCode(400)->setBody('bad signature');
        }
        $event = json_decode($body, true) ?: [];
        if (in_array($event['event'] ?? '', ['payment.captured', 'order.paid'], true)) {
            $p   = $event['payload']['payment']['entity'] ?? [];
            $ref = (string) ($p['order_id'] ?? '');
            $pay = db_connect()->table('payments')->where('provider_ref', $ref)->get()->getRowArray();
            if ($pay) {
                InviteService::markPaid((int) $pay['order_id'], $ref, (string) ($p['id'] ?? ''));
            }
        }

        return $this->response->setBody('ok');
    }
}
