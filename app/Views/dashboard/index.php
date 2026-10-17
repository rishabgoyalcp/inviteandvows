<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.myInvites') ?></h1>
<?php if ($ok): ?><p><strong><?= esc($ok) ?></strong></p><?php endif; ?>
<?php if ($purchases): ?><h2><?= lang('Site.myPurchases') ?></h2>
<?php foreach ($purchases as $p): ?>
  <div class="card"><strong><?= esc(i18n($p['template_names'], '')) ?></strong> · <?= lang('Site.fmt_' . $p['format']) ?>
    · <a href="/dashboard/orders/<?= (int) $p['id'] ?>/open" rel="noopener"><?= lang('Site.openCanva') ?></a></div>
<?php endforeach; ?>
<h2><?= lang('Site.myInvites') ?></h2>
<?php endif; ?>
<?php foreach ($invites as $i): ?>
  <div class="card">
    <strong><?= esc(i18n($i['template_names'], '')) ?></strong> · <?= esc($i['status']) ?>
    <?php if ($i['status'] === 'published'): ?>
      <br><a href="<?= esc(invite_url($i['short_code']), 'attr') ?>"><?= esc(invite_url($i['short_code'])) ?></a>
      <?php if ($i['format'] === 'video'): ?><br>
        <?php if ($i['video_file']): ?><a href="<?= esc(base_url($i['video_file']), 'attr') ?>" download><?= lang('Site.downloadVideo') ?></a>
        <?php elseif ($i['video_status'] === 'failed'): ?><?= lang('Site.videoFailed') ?>
        <?php elseif ($i['video_status']): ?><?= lang('Site.videoPreparing') ?>
        <?php else: ?><a href="/dashboard/invites/<?= (int) $i['id'] ?>/video"><?= lang('Site.createVideo') ?></a><?php endif; ?>
      <?php endif; ?>
      <br><a href="/dashboard/invites/<?= (int) $i['id'] ?>/guests"><?= lang('Site.guestList') ?></a> ·
      <a href="/dashboard/invites/<?= (int) $i['id'] ?>/rsvps"><?= lang('Site.rsvp') ?> (<?= (int) $i['rsvp_count'] ?>)</a>
    <?php endif; ?>
    · <a href="/build/<?= esc($i['edit_token'], 'url') ?>"><?= lang('Site.edit') ?></a>
    <?php if ($i['status'] !== 'published'): ?> · <a href="/checkout/<?= esc($i['edit_token'], 'url') ?>"><?= lang('Site.payNow') ?></a><?php endif; ?>
  </div>
<?php endforeach; ?>
<div class="card"><strong><?= lang('Site.referTitle') ?></strong><br><small><?= lang('Site.referNote') ?></small><br>
  <input readonly value="<?= esc($refLink, 'attr') ?>" onclick="this.select()" style="width:100%">
  <?php if ($coupons): ?><br><strong><?= lang('Site.yourCoupons') ?>:</strong>
    <?php foreach ($coupons as $c): ?><code><?= esc($c['code']) ?></code> (<?= $c['type'] === 'percent' ? (int) $c['value'] . '%' : esc(money((int) $c['value'], (string) $c['currency'])) ?>) <?php endforeach; ?>
  <?php endif; ?></div>
<?php if (! $invites && ! $purchases): ?><p><a href="/templates"><?= lang('Site.templates') ?></a></p><?php endif; ?>
<?= $this->endSection() ?>
