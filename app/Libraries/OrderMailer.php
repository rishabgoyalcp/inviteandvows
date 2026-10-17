<?php

namespace App\Libraries;

/** Order emails. A failed send is logged and never blocks the purchase. */
class OrderMailer
{
    public static function paid(int $orderId): void
    {
        try {
            $db = db_connect();
            $o  = $db->table('orders o')
                ->select('o.*, u.email, u.name AS user_name, tf.format, t.names AS template_names, i.short_code')
                ->join('users u', 'u.id = o.user_id')->join('template_formats tf', 'tf.id = o.template_format_id')
                ->join('templates t', 't.id = tf.template_id')->join('invites i', 'i.id = o.invite_id', 'left')
                ->where('o.id', $orderId)->get()->getRowArray();
            if (! $o) {
                return;
            }
            $product = i18n($o['template_names'], 'Invitation');
            $amount  = money((int) $o['amount_minor'], $o['currency']);
            $lines   = ["Hi {$o['user_name']},", '', "Thank you for your purchase: {$product} ({$o['format']}), {$amount}.", ''];
            if ($o['format'] === 'website' && $o['short_code']) {
                $lines[] = 'Your invite is live: ' . invite_url($o['short_code']);
                $lines[] = 'Manage it and see RSVPs: ' . site_url('dashboard');
            } else {
                $lines[] = 'Open your editable design in your account: ' . site_url('dashboard');
            }
            $lines[] = '';
            $lines[] = 'Order #' . $o['id'] . ' · Invite & Vows';

            $email = service('email');
            $email->setTo($o['email']);
            $email->setSubject('Your Invite & Vows order #' . $o['id']);
            $email->setMessage(implode("\n", $lines));
            if (! $email->send(false)) {
                log_message('error', 'Order email failed for order ' . $orderId . ': ' . $email->printDebugger(['headers']));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Order email error: ' . $e->getMessage());
        }
    }
}
