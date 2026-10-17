<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Blog posts</h1>
<p><a href="/admin">Admin</a> · <a href="/admin/blog/new">New post</a></p>
<?php foreach ($rows as $r): ?>
  <div class="card"><a href="/admin/blog/<?= (int) $r['id'] ?>"><?= esc($r['title']) ?></a> · <?= esc($r['locale']) ?> · /<?= esc($r['slug']) ?> · <?= $r['is_published'] ? 'published' : 'draft' ?></div>
<?php endforeach; ?>
<?php if (! $rows): ?><p>No posts yet.</p><?php endif; ?>
<?= $this->endSection() ?>
