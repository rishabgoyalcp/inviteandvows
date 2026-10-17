<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Updater\Backups;
use App\Libraries\Updater\Updater;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Admin > Updates: install signed releases, take and restore backups. */
class Updates extends BaseController
{
    private function record(string $kind, ?string $from, ?string $to, string $status, ?string $backup, array $log): void
    {
        db_connect()->table('app_updates')->insert(['kind' => $kind, 'from_version' => $from, 'to_version' => $to, 'status' => $status, 'backup_file' => $backup,
            'log' => implode("\n", $log), 'user_id' => session()->get('user_id'), 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function index()
    {
        $db      = db_connect();
        $backups = Backups::all();

        return view('admin/updates', [
            'version' => app_version(), 'php' => PHP_VERSION, 'env' => ENVIRONMENT, 'dbDriver' => $db->DBDriver,
            'writable' => is_writable(WRITEPATH), 'freeMb' => (int) round((float) @disk_free_space(WRITEPATH) / 1048576),
            'keyOk' => Updater::publicKey() !== null, 'sodium' => function_exists('sodium_crypto_sign_verify_detached') && class_exists(\ZipArchive::class),
            'backups' => $backups, 'history' => $db->table('app_updates')->orderBy('id', 'DESC')->limit(20)->get()->getResultArray(),
            'flash' => session()->getFlashdata('log'), 'flashStatus' => session()->getFlashdata('status'),
        ]);
    }

    public function upload()
    {
        $pkg = $this->request->getFile('package');
        if (! $pkg || ! $pkg->isValid() || $pkg->hasMoved() || strtolower($pkg->getExtension()) !== 'zip') {
            return redirect()->to('/admin/updates')->with('status', 'failed')->with('log', ['Choose the update .zip file.']);
        }
        $sig = (string) $this->request->getPost('signature');
        if (($sf = $this->request->getFile('sigfile')) && $sf->isValid() && $sf->getSize() < 1024) {
            $sig = (string) file_get_contents($sf->getTempName());
        }
        set_time_limit(300);
        $log = [];
        $res = Updater::install($pkg->getTempName(), $sig, (bool) $this->request->getPost('force'), static function (string $l) use (&$log) { $log[] = $l; });
        $this->record('update', app_version() === ($res['to'] ?? null) ? null : app_version(), $res['to'], $res['status'], $res['backup'], $log);

        return redirect()->to('/admin/updates')->with('status', $res['status'])->with('log', $log);
    }

    public function backup()
    {
        try {
            $b   = Backups::create('manual');
            $log = ['Backup created: ' . $b['file']];
            $this->record('backup', app_version(), null, 'ok', $b['file'], $log);

            return redirect()->to('/admin/updates')->with('status', 'ok')->with('log', $log);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/updates')->with('status', 'failed')->with('log', [$e->getMessage()]);
        }
    }

    public function download(string $file)
    {
        $p = Backups::pathOf($file) ?? throw PageNotFoundException::forPageNotFound();

        return $this->response->download($p, null);
    }

    public function restore()
    {
        $file = (string) $this->request->getPost('file');
        $log  = [];
        try {
            Backups::pathOf($file) ?? throw new \RuntimeException('Backup not found');
            set_time_limit(300);
            $log[] = 'Taking a safety backup of the current state';
            $safe  = Backups::create('before-restore');
            file_put_contents(Updater::maintenanceFile(), date('c'));
            $log[] = "Restoring {$file}";
            Backups::restore($file);
            @unlink(Updater::maintenanceFile());
            $log[]  = 'Restored. Now on version ' . app_version();
            $status = 'ok';
            $this->record('restore', null, app_version(), 'ok', $file, $log);
        } catch (\Throwable $e) {
            @unlink(Updater::maintenanceFile());
            $log[]  = 'FAILED: ' . $e->getMessage();
            $status = 'failed';
            $this->record('restore', null, null, 'failed', $file, $log);
        }

        return redirect()->to('/admin/updates')->with('status', $status)->with('log', $log);
    }

    public function deleteBackup()
    {
        if ($p = Backups::pathOf((string) $this->request->getPost('file'))) {
            @unlink($p);
        }

        return redirect()->to('/admin/updates');
    }
}
