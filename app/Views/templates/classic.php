<?php
/** Placeholder template: renders whatever fields the event type defines. Real designs replace this file per template. */
$img = '';
foreach ($fields as $f) {
    if ($f['field_type'] === 'image' && ! empty($content[$f['field_key']])) {
        $img = $content[$f['field_key']];
    }
}
?>
<div style="max-width:560px;margin:0 auto;padding:32px 20px;text-align:center;font-family:var(--font,Georgia,serif);color:#3b2a2a;background:#fffaf2;border:1px solid #e8d9c5">
  <?php if ($img): ?><img src="<?= esc(base_url($img)) ?>" alt="" style="max-width:100%;border-radius:8px;margin-bottom:16px"><?php endif; ?>
  <?php foreach ($fields as $f):
      $v = $content[$f['field_key']] ?? '';
      if ($v === '' || $f['field_type'] === 'image') { continue; }
      $label = i18n($f['labels'], $f['field_key']);
  ?>
    <?php if ($f['field_type'] === 'url'): ?>
      <p><a href="<?= esc($v, 'attr') ?>" rel="noopener" target="_blank"><?= esc($label) ?></a></p>
    <?php elseif ($f['field_type'] === 'date'): ?>
      <p style="font-size:1.2em"><?= esc(date('j F Y', strtotime($v))) ?></p>
    <?php elseif ($f['field_type'] === 'textarea'): ?>
      <p style="white-space:pre-line"><?= esc($v) ?></p>
    <?php elseif ($f === reset($fields)): ?>
      <h1 style="font-weight:normal;font-size:2em;margin:8px 0"><?= esc($v) ?></h1>
    <?php else: ?>
      <p style="font-size:1.1em;margin:6px 0"><?= esc($v) ?></p>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
