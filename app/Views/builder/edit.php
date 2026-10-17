<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= esc(i18n($tpl['names'], $tpl['slug'])) ?></h1>
<style>
.b{display:grid;grid-template-columns:minmax(260px,1fr) minmax(280px,1fr);gap:16px}
@media(max-width:700px){.b{grid-template-columns:1fr}}
.b label{display:block;margin-top:10px;font-size:.9em}
.b input[type=text],.b input[type=date],.b input[type=time],.b input[type=url],.b textarea{width:100%;box-sizing:border-box;padding:8px}
iframe{width:100%;height:600px;border:1px solid #eadfd6;border-radius:10px;background:#fff}
</style>
<div class="b">
  <form id="f" class="card" onsubmit="return false">
    <?php foreach ($fields as $f):
        $k = $f['field_key']; $v = $content[$k] ?? ''; $label = i18n($f['labels'], $k); ?>
      <label><?= esc($label) ?><?= $f['is_required'] ? ' *' : '' ?>
      <?php if ($f['field_type'] === 'textarea'): ?>
        <textarea name="<?= esc($k, 'attr') ?>" rows="4"><?= esc($v) ?></textarea>
      <?php elseif ($f['field_type'] === 'image'): ?>
        <input type="file" accept="image/jpeg,image/png,image/webp" data-field="<?= esc($k, 'attr') ?>">
      <?php else: ?>
        <input type="<?= in_array($f['field_type'], ['date', 'time', 'url'], true) ? $f['field_type'] : 'text' ?>" name="<?= esc($k, 'attr') ?>" value="<?= esc($v, 'attr') ?>">
      <?php endif; ?>
      </label>
    <?php endforeach; ?>
    <p><span id="st"></span></p>
    <a href="/checkout/<?= esc($inv['edit_token'], 'url') ?>"><button type="button"><?= lang('Site.continue') ?></button></a>
  </form>
  <iframe id="pv" sandbox="allow-popups allow-popups-to-escape-sandbox" title="Preview"></iframe>
</div>
<script>
(() => {
  const token = <?= json_encode($inv['edit_token']) ?>, csrf = <?= json_encode(csrf_hash()) ?>, f = document.getElementById('f'), pv = document.getElementById('pv'), st = document.getElementById('st');
  const post = (url, body) => fetch(url, {method: 'POST', body, headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'}});
  let timer;
  async function refresh(save) {
    const fd = new FormData(f);
    pv.srcdoc = await (await post('/build/' + token + '/preview', fd)).text();
    if (save) { const r = await post('/build/' + token + '/save', fd); st.textContent = r.ok ? <?= json_encode(lang('Site.saved')) ?> : ''; }
  }
  f.addEventListener('input', () => { clearTimeout(timer); st.textContent = '…'; timer = setTimeout(() => refresh(true), 600); });
  f.querySelectorAll('input[type=file]').forEach(inp => inp.addEventListener('change', async () => {
    if (!inp.files[0]) return;
    const fd = new FormData(); fd.append('field', inp.dataset.field); fd.append('file', inp.files[0]);
    const r = await post('/build/' + token + '/upload', fd); const j = await r.json();
    st.textContent = r.ok ? <?= json_encode(lang('Site.saved')) ?> : (j.error || 'Error'); refresh(false);
  }));
  refresh(false);
})();
</script>
<?= $this->endSection() ?>
