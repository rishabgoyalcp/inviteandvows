<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Dashboard extends BaseController
{
    public function index()
    {
        $invites = db_connect()->table('invites i')
            ->select('i.*, t.names AS template_names, (SELECT COUNT(*) FROM rsvps r WHERE r.invite_id = i.id) AS rsvp_count, (SELECT v.status FROM video_jobs v WHERE v.invite_id = i.id ORDER BY v.id DESC LIMIT 1) AS video_status, (SELECT v.file_path FROM video_jobs v WHERE v.invite_id = i.id AND v.status = \'done\' ORDER BY v.id DESC LIMIT 1) AS video_file')
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

    /** The customer's own published video invite, or 404. */
    private function videoInvite(int $id): array
    {
        $inv = db_connect()->table('invites i')->select('i.*, t.config')->join('templates t', 't.id = i.template_id')
            ->where('i.id', $id)->where('i.user_id', session()->get('user_id'))->where('i.format', 'video')->where('i.status', 'published')->get()->getRowArray();
        if (! $inv) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    public function makeVideo(int $id)
    {
        $inv  = $this->videoInvite($id);
        $lang = db_connect()->table('languages')->where('code', $inv['locale'])->get()->getRowArray() ?: [];

        return view('dashboard/make_video', ['inv' => $inv, 'fontLinks' => \App\Libraries\Design\DesignRenderer::fontLinks(['frames' => []], $lang)]);
    }

    /** Resolved scenes (text, image URLs, fonts) that the in-browser renderer draws. */
    public function videoData(int $id)
    {
        $inv     = $this->videoInvite($id);
        $fields  = \App\Libraries\InviteService::fieldsFor((int) $inv['template_id']);
        $content = json_decode($inv['content'] ?? '{}', true) ?: [];
        $lang    = db_connect()->table('languages')->where('code', $inv['locale'])->get()->getRowArray() ?: [];
        $design  = \App\Libraries\Video\VideoScenes::design($inv, $fields, $content, (int) $inv['id']);
        if (! $design['frames']) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Nothing to render']);
        }
        $data = \App\Libraries\Video\VideoScenes::forBrowser($design, $fields, $content, $lang);
        $data['fontsCss'] = 'https://fonts.googleapis.com/css2?' . implode('&', array_map(static fn ($n) => 'family=' . str_replace(' ', '+', $n) . (in_array($n, config(\Config\Designer::class)->singleWeight, true) ? '' : ':wght@300;400;600;700'), $data['fonts'])) . '&display=swap';

        return $this->response->setJSON($data);
    }

    /** Receives the video the browser recorded. Only real MP4 or WebM files are kept. */
    public function uploadVideo(int $id)
    {
        $inv  = $this->videoInvite($id);
        $file = $this->request->getFile('video');
        if (! $file || ! $file->isValid() || $file->hasMoved() || $file->getSize() > 40 * 1024 * 1024 || $file->getSize() < 2000) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid video']);
        }
        $head = (string) file_get_contents($file->getTempName(), false, null, 0, 12);
        $ext  = str_starts_with($head, "\x1A\x45\xDF\xA3") ? 'webm' : (substr($head, 4, 4) === 'ftyp' ? 'mp4' : null); // magic bytes, not the client's claim
        if (! $ext) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Unsupported video format']);
        }
        $name = 'videos/' . bin2hex(random_bytes(12)) . '.' . $ext;
        $file->move(FCPATH . 'videos', basename($name));

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $db->table('video_jobs')->insert(['invite_id' => $inv['id'], 'status' => 'done', 'attempts' => 0, 'file_path' => $name, 'started_at' => $now, 'finished_at' => $now, 'created_at' => $now, 'updated_at' => $now]);

        return $this->response->setJSON(['ok' => true, 'file' => $name]);
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
