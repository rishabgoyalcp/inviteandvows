<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.tagline') ?></h1>
<p><?= lang('Site.region') ?>: <strong><?= esc($region['domain']) ?></strong> · <?= esc($region['currency']) ?></p>
<?php foreach ($eventTypes as $e):
    $names = json_decode($e['names'] ?? '{}', true) ?: [];
    $name  = $names[service('request')->getLocale()] ?? $names['en'] ?? $e['slug'];
?>
  <div class="card"><?= esc($name) ?></div>
<?php endforeach; ?>
<?= $this->endSection() ?>
