<?php

namespace App\Libraries\Updater;

use ZipArchive;

/**
 * Installs a signed update package: verify, back up, switch on maintenance mode, copy files, run migrations.
 * If anything fails after the backup, the files and the database go back to how they were.
 */
class Updater
{
    public static function maintenanceFile(): string
    {
        return WRITEPATH . 'maintenance.flag';
    }

    public static function publicKey(): ?string
    {
        $k = (string) env('updates.publicKey', '');

        return $k !== '' && strlen((string) base64_decode($k, true)) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ? $k : null;
    }

    /**
     * @param callable(string): void $log
     * @return array{status: string, backup: ?string, to: ?string}
     */
    public static function install(string $zipPath, string $sigBase64, bool $force, callable $log): array
    {
        $cfg  = config(\Config\Updates::class);
        $from = app_version();
        $work = null;
        $backup = null;
        $maintenance = false;

        try {
            // ---- 1. checks that change nothing ----
            $key = self::publicKey() ?? throw new \RuntimeException('No update key is set. Add updates.publicKey to .env first.');
            $log('Checking the signature');
            $sig = base64_decode(trim($sigBase64), true);
            if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES
                || ! sodium_crypto_sign_verify_detached($sig, (string) file_get_contents($zipPath), base64_decode($key))) {
                throw new \RuntimeException('The signature does not match this package. It was not made with your release key, or it was changed.');
            }
            if (filesize($zipPath) > $cfg->maxPackageMb * 1048576) {
                throw new \RuntimeException('Package is too large');
            }
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('Package is not a valid zip file');
            }
            $meta = json_decode((string) $zip->getFromName('release.json'), true);
            $to   = is_array($meta) ? (string) ($meta['version'] ?? '') : '';
            if (! preg_match('/^\d+\.\d+\.\d+$/', $to)) {
                throw new \RuntimeException('release.json is missing or has no valid version');
            }
            if (version_compare($to, $from, '<=') && ! $force) {
                throw new \RuntimeException("Version {$to} is not newer than the installed {$from}");
            }
            if (version_compare(PHP_VERSION, (string) ($meta['min_php'] ?? '8.2'), '<')) {
                throw new \RuntimeException('This release needs PHP ' . $meta['min_php'] . ' or newer; the server runs ' . PHP_VERSION);
            }
            if ($zip->numFiles > $cfg->maxEntries) {
                throw new \RuntimeException('Package has too many files');
            }

            $log('Unpacking and checking every file');
            $work = rtrim($cfg->workDir, '/') . '/u-' . bin2hex(random_bytes(5)) . '/';
            mkdir($work, 0750, true);
            $files = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if ($name === 'release.json' || str_ends_with($name, '/')) {
                    continue;
                }
                if (! self::pathAllowed($name, $cfg)) {
                    throw new \RuntimeException("Package contains a file that is not allowed: {$name}");
                }
                $data   = $zip->getFromIndex($i);
                $total += strlen((string) $data);
                if ($data === false || $total > $cfg->maxPackageMb * 4 * 1048576) {
                    throw new \RuntimeException('Package could not be read or is unreasonably large when unpacked');
                }
                if (str_ends_with($name, '.php')) { // a syntax error would take the whole site down, so refuse it now
                    try {
                        token_get_all($data, TOKEN_PARSE);
                    } catch (\ParseError $e) {
                        throw new \RuntimeException("PHP syntax error in {$name}: " . $e->getMessage());
                    }
                }
                @mkdir(dirname($work . $name), 0750, true);
                file_put_contents($work . $name, $data);
                $files[] = $name;
            }
            $zip->close();
            if (! $files) {
                throw new \RuntimeException('Package is empty');
            }
            foreach (array_unique(array_map(static fn ($f) => dirname(ROOTPATH . $f), $files)) as $dir) {
                if (is_dir($dir) && ! is_writable($dir)) {
                    throw new \RuntimeException('Not writable: ' . substr($dir, strlen(ROOTPATH)) . '. Check folder permissions.');
                }
            }

            // ---- 2. backup (database plus the files this update will replace) ----
            $log('Making a backup first');
            $replace = array_values(array_filter($files, static fn ($f) => is_file(ROOTPATH . $f)));
            $added   = array_values(array_diff($files, $replace));
            $backup  = Backups::create('before-' . $to, $replace, $added)['file'];

            // ---- 3. install ----
            file_put_contents(self::maintenanceFile(), date('c'));
            $maintenance = true;
            $log('Maintenance mode on. Copying ' . count($files) . ' files');
            foreach ($files as $f) {
                $dest = ROOTPATH . $f;
                @mkdir(dirname($dest), 0755, true);
                $tmp  = $dest . '.new-' . bin2hex(random_bytes(3));
                $mode = is_file($dest) ? (fileperms($dest) & 0777) : 0644; // keep an existing file's permissions (spark stays executable)
                if (! copy($work . $f, $tmp) || ! chmod($tmp, $mode) || ! rename($tmp, $dest)) {
                    @unlink($tmp);
                    throw new \RuntimeException("Could not write {$f}");
                }
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            $log('Running database changes');
            $migrations = \Config\Services::migrations(null, null, false);
            $migrations->setNamespace('App');
            $migrations->latest();

            // ---- 4. finish ----
            foreach (glob(WRITEPATH . 'cache/*') ?: [] as $c) {
                if (is_file($c) && basename($c) !== 'index.html') {
                    @unlink($c);
                }
            }
            @unlink(self::maintenanceFile());
            $maintenance = false;
            $log("Done. Now on version {$to}");
            self::cleanup($work);

            return ['status' => 'ok', 'backup' => $backup, 'to' => $to];
        } catch (\Throwable $e) {
            $log('FAILED: ' . $e->getMessage());
            $status = 'failed';
            if ($backup !== null) {
                try {
                    $log('Putting everything back from the backup');
                    Backups::restore($backup);
                    $status = 'rolled_back';
                    $log('Rolled back to version ' . $from);
                } catch (\Throwable $r) {
                    $log('ROLLBACK FAILED: ' . $r->getMessage() . ". Restore {$backup} by hand.");
                }
            }
            if ($maintenance && $status === 'rolled_back') {
                @unlink(self::maintenanceFile());
            }
            self::cleanup($work);

            return ['status' => $status, 'backup' => $backup, 'to' => null];
        }
    }

    private static function pathAllowed(string $name, \Config\Updates $cfg): bool
    {
        if ($name === '' || str_contains($name, '..') || str_contains($name, '\\') || str_starts_with($name, '/') || preg_match('/[\x00-\x1f]/', $name)) {
            return false;
        }
        if (Backups::isProtected($name)) {
            return false;
        }
        foreach ($cfg->allowed as $a) {
            if ($name === $a || (str_ends_with($a, '/') && str_starts_with($name, $a))) {
                return true;
            }
        }

        return false;
    }

    private static function cleanup(?string $dir): void
    {
        if (! $dir || ! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
