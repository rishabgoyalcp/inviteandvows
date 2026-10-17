<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Design\DesignSanitizer;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Browser-based template designer: stores a design (frames of elements) in templates.config. */
class Designer extends BaseController
{
    private function template(int $id): array
    {
        $t = db_connect()->table('templates')->where('id', $id)->get()->getRowArray();
        if (! $t) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $t;
    }

    private function fieldsOf(array $t): array
    {
        return db_connect()->table('field_definitions')->where('event_type_id', $t['event_type_id'])->orderBy('sort')->get()->getResultArray();
    }

    public function edit(int $id)
    {
        $t      = $this->template($id);
        $fields = $this->fieldsOf($t);
        $cfg    = json_decode((string) $t['config'], true) ?: [];

        return view('admin/designer', [
            't'      => $t,
            'design' => $cfg['design'] ?? ['canvas' => ['w' => 1080, 'h' => 1920], 'frames' => []],
            'fields' => array_map(static fn ($f) => ['key' => $f['field_key'], 'type' => $f['field_type'], 'label' => i18n($f['labels'], $f['field_key'])], $fields),
            'fonts'  => config(\Config\Designer::class)->fonts,
        ]);
    }

    public function save(int $id)
    {
        $t      = $this->template($id);
        $keys   = array_column($this->fieldsOf($t), 'field_key');
        $design = DesignSanitizer::clean($this->request->getJSON(true), $id, $keys);
        $cfg    = json_decode((string) $t['config'], true) ?: [];
        $cfg['design'] = $design;

        $update = ['config' => json_encode($cfg, JSON_UNESCAPED_UNICODE), 'updated_at' => date('Y-m-d H:i:s')];
        if ($design['frames']) {
            $update['view_file'] = 'templates/designed';
        }
        db_connect()->table('templates')->where('id', $id)->update($update);

        return $this->response->setJSON(['ok' => true, 'design' => $design]);
    }

    public function upload(int $id)
    {
        $this->template($id);
        $file = $this->request->getFile('file');
        $ok   = $file && $this->validate(['file' => 'uploaded[file]|is_image[file]|mime_in[file,image/jpeg,image/png,image/webp]|max_size[file,5120]']);
        if (! $ok || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Upload a JPG, PNG or WebP image under 5 MB.']);
        }
        $dir  = 'uploads/templates/' . $id;
        $name = $file->getRandomName();
        $file->move(FCPATH . $dir, $name);

        return $this->response->setJSON(['ok' => true, 'path' => $dir . '/' . $name]);
    }
}
