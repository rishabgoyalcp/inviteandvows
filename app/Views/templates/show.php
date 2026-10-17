<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= esc(i18n($t['names'], $t['slug'])) ?></h1>
<p><?= esc(money((int) $t['price_minor'], $region['currency'])) ?> · <?= lang('Site.websiteInvite') ?></p>
<form method="post" action="/build/start/<?= esc($t['slug'], 'url') ?>">
  <?= csrf_field() ?>
  <button type="submit"><?= lang('Site.customize') ?></button>
</form>
<div class="card"><?= view($t['view_file'], ['fields' => $fields, 'content' => $content]) ?></div>
<?= $this->endSection() ?>
