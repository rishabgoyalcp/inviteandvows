<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.rsvp') ?></h1>
<p><a href="/dashboard">&larr;</a> · <a href="?csv=1">CSV</a></p>
<?php foreach ($rows as $r): ?>
  <div class="card"><strong><?= esc($r['guest_name']) ?></strong> · <?= esc($r['status']) ?> · <?= (int) $r['guests'] ?>
    <?php if ($r['contact']): ?> · <?= esc($r['contact']) ?><?php endif; ?>
    <?php if ($r['message']): ?><br><?= esc($r['message']) ?><?php endif; ?></div>
<?php endforeach; ?>
<?php if (! $rows): ?><p>—</p><?php endif; ?>
<?= $this->endSection() ?>
