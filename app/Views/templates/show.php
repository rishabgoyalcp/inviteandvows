<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= esc(i18n($t['names'], $t['slug'])) ?></h1>
<?php foreach ($formats as $f): ?>
  <div class="card">
    <strong><?= lang('Site.fmt_' . $f['format']) ?></strong> · <?= esc(money((int) $f['price_minor'], $region['currency'])) ?>
    <?php if ($f['format'] === 'website'): ?>
      <form method="post" action="/build/start/<?= esc($t['slug'], 'url') ?>" style="display:inline">
        <?= csrf_field() ?><button type="submit"><?= lang('Site.customize') ?></button>
      </form>
    <?php elseif ($f['delivery_url']): ?>
      <a href="/buy/<?= (int) $f['id'] ?>"><button type="button"><?= lang('Site.buy') ?></button></a>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<div class="card"><?= view($t['view_file'], ['fields' => $fields, 'content' => $content]) ?></div>
<?= $this->endSection() ?>
