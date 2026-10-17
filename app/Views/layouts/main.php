<?php
$languages = \App\Libraries\Region::languages();
$locale    = service('request')->getLocale();
$rtl       = ! empty($languages[$locale]['is_rtl']);
?>
<!doctype html>
<html lang="<?= esc($locale) ?>" dir="<?= $rtl ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? lang('Site.brand')) ?></title>
<style>
body{font-family:system-ui,"Noto Sans","Noto Sans Devanagari",sans-serif;margin:0;color:#2b2430;background:#fffaf5}
header,main,footer{max-width:960px;margin:0 auto;padding:16px}
header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
a{color:#8a2850}
.card{background:#fff;border:1px solid #eadfd6;border-radius:10px;padding:16px;margin:12px 0}
input,button{font:inherit;padding:8px 10px;margin:4px 0}
button{background:#8a2850;color:#fff;border:0;border-radius:6px;cursor:pointer}
.err{color:#b00020}
</style>
</head>
<body>
<header>
  <strong><a href="/" style="text-decoration:none"><?= lang('Site.brand') ?></a></strong>
  <nav>
    <?php foreach ($languages as $code => $l): ?>
      <a href="?lang=<?= esc($code, 'url') ?>"><?= esc($l['native']) ?></a>
    <?php endforeach; ?>
    <?php if (session()->get('user_id')): ?>
      · <a href="/admin"><?= lang('Site.admin') ?></a> · <a href="/logout"><?= lang('Site.logout') ?></a>
    <?php endif; ?>
  </nav>
</header>
<main><?= $this->renderSection('content') ?></main>
<footer><small>&copy; <?= date('Y') ?> Invite &amp; Vows</small></footer>
</body>
</html>
