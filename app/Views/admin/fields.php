<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Fields: <?= esc($et['slug']) ?></h1>
<p><a href="/admin/event-types">&larr; Event types</a></p>
<?php foreach (array_merge($rows, [['id' => 0, 'field_key' => '', 'field_type' => 'text', 'labels' => '{}', 'is_required' => 0, 'sort' => count($rows)]]) as $r):
    $n = json_decode($r['labels'] ?? '{}', true) ?: []; ?>
  <form method="post" action="/admin/event-types/<?= (int) $et['id'] ?>/fields" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <?php if ($r['id']): ?><strong><?= esc($r['field_key']) ?></strong><?php else: ?><strong>New</strong> <input name="field_key" placeholder="key e.g. venue_name"><?php endif; ?>
    <select name="field_type"><?php foreach ($types as $t): ?><option <?= $t === $r['field_type'] ? 'selected' : '' ?>><?= esc($t) ?></option><?php endforeach; ?></select>
    <?php foreach ($langs as $l): ?><input name="labels[<?= esc($l['code'], 'attr') ?>]" value="<?= esc($n[$l['code']] ?? '', 'attr') ?>" placeholder="Label <?= esc($l['name'], 'attr') ?>"><?php endforeach; ?>
    <input type="number" name="sort" value="<?= (int) $r['sort'] ?>" style="width:70px">
    <label><input type="checkbox" name="is_required" value="1" <?= $r['is_required'] ? 'checked' : '' ?>> required</label>
    <button type="submit">Save</button>
    <?php if ($r['id']): ?><button type="submit" formaction="/admin/event-types/<?= (int) $et['id'] ?>/fields/<?= (int) $r['id'] ?>/delete" onclick="return confirm('Delete field?')">Delete</button><?php endif; ?>
  </form>
<?php endforeach; ?>
<?= $this->endSection() ?>
