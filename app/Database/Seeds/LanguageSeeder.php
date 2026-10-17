<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Fonts per language and translated names for the starter event types. Safe to re-run; never overwrites names an admin edited. */
class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $fonts = [
            'en' => 'Noto Sans', 'es' => 'Noto Sans', 'fr' => 'Noto Sans', 'de' => 'Noto Sans', 'pt' => 'Noto Sans',
            'hi' => 'Noto Sans Devanagari', 'mr' => 'Noto Sans Devanagari', 'bn' => 'Noto Sans Bengali', 'as' => 'Noto Sans Bengali',
            'te' => 'Noto Sans Telugu', 'ta' => 'Noto Sans Tamil', 'gu' => 'Noto Sans Gujarati', 'kn' => 'Noto Sans Kannada',
            'ml' => 'Noto Sans Malayalam', 'pa' => 'Noto Sans Gurmukhi', 'or' => 'Noto Sans Oriya',
            'ur' => 'Noto Nastaliq Urdu', 'ar' => 'Noto Sans Arabic',
        ];
        foreach ($fonts as $code => $font) {
            $this->db->table('languages')->where('code', $code)->update(['font_family' => $font]);
        }

        $names = [
            'wedding' => ['bn' => 'বিয়ে', 'mr' => 'लग्न', 'te' => 'పెళ్లి', 'ta' => 'திருமணம்', 'gu' => 'લગ્ન', 'kn' => 'ಮದುವೆ', 'ml' => 'വിവാഹം', 'pa' => 'ਵਿਆਹ', 'or' => 'ବିବାହ', 'ur' => 'شادی', 'as' => 'বিবাহ', 'es' => 'Boda', 'fr' => 'Mariage', 'de' => 'Hochzeit', 'pt' => 'Casamento', 'ar' => 'زفاف'],
            'birthday' => ['bn' => 'জন্মদিন', 'mr' => 'वाढदिवस', 'te' => 'పుట్టినరోజు', 'ta' => 'பிறந்தநாள்', 'gu' => 'જન્મદિવસ', 'kn' => 'ಹುಟ್ಟುಹಬ್ಬ', 'ml' => 'ജന്മദിനം', 'pa' => 'ਜਨਮ ਦਿਨ', 'or' => 'ଜନ୍ମଦିନ', 'ur' => 'سالگرہ', 'as' => 'জন্মদিন', 'es' => 'Cumpleaños', 'fr' => 'Anniversaire', 'de' => 'Geburtstag', 'pt' => 'Aniversário', 'ar' => 'عيد ميلاد'],
            'baby-shower' => ['bn' => 'বেবি শাওয়ার', 'mr' => 'डोहाळे जेवण', 'te' => 'శ్రీమంతం', 'ta' => 'வளைகாப்பு', 'gu' => 'સીમંત', 'kn' => 'ಸೀಮಂತ', 'ml' => 'ബേബി ഷവർ', 'pa' => 'ਬੇਬੀ ਸ਼ਾਵਰ', 'or' => 'ସାଧଭକ୍ଷଣ', 'ur' => 'بیبی شاور', 'as' => 'বেবী শ্বাৱাৰ', 'es' => 'Baby shower', 'fr' => 'Baby shower', 'de' => 'Babyparty', 'pt' => 'Chá de bebê', 'ar' => 'حفل استقبال المولود'],
        ];
        foreach ($names as $slug => $add) {
            $row = $this->db->table('event_types')->where('slug', $slug)->get()->getRowArray();
            if (! $row) {
                continue;
            }
            $have = json_decode($row['names'] ?? '{}', true) ?: [];
            $this->db->table('event_types')->where('id', $row['id'])->update(['names' => json_encode($have + $add, JSON_UNESCAPED_UNICODE)]);
        }
    }
}
