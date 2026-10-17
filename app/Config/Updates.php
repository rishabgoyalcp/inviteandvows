<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Updates extends BaseConfig
{
    /** Where backups and unpacked updates live. Must be outside public/. */
    public string $backupDir = WRITEPATH . 'backups/';
    public string $workDir   = WRITEPATH . 'updates/';
    public int $keepBackups  = 15;

    /** An update package may only contain files under these paths. Anything else makes the whole package invalid. */
    public array $allowed = ['app/', 'public/', 'vendor/', 'deploy/', 'spark', 'composer.json', 'composer.lock', 'VERSION'];

    /** Never touched by an update or a restore, whatever a package contains. */
    public array $protected = ['.env', 'writable/', 'public/uploads/', 'public/videos/'];

    /** What a manual backup copies besides the database (user uploads and videos are not included; they can be large). */
    public array $backupCode = ['app', 'public', 'spark', 'composer.json', 'composer.lock', 'VERSION'];

    public int $maxPackageMb = 120;
    public int $maxEntries   = 20000;
}
