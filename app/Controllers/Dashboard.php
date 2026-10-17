<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Dashboard extends BaseController
{
    public function index()
    {
        $invites = db_connect()->table('invites i')
            ->select('i.*, t.names AS template_names, (SELECT COUNT(*) FROM rsvps r WHERE r.invite_id = i.id) AS rsvp_count')
            ->join('templates t', 't.id = i.template_id')->where('i.user_id', session()->get('user_id'))->orderBy('i.id', 'DESC')->get()->getResultArray();

        $purchases = db_connect()->table('orders o')->select('o.id, o.amount_minor, o.currency, o.created_at, tf.format, t.names AS template_names')
            ->join('template_formats tf', 'tf.id = o.template_format_id')->join('templates t', 't.id = tf.template_id')
            ->where('o.user_id', session()->get('user_id'))->where('o.status', 'paid')->whereIn('tf.format', ['canva_design', 'canva_pdf'])
            ->orderBy('o.id', 'DESC')->get()->getResultArray();

        return view('dashboard/index', ['invites' => $invites, 'purchases' => $purchases, 'ok' => session()->getFlashdata('ok')]);
    }

    /** Sends a paying customer to their Canva template. The link is never shown before payment. */
    public function openOrder(int $id)
    {
        $db = db_connect();
        $o  = $db->table('orders o')->select('o.id, tf.delivery_url')->join('template_formats tf', 'tf.id = o.template_format_id')
            ->where('o.id', $id)->where('o.user_id', session()->get('user_id'))->where('o.status', 'paid')
            ->whereIn('tf.format', ['canva_design', 'canva_pdf'])->get()->getRowArray();
        if (! $o || ! preg_match('#^https://(www\.canva\.com|canva\.link)/#', (string) $o['delivery_url'])) {
            throw PageNotFoundException::forPageNotFound();
        }
        $db->table('orders')->where('id', $id)->set('access_count', 'access_count + 1', false)->update();

        return redirect()->to($o['delivery_url']);
    }

    public function rsvps(int $id)
    {
        $db  = db_connect();
        $inv = $db->table('invites')->where('id', $id)->where('user_id', session()->get('user_id'))->get()->getRowArray();
        if (! $inv) {
            throw PageNotFoundException::forPageNotFound();
        }
        $rows = $db->table('rsvps')->where('invite_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();

        if ($this->request->getGet('csv')) {
            // A leading = + - @ would run as a formula when opened in Excel, so neutralise it.
            $safe = static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;
            $out  = fopen('php://temp', 'r+');
            fputcsv($out, ['Name', 'Contact', 'Status', 'Guests', 'Message', 'Received']);
            foreach ($rows as $r) {
                fputcsv($out, [$safe($r['guest_name']), $safe($r['contact']), $r['status'], $r['guests'], $safe($r['message']), $r['created_at']]);
            }
            rewind($out);

            return $this->response->download('rsvps-' . $id . '.csv', stream_get_contents($out));
        }

        return view('dashboard/rsvps', ['inv' => $inv, 'rows' => $rows]);
    }
}
