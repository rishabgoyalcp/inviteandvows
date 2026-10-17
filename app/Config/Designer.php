<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Designer extends BaseConfig
{
    /** Google Fonts a template designer may pick. 'auto' (the invite's own language font) is always allowed. */
    public array $fonts = [
        'Playfair Display', 'Cormorant Garamond', 'Great Vibes', 'Dancing Script', 'Poppins', 'Montserrat', 'Inter', 'Noto Serif',
        'Noto Sans', 'Noto Sans Devanagari', 'Noto Serif Devanagari', 'Noto Sans Bengali', 'Noto Sans Tamil', 'Noto Sans Telugu',
        'Noto Sans Gujarati', 'Noto Sans Kannada', 'Noto Sans Malayalam', 'Noto Sans Gurmukhi', 'Noto Sans Oriya',
        'Noto Nastaliq Urdu', 'Noto Sans Arabic',
    ];

    /** Fonts that exist only in regular weight on Google Fonts (asking for other weights returns an error). */
    public array $singleWeight = ['Great Vibes'];

    public int $maxFrames   = 12;
    public int $maxElements = 60;
    public int $maxCanvas   = 4000;
}
