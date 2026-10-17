<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Coupons</h1>
<p><a href="/admin">Admin</a></p>
<?php if ($ok): ?><p class="err"><?= esc($ok) ?></p><?php endif; ?>
<form method="post" action="/admin/coupons" class="card">
  <?= csrf_field() ?><input type="hidden" name="id" value="0">
  <strong>New coupon</strong><br>
  <input name="code" placeholder="CODE (blank = generate)" style="text-transform:uppercase">
  <select name="type"><option value="percent">Percent off</option><option value="fixed">Fixed amount off</option></select>
  <input name="value" type="number" step="0.01" min="0" placeholder="Value (10 or 200)" required>
  <select name="currency"><?php foreach ($regions as $g): ?><option><?= esc($g['currency']) ?></option><?php endforeach; ?></select> <small>(currency for fixed only)</small><br>
  Formats: <?php foreach (['website', 'video', 'canva_design', 'canva_pdf'] as $f): ?><label><input type="checkbox" name="formats[]" value="<?= $f ?>"> <?= $f ?></label> <?php endforeach; ?> <small>(none ticked = all)</small><br>
  <input name="max_uses" type="number" min="0" placeholder="Max uses (0 = unlimited)"> <label>Expires <input name="expires" type="date"></label>
  <button type="submit">Create</button>
</form>
<?php foreach ($rows as $c): ?>
  <form method="post" action="/admin/coupons" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <strong><?= esc($c['code']) ?></strong> · <?= $c['type'] === 'percent' ? (int) $c['value'] . '%' : esc(($c['currency'] ?? '') . ' ' . $c['value'] / 100) ?>
    · used <?= (int) $c['used'] ?><?= $c['max_uses'] ? '/' . (int) $c['max_uses'] : '' ?> · <?= esc($c['source']) ?><?= $c['user_id'] ? ' · for user #' . (int) $c['user_id'] : '' ?>
    <?= $c['expires_at'] ? ' · expires ' . esc(substr($c['expires_at'], 0, 10)) : '' ?><?= $c['formats'] ? ' · ' . esc($c['formats']) : '' ?>
    <label><input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?>> active</label> <button type="submit">Save</button>
  </form>
<?php endforeach; ?>
<?= $this->endSection() ?>
