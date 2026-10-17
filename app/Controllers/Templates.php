<?php

namespace App\Controllers;

use App\Libraries\InviteService;
use App\Libraries\Region;

class Templates extends BaseController
{
    /** Active templates that have at least one active format in this region. */
    private function templatesQuery()
    {
        $rid = (int) Region::current()['id'];

        return db_connect()->table('templates t')
            ->select('t.*, e.slug AS event_slug, e.names AS event_names')
            ->join('event_types e', 'e.id = t.event_type_id')
            ->where('t.is_active', 1)->where('e.is_active', 1)
            ->where("EXISTS (SELECT 1 FROM template_formats f WHERE f.template_id = t.id AND f.region_id = {$rid} AND f.is_active = 1)", null, false);
    }

    /** @return array<int, array<string, array>> template_id => format => row */
    private function formatsFor(array $templateIds): array
    {
        if (! $templateIds) {
            return [];
        }
        $rows = db_connect()->table('template_formats')->whereIn('template_id', $templateIds)
            ->where('region_id', Region::current()['id'])->where('is_active', 1)->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['template_id']][$r['format']] = $r;
        }

        return $out;
    }

    public function index()
    {
        $event = (string) $this->request->getGet('event');
        $q     = $this->templatesQuery()->orderBy('t.id');
        if ($event !== '') {
            $q->where('e.slug', $event);
        }
        $templates = $q->get()->getResultArray();

        return view('templates/index', [
            'region'     => Region::current(),
            'templates'  => $templates,
            'formats'    => $this->formatsFor(array_column($templates, 'id')),
            'eventTypes' => db_connect()->table('event_types')->where('is_active', 1)->orderBy('sort')->get()->getResultArray(),
            'event'      => $event,
        ]);
    }

    public function show(string $slug)
    {
        $t = $this->templatesQuery()->where('t.slug', $slug)->get()->getRowArray();
        if (! $t) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $fields  = InviteService::fieldsFor((int) $t['id']);
        $content = [];
        foreach ($fields as $f) {
            $content[$f['field_key']] = match ($f['field_type']) {
                'date'     => date('Y-m-d', strtotime('+60 days')),
                'time'     => '18:30',
                'url', 'image' => '',
                'textarea' => 'Your message appears here.',
                default    => i18n($f['labels'], $f['field_key']),
            };
        }

        return view('templates/show', ['t' => $t, 'region' => Region::current(), 'fields' => $fields, 'content' => $content, 'formats' => $this->formatsFor([(int) $t['id']])[$t['id']] ?? []]);
    }
}
