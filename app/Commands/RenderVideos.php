<?php

namespace App\Commands;

use App\Libraries\Video\VideoQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RenderVideos extends BaseCommand
{
    protected $group       = 'Invites';
    protected $name        = 'video:render';
    protected $description = 'Renders queued video invites. Run every minute from cron on a machine with Chromium and ffmpeg.';
    protected $usage       = 'video:render [limit]';

    public function run(array $params)
    {
        $lines = VideoQueue::work(max(1, (int) ($params[0] ?? 3)));
        foreach ($lines as $l) {
            CLI::write($l);
        }
        if (! $lines) {
            CLI::write('No queued videos.');
        }
    }
}
