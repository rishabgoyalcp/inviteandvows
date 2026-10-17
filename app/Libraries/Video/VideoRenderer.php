<?php

namespace App\Libraries\Video;

use App\Libraries\InviteService;

/**
 * Renders a video invite: each scene is an HTML page screenshotted by headless Chromium
 * (so every script shapes correctly), then ffmpeg joins the scenes with cross-fades into an MP4.
 * Needs Chromium and ffmpeg on the machine that runs `php spark video:render` (a VPS, not shared hosting).
 */
class VideoRenderer
{
    private int $width;
    private int $height;
    private float $seconds = 3.5;
    private float $fade    = 0.6;

    public function __construct()
    {
        [$w, $h]      = array_map('intval', explode('x', (string) env('video.size', '1080x1920')) + [1 => 1920]);
        $this->width  = max(320, $w);
        $this->height = max(320, $h);
    }

    /** @return string path relative to public/, e.g. videos/ab12.mp4 */
    public function render(int $inviteId): string
    {
        $db  = db_connect();
        $inv = $db->table('invites i')->select('i.*, t.config AS template_config')->join('templates t', 't.id = i.template_id')->where('i.id', $inviteId)->get()->getRowArray();
        if (! $inv) {
            throw new \RuntimeException("Invite {$inviteId} not found");
        }
        $fields  = InviteService::fieldsFor((int) $inv['template_id']);
        $content = json_decode($inv['content'] ?? '{}', true) ?: [];
        $locale  = $inv['locale'] ?: 'en';
        $lang    = $db->table('languages')->where('code', $locale)->get()->getRowArray() ?: [];

        $work = sys_get_temp_dir() . '/vid_' . bin2hex(random_bytes(6));
        mkdir($work, 0700, true);
        try {
            $scenes = $this->scenes($fields, $content, json_decode($inv['template_config'] ?? '{}', true) ?: [], $locale);
            if (! $scenes) {
                throw new \RuntimeException('Invite has no content to render');
            }
            $bg = $this->coverImage($fields, $content, (int) $inv['id'], $work);
            foreach ($scenes as $i => $scene) {
                file_put_contents("{$work}/s{$i}.html", $this->sceneHtml($scene, $lang, $i === 0 ? $bg : null));
                $this->run([
                    env('video.chromiumBin', 'chromium'), '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run',
                    ...array_filter(explode(' ', (string) env('video.chromiumFlags', '--no-sandbox'))),
                    '--virtual-time-budget=5000', "--window-size={$this->width},{$this->height}",
                    "--screenshot={$work}/s{$i}.png", "file://{$work}/s{$i}.html",
                ], 90);
                if (! is_file("{$work}/s{$i}.png")) {
                    throw new \RuntimeException("Scene {$i} screenshot failed");
                }
            }
            $out = 'videos/' . bin2hex(random_bytes(12)) . '.mp4';
            $this->run($this->ffmpegCommand(count($scenes), $work, FCPATH . $out), 300);
            if (! is_file(FCPATH . $out) || filesize(FCPATH . $out) < 1000) {
                throw new \RuntimeException('ffmpeg produced no output');
            }

            return $out;
        } finally {
            foreach (glob("{$work}/*") ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($work);
        }
    }

    /**
     * Scenes come from the template's config {"video":{"scenes":[{"fields":["a","b"],"bg":"#123456"}]}}
     * or, when absent, are built automatically: two values per scene, in field order.
     */
    private function scenes(array $fields, array $content, array $config, string $locale): array
    {
        $byKey = [];
        foreach ($fields as $f) {
            $byKey[$f['field_key']] = $f;
        }
        $line = function (string $key) use ($byKey, $content): ?string {
            $f = $byKey[$key] ?? null;
            $v = trim((string) ($content[$key] ?? ''));
            if (! $f || $v === '' || in_array($f['field_type'], ['image', 'url'], true)) {
                return null;
            }
            if ($f['field_type'] === 'date') {
                return date('j F Y', strtotime($v));
            }

            return mb_strlen($v) > 140 ? mb_substr($v, 0, 137) . '…' : $v;
        };

        $scenes = [];
        foreach ($config['video']['scenes'] ?? [] as $s) {
            $lines = array_values(array_filter(array_map($line, (array) ($s['fields'] ?? []))));
            if ($lines) {
                $scenes[] = ['lines' => $lines, 'bg' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($s['bg'] ?? '')) ? $s['bg'] : null];
            }
        }
        if (! $scenes) {
            $all = array_values(array_filter(array_map($line, array_keys($byKey))));
            foreach (array_chunk($all, 2) as $chunk) {
                $scenes[] = ['lines' => $chunk, 'bg' => null];
            }
        }

        return array_slice($scenes, 0, 6);
    }

    /** Copies the cover photo into the work folder so Chromium reads it next to the page. Only the invite's own uploads are allowed. */
    private function coverImage(array $fields, array $content, int $inviteId, string $work): ?string
    {
        foreach ($fields as $f) {
            $p = (string) ($content[$f['field_key']] ?? '');
            if ($f['field_type'] === 'image' && preg_match('#^uploads/invites/' . $inviteId . '/[A-Za-z0-9_.-]+$#', $p) && is_file(FCPATH . $p)) {
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                copy(FCPATH . $p, "{$work}/cover.{$ext}");

                return "cover.{$ext}";
            }
        }

        return null;
    }

    private function sceneHtml(array $scene, array $lang, ?string $bgImage): string
    {
        $font  = $lang['font_family'] ?? 'Noto Sans';
        $rtl   = ! empty($lang['is_rtl']);
        $bg    = $scene['bg'] ?? '#fffaf2';
        $color = $bgImage ? '#ffffff' : '#3b2a2a';
        $css   = $bgImage ? "background:linear-gradient(rgba(0,0,0,.45),rgba(0,0,0,.45)),url('" . $bgImage . "') center/cover" : "background:{$bg}";
        $link  = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font) . ':wght@400;600&display=swap">';
        $html  = '<!doctype html><html dir="' . ($rtl ? 'rtl' : 'ltr') . '"><head><meta charset="utf-8">' . $link
            . '<style>html,body{margin:0;width:100%;height:100%}body{' . $css . ';color:' . $color . ';font-family:"' . htmlspecialchars($font, ENT_QUOTES) . '",sans-serif;'
            . 'display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:0 8%;box-sizing:border-box}'
            . 'p{font-size:' . (int) ($this->width / 11) . 'px;line-height:1.35;margin:.4em 0;white-space:pre-line}p:first-child{font-size:' . (int) ($this->width / 8) . 'px;font-weight:600}</style></head><body>';
        foreach ($scene['lines'] as $l) {
            $html .= '<p>' . htmlspecialchars($l, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }

        return $html . '</body></html>';
    }

    private function ffmpegCommand(int $n, string $work, string $out): array
    {
        $cmd = [env('video.ffmpegBin', 'ffmpeg'), '-y', '-loglevel', 'error'];
        for ($i = 0; $i < $n; $i++) {
            array_push($cmd, '-loop', '1', '-t', (string) $this->seconds, '-i', "{$work}/s{$i}.png");
        }
        $filter = '';
        $last   = '0:v';
        for ($i = 1; $i < $n; $i++) {
            $offset  = round($i * ($this->seconds - $this->fade), 2);
            $filter .= "[{$last}][{$i}:v]xfade=transition=fade:duration={$this->fade}:offset={$offset}[v{$i}];";
            $last    = "v{$i}";
        }
        $filter .= "[{$last}]format=yuv420p[out]";
        array_push($cmd, '-filter_complex', $filter, '-map', '[out]', '-r', '30', '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-movflags', '+faststart', $out);

        return $cmd;
    }

    /** Runs a command without a shell (no injection) and fails on non-zero exit or timeout. */
    private function run(array $cmd, int $timeout): void
    {
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($p)) {
            throw new \RuntimeException('Could not start ' . $cmd[0]);
        }
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $err   = '';
        $start = time();
        while (true) {
            $st = proc_get_status($p);
            $err .= (string) stream_get_contents($pipes[2]);
            stream_get_contents($pipes[1]);
            if (! $st['running']) {
                break;
            }
            if (time() - $start > $timeout) {
                proc_terminate($p, 9);
                throw new \RuntimeException(basename($cmd[0]) . ' timed out');
            }
            usleep(100000);
        }
        $code = $st['exitcode'];
        proc_close($p);
        // Chromium often exits 0 with noisy stderr; ffmpeg's exit code is reliable.
        if ($code !== 0 && $code !== -1) {
            throw new \RuntimeException(basename($cmd[0]) . " exited {$code}: " . mb_substr($err, -400));
        }
    }
}
