<?php

namespace App\Libraries\Design;

/** Turns whatever the browser editor sends into a safe, normalised design. Nothing outside this whitelist is stored. */
class DesignSanitizer
{
    /**
     * @param list<string> $fieldKeys field keys of the template's event type (text/image bindings must exist)
     */
    public static function clean(mixed $in, int $templateId, array $fieldKeys): array
    {
        $cfg = config(\Config\Designer::class);
        $in  = is_array($in) ? $in : [];
        $w   = self::num($in['canvas']['w'] ?? 1080, 200, $cfg->maxCanvas, 1080);
        $h   = self::num($in['canvas']['h'] ?? 1920, 200, $cfg->maxCanvas, 1920);

        $frames = [];
        foreach (array_slice((array) ($in['frames'] ?? []), 0, $cfg->maxFrames) as $f) {
            if (! is_array($f)) {
                continue;
            }
            $bg = (array) ($f['bg'] ?? []);
            $frames[] = [
                'id'       => self::id($f['id'] ?? ''),
                'seconds'  => self::num($f['seconds'] ?? 3.5, 1, 15, 3.5),
                'bg'       => [
                    'type'   => in_array($bg['type'] ?? '', ['color', 'gradient', 'image'], true) ? $bg['type'] : 'color',
                    'color'  => self::color($bg['color'] ?? '#fffaf2', '#fffaf2'),
                    'color2' => self::color($bg['color2'] ?? '#f3e3d3', '#f3e3d3'),
                    'angle'  => self::num($bg['angle'] ?? 180, 0, 360, 180),
                    'path'   => self::path($bg['path'] ?? '', $templateId),
                ],
                'elements' => self::elements((array) ($f['elements'] ?? []), $templateId, $fieldKeys, $cfg),
            ];
        }

        return ['canvas' => ['w' => (int) $w, 'h' => (int) $h], 'frames' => $frames];
    }

    private static function elements(array $list, int $templateId, array $fieldKeys, $cfg): array
    {
        $out = [];
        foreach (array_slice($list, 0, $cfg->maxElements) as $e) {
            if (! is_array($e) || ! in_array($e['type'] ?? '', ['text', 'image', 'shape'], true)) {
                continue;
            }
            $el = [
                'id' => self::id($e['id'] ?? ''), 'type' => $e['type'],
                'x'  => self::num($e['x'] ?? 0, -$cfg->maxCanvas, $cfg->maxCanvas, 0), 'y' => self::num($e['y'] ?? 0, -$cfg->maxCanvas, $cfg->maxCanvas, 0),
                'w'  => self::num($e['w'] ?? 100, 1, $cfg->maxCanvas, 100), 'h' => self::num($e['h'] ?? 100, 1, $cfg->maxCanvas, 100),
                'rot' => self::num($e['rot'] ?? 0, -360, 360, 0), 'opacity' => self::num($e['opacity'] ?? 1, 0, 1, 1),
            ];
            if ($e['type'] === 'text') {
                $font = (string) ($e['font'] ?? 'auto');
                $el += [
                    'src'    => ($e['src'] ?? '') === 'field' ? 'field' : 'static',
                    'field'  => in_array($e['field'] ?? '', $fieldKeys, true) ? $e['field'] : '',
                    'value'  => mb_substr((string) ($e['value'] ?? ''), 0, 500),
                    'font'   => $font === 'auto' || in_array($font, $cfg->fonts, true) ? $font : 'auto',
                    'size'   => self::num($e['size'] ?? 60, 8, 600, 60), 'weight' => in_array((int) ($e['weight'] ?? 400), [300, 400, 600, 700], true) ? (int) $e['weight'] : 400,
                    'color'  => self::color($e['color'] ?? '#3b2a2a', '#3b2a2a'),
                    'align'  => in_array($e['align'] ?? '', ['left', 'center', 'right'], true) ? $e['align'] : 'center',
                    'lh'     => self::num($e['lh'] ?? 1.3, 0.8, 3, 1.3), 'ls' => self::num($e['ls'] ?? 0, -10, 40, 0),
                    'upper'  => ! empty($e['upper']),
                ];
                if ($el['src'] === 'field' && $el['field'] === '') {
                    $el['src'] = 'static';
                }
            } elseif ($e['type'] === 'image') {
                $el += [
                    'src'    => ($e['src'] ?? '') === 'field' ? 'field' : 'static',
                    'field'  => in_array($e['field'] ?? '', $fieldKeys, true) ? $e['field'] : '',
                    'path'   => self::path($e['path'] ?? '', $templateId),
                    'fit'    => ($e['fit'] ?? '') === 'contain' ? 'contain' : 'cover',
                    'radius' => self::num($e['radius'] ?? 0, 0, 2000, 0),
                ];
            } else {
                $el += [
                    'shape'  => in_array($e['shape'] ?? '', ['rect', 'ellipse'], true) ? $e['shape'] : 'rect',
                    'fill'   => self::color($e['fill'] ?? '#d9b99b', '#d9b99b'), 'stroke' => self::color($e['stroke'] ?? '#000000', '#000000'),
                    'strokeW' => self::num($e['strokeW'] ?? 0, 0, 100, 0), 'radius' => self::num($e['radius'] ?? 0, 0, 2000, 0),
                ];
            }
            $out[] = $el;
        }

        return $out;
    }

    private static function num(mixed $v, float $min, float $max, float $default): float|int
    {
        if (! is_numeric($v)) {
            return $default;
        }
        $n = max($min, min($max, (float) $v));

        return round($n, 2) == (int) $n ? (int) $n : round($n, 2);
    }

    private static function color(mixed $v, string $default): string
    {
        return is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $default;
    }

    private static function id(mixed $v): string
    {
        $v = preg_replace('/[^a-z0-9_]/i', '', (string) $v);

        return $v !== '' ? substr($v, 0, 20) : 'e' . bin2hex(random_bytes(4));
    }

    /** Static images must live in this template's own upload folder. */
    private static function path(mixed $v, int $templateId): string
    {
        return is_string($v) && preg_match('#^uploads/templates/' . $templateId . '/[A-Za-z0-9_.-]+$#', $v) ? $v : '';
    }
}
