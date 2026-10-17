<?php

namespace App\Controllers;

use App\Libraries\InviteService;
use App\Libraries\Region;
use CodeIgniter\Exceptions\PageNotFoundException;

class Builder extends BaseController
{
    /** Invite the visitor may edit: owner by account, or holder of the draft token in their session. */
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

    public function start(string $slug)
    {
        $db     = db_connect();
        $format = $this->request->getPost('format') === 'video' ? 'video' : 'website';
        $t      = $db->table('templates t')->select('t.id')
            ->join('template_formats tf', "tf.template_id = t.id AND tf.format = " . $db->escape($format) . " AND tf.is_active = 1 AND tf.region_id = " . (int) Region::current()['id'])
            ->where('t.slug', $slug)->where('t.is_active', 1)->get()->getRowArray();
        if (! $t) {
            throw PageNotFoundException::forPageNotFound();
        }
        $token = bin2hex(random_bytes(16));
        $now   = date('Y-m-d H:i:s');
        $db->table('invites')->insert([
            'user_id' => session()->get('user_id') ?: null, 'template_id' => $t['id'], 'region_id' => Region::current()['id'],
            'locale' => service('request')->getLocale(), 'content' => '{}', 'status' => 'draft', 'format' => $format,
            'edit_token' => $token, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $drafts   = (array) session()->get('drafts');
        $drafts[] = $token;
        session()->set('drafts', $drafts);

        return redirect()->to('/build/' . $token);
    }

    public function edit(string $token)
    {
        $inv = $this->owned($token);
        $tpl = db_connect()->table('templates')->where('id', $inv['template_id'])->get()->getRowArray();

        return view('builder/edit', [
            'inv'     => $inv,
            'tpl'     => $tpl,
            'fields'  => InviteService::fieldsFor((int) $inv['template_id']),
            'content' => json_decode($inv['content'] ?? '{}', true) ?: [],
        ]);
    }

    public function save(string $token)
    {
        $inv     = $this->owned($token);
        $fields  = InviteService::fieldsFor((int) $inv['template_id']);
        $content = InviteService::cleanContent($fields, (array) $this->request->getPost(), json_decode($inv['content'] ?? '{}', true) ?: []);
        db_connect()->table('invites')->where('id', $inv['id'])->update(['content' => json_encode($content, JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['ok' => true]);
    }

    /** Renders the template with unsaved form values; the page puts the HTML into the preview iframe. */
    public function preview(string $token)
    {
        $inv     = $this->owned($token);
        $fields  = InviteService::fieldsFor((int) $inv['template_id']);
        $content = InviteService::cleanContent($fields, (array) $this->request->getPost(), json_decode($inv['content'] ?? '{}', true) ?: []);
        $tpl     = db_connect()->table('templates')->where('id', $inv['template_id'])->get()->getRowArray();

        return $this->response->setBody('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="margin:0">'
            . view($tpl['view_file'], ['fields' => $fields, 'content' => $content]));
    }

    public function upload(string $token)
    {
        $inv = $this->owned($token);
        $key = (string) $this->request->getPost('field');
        $def = null;
        foreach (InviteService::fieldsFor((int) $inv['template_id']) as $f) {
            if ($f['field_key'] === $key && $f['field_type'] === 'image') {
                $def = $f;
            }
        }
        $file = $this->request->getFile('file');
        if (! $def || ! $file) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid field']);
        }
        $ok = $this->validate(['file' => 'uploaded[file]|is_image[file]|mime_in[file,image/jpeg,image/png,image/webp]|max_size[file,5120]']);
        if (! $ok || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => lang('Site.badImage')]);
        }
        $dir  = 'uploads/invites/' . (int) $inv['id'];
        $name = $file->getRandomName();
        $file->move(FCPATH . $dir, $name);

        $content       = json_decode($inv['content'] ?? '{}', true) ?: [];
        $content[$key] = $dir . '/' . $name;
        db_connect()->table('invites')->where('id', $inv['id'])->update(['content' => json_encode($content, JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['ok' => true, 'path' => $content[$key]]);
    }
}
