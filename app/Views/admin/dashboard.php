<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.admin') ?></h1>
<?php foreach ($counts as $table => $n): ?>
  <div class="card"><?= esc($table) ?>: <strong><?= (int) $n ?></strong></div>
<?php endforeach; ?>
<?= $this->endSection() ?>
