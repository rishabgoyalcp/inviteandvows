<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Languages</h1>
<p><a href="/admin">Admin</a></p>
<p>Translations other than English and Hindi are machine-translated drafts. Switch a language on only after a native speaker has reviewed it
(files in <code>app/Language/&lt;code&gt;/Site.php</code>; event type and field names are edited under Event types).</p>
<form method="post" action="/admin/languages" class="card">
  <?= csrf_field() ?>
  <?php foreach ($rows as $l): ?>
    <label style="display:block"><input type="checkbox" name="active[<?= esc($l['code'], 'attr') ?>]" value="1" <?= $l['is_active'] ? 'checked' : '' ?> <?= $l['code'] === 'en' ? 'disabled checked' : '' ?>>
      <?= esc($l['name']) ?> (<?= esc($l['native']) ?>) · <?= esc($l['code']) ?> <?= $l['is_rtl'] ? '· right-to-left' : '' ?></label>
  <?php endforeach; ?>
  <button type="submit">Save</button>
</form>
<?= $this->endSection() ?>
