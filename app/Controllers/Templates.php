<?php

namespace App\Controllers;

use App\Libraries\InviteService;
use App\Libraries\Region;

class Templates extends BaseController
{
    private function formatsQuery()
    {
        return db_connect()->table('templates t')
            ->select('t.*, e.slug AS event_slug, e.names AS event_names, tf.price_minor, tf.format')
            ->join('event_types e', 'e.id = t.event_type_id')
            ->join('template_formats tf', 'tf.template_id = t.id AND tf.is_active = 1 AND tf.region_id = ' . (int) Region::current()['id'])
            ->where('t.is_active', 1)->where('e.is_active', 1);
    }

    public function index()
    {
        $event = (string) $this->request->getGet('event');
        $q     = $this->formatsQuery()->orderBy('t.id');
        if ($event !== '') {
            $q->where('e.slug', $event);
        }

        return view('templates/index', [
            'region'     => Region::current(),
            'templates'  => $q->get()->getResultArray(),
            'eventTypes' => db_connect()->table('event_types')->where('is_active', 1)->orderBy('sort')->get()->getResultArray(),
            'event'      => $event,
        ]);
    }

    public function show(string $slug)
    {
        $t = $this->formatsQuery()->where('t.slug', $slug)->get()->getRowArray();
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

        return view('templates/show', ['t' => $t, 'region' => Region::current(), 'fields' => $fields, 'content' => $content]);
    }
}
