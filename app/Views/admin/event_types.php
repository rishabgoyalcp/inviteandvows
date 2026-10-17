<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Event types</h1>
<p><a href="/admin">Admin</a> · <a href="/admin/templates">Templates</a></p>
<?php foreach (array_merge($rows, [['id' => 0, 'slug' => '', 'names' => '{}', 'sort' => count($rows), 'is_active' => 1]]) as $r):
    $n = json_decode($r['names'] ?? '{}', true) ?: []; ?>
  <form method="post" action="/admin/event-types" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <?php if ($r['id']): ?><strong><?= esc($r['slug']) ?></strong> · <a href="/admin/event-types/<?= (int) $r['id'] ?>/fields">Fields</a><br>
    <?php else: ?><strong>New</strong> <input name="slug" placeholder="slug e.g. anniversary"><br><?php endif; ?>
    <?php foreach ($langs as $l): ?><input name="names[<?= esc($l['code'], 'attr') ?>]" value="<?= esc($n[$l['code']] ?? '', 'attr') ?>" placeholder="<?= esc($l['name'], 'attr') ?>"><?php endforeach; ?>
    <input type="number" name="sort" value="<?= (int) $r['sort'] ?>" style="width:70px">
    <label><input type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>> active</label>
    <button type="submit">Save</button>
  </form>
<?php endforeach; ?>
<?= $this->endSection() ?>
