<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Admin screens for event types, their fields, templates and per-region prices. All content is data. */
class Catalog extends BaseController
{
    private const FIELD_TYPES = ['text', 'textarea', 'date', 'time', 'url', 'image'];

    private function langs(): array
    {
        return db_connect()->table('languages')->where('is_active', 1)->orderBy('id')->get()->getResultArray();
    }

    /** Collect names[xx] inputs into JSON, dropping empty entries. */
    private function namesJson(string $key = 'names'): string
    {
        $out = [];
        foreach ((array) $this->request->getPost($key) as $code => $v) {
            if (is_string($v) && trim($v) !== '' && preg_match('/^[a-z]{2,3}$/', (string) $code)) {
                $out[$code] = mb_substr(trim($v), 0, 120);
            }
        }

        return json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    private function slug(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
    }

    // ---- event types ----
    public function eventTypes()
    {
        return view('admin/event_types', ['rows' => db_connect()->table('event_types')->orderBy('sort')->get()->getResultArray(), 'langs' => $this->langs()]);
    }

    public function saveEventType()
    {
        $db   = db_connect();
        $id   = (int) $this->request->getPost('id');
        $slug = $this->slug((string) $this->request->getPost('slug'));
        $data = ['names' => $this->namesJson(), 'sort' => (int) $this->request->getPost('sort'), 'is_active' => $this->request->getPost('is_active') ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) {
            $db->table('event_types')->where('id', $id)->update($data);
        } elseif ($slug !== '' && $db->table('event_types')->where('slug', $slug)->countAllResults() === 0) {
            $db->table('event_types')->insert($data + ['slug' => $slug, 'created_at' => $data['updated_at']]);
        }

        return redirect()->to('/admin/event-types');
    }

    // ---- fields ----
    public function fields(int $eventTypeId)
    {
        $db = db_connect();
        $et = $db->table('event_types')->where('id', $eventTypeId)->get()->getRowArray();
        if (! $et) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('admin/fields', ['et' => $et, 'rows' => $db->table('field_definitions')->where('event_type_id', $eventTypeId)->orderBy('sort')->get()->getResultArray(), 'langs' => $this->langs(), 'types' => self::FIELD_TYPES]);
    }

    public function saveField(int $eventTypeId)
    {
        $db   = db_connect();
        $id   = (int) $this->request->getPost('id');
        $key  = preg_replace('/[^a-z0-9_]+/', '_', strtolower((string) $this->request->getPost('field_key')));
        $type = (string) $this->request->getPost('field_type');
        if (! in_array($type, self::FIELD_TYPES, true)) {
            return redirect()->back();
        }
        $data = ['field_type' => $type, 'labels' => $this->namesJson('labels'), 'is_required' => $this->request->getPost('is_required') ? 1 : 0, 'sort' => (int) $this->request->getPost('sort'), 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) {
            $db->table('field_definitions')->where(['id' => $id, 'event_type_id' => $eventTypeId])->update($data);
        } elseif ($key !== '' && $db->table('field_definitions')->where(['event_type_id' => $eventTypeId, 'field_key' => $key])->countAllResults() === 0) {
            $db->table('field_definitions')->insert($data + ['event_type_id' => $eventTypeId, 'field_key' => $key, 'created_at' => $data['updated_at']]);
        }

        return redirect()->to('/admin/event-types/' . $eventTypeId . '/fields');
    }

    public function deleteField(int $eventTypeId, int $id)
    {
        db_connect()->table('field_definitions')->where(['id' => $id, 'event_type_id' => $eventTypeId])->delete();

        return redirect()->to('/admin/event-types/' . $eventTypeId . '/fields');
    }

    // ---- templates and prices ----
    public function templates()
    {
        $db = db_connect();

        return view('admin/templates', [
            'rows'       => $db->table('templates t')->select('t.*, e.slug AS event_slug')->join('event_types e', 'e.id = t.event_type_id')->orderBy('t.id', 'DESC')->get()->getResultArray(),
            'eventTypes' => $db->table('event_types')->orderBy('sort')->get()->getResultArray(),
            'langs'      => $this->langs(),
            'regions'    => $db->table('regions')->get()->getResultArray(),
            'prices'     => $db->table('template_formats')->where('format', 'website')->get()->getResultArray(),
            'views'      => $this->templateViews(),
        ]);
    }

    /** Template view files available on disk (app/Views/templates/*.php, except the admin/gallery pages). */
    private function templateViews(): array
    {
        $out = [];
        foreach (glob(APPPATH . 'Views/templates/*.php') as $f) {
            $n = basename($f, '.php');
            if (! in_array($n, ['index', 'show'], true)) {
                $out[] = 'templates/' . $n;
            }
        }

        return $out;
    }

    public function saveTemplate()
    {
        $db   = db_connect();
        $id   = (int) $this->request->getPost('id');
        $now  = date('Y-m-d H:i:s');
        $view = (string) $this->request->getPost('view_file');
        if (! in_array($view, $this->templateViews(), true)) {
            return redirect()->back();
        }
        $data = ['names' => $this->namesJson(), 'view_file' => $view, 'is_active' => $this->request->getPost('is_active') ? 1 : 0, 'updated_at' => $now];
        if ($id) {
            $db->table('templates')->where('id', $id)->update($data);
        } else {
            $slug = $this->slug((string) $this->request->getPost('slug'));
            $et   = (int) $this->request->getPost('event_type_id');
            if ($slug === '' || ! $et || $db->table('templates')->where('slug', $slug)->countAllResults() > 0) {
                return redirect()->back();
            }
            $db->table('templates')->insert($data + ['slug' => $slug, 'event_type_id' => $et, 'created_at' => $now]);
            $id = (int) $db->insertID();
        }
        // Website price per region, entered in major units (rupees / dollars).
        foreach ((array) $this->request->getPost('price') as $regionId => $amount) {
            if (! is_numeric($amount)) {
                continue;
            }
            $where = ['template_id' => $id, 'region_id' => (int) $regionId, 'format' => 'website'];
            $minor = (int) round(((float) $amount) * 100);
            if ($db->table('template_formats')->where($where)->countAllResults() > 0) {
                $db->table('template_formats')->where($where)->update(['price_minor' => $minor, 'updated_at' => $now]);
            } else {
                $db->table('template_formats')->insert($where + ['price_minor' => $minor, 'is_active' => 1, 'created_at' => $now]);
            }
        }

        return redirect()->to('/admin/templates');
    }
}
