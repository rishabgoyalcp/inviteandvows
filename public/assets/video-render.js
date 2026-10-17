/*
 * Renders a video invite inside the visitor's browser: draws each scene on a canvas, cross-fades between
 * scenes and records the canvas with MediaRecorder. No server, Chromium or ffmpeg is involved.
 * Input is the JSON from /dashboard/invites/{id}/video-data (text and image URLs already resolved).
 */
(function () {
  'use strict';
  const FADE = 0.6;
  const MAX_W = 720; // 720x1280 keeps phones fast; the server renderer makes the 1080 version

  function pickMime() {
    const list = ['video/mp4;codecs=avc1.42E01E', 'video/mp4', 'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm'];
    return list.find(m => window.MediaRecorder && MediaRecorder.isTypeSupported(m)) || '';
  }

  function supported() {
    return !!(window.MediaRecorder && HTMLCanvasElement.prototype.captureStream && pickMime());
  }

  function loadImage(url) {
    return new Promise(res => {
      const i = new Image(); i.crossOrigin = 'anonymous';
      i.onload = () => res(i); i.onerror = () => res(null); i.src = url;
    });
  }

  function withTimeout(p, ms) { return Promise.race([p, new Promise(r => setTimeout(r, ms))]); }

  async function loadFonts(data) {
    if (data.fontsCss) {
      await withTimeout(new Promise(res => {
        const l = document.createElement('link'); l.rel = 'stylesheet'; l.href = data.fontsCss; l.onload = l.onerror = res; document.head.appendChild(l);
      }), 8000);
    }
    const jobs = [];
    data.scenes.forEach(s => s.items.forEach(it => {
      if (it.type === 'text') jobs.push(document.fonts.load(`${it.weight} 40px "${it.family}"`, it.text));
    }));
    await withTimeout(Promise.all(jobs), 8000);
  }

  function rr(ctx, x, y, w, h, r) {
    r = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    if (r <= 0) { ctx.rect(x, y, w, h); return; }
    ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
  }

  function drawCover(ctx, img, x, y, w, h, fit) {
    const k = fit === 'contain' ? Math.min(w / img.width, h / img.height) : Math.max(w / img.width, h / img.height);
    const dw = img.width * k, dh = img.height * k;
    ctx.drawImage(img, x + (w - dw) / 2, y + (h - dh) / 2, dw, dh);
  }

  function wrap(ctx, text, maxW) {
    const out = [];
    String(text).split('\n').forEach(par => {
      let line = '';
      par.split(' ').forEach(word => {
        const test = line ? line + ' ' + word : word;
        if (ctx.measureText(test).width <= maxW || !line) {
          line = test;
          // a single word wider than the box is broken by characters
          while (ctx.measureText(line).width > maxW && line.length > 1) {
            let n = line.length - 1; while (n > 1 && ctx.measureText(line.slice(0, n)).width > maxW) n--;
            out.push(line.slice(0, n)); line = line.slice(n);
          }
        } else { out.push(line); line = word; }
      });
      out.push(line);
    });
    return out;
  }

  function drawBackground(ctx, bg, img, W, H) {
    if (bg.type === 'gradient') {
      const a = (bg.angle || 0) * Math.PI / 180, dx = Math.sin(a), dy = -Math.cos(a), len = Math.abs(W * dx) + Math.abs(H * dy);
      const g = ctx.createLinearGradient(W / 2 - dx * len / 2, H / 2 - dy * len / 2, W / 2 + dx * len / 2, H / 2 + dy * len / 2);
      g.addColorStop(0, bg.color); g.addColorStop(1, bg.color2); ctx.fillStyle = g;
    } else ctx.fillStyle = bg.color;
    ctx.fillRect(0, 0, W, H);
    if (bg.type === 'image' && img) drawCover(ctx, img, 0, 0, W, H, 'cover');
  }

  function drawItem(ctx, it, S, rtl, images) {
    ctx.save();
    ctx.globalAlpha *= it.opacity;
    ctx.translate((it.x + it.w / 2) * S, (it.y + it.h / 2) * S);
    ctx.rotate((it.rot || 0) * Math.PI / 180);
    ctx.translate(-it.w * S / 2, -it.h * S / 2);
    const w = it.w * S, h = it.h * S;
    if (it.type === 'shape') {
      if (it.shape === 'ellipse') { ctx.beginPath(); ctx.ellipse(w / 2, h / 2, w / 2, h / 2, 0, 0, Math.PI * 2); } else rr(ctx, 0, 0, w, h, it.radius * S);
      ctx.fillStyle = it.fill; ctx.fill();
      if (it.strokeW > 0) { ctx.lineWidth = it.strokeW * S; ctx.strokeStyle = it.stroke; ctx.stroke(); }
    } else if (it.type === 'image') {
      const img = images.get(it.url);
      if (img) { rr(ctx, 0, 0, w, h, it.radius * S); ctx.clip(); drawCover(ctx, img, 0, 0, w, h, it.fit); }
    } else if (it.type === 'text') {
      const size = it.size * S;
      ctx.font = `${it.weight} ${size}px "${it.family}", "Noto Sans", sans-serif`;
      ctx.fillStyle = it.color; ctx.textBaseline = 'middle'; ctx.textAlign = it.align;
      if ('letterSpacing' in ctx) ctx.letterSpacing = (it.ls * S) + 'px';
      if ('direction' in ctx) ctx.direction = rtl ? 'rtl' : 'ltr';
      const lines = wrap(ctx, it.upper ? it.text.toUpperCase() : it.text, w), lh = size * it.lh, top = (h - lines.length * lh) / 2;
      const x = it.align === 'left' ? 0 : it.align === 'right' ? w : w / 2;
      lines.forEach((l, k) => ctx.fillText(l, x, top + lh * (k + 0.5)));
    }
    ctx.restore();
  }

  async function render(data, onProgress) {
    onProgress = onProgress || function () {};
    const mime = pickMime();
    if (!mime) throw new Error('unsupported');
    const W = data.canvas.w, H = data.canvas.h, S = Math.min(1, MAX_W / W);
    const cw = Math.round(W * S / 2) * 2, ch = Math.round(H * S / 2) * 2;

    onProgress('fonts', 0);
    await loadFonts(data);
    const images = new Map();
    const urls = new Set();
    data.scenes.forEach(s => { if (s.bg.url) urls.add(s.bg.url); s.items.forEach(i => i.url && urls.add(i.url)); });
    await Promise.all([...urls].map(async u => images.set(u, await loadImage(u))));

    const canvas = document.createElement('canvas'); canvas.width = cw; canvas.height = ch;
    canvas.style.cssText = 'position:fixed;left:-9999px;top:0'; document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    const starts = []; let acc = 0;
    data.scenes.forEach((s, i) => { starts.push(acc); acc += s.seconds - (i < data.scenes.length - 1 ? FADE : 0); });
    const total = acc;

    function frameAt(t) {
      ctx.globalAlpha = 1; ctx.fillStyle = '#000'; ctx.fillRect(0, 0, cw, ch);
      data.scenes.forEach((s, i) => {
        if (t < starts[i] || t >= starts[i] + s.seconds) return;
        ctx.save();
        ctx.globalAlpha = (i > 0 && t < starts[i] + FADE) ? (t - starts[i]) / FADE : 1;
        drawBackground(ctx, s.bg, s.bg.url ? images.get(s.bg.url) : null, cw, ch);
        s.items.forEach(it => drawItem(ctx, it, S, data.rtl, images));
        ctx.restore();
      });
    }

    const stream = canvas.captureStream(30);
    const rec = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 4000000 });
    const chunks = [];
    rec.ondataavailable = e => e.data && e.data.size && chunks.push(e.data);
    const stopped = new Promise(res => rec.onstop = res);
    frameAt(0); rec.start(500);

    await new Promise(res => {
      const t0 = performance.now();
      const timer = setInterval(() => {
        const t = (performance.now() - t0) / 1000;
        if (t >= total) { frameAt(total - 0.001); clearInterval(timer); res(); return; }
        frameAt(t); onProgress('recording', t / total);
      }, 33);
    });
    await new Promise(r => setTimeout(r, 200)); // let the last frame reach the encoder
    rec.stop(); await stopped;
    stream.getTracks().forEach(t => t.stop()); canvas.remove();
    return new Blob(chunks, { type: rec.mimeType || mime });
  }

  window.InviteVideo = { supported, render };
})();
