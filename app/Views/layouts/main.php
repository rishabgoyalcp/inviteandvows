<?php
$languages = \App\Libraries\Region::languages();
$locale    = service('request')->getLocale();
$cur       = $languages[$locale] ?? reset($languages);
$rtl       = ! empty($cur['is_rtl']);
$segs      = service('request')->getUri()->getSegments();
if ($segs && isset($languages[$segs[0]])) { array_shift($segs); }
$path      = implode('/', $segs);
$seoPage   = $path === '' || $path === 'templates' || str_starts_with($path, 'templates/');
$title     = $title ?? lang('Site.brand');
$description = $description ?? lang('Site.descHome');
?>
<!doctype html>
<html lang="<?= esc($locale) ?>" dir="<?= $rtl ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<meta name="description" content="<?= esc($description, 'attr') ?>">
<?php if ($seoPage): ?>
<link rel="canonical" href="<?= esc(lang_url($locale, $path), 'attr') ?>">
<?php foreach ($languages as $code => $l): ?><link rel="alternate" hreflang="<?= esc($code, 'attr') ?>" href="<?= esc(lang_url($code, $path), 'attr') ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= esc(lang_url(array_key_first($languages), $path), 'attr') ?>">
<meta property="og:title" content="<?= esc($title, 'attr') ?>">
<meta property="og:description" content="<?= esc($description, 'attr') ?>">
<meta property="og:url" content="<?= esc(lang_url($locale, $path), 'attr') ?>">
<meta property="og:locale" content="<?= esc($locale, 'attr') ?>">
<?php else: ?><meta name="robots" content="noindex"><?php endif; ?>
<?= font_link($cur['font_family'] ?? null) ?>
<?php if (! empty($jsonld)): ?><script type="application/ld+json"><?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script><?php endif; ?>
<style>
:root{--font:"<?= esc($cur['font_family'] ?? 'Noto Sans', 'css') ?>",system-ui,sans-serif}
body{font-family:var(--font);margin:0;color:#2b2430;background:#fffaf5;line-height:1.6}
header,main,footer{max-width:960px;margin:0 auto;padding:16px}
header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
a{color:#8a2850}
.card{background:#fff;border:1px solid #eadfd6;border-radius:10px;padding:16px;margin:12px 0}
input,button,select{font:inherit;padding:8px 10px;margin:4px 0}
button{background:#8a2850;color:#fff;border:0;border-radius:6px;cursor:pointer}
.err{color:#b00020}
</style>
</head>
<body>
<header>
  <strong><a href="<?= esc(lang_url($locale, ''), 'attr') ?>" style="text-decoration:none"><?= lang('Site.brand') ?></a></strong>
  <nav>
    <a href="<?= esc(lang_url($locale, 'templates'), 'attr') ?>"><?= lang('Site.templates') ?></a>
    <?php if (session()->get('user_id')): ?>
      · <a href="/dashboard"><?= lang('Site.myInvites') ?></a>
      <?php if (session()->get('user_role') === 'admin'): ?> · <a href="/admin"><?= lang('Site.admin') ?></a><?php endif; ?>
      · <a href="/logout"><?= lang('Site.logout') ?></a>
    <?php else: ?> · <a href="/login"><?= lang('Site.login') ?></a><?php endif; ?>
    <?php if (count($languages) > 1): ?>
      · <select aria-label="<?= lang('Site.language') ?>" onchange="location.href=this.value">
        <?php foreach ($languages as $code => $l): ?>
          <option value="<?= esc(lang_url($code, $path), 'attr') ?>" <?= $code === $locale ? 'selected' : '' ?>><?= esc($l['native']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
  </nav>
</header>
<main>
<?php if ($e = session()->getFlashdata('error')): ?><p class="err"><?= esc($e) ?></p><?php endif; ?>
<?= $this->renderSection('content') ?></main>
<footer><small>&copy; <?= date('Y') ?> Invite &amp; Vows</small></footer>
</body>
</html>
