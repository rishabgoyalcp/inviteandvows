<?php
// Builds a signed update package.   php tools/build-release.php 1.1.0 ["Release notes"]
// Run `composer install --no-dev --optimize-autoloader` first if the release should carry vendor/ changes.
// Output: dist/invite-and-vows-<version>.zip and .sig. Upload both in Admin > Updates.
$noVendor = in_array('--no-vendor', $argv, true); // code-only update: much smaller package
$args     = array_values(array_filter(array_slice($argv, 1), static fn ($a) => $a !== '--no-vendor'));
if (! $args || ! preg_match('/^\d+\.\d+\.\d+$/', $args[0])) {
    fwrite(STDERR, "Usage: php tools/build-release.php <version e.g. 1.1.0> [notes] [--no-vendor]\n");
    exit(1);
}
$version = $args[0];
$notes   = $args[1] ?? '';
$root    = realpath(__DIR__ . '/..');
$keyFile = getenv('RELEASE_KEY') ?: $root . '/release.key';
if (! is_file($keyFile)) {
    fwrite(STDERR, "No release.key. Run php tools/make-keys.php first.\n");
    exit(1);
}
$secret = base64_decode(trim(file_get_contents($keyFile)), true);

// Only these parts of the project ever go into a package (the server refuses anything else).
$roots = ['app', 'public', 'spark', 'composer.json', 'composer.lock', 'deploy'];
if (! $noVendor) {
    $roots[] = 'vendor';
}
$skip  = ['#^public/(uploads|videos)/#', '#^vendor/(bin|composer/installed)#', '#/\.git/#', '#\.DS_Store$#', '#^app/.*\.(bak|orig)$#'];
$files = [];
foreach ($roots as $r) {
    $p = "$root/$r";
    if (is_file($p)) {
        $files[] = $r;
    } elseif (is_dir($p)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->isFile() && ! $f->isLink()) {
                $files[] = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
            }
        }
    }
}
$files = array_values(array_filter($files, static function ($f) use ($skip) {
    foreach ($skip as $re) {
        if (preg_match($re, $f)) {
            return false;
        }
    }

    return true;
}));
@mkdir("$root/dist", 0755, true);
$out = "$root/dist/invite-and-vows-$version.zip";
@unlink($out);
$zip = new ZipArchive();
$zip->open($out, ZipArchive::CREATE);
foreach ($files as $f) {
    $zip->addFile("$root/$f", $f);
}
$zip->addFromString('VERSION', $version . "\n");
$zip->addFromString('release.json', json_encode(['version' => $version, 'min_php' => '8.2', 'notes' => $notes, 'built' => date('c'), 'files' => count($files)], JSON_PRETTY_PRINT));
$zip->close();
file_put_contents($out . '.sig', base64_encode(sodium_crypto_sign_detached(file_get_contents($out), $secret)));
printf("Built %s (%d files, %.1f MB)\nSignature: %s.sig\n", $out, count($files), filesize($out) / 1048576, $out);
