<?php

namespace App\Libraries\Video;

use App\Libraries\Design\DesignRenderer;
use App\Libraries\InviteService;

/**
 * Renders a video invite: each scene is an HTML page screenshotted by headless Chromium
 * (so every script shapes correctly), then ffmpeg joins the scenes with cross-fades into an MP4.
 * Needs Chromium and ffmpeg on the machine that runs `php spark video:render` (a VPS, not shared hosting).
 */
class VideoRenderer
{
    private float $fade = 0.6;

    /** @return string path relative to public/, e.g. videos/ab12.mp4 */
    public function render(int $inviteId): string
    {
        $db  = db_connect();
        $inv = $db->table('invites i')->select('i.*, t.config')->join('templates t', 't.id = i.template_id')->where('i.id', $inviteId)->get()->getRowArray();
        if (! $inv) {
            throw new \RuntimeException("Invite {$inviteId} not found");
        }
        $fields  = InviteService::fieldsFor((int) $inv['template_id']);
        $content = json_decode($inv['content'] ?? '{}', true) ?: [];
        $lang    = $db->table('languages')->where('code', $inv['locale'] ?: 'en')->get()->getRowArray() ?: [];
        $design  = VideoScenes::design($inv, $fields, $content, (int) $inv['id']);
        if (! $design['frames']) {
            throw new \RuntimeException('Invite has no content to render');
        }
        $W = (int) $design['canvas']['w'];
        $H = (int) $design['canvas']['h'];

        $work = sys_get_temp_dir() . '/vid_' . bin2hex(random_bytes(6));
        mkdir($work, 0700, true);
        try {
            // Images are copied next to the page so Chromium can read them without any wider file access.
            $copies = [];
            $img    = function (string $path) use ($work, &$copies): string {
                if (! isset($copies[$path])) {
                    $name = 'img' . count($copies) . '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    if (is_file(FCPATH . $path)) {
                        copy(FCPATH . $path, "{$work}/{$name}");
                    }
                    $copies[$path] = $name;
                }

                return $copies[$path];
            };
            foreach ($design['frames'] as $i => $frame) {
                file_put_contents("{$work}/s{$i}.html", DesignRenderer::pageHtml($design, $frame, $fields, $content, $lang, $img));
                $this->run([
                    env('video.chromiumBin', 'chromium'), '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run',
                    ...array_filter(explode(' ', (string) env('video.chromiumFlags', '--no-sandbox'))),
                    '--virtual-time-budget=5000', "--window-size={$W}," . ($H + 300), // headless Chromium loses part of the height to its window frame; ffmpeg crops the extra

                    "--screenshot={$work}/s{$i}.png", "file://{$work}/s{$i}.html",
                ], 90);
                if (! is_file("{$work}/s{$i}.png")) {
                    throw new \RuntimeException("Scene {$i} screenshot failed");
                }
            }
            $out = 'videos/' . bin2hex(random_bytes(12)) . '.mp4';
            $this->run($this->ffmpegCommand(array_column($design['frames'], 'seconds'), $work, FCPATH . $out, $W, $H), 300);
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

    /** @param list<float|int> $seconds length of each scene */
    private function ffmpegCommand(array $seconds, string $work, string $out, int $W, int $H): array
    {
        $cmd = [env('video.ffmpegBin', 'ffmpeg'), '-y', '-loglevel', 'error'];
        foreach ($seconds as $i => $d) {
            array_push($cmd, '-loop', '1', '-t', (string) $d, '-i', "{$work}/s{$i}.png");
        }
        $filter = '';
        foreach (array_keys($seconds) as $i) {
            $filter .= "[{$i}:v]crop={$W}:{$H}:0:0,setsar=1[c{$i}];";
        }
        $last = 'c0';
        $sum  = 0.0;
        for ($i = 1; $i < count($seconds); $i++) {
            $sum    += $seconds[$i - 1];
            $offset  = round($sum - $i * $this->fade, 2);
            $filter .= "[{$last}][c{$i}]xfade=transition=fade:duration={$this->fade}:offset={$offset}[v{$i}];";
            $last    = "v{$i}";
        }
        // Even dimensions are required by H.264.
        $filter .= "[{$last}]scale=trunc(iw/2)*2:trunc(ih/2)*2,format=yuv420p[out]";
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
