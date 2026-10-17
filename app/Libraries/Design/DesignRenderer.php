<?php

namespace App\Libraries\Design;

/**
 * Renders a template design (frames of positioned text, image and shape elements) to HTML.
 * Positions are percentages and sizes use container-width units, so a frame looks the same at any width:
 * in a phone browser, and at 1080px wide when Chromium screenshots it for a video.
 */
class DesignRenderer
{
    /** Frames stacked top to bottom, for the website invite. Callers add the font <link>s with fontLinks(). */
    public static function websiteHtml(array $design, array $fields, array $content, array $lang): string
    {
        $out = '<div class="dz-wrap" style="max-width:520px;margin:0 auto">' . self::css();
        foreach ($design['frames'] as $frame) {
            $out .= self::frame($design, $frame, $fields, $content, $lang, static fn (string $p) => base_url($p));
        }

        return $out . '</div>';
    }

    /** A single frame as a complete page, for the video renderer. */
    public static function pageHtml(array $design, array $frame, array $fields, array $content, array $lang, callable $img, bool $withFonts = true): string
    {
        return '<!doctype html><html><head><meta charset="utf-8">' . ($withFonts ? self::fontLinks($design, $lang) : '') . self::css()
            . '<style>html,body{margin:0;background:#000}.dz-frame{width:100vw}</style></head><body>'
            . self::frame($design, $frame, $fields, $content, $lang, $img) . '</body></html>';
    }

    public static function css(): string
    {
        return '<style>.dz-frame{position:relative;width:100%;overflow:hidden;container-type:inline-size}'
            . '.dz-el{position:absolute;box-sizing:border-box}.dz-text{display:flex;align-items:center;white-space:pre-wrap;overflow-wrap:anywhere}'
            . '.dz-img{object-fit:cover;display:block}</style>';
    }

    /** <link> tags for every Google font the design (and the invite's language) needs. */
    public static function fontLinks(array $design, array $lang): string
    {
        $cfg   = config(\Config\Designer::class);
        $names = [$lang['font_family'] ?? 'Noto Sans'];
        foreach ($design['frames'] as $f) {
            foreach ($f['elements'] as $e) {
                if ($e['type'] === 'text' && $e['font'] !== 'auto') {
                    $names[] = $e['font'];
                }
            }
        }
        $q = [];
        foreach (array_unique($names) as $n) {
            $q[] = 'family=' . str_replace(' ', '+', $n) . (in_array($n, $cfg->singleWeight, true) ? '' : ':wght@300;400;600;700');
        }

        return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . implode('&', $q) . '&display=swap">';
    }

    private static function frame(array $design, array $frame, array $fields, array $content, array $lang, callable $img): string
    {
        $W  = max(1, (int) $design['canvas']['w']);
        $H  = max(1, (int) $design['canvas']['h']);
        $bg = $frame['bg'];
        $bgCss = match ($bg['type']) {
            'gradient' => "linear-gradient({$bg['angle']}deg,{$bg['color']},{$bg['color2']})",
            'image'    => preg_match('#^uploads/(invites|templates)/\d+/[A-Za-z0-9_.-]+$#', $bg['path']) ? "{$bg['color']} url('" . htmlspecialchars($img($bg['path']), ENT_QUOTES) . "') center/cover no-repeat" : $bg['color'],
            default    => $bg['color'],
        };
        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f['field_key']] = $f;
        }

        $html = '<div class="dz-frame" style="aspect-ratio:' . $W . '/' . $H . ';background:' . $bgCss . '">';
        foreach ($frame['elements'] as $e) {
            $box = sprintf('left:%.4f%%;top:%.4f%%;width:%.4f%%;height:%.4f%%;opacity:%s;%s', $e['x'] / $W * 100, $e['y'] / $H * 100, $e['w'] / $W * 100, $e['h'] / $H * 100, $e['opacity'], $e['rot'] ? 'transform:rotate(' . $e['rot'] . 'deg);' : '');
            $cq  = static fn (float $px): string => round($px / $W * 100, 4) . 'cqw';

            if ($e['type'] === 'text') {
                $text = self::textValue($e, $byKey, $content);
                if ($text === '') {
                    continue;
                }
                $family = $e['font'] === 'auto' ? ($lang['font_family'] ?? 'Noto Sans') : $e['font'];
                $just   = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$e['align']];
                $html  .= '<div class="dz-el dz-text" style="' . $box . 'justify-content:' . $just . ';text-align:' . $e['align']
                    . ';font-family:\'' . htmlspecialchars($family, ENT_QUOTES) . '\',\'Noto Sans\',sans-serif;font-size:' . $cq($e['size']) . ';font-weight:' . $e['weight']
                    . ';color:' . $e['color'] . ';line-height:' . $e['lh'] . ';letter-spacing:' . $cq($e['ls']) . ($e['upper'] ? ';text-transform:uppercase' : '') . '"><span>'
                    . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span></div>';
            } elseif ($e['type'] === 'image') {
                $path = $e['src'] === 'field' ? (string) ($content[$e['field']] ?? '') : $e['path'];
                if (! preg_match('#^uploads/(invites|templates)/\d+/[A-Za-z0-9_.-]+$#', $path)) {
                    continue;
                }
                $html .= '<img class="dz-el dz-img" alt="" src="' . htmlspecialchars($img($path), ENT_QUOTES) . '" style="' . $box . 'object-fit:' . $e['fit'] . ';border-radius:' . $cq($e['radius']) . '">';
            } else {
                $css = 'background:' . $e['fill'] . ';border-radius:' . ($e['shape'] === 'ellipse' ? '50%' : $cq($e['radius']))
                    . ($e['strokeW'] > 0 ? ';border:' . $cq($e['strokeW']) . ' solid ' . $e['stroke'] : '');
                $html .= '<div class="dz-el" style="' . $box . $css . '"></div>';
            }
        }

        return $html . '</div>';
    }

    public static function textValue(array $e, array $byKey, array $content): string
    {
        if ($e['src'] !== 'field') {
            return $e['value'];
        }
        $v = trim((string) ($content[$e['field']] ?? ''));
        if ($v === '') {
            return '';
        }
        $type = $byKey[$e['field']]['field_type'] ?? 'text';
        if ($type === 'date' && ($ts = strtotime($v))) {
            return date('j F Y', $ts);
        }

        return $v;
    }
}
