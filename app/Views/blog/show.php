<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<article>
  <h1><?= esc($p['title']) ?></h1>
  <small><?= esc(substr((string) $p['published_at'], 0, 10)) ?></small>
  <?= blog_html((string) $p['body']) ?>
  <p><a href="<?= esc(lang_url(service('request')->getLocale(), 'templates'), 'attr') ?>"><button type="button"><?= lang('Site.templates') ?></button></a></p>
</article>
<?= $this->endSection() ?>
