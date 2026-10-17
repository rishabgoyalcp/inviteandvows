<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.guestList') ?></h1>
<p><a href="/dashboard">&larr;</a></p>
<?php if ($ok): ?><p><strong><?= esc($ok) ?></strong></p><?php endif; ?>
<form method="post" action="/dashboard/invites/<?= (int) $inv['id'] ?>/guests/import" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?>
  <p><small><?= lang('Site.guestsHelp') ?></small></p>
  <textarea name="list" rows="5" style="width:100%" placeholder="Asha Sharma, 9876543210, asha@example.com"></textarea>
  <div><input type="file" name="csv" accept=".csv,text/csv,text/plain"> <label><?= lang('Site.countryCode') ?> <input name="cc" value="<?= esc($cc, 'attr') ?>" style="width:60px"></label>
  <button type="submit"><?= lang('Site.import') ?></button></div>
</form>
<?php foreach ($guests as $g):
    $state = $g['reply'] ? lang('Site.replied') . ' (' . esc($g['reply']) . ')' : ($g['opened_at'] ? lang('Site.opened') : lang('Site.notSent'));
?>
  <div class="card">
    <strong><?= esc($g['name']) ?></strong><?= $g['phone'] ? ' · +' . esc($g['phone']) : '' ?> · <?= $state ?>
    <?= $g['last_reminded_at'] ? ' · ' . esc(substr($g['last_reminded_at'], 0, 10)) : '' ?><br>
    <?php if ($g['phone']): ?>
      <a href="/dashboard/invites/<?= (int) $inv['id'] ?>/guests/<?= (int) $g['id'] ?>/whatsapp" target="_blank" rel="noopener"><?= lang('Site.sendWhatsapp') ?></a>
      <?php if (! $g['reply']): ?> · <a href="/dashboard/invites/<?= (int) $inv['id'] ?>/guests/<?= (int) $g['id'] ?>/whatsapp?type=remind" target="_blank" rel="noopener"><?= lang('Site.remind') ?></a><?php endif; ?> ·
    <?php endif; ?>
    <input readonly value="<?= esc(invite_url($inv['short_code']) . '?g=' . $g['token'], 'attr') ?>" onclick="this.select()" style="width:55%">
    <form method="post" action="/dashboard/invites/<?= (int) $inv['id'] ?>/guests/<?= (int) $g['id'] ?>/delete" style="display:inline" onsubmit="return confirm('?')"><?= csrf_field() ?><button type="submit">&times;</button></form>
  </div>
<?php endforeach; ?>
<?= $this->endSection() ?>
