<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1><?= lang('Site.createVideo') ?></h1>
<p><?= lang('Site.videoBrowserNote') ?></p>
<div class="card">
  <p id="status"><?= lang('Site.videoReadyToStart') ?></p>
  <progress id="bar" max="100" value="0" style="width:100%"></progress>
  <p><button id="go" type="button"><?= lang('Site.videoStart') ?></button> <a href="/dashboard"><?= lang('Site.back') ?></a></p>
  <video id="out" controls playsinline style="display:none;width:100%;max-width:300px"></video>
</div>
<script src="/assets/video-render.js"></script>
<script>
(() => {
  const id = <?= (int) $inv['id'] ?>, csrf = <?= json_encode(csrf_hash()) ?>, T = <?= json_encode([
      'loading' => lang('Site.vLoading'), 'fonts' => lang('Site.vFonts'), 'recording' => lang('Site.vRecording'), 'uploading' => lang('Site.vUploading'), 'done' => lang('Site.vDone'),
      'unsupported' => lang('Site.vUnsupported'), 'failed' => lang('Site.vFailed')], JSON_UNESCAPED_UNICODE) ?>;
  const st = document.getElementById('status'), bar = document.getElementById('bar'), go = document.getElementById('go');
  go.onclick = async () => {
    go.disabled = true;
    try {
      if (!window.InviteVideo.supported()) { st.textContent = T.unsupported; return; }
      st.textContent = T.loading;
      const data = await (await fetch('/dashboard/invites/' + id + '/video-data')).json();
      if (data.error) throw new Error(data.error);
      const blob = await window.InviteVideo.render(data, (phase, p) => { st.textContent = T[phase] || phase; bar.value = Math.round(p * 90); });
      st.textContent = T.uploading; bar.value = 95;
      const fd = new FormData(); fd.append('video', blob, 'invite.' + (blob.type.includes('mp4') ? 'mp4' : 'webm'));
      const r = await fetch('/dashboard/invites/' + id + '/video', {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': csrf}});
      if (!r.ok) throw new Error((await r.json()).error || r.status);
      bar.value = 100; st.textContent = T.done;
      const v = document.getElementById('out'); v.src = URL.createObjectURL(blob); v.style.display = 'block';
      setTimeout(() => location.href = '/dashboard', 2500);
    } catch (e) { st.textContent = T.failed + ' ' + e.message; go.disabled = false; }
  };
})();
</script>
<?= $this->endSection() ?>
