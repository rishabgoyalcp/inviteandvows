<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= $p['id'] ? 'Edit post' : 'New post' ?></h1>
<p><a href="/admin/blog">&larr; Posts</a></p>
<?php if ($m = session()->getFlashdata('ok')): ?><p class="err"><?= esc($m) ?></p><?php endif; ?>
<form method="post" action="/admin/blog" class="card">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
  <div><select name="locale"><?php foreach ($langs as $l): ?><option value="<?= esc($l['code'], 'attr') ?>" <?= $l['code'] === $p['locale'] ? 'selected' : '' ?>><?= esc($l['name']) ?></option><?php endforeach; ?></select>
  <input name="slug" value="<?= esc($p['slug'], 'attr') ?>" placeholder="url-slug"></div>
  <div><input name="title" value="<?= esc($p['title'], 'attr') ?>" placeholder="Title" style="width:100%"></div>
  <div><input name="excerpt" value="<?= esc((string) $p['excerpt'], 'attr') ?>" placeholder="Short summary for search results (max 300 characters)" style="width:100%"></div>
  <div><textarea name="body" rows="18" style="width:100%" placeholder="Blank line = new paragraph. ## Heading. **bold**. [link text](https://example.com)"><?= esc((string) $p['body']) ?></textarea></div>
  <label><input type="checkbox" name="is_published" value="1" <?= $p['is_published'] ? 'checked' : '' ?>> published</label> <button type="submit">Save</button>
</form>
<?= $this->endSection() ?>
