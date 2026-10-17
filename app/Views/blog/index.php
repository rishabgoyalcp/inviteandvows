<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.blog') ?></h1>
<?php foreach ($posts as $p): ?>
  <div class="card"><h2 style="margin:0 0 6px"><a href="<?= esc(lang_url(service('request')->getLocale(), 'blog/' . $p['slug']), 'attr') ?>"><?= esc($p['title']) ?></a></h2>
    <small><?= esc(substr((string) $p['published_at'], 0, 10)) ?></small><p><?= esc($p['excerpt']) ?></p></div>
<?php endforeach; ?>
<?php if (! $posts): ?><p><?= lang('Site.noPosts') ?></p><?php endif; ?>
<?= $this->endSection() ?>
