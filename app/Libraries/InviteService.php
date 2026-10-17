<?php

namespace App\Libraries;

class InviteService
{
    /** Marks an order paid and publishes its invite. Safe to call twice (webhook + browser return). */
    public static function markPaid(int $orderId, string $providerRef, string $paymentId): void
    {
        $db = db_connect();
        $db->transStart();

        $newlyPaid = false;
        $order     = $db->table('orders')->where('id', $orderId)->get()->getRowArray();
        if ($order && $order['status'] !== 'paid') {
            $newlyPaid = true;
            $now = date('Y-m-d H:i:s');
            $db->table('orders')->where('id', $orderId)->update(['status' => 'paid', 'updated_at' => $now]);
            $db->table('payments')->where('order_id', $orderId)->where('provider_ref', $providerRef)
                ->update(['status' => 'captured', 'raw' => json_encode(['payment_id' => $paymentId]), 'updated_at' => $now]);

            $invite = $order['invite_id'] ? $db->table('invites')->where('id', $order['invite_id'])->get()->getRowArray() : null;
            if ($invite && empty($invite['short_code'])) {
                do {
                    $code = random_code(7);
                } while ($db->table('invites')->where('short_code', $code)->countAllResults() > 0);
                $db->table('invites')->where('id', $invite['id'])->update(['short_code' => $code, 'status' => 'published', 'updated_at' => $now]);
            }
            if ($invite && ($invite['format'] ?? '') === 'video') {
                \App\Libraries\Video\VideoQueue::enqueue((int) $invite['id']);
            }
        }
        $db->transComplete();

        if ($newlyPaid && $db->transStatus()) {
            OrderMailer::paid($orderId);
        }
    }

    /** Field definitions for a template's event type, in display order. */
    public static function fieldsFor(int $templateId): array
    {
        return db_connect()->table('field_definitions f')
            ->select('f.*')->join('templates t', 't.event_type_id = f.event_type_id')
            ->where('t.id', $templateId)->orderBy('f.sort')->get()->getResultArray();
    }

    /** Keep only known fields and trim values. Image fields store a path, never raw input. */
    public static function cleanContent(array $fields, array $input, array $existing = []): array
    {
        $out = [];
        foreach ($fields as $f) {
            $k = $f['field_key'];
            $v = $input[$k] ?? ($existing[$k] ?? '');
            $v = is_string($v) ? trim($v) : '';
            if ($f['field_type'] === 'image') {
                $v = $existing[$k] ?? ''; // changed only through the upload endpoint
            } elseif ($f['field_type'] === 'url' && $v !== '' && ! preg_match('#^https?://#i', $v)) {
                $v = '';
            } elseif ($f['field_type'] === 'date' && $v !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                $v = '';
            } elseif ($f['field_type'] === 'time' && $v !== '' && ! preg_match('/^\d{2}:\d{2}$/', $v)) {
                $v = '';
            }
            $out[$k] = mb_substr($v, 0, $f['field_type'] === 'textarea' ? 3000 : 500);
        }

        return $out;
    }
}
