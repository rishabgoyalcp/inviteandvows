<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.orderSummary') ?></h1>
<p><strong><?= esc($name) ?></strong> · <?= esc($price) ?></p>
<?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
<form method="post" action="<?= esc($action, 'attr') ?>" class="card">
  <?= csrf_field() ?>
  <label><?= lang('Site.couponCode') ?> <input name="coupon" value="<?= esc($coupon, 'attr') ?>" autocomplete="off" style="text-transform:uppercase"></label>
  <div><button type="submit"><?= lang('Site.payNow') ?></button></div>
</form>
<?= $this->endSection() ?>
