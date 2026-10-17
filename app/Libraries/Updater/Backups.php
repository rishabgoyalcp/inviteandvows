<?php

namespace App\Libraries\Updater;

use ZipArchive;

/** Database and code backups, stored as zip files in writable/backups. */
class Backups
{
    private const MARK = ";\n-- @@\n";

    public static function dir(): string
    {
        $d = config(\Config\Updates::class)->backupDir;
        if (! is_dir($d)) {
            mkdir($d, 0750, true);
        }
        if (! is_file($d . '.htaccess')) {
            file_put_contents($d . '.htaccess', "Require all denied\n");
            file_put_contents($d . 'index.html', '');
        }

        return $d;
    }

    /**
     * @param list<string> $files relative paths to copy into the backup (null = the standard code set)
     * @param list<string> $newFiles files an update will create; a restore deletes them again
     * @return array{file: string, size: int}
     */
    public static function create(string $label, ?array $files = null, array $newFiles = []): array
    {
        $cfg  = config(\Config\Updates::class);
        $name = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($label)) . '-' . bin2hex(random_bytes(3)) . '.zip';
        $path = self::dir() . $name;
        $zip  = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create the backup file. Is writable/backups writable?');
        }
        $zip->addFromString('db.sql', self::dumpDatabase());
        $files ??= self::codeFiles($cfg->backupCode);
        $kept = [];
        foreach ($files as $rel) {
            if (is_file(ROOTPATH . $rel) && ! self::isProtected($rel)) {
                $zip->addFile(ROOTPATH . $rel, 'code/' . $rel);
                $kept[] = $rel;
            }
        }
        $zip->addFromString('manifest.json', json_encode(['version' => app_version(), 'label' => $label, 'created' => date('c'), 'files' => $kept, 'new_files' => array_values($newFiles), 'db' => db_connect()->DBDriver], JSON_PRETTY_PRINT));
        if (! $zip->close() || ! is_file($path)) {
            throw new \RuntimeException('Could not write the backup file.');
        }
        self::prune();

        return ['file' => $name, 'size' => (int) filesize($path)];
    }

    public static function isProtected(string $rel): bool
    {
        foreach (config(\Config\Updates::class)->protected as $p) {
            if ($rel === $p || (str_ends_with($p, '/') && str_starts_with($rel, $p))) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public static function codeFiles(array $roots): array
    {
        $out = [];
        foreach ($roots as $r) {
            if (is_file(ROOTPATH . $r)) {
                $out[] = $r;
            } elseif (is_dir(ROOTPATH . $r)) {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH . $r, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(ROOTPATH)));
                    if ($f->isFile() && ! self::isProtected($rel)) {
                        $out[] = $rel;
                    }
                }
            }
        }

        return $out;
    }

    /** @return list<array{file: string, size: int, time: int, version: string, label: string}> newest first */
    public static function all(): array
    {
        $out = [];
        foreach (glob(self::dir() . '*.zip') ?: [] as $f) {
            $meta = ['version' => '?', 'label' => ''];
            $zip  = new ZipArchive();
            if ($zip->open($f) === true) {
                $m = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
                $meta = ['version' => $m['version'] ?? '?', 'label' => $m['label'] ?? ''];
                $zip->close();
            }
            $out[] = ['file' => basename($f), 'size' => (int) filesize($f), 'time' => (int) filemtime($f)] + $meta;
        }
        usort($out, static fn ($a, $b) => $b['time'] <=> $a['time']);

        return $out;
    }

    public static function pathOf(string $file): ?string
    {
        $p = self::dir() . basename($file);

        return preg_match('/^[A-Za-z0-9._-]+\.zip$/', $file) && is_file($p) ? $p : null;
    }

    private static function prune(): void
    {
        foreach (array_slice(self::all(), config(\Config\Updates::class)->keepBackups) as $old) {
            @unlink(self::dir() . $old['file']);
        }
    }

    // ---- database dump and restore (own format, works on MySQL and SQLite) ----

    public static function dumpDatabase(): string
    {
        $db  = db_connect();
        $out = '-- Invite & Vows dump ' . date('c') . "\n";
        foreach ($db->listTables() as $t) {
            if ($t === 'app_updates') { // the update history must survive a restore
                continue;
            }
            $q = $db->escapeIdentifiers($t);
            $create = $db->DBDriver === 'SQLite3'
                ? (string) $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name = " . $db->escape($t))->getRow('sql')
                : (string) ($db->query("SHOW CREATE TABLE {$q}")->getRowArray()['Create Table'] ?? '');
            if ($create === '') {
                throw new \RuntimeException("Could not read the structure of {$t}");
            }
            $out .= "DROP TABLE IF EXISTS {$q}" . self::MARK . $create . self::MARK;
            $cols = array_map(static fn ($c) => $db->escapeIdentifiers($c), $db->getFieldNames($t));
            $offset = 0;
            while (true) {
                $rows = $db->query("SELECT * FROM {$q} LIMIT 500 OFFSET {$offset}")->getResultArray();
                if (! $rows) {
                    break;
                }
                $vals = [];
                foreach ($rows as $r) {
                    $vals[] = '(' . implode(',', array_map(static fn ($v) => $v === null ? 'NULL' : $db->escape($v), array_values($r))) . ')';
                }
                $out .= "INSERT INTO {$q} (" . implode(',', $cols) . ') VALUES ' . implode(',', $vals) . self::MARK;
                $offset += 500;
            }
        }

        return $out;
    }

    public static function restoreDatabase(string $sql): void
    {
        $db = db_connect();
        if ($db->DBDriver === 'MySQLi') {
            $db->simpleQuery('SET FOREIGN_KEY_CHECKS=0');
        }
        $known = ['app_updates'];
        foreach (explode(self::MARK, $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt !== '' && ! str_starts_with($stmt, '-- Invite')) {
                if (preg_match('/^DROP TABLE IF EXISTS [`"]?([A-Za-z0-9_]+)/', $stmt, $m)) {
                    $known[] = $m[1];
                }
                if (! $db->simpleQuery($stmt)) {
                    throw new \RuntimeException('Restore failed on: ' . mb_substr($stmt, 0, 80));
                }
            }
        }
        // Tables a newer version added do not exist in the backup, so they go too.
        foreach ($db->listTables() as $t) {
            if (! in_array($t, $known, true)) {
                $db->simpleQuery('DROP TABLE IF EXISTS ' . $db->escapeIdentifiers($t));
            }
        }
        if ($db->DBDriver === 'MySQLi') {
            $db->simpleQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /** Puts the saved files back, deletes files the update added, and restores the database. */
    public static function restore(string $file, bool $withDatabase = true): void
    {
        $path = self::pathOf($file) ?? throw new \RuntimeException('Backup not found');
        $zip  = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Backup file is damaged');
        }
        $m = json_decode((string) $zip->getFromName('manifest.json'), true) ?: [];
        foreach ($m['files'] ?? [] as $rel) {
            if (self::isProtected($rel) || str_contains($rel, '..')) {
                continue;
            }
            $data = $zip->getFromName('code/' . $rel);
            if ($data !== false) {
                @mkdir(dirname(ROOTPATH . $rel), 0755, true);
                file_put_contents(ROOTPATH . $rel, $data);
            }
        }
        foreach ($m['new_files'] ?? [] as $rel) {
            if (! self::isProtected($rel) && ! str_contains($rel, '..') && is_file(ROOTPATH . $rel)) {
                @unlink(ROOTPATH . $rel);
            }
        }
        if ($withDatabase && ($sql = $zip->getFromName('db.sql')) !== false) {
            self::restoreDatabase($sql);
        }
        $zip->close();
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
}
