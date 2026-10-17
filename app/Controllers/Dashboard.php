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

        return view('dashboard/index', ['invites' => $invites, 'ok' => session()->getFlashdata('ok')]);
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
