<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Templates</h1>
<p><a href="/admin">Admin</a> · <a href="/admin/event-types">Event types</a></p>
<?php
$price = [];
foreach ($prices as $p) { $price[$p['template_id']][$p['region_id']] = $p['price_minor'] / 100; }
foreach (array_merge($rows, [['id' => 0, 'slug' => '', 'names' => '{}', 'view_file' => $views[0] ?? '', 'is_active' => 0, 'event_slug' => '']]) as $r):
    $n = json_decode($r['names'] ?? '{}', true) ?: []; ?>
  <form method="post" action="/admin/templates" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <?php if ($r['id']): ?><strong><?= esc($r['slug']) ?></strong> (<?= esc($r['event_slug']) ?>)<br>
    <?php else: ?><strong>New</strong> <input name="slug" placeholder="slug">
      <select name="event_type_id"><?php foreach ($eventTypes as $e): ?><option value="<?= (int) $e['id'] ?>"><?= esc($e['slug']) ?></option><?php endforeach; ?></select><br><?php endif; ?>
    <?php foreach ($langs as $l): ?><input name="names[<?= esc($l['code'], 'attr') ?>]" value="<?= esc($n[$l['code']] ?? '', 'attr') ?>" placeholder="<?= esc($l['name'], 'attr') ?>"><?php endforeach; ?>
    <select name="view_file"><?php foreach ($views as $v): ?><option <?= $v === $r['view_file'] ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
    <?php foreach ($regions as $g): ?><label><?= esc($g['code']) ?> <?= esc($g['currency']) ?> <input type="number" step="0.01" min="0" name="price[<?= (int) $g['id'] ?>]" value="<?= esc((string) ($price[$r['id']][$g['id']] ?? ''), 'attr') ?>" style="width:90px"></label><?php endforeach; ?>
    <label><input type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>> active</label>
    <button type="submit">Save</button>
  </form>
<?php endforeach; ?>
<?= $this->endSection() ?>
