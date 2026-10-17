<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.budgetTitle') ?></h1>
<p><?= lang('Site.budgetDesc') ?></p>
<div class="card">
  <label><?= lang('Site.budgetTotal') ?> <input id="total" type="number" min="0" step="1000" value="<?= $currency === 'INR' ? 1000000 : 20000 ?>"></label>
  <table style="width:100%;margin-top:12px;border-collapse:collapse">
    <?php foreach ($cats as $c): ?>
      <tr><td><?= esc($c['label']) ?></td><td style="width:90px"><input class="pct" data-k="<?= esc($c['key'], 'attr') ?>" type="number" min="0" max="100" style="width:70px"> %</td><td class="amt" data-k="<?= esc($c['key'], 'attr') ?>" style="text-align:end"></td></tr>
    <?php endforeach; ?>
    <tr><td><strong><?= lang('Site.budgetLeft') ?></strong></td><td id="sum"></td><td id="left" style="text-align:end;font-weight:600"></td></tr>
  </table>
</div>
<script>
(() => {
  const defaults = {venue: 20, catering: 30, decor: 10, photo: 10, attire: 15, music: 5, invites: 2, misc: 8};
  const fmt = new Intl.NumberFormat(<?= json_encode($locale) ?>, {style: 'currency', currency: <?= json_encode($currency) ?>, maximumFractionDigits: 0});
  const total = document.getElementById('total'), pcts = [...document.querySelectorAll('.pct')];
  let saved = {}; try { saved = JSON.parse(localStorage.getItem('iv_budget') || '{}'); } catch (e) {}
  if (saved.total) total.value = saved.total;
  pcts.forEach(p => p.value = (saved.pct && saved.pct[p.dataset.k] !== undefined) ? saved.pct[p.dataset.k] : defaults[p.dataset.k]);
  function calc() {
    const t = Math.max(0, parseFloat(total.value) || 0); let used = 0; const pct = {};
    pcts.forEach(p => { const v = Math.max(0, Math.min(100, parseFloat(p.value) || 0)); pct[p.dataset.k] = v; used += v;
      document.querySelector('.amt[data-k="' + p.dataset.k + '"]').textContent = fmt.format(t * v / 100); });
    document.getElementById('sum').textContent = used + ' %'; document.getElementById('left').textContent = fmt.format(t * (100 - used) / 100);
    document.getElementById('left').style.color = used > 100 ? '#b00020' : '';
    try { localStorage.setItem('iv_budget', JSON.stringify({total: t, pct})); } catch (e) {}
  }
  total.oninput = calc; pcts.forEach(p => p.oninput = calc); calc();
})();
</script>
<?= $this->endSection() ?>
