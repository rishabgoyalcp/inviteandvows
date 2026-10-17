<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.admin') ?></h1>
<p><a href="/admin/event-types">Event types &amp; fields</a> · <a href="/admin/templates">Templates &amp; prices</a> · <a href="/admin/languages">Languages</a></p>
<?php foreach ($counts as $table => $n): ?>
  <div class="card"><?= esc($table) ?>: <strong><?= (int) $n ?></strong></div>
<?php endforeach; ?>
<?= $this->endSection() ?>
