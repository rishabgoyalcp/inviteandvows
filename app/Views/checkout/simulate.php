<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Test payment: <?= esc($amount) ?></h1>
<p>Developer mode only. No Razorpay keys are set, so no real payment happens.</p>
<form method="post" action="/checkout/<?= esc($token, 'attr') ?>/simulate">
  <?= csrf_field() ?><input type="hidden" name="ref" value="<?= esc($ref, 'attr') ?>">
  <button type="submit">Simulate successful payment</button>
</form>
<?= $this->endSection() ?>
