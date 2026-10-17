<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.newPassword') ?></h1>
<?php if (! $valid): ?>
  <p class="err"><?= lang('Site.resetInvalid') ?></p><p><a href="/forgot"><?= lang('Site.forgot') ?></a></p>
<?php else: ?>
  <?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
  <form method="post" action="/reset/<?= esc($token, 'attr') ?>" class="card">
    <?= csrf_field() ?>
    <div><input type="password" name="password" minlength="8" placeholder="<?= lang('Site.password') ?>" required autocomplete="new-password"></div>
    <button type="submit"><?= lang('Site.save') ?></button>
  </form>
<?php endif; ?>
<?= $this->endSection() ?>
