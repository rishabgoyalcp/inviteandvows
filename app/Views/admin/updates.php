<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Updates</h1>
<p><a href="/admin">Admin</a></p>
<?php if ($flash): ?>
  <div class="card" style="border-color:<?= $flashStatus === 'ok' ? '#2d7a3a' : '#b00020' ?>">
    <strong><?= $flashStatus === 'ok' ? 'Done' : ($flashStatus === 'rolled_back' ? 'Update failed and was rolled back' : 'Failed') ?></strong>
    <pre style="white-space:pre-wrap;margin:6px 0 0"><?= esc(implode("\n", (array) $flash)) ?></pre>
  </div>
<?php endif; ?>
<div class="card">
  <strong>System</strong><br>
  Version <strong><?= esc($version) ?></strong> · PHP <?= esc($php) ?> · <?= esc($env) ?> · database <?= esc($dbDriver) ?><br>
  Storage folder <?= $writable ? 'is writable' : '<span class="err">is NOT writable</span>' ?> · free disk <?= number_format($freeMb) ?> MB ·
  <?= count($backups) ?> backups<?= $backups ? ', latest ' . esc(date('Y-m-d H:i', $backups[0]['time'])) : '' ?>
</div>

<div class="card">
  <strong>Install an update</strong>
  <?php if (! $keyOk || ! $sodium): ?>
    <p class="err">Updates are switched off. <?= ! $sodium ? 'This server needs the sodium and zip PHP extensions.' : 'Run <code>php tools/make-keys.php</code> on your computer and put the public key in .env as <code>updates.publicKey</code>.' ?></p>
  <?php else: ?>
    <form method="post" action="/admin/updates" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div><label>Update file (.zip) <input type="file" name="package" accept=".zip" required></label></div>
      <div><label>Signature file (.sig) <input type="file" name="sigfile" accept=".sig,text/plain"></label> or paste it: <input name="signature" style="width:40%"></div>
      <label><input type="checkbox" name="force" value="1"> Reinstall even if the version is not newer</label><br>
      <button type="submit" onclick="return confirm('Install this update now? A backup is made first and the site shows an update notice while it runs.')">Install update</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <strong>Backups</strong>
  <form method="post" action="/admin/updates/backup" style="display:inline"><?= csrf_field() ?><button type="submit">Back up now</button></form>
  <p><small>A backup holds the database and the code. Uploaded photos and videos are not included; copy <code>public/uploads</code> and <code>public/videos</code> separately.</small></p>
  <?php foreach ($backups as $b): ?>
    <div style="border-top:1px solid #eadfd6;padding:6px 0">
      <?= esc(date('Y-m-d H:i', $b['time'])) ?> · v<?= esc($b['version']) ?> · <?= esc($b['label']) ?> · <?= number_format($b['size'] / 1048576, 1) ?> MB ·
      <a href="/admin/updates/download/<?= esc($b['file'], 'url') ?>">Download</a>
      <form method="post" action="/admin/updates/restore" style="display:inline"><?= csrf_field() ?><input type="hidden" name="file" value="<?= esc($b['file'], 'attr') ?>">
        <button type="submit" onclick="return confirm('Restore this backup? The current database and code are replaced. A safety backup is made first.')">Restore</button></form>
      <form method="post" action="/admin/updates/delete" style="display:inline"><?= csrf_field() ?><input type="hidden" name="file" value="<?= esc($b['file'], 'attr') ?>">
        <button type="submit" onclick="return confirm('Delete this backup?')">&times;</button></form>
    </div>
  <?php endforeach; ?>
  <?php if (! $backups): ?><p>No backups yet.</p><?php endif; ?>
</div>

<div class="card">
  <strong>History</strong>
  <?php foreach ($history as $h): ?>
    <div style="border-top:1px solid #eadfd6;padding:6px 0"><?= esc($h['created_at']) ?> · <?= esc($h['kind']) ?> · <?= esc((string) $h['from_version']) ?><?= $h['to_version'] ? ' → ' . esc($h['to_version']) : '' ?> · <strong><?= esc($h['status']) ?></strong>
      <details><summary>log</summary><pre style="white-space:pre-wrap"><?= esc((string) $h['log']) ?></pre></details></div>
  <?php endforeach; ?>
  <?php if (! $history): ?><p>Nothing yet.</p><?php endif; ?>
</div>
<?= $this->endSection() ?>
