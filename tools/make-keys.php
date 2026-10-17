<?php
// Run once on your own computer: php tools/make-keys.php
// Prints the PUBLIC key for .env (updates.publicKey) and writes the SECRET key to release.key.
// Keep release.key private and out of git and off the server: whoever holds it can push code to your site.
if (! function_exists('sodium_crypto_sign_keypair')) {
    fwrite(STDERR, "PHP sodium extension is required\n");
    exit(1);
}
$target = __DIR__ . '/../release.key';
if (is_file($target)) {
    fwrite(STDERR, "release.key already exists. Delete it first if you really want a new key (old releases will stop installing).\n");
    exit(1);
}
$kp = sodium_crypto_sign_keypair();
file_put_contents($target, base64_encode(sodium_crypto_sign_secretkey($kp)));
chmod($target, 0600);
echo "Secret key written to release.key (do not share or commit it).\n\nAdd this line to the server's .env:\n\nupdates.publicKey = " . base64_encode(sodium_crypto_sign_publickey($kp)) . "\n";
