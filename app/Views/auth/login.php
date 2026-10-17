<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.login') ?></h1>
<?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
<form method="post" action="/login" class="card">
  <?= csrf_field() ?>
  <div><input type="email" name="email" placeholder="<?= lang('Site.email') ?>" required></div>
  <div><input type="password" name="password" placeholder="<?= lang('Site.password') ?>" required></div>
  <button type="submit"><?= lang('Site.login') ?></button>
</form>
<?= $this->endSection() ?>
