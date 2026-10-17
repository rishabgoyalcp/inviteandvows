<?php

namespace App\Libraries\Video;

use App\Libraries\Design\DesignRenderer;

/** Turns an invite into video scenes. Designed templates use their own frames; others get a plain automatic layout. */
class VideoScenes
{
    /** The design to render: the template's own, or an automatic one built from the invite's values. */
    public static function design(array $template, array $fields, array $content, int $inviteId): array
    {
        if ($d = design_of($template)) {
            return $d;
        }
        $W = 1080;
        $H = 1920;
        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f['field_key']] = $f;
        }
        $lines = [];
        foreach ($byKey as $key => $f) {
            $v = trim((string) ($content[$key] ?? ''));
            if ($v === '' || in_array($f['field_type'], ['image', 'url'], true)) {
                continue;
            }
            $lines[] = $f['field_type'] === 'date' && strtotime($v) ? date('j F Y', strtotime($v)) : (mb_strlen($v) > 140 ? mb_substr($v, 0, 137) . '…' : $v);
        }
        $cover = '';
        foreach ($fields as $f) {
            $p = (string) ($content[$f['field_key']] ?? '');
            if ($f['field_type'] === 'image' && preg_match('#^uploads/invites/' . $inviteId . '/[A-Za-z0-9_.-]+$#', $p)) {
                $cover = $p;
                break;
            }
        }

        $frames = [];
        foreach (array_slice(array_chunk($lines, 2), 0, 6) as $i => $chunk) {
            $dark = $i === 0 && $cover !== '';
            $els  = [];
            if ($dark) {
                $els[] = ['id' => 'ov', 'type' => 'shape', 'x' => 0, 'y' => 0, 'w' => $W, 'h' => $H, 'rot' => 0, 'opacity' => 0.45, 'shape' => 'rect', 'fill' => '#000000', 'stroke' => '#000000', 'strokeW' => 0, 'radius' => 0];
            }
            foreach ($chunk as $j => $text) {
                $els[] = ['id' => 't' . $j, 'type' => 'text', 'x' => 86, 'y' => 700 + $j * 280, 'w' => 908, 'h' => 260, 'rot' => 0, 'opacity' => 1, 'src' => 'static', 'field' => '', 'value' => $text,
                    'font' => 'auto', 'size' => $j === 0 ? 120 : 90, 'weight' => $j === 0 ? 600 : 400, 'color' => $dark ? '#ffffff' : '#3b2a2a', 'align' => 'center', 'lh' => 1.3, 'ls' => 0, 'upper' => false];
            }
            $frames[] = ['id' => 'f' . $i, 'seconds' => 3.5, 'bg' => ['type' => $dark ? 'image' : 'color', 'color' => '#fffaf2', 'color2' => '#f3e3d3', 'angle' => 180, 'path' => $dark ? $cover : ''], 'elements' => $els];
        }

        return ['canvas' => ['w' => $W, 'h' => $H], 'frames' => $frames];
    }

    /**
     * Everything the in-browser renderer needs, with text and image paths already resolved,
     * so the JavaScript only has to draw (it never sees field logic).
     */
    public static function forBrowser(array $design, array $fields, array $content, array $lang): array
    {
        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f['field_key']] = $f;
        }
        $scenes = [];
        $fonts  = [$lang['font_family'] ?? 'Noto Sans'];
        foreach ($design['frames'] as $frame) {
            $bg = $frame['bg'];
            $items = [];
            foreach ($frame['elements'] as $e) {
                $item = $e;
                if ($e['type'] === 'text') {
                    $item['text']   = DesignRenderer::textValue($e, $byKey, $content);
                    $item['family'] = $e['font'] === 'auto' ? ($lang['font_family'] ?? 'Noto Sans') : $e['font'];
                    $fonts[]        = $item['family'];
                    if ($item['text'] === '') {
                        continue;
                    }
                } elseif ($e['type'] === 'image') {
                    $path = $e['src'] === 'field' ? (string) ($content[$e['field']] ?? '') : $e['path'];
                    if (! preg_match('#^uploads/(invites|templates)/\d+/[A-Za-z0-9_.-]+$#', $path)) {
                        continue;
                    }
                    $item['url'] = base_url($path);
                }
                $items[] = $item;
            }
            $bgUrl = $bg['type'] === 'image' && preg_match('#^uploads/(invites|templates)/\d+/[A-Za-z0-9_.-]+$#', $bg['path']) ? base_url($bg['path']) : null;
            $scenes[] = ['seconds' => $frame['seconds'], 'bg' => $bg + ['url' => $bgUrl], 'items' => $items];
        }

        return ['canvas' => $design['canvas'], 'rtl' => ! empty($lang['is_rtl']), 'fonts' => array_values(array_unique($fonts)), 'scenes' => $scenes];
    }
}
