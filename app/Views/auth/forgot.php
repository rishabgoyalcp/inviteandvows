<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.forgot') ?></h1>
<?php if ($sent): ?><p><strong><?= lang('Site.resetSent') ?></strong></p><?php endif; ?>
<?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
<form method="post" action="/forgot" class="card">
  <?= csrf_field() ?>
  <div><input type="email" name="email" placeholder="<?= lang('Site.email') ?>" required></div>
  <button type="submit"><?= lang('Site.sendLink') ?></button>
</form>
<?= $this->endSection() ?>
