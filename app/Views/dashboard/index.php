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
      <br><a href="/dashboard/invites/<?= (int) $i['id'] ?>/rsvps"><?= lang('Site.rsvp') ?> (<?= (int) $i['rsvp_count'] ?>)</a>
    <?php endif; ?>
    · <a href="/build/<?= esc($i['edit_token'], 'url') ?>"><?= lang('Site.edit') ?></a>
    <?php if ($i['status'] !== 'published'): ?> · <a href="/checkout/<?= esc($i['edit_token'], 'url') ?>"><?= lang('Site.payNow') ?></a><?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (! $invites && ! $purchases): ?><p><a href="/templates"><?= lang('Site.templates') ?></a></p><?php endif; ?>
<?= $this->endSection() ?>
