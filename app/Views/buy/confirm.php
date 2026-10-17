<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= esc(i18n($tf['template_names'], $tf['slug'])) ?></h1>
<p><?= lang('Site.fmt_' . $tf['format']) ?> · <strong><?= esc($amount) ?></strong></p>
<p><?= lang('Site.canvaNote') ?></p>
<?php if ($error): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>
<form method="post" action="/buy/<?= (int) $tf['id'] ?>">
  <?= csrf_field() ?>
  <label><?= lang('Site.couponCode') ?> <input name="coupon" value="<?= esc($coupon, 'attr') ?>" autocomplete="off" style="text-transform:uppercase"></label><br>
  <button type="submit"><?= lang('Site.payNow') ?></button>
</form>
<?= $this->endSection() ?>
