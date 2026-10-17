<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.templates') ?></h1>
<p>
  <a href="/templates"><?= lang('Site.all') ?></a>
  <?php foreach ($eventTypes as $e): ?> · <a href="/templates?event=<?= esc($e['slug'], 'url') ?>"><?= esc(i18n($e['names'], $e['slug'])) ?></a><?php endforeach; ?>
</p>
<?php foreach ($templates as $t): ?>
  <div class="card">
    <strong><?= esc(i18n($t['names'], $t['slug'])) ?></strong> · <?= esc(i18n($t['event_names'], $t['event_slug'])) ?>
    <?php foreach ($formats[$t['id']] ?? [] as $f): ?> · <?= lang('Site.fmt_' . $f['format']) ?> <?= esc(money((int) $f['price_minor'], $region['currency'])) ?><?php endforeach; ?>
    · <a href="/templates/<?= esc($t['slug'], 'url') ?>"><?= lang('Site.preview') ?></a>
  </div>
<?php endforeach; ?>
<?php if (! $templates): ?><p><?= lang('Site.noTemplates') ?></p><?php endif; ?>
<?= $this->endSection() ?>
