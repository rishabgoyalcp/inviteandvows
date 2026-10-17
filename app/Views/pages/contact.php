<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Contact us</h1>
<p><?= esc($biz['name']) ?></p>
<p>Email: <a href="mailto:<?= esc($biz['email'], 'attr') ?>"><?= esc($biz['email']) ?></a></p>
<?php if ($biz['phone']): ?><p>Phone: <?= esc($biz['phone']) ?></p><?php endif; ?>
<?php if ($biz['address']): ?><p>Address: <?= esc($biz['address']) ?></p><?php endif; ?>
<?php if ($biz['gstin']): ?><p>GSTIN: <?= esc($biz['gstin']) ?></p><?php endif; ?>
<p>We reply within 2 working days.</p>
<?= $this->endSection() ?>
