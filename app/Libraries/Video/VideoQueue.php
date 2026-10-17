<?php

namespace App\Libraries\Video;

class VideoQueue
{
    public static function enqueue(int $inviteId): void
    {
        $db = db_connect();
        if ($db->table('video_jobs')->where('invite_id', $inviteId)->whereIn('status', ['queued', 'rendering', 'done'])->countAllResults() === 0) {
            $now = date('Y-m-d H:i:s');
            $db->table('video_jobs')->insert(['invite_id' => $inviteId, 'status' => 'queued', 'attempts' => 0, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    /** Renders up to $limit queued jobs. Returns a log line per job. */
    public static function work(int $limit = 3): array
    {
        $db  = db_connect();
        $log = [];
        $seen = [0]; // a job that failed this run is retried on the next run, not immediately
        // A worker that died mid-render leaves the job 'rendering'; put it back after 15 minutes.
        $db->table('video_jobs')->where('status', 'rendering')->where('started_at <', date('Y-m-d H:i:s', time() - 900))->update(['status' => 'queued']);

        for ($n = 0; $n < $limit; $n++) {
            $job = $db->table('video_jobs')->where('status', 'queued')->whereNotIn('id', $seen)->orderBy('id')->get(1)->getRowArray();
            if (! $job) {
                break;
            }
            $seen[] = (int) $job['id'];
            $now = date('Y-m-d H:i:s');
            // Claim atomically so two cron runs never render the same job.
            $db->table('video_jobs')->where(['id' => $job['id'], 'status' => 'queued'])->set('attempts', 'attempts + 1', false)->update(['status' => 'rendering', 'started_at' => $now, 'updated_at' => $now]);
            if ($db->affectedRows() !== 1) {
                continue;
            }
            try {
                $file = (new VideoRenderer())->render((int) $job['invite_id']);
                $db->table('video_jobs')->where('id', $job['id'])->update(['status' => 'done', 'file_path' => $file, 'error' => null, 'finished_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                $log[] = "job {$job['id']}: done ({$file})";
            } catch (\Throwable $e) {
                $failed = (int) $job['attempts'] + 1 >= 3;
                $db->table('video_jobs')->where('id', $job['id'])->update(['status' => $failed ? 'failed' : 'queued', 'error' => mb_substr($e->getMessage(), 0, 1000), 'updated_at' => date('Y-m-d H:i:s')]);
                $log[] = "job {$job['id']}: " . ($failed ? 'FAILED' : 'will retry') . ' - ' . $e->getMessage();
                log_message('error', 'Video job ' . $job['id'] . ': ' . $e->getMessage());
            }
        }

        return $log;
    }
}
