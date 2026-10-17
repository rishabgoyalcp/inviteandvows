<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Placeholder fields and one placeholder template per event type, so the flow can be tried end to end. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        // [key, type, label, required]
        $common = [['venue_name', 'text', 'Venue name', 0], ['venue_address', 'textarea', 'Venue address', 0], ['map_url', 'url', 'Google Maps link', 0]];
        $sets = [
            'wedding' => array_merge([
                ['partner1_name', 'text', 'Bride / Partner 1 name', 1], ['partner2_name', 'text', 'Groom / Partner 2 name', 1],
                ['event_date', 'date', 'Wedding date', 1], ['event_time', 'time', 'Time', 0],
            ], $common, [['story', 'textarea', 'Our story', 0], ['cover_photo', 'image', 'Cover photo', 0]]),
            'birthday' => array_merge([
                ['person_name', 'text', 'Name', 1], ['age', 'text', 'Turning age', 0],
                ['event_date', 'date', 'Party date', 1], ['event_time', 'time', 'Time', 0],
            ], $common, [['message', 'textarea', 'Message to guests', 0], ['cover_photo', 'image', 'Photo', 0]]),
            'baby-shower' => array_merge([
                ['parents_names', 'text', 'Parents names', 1],
                ['event_date', 'date', 'Date', 1], ['event_time', 'time', 'Time', 0],
            ], $common, [['message', 'textarea', 'Message to guests', 0], ['cover_photo', 'image', 'Photo', 0]]),
        ];

        $prices = ['in' => 149900, 'com' => 1900];
        $regions = $this->db->table('regions')->get()->getResultArray();

        foreach ($sets as $slug => $fields) {
            $et = $this->db->table('event_types')->where('slug', $slug)->get()->getRowArray();
            if (! $et) {
                continue;
            }
            foreach ($fields as $i => [$key, $type, $label, $req]) {
                if ($this->db->table('field_definitions')->where(['event_type_id' => $et['id'], 'field_key' => $key])->countAllResults() === 0) {
                    $this->db->table('field_definitions')->insert([
                        'event_type_id' => $et['id'], 'field_key' => $key, 'field_type' => $type,
                        'labels' => json_encode(['en' => $label], JSON_UNESCAPED_UNICODE), 'is_required' => $req, 'sort' => $i, 'created_at' => $now,
                    ]);
                }
            }
            $tslug = 'classic-' . $slug;
            if ($this->db->table('templates')->where('slug', $tslug)->countAllResults() === 0) {
                $this->db->table('templates')->insert([
                    'event_type_id' => $et['id'], 'slug' => $tslug, 'names' => json_encode(['en' => 'Classic ' . ucwords(str_replace('-', ' ', $slug))]),
                    'view_file' => 'templates/classic', 'is_active' => 1, 'created_at' => $now,
                ]);
            }
            $tid = $this->db->table('templates')->where('slug', $tslug)->get()->getRow()->id;
            foreach ($regions as $r) {
                $where = ['template_id' => $tid, 'region_id' => $r['id'], 'format' => 'website'];
                if ($this->db->table('template_formats')->where($where)->countAllResults() === 0) {
                    $this->db->table('template_formats')->insert($where + ['price_minor' => $prices[$r['code']] ?? 1900, 'is_active' => 1, 'created_at' => $now]);
                }
            }
        }
    }
}
