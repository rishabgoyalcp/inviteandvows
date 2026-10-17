<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.payNow') ?>: <?= esc($amount) ?></h1>
<form id="vf" method="post" action="/checkout/<?= esc($token, 'attr') ?>/verify">
  <?= csrf_field() ?>
  <input type="hidden" name="razorpay_order_id"><input type="hidden" name="razorpay_payment_id"><input type="hidden" name="razorpay_signature">
</form>
<button id="pay"><?= lang('Site.payNow') ?></button>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('pay').onclick = () => new Razorpay({
  key: <?= json_encode($data['key_id']) ?>, order_id: <?= json_encode($data['provider_ref']) ?>,
  amount: <?= (int) $data['amount'] ?>, currency: <?= json_encode($data['currency']) ?>, name: 'Invite & Vows',
  prefill: {name: <?= json_encode($user['name']) ?>, email: <?= json_encode($user['email']) ?>},
  handler: r => { const f = document.getElementById('vf'); for (const k in r) f.elements[k].value = r[k]; f.submit(); }
}).open();
document.getElementById('pay').click();
</script>
<?= $this->endSection() ?>
