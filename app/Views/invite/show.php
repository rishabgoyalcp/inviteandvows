<?php $cur = \App\Libraries\Region::languages()[service('request')->getLocale()] ?? []; $rtl = ! empty($cur['is_rtl']); ?>
<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>" dir="<?= $rtl ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<meta property="og:title" content="<?= esc($title, 'attr') ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= esc($url, 'attr') ?>">
<?php if ($image): ?><meta property="og:image" content="<?= esc($image, 'attr') ?>"><?php endif; ?>
<meta name="robots" content="noindex">
<?= font_link($cur['font_family'] ?? null) ?>
<style>:root{--font:"<?= esc($cur['font_family'] ?? 'Noto Sans', 'css') ?>",system-ui,sans-serif}body{margin:0;background:#f3ece4;font-family:var(--font)}
.rsvp{max-width:560px;margin:16px auto;padding:16px;background:#fff;border-radius:10px;box-sizing:border-box}
input,select,textarea,button{font:inherit;padding:8px;margin:4px 0;width:100%;box-sizing:border-box}
button{background:#8a2850;color:#fff;border:0;border-radius:6px}.hp{position:absolute;left:-9999px}.err{color:#b00020}</style>
</head>
<body>
<?php if ($video): ?>
  <div style="text-align:center;padding:16px"><video controls playsinline preload="metadata" style="width:100%;max-width:420px;border-radius:10px" src="<?= esc($video, 'attr') ?>"></video></div>
<?php else: ?>
  <?= view($inv['view_file'], ['fields' => $fields, 'content' => $content, 'design' => design_of($inv)]) ?>
<?php endif; ?>
<form class="rsvp" method="post" action="/i/<?= esc($inv['short_code'], 'attr') ?>/rsvp">
  <?= csrf_field() ?>
  <h2><?= lang('Site.rsvp') ?></h2>
  <?php if ($guest): ?><p><?= lang('Site.dear', [esc($guest['name'])]) ?></p><input type="hidden" name="g" value="<?= esc($guest['token'], 'attr') ?>"><?php endif; ?>
  <?php if ($sent): ?><p><strong><?= lang('Site.rsvpThanks') ?></strong></p><?php endif; ?>
  <?php if ($e = session()->getFlashdata('error')): ?><p class="err"><?= esc($e) ?></p><?php endif; ?>
  <input name="guest_name" value="<?= esc($guest['name'] ?? '', 'attr') ?>" placeholder="<?= lang('Site.name') ?>" required>
  <input name="contact" placeholder="<?= lang('Site.contact') ?>">
  <select name="status"><option value="yes"><?= lang('Site.attending') ?></option><option value="maybe"><?= lang('Site.maybe') ?></option><option value="no"><?= lang('Site.notAttending') ?></option></select>
  <input type="number" name="guests" min="1" max="20" value="1" aria-label="<?= lang('Site.guests') ?>">
  <textarea name="message" rows="3" placeholder="<?= lang('Site.messageOpt') ?>"></textarea>
  <input class="hp" name="website" tabindex="-1" autocomplete="off">
  <button type="submit"><?= lang('Site.send') ?></button>
</form>
</body>
</html>
