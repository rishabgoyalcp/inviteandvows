<?php

namespace App\Controllers;

use App\Libraries\InviteService;
use CodeIgniter\Exceptions\PageNotFoundException;

class Invite extends BaseController
{
    private function published(string $code): array
    {
        $inv = db_connect()->table('invites i')->select('i.*, t.view_file, t.names AS template_names')->join('templates t', 't.id = i.template_id')
            ->where('i.short_code', $code)->where('i.status', 'published')->get()->getRowArray();
        if (! $inv || ($inv['expires_at'] && strtotime($inv['expires_at']) < time())) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $inv;
    }

    public function show(string $code)
    {
        $inv = $this->published($code);
        // Guests see the invite in the language it was created in.
        service('request')->setLocale($inv['locale']);
        $fields  = InviteService::fieldsFor((int) $inv['template_id']);
        $content = json_decode($inv['content'] ?? '{}', true) ?: [];
        $title   = trim(implode(' & ', array_filter([$content['partner1_name'] ?? '', $content['partner2_name'] ?? ''])))
            ?: ($content['person_name'] ?? $content['parents_names'] ?? i18n($inv['template_names'], 'Invitation'));
        $image = ! empty($content['cover_photo']) ? base_url($content['cover_photo']) : '';

        return view('invite/show', [
            'inv' => $inv, 'fields' => $fields, 'content' => $content, 'title' => $title, 'image' => $image,
            'url' => invite_url($code), 'sent' => (bool) session()->getFlashdata('rsvp_ok'),
        ]);
    }

    public function rsvp(string $code)
    {
        $inv  = $this->published($code);
        $back = redirect()->to('/i/' . $code);
        if ((string) $this->request->getPost('website') !== '') { // honeypot
            return $back;
        }
        if (! service('throttler')->check('rsvp-' . md5($this->request->getIPAddress()), 5, MINUTE)) {
            return $back->with('error', lang('Site.tooMany'));
        }
        $name   = trim((string) $this->request->getPost('guest_name'));
        $status = (string) $this->request->getPost('status');
        if ($name === '' || ! in_array($status, ['yes', 'no', 'maybe'], true)) {
            return $back->with('error', lang('Site.rsvpInvalid'));
        }
        $now = date('Y-m-d H:i:s');
        db_connect()->table('rsvps')->insert([
            'invite_id' => $inv['id'], 'guest_name' => mb_substr($name, 0, 120), 'contact' => mb_substr(trim((string) $this->request->getPost('contact')), 0, 120),
            'status' => $status, 'guests' => max(1, min(20, (int) $this->request->getPost('guests'))),
            'message' => mb_substr(trim((string) $this->request->getPost('message')), 0, 1000), 'created_at' => $now, 'updated_at' => $now,
        ]);

        return $back->with('rsvp_ok', true);
    }
}
