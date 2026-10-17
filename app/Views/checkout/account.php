<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.createAccount') ?></h1>
<p><?= lang('Site.accountNote') ?></p>
<?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
<form method="post" action="/checkout/<?= esc($token, 'attr') ?>/account" class="card">
  <?= csrf_field() ?>
  <div><input name="name" placeholder="<?= lang('Site.name') ?>"></div>
  <div><input type="email" name="email" placeholder="<?= lang('Site.email') ?>" required></div>
  <div><input type="password" name="password" minlength="8" placeholder="<?= lang('Site.password') ?>" required></div>
  <button type="submit"><?= lang('Site.continue') ?></button>
</form>
<?= $this->endSection() ?>
