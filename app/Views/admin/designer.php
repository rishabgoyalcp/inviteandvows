<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Design: <?= esc($t['slug']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<style>
*{box-sizing:border-box}
body{margin:0;font:14px system-ui,sans-serif;color:#2b2430;background:#e9e4de;height:100vh;display:flex;flex-direction:column}
#top{display:flex;gap:6px;align-items:center;padding:8px 12px;background:#fff;border-bottom:1px solid #d8d0c8;flex-wrap:wrap}
#top .sp{flex:1}
button,select,input,textarea{font:inherit}
button{padding:6px 10px;border:1px solid #c9bfb4;background:#fff;border-radius:6px;cursor:pointer}
button.pri{background:#8a2850;color:#fff;border-color:#8a2850}
button:disabled{opacity:.4;cursor:default}
#main{flex:1;display:grid;grid-template-columns:130px 1fr 270px;min-height:0}
#frames{background:#f6f2ee;border-right:1px solid #d8d0c8;overflow:auto;padding:8px;display:flex;flex-direction:column;gap:6px}
.fr{border:2px solid transparent;border-radius:6px;background:#fff;padding:6px;text-align:center;cursor:pointer;font-size:12px}
.fr.sel{border-color:#8a2850}
#stage{overflow:auto;display:flex;align-items:center;justify-content:center;padding:16px;min-width:0}
#holder{position:relative;box-shadow:0 2px 14px rgba(0,0,0,.25);flex:none}
#cv{position:absolute;left:0;top:0;transform-origin:0 0;overflow:hidden}
.el{position:absolute;cursor:move;user-select:none}
.el.sel{outline:2px solid #2a7de1}
.hd{position:absolute;width:14px;height:14px;background:#fff;border:2px solid #2a7de1;border-radius:3px;z-index:9999}
#props{background:#fff;border-left:1px solid #d8d0c8;overflow:auto;padding:10px}
#props h3{margin:12px 0 6px;font-size:12px;text-transform:uppercase;color:#7a6f66}
#props label{display:flex;justify-content:space-between;align-items:center;gap:6px;margin:4px 0}
#props input[type=number]{width:70px}#props input[type=text],#props textarea,#props select{width:150px}
#props input[type=color]{width:44px;height:26px;padding:0}
#msg{color:#2d7a3a;min-width:80px}
@media(max-width:900px){#main{grid-template-columns:90px 1fr}#props{grid-column:1/3;max-height:40vh}}
</style>
</head>
<body>
<div id="top">
  <strong>Design: <?= esc($t['slug']) ?></strong>
  <button data-add="text">+ Text</button><button data-add="image">+ Image</button><button data-add="rect">+ Rectangle</button><button data-add="ellipse">+ Ellipse</button>
  <button id="dup">Duplicate</button><button id="del">Delete</button><button id="fwd">Forward</button><button id="bwd">Back</button>
  <button id="undo">Undo</button>
  <select id="size" title="Canvas size"><option value="1080x1920">9:16 Story 1080×1920</option><option value="1080x1350">4:5 Post 1080×1350</option><option value="1080x1080">1:1 Square 1080×1080</option></select>
  <span class="sp"></span><span id="msg"></span>
  <a href="/admin/templates">Templates</a>
  <a href="/templates/<?= esc($t['slug'], 'url') ?>" target="_blank" rel="noopener">Public preview</a>
  <button class="pri" id="save">Save design</button>
</div>
<div id="main">
  <div id="frames"></div>
  <div id="stage"><div id="holder"><div id="cv"></div></div></div>
  <div id="props"></div>
</div>
<input type="file" id="file" accept="image/jpeg,image/png,image/webp" hidden>
<script>
(() => {
const URL_BASE = <?= json_encode('/admin/templates/' . (int) $t['id'] . '/design') ?>;
const CSRF = <?= json_encode(csrf_hash()) ?>;
const FIELDS = <?= json_encode($fields, JSON_UNESCAPED_UNICODE) ?>;
const FONTS = <?= json_encode($fonts) ?>;
let design = <?= json_encode($design, JSON_UNESCAPED_UNICODE) ?>;
const $ = s => document.querySelector(s);
let fi = 0, sel = null, scale = 0.3, hist = [], loadedFonts = new Set();
const uid = () => 'e' + Math.random().toString(36).slice(2, 9);
const byKey = Object.fromEntries(FIELDS.map(f => [f.key, f]));
const textFields = FIELDS.filter(f => f.type !== 'image' && f.type !== 'url');
const imageFields = FIELDS.filter(f => f.type === 'image');

if (!design.frames.length) design.frames.push(newFrame());
function newFrame() { return {id: uid(), seconds: 3.5, bg: {type: 'color', color: '#fffaf2', color2: '#f3e3d3', angle: 180, path: ''}, elements: []}; }
const frame = () => design.frames[fi];
const element = () => frame().elements.find(e => e.id === sel);
const snap = () => { hist.push(JSON.stringify(design)); if (hist.length > 60) hist.shift(); };

function loadFont(name) {
  if (!name || name === 'auto' || loadedFonts.has(name)) return;
  loadedFonts.add(name);
  const l = document.createElement('link'); l.rel = 'stylesheet';
  l.href = 'https://fonts.googleapis.com/css2?family=' + name.replace(/ /g, '+') + (name === 'Great Vibes' ? '' : ':wght@300;400;600;700') + '&display=swap';
  document.head.appendChild(l);
}

function sample(e) {
  if (e.src !== 'field') return e.value;
  const f = byKey[e.field]; if (!f) return '';
  if (f.type === 'date') return '5 December 2026';
  if (f.type === 'time') return '18:30';
  return f.label;
}

function fit() {
  const st = $('#stage'), W = design.canvas.w, H = design.canvas.h;
  scale = Math.min((st.clientWidth - 40) / W, (st.clientHeight - 40) / H);
  $('#holder').style.width = W * scale + 'px'; $('#holder').style.height = H * scale + 'px';
  $('#cv').style.width = W + 'px'; $('#cv').style.height = H + 'px'; $('#cv').style.transform = 'scale(' + scale + ')';
}

function bgCss(b) {
  if (b.type === 'gradient') return `linear-gradient(${b.angle}deg,${b.color},${b.color2})`;
  if (b.type === 'image' && b.path) return `${b.color} url('/${b.path}') center/cover no-repeat`;
  return b.color;
}

function drawCanvas() {
  const cv = $('#cv'), f = frame(); cv.innerHTML = ''; cv.style.background = bgCss(f.bg);
  f.elements.forEach(e => {
    const d = document.createElement('div'); d.className = 'el' + (e.id === sel ? ' sel' : ''); d.dataset.id = e.id;
    Object.assign(d.style, {left: e.x + 'px', top: e.y + 'px', width: e.w + 'px', height: e.h + 'px', opacity: e.opacity, transform: e.rot ? `rotate(${e.rot}deg)` : ''});
    if (e.type === 'text') {
      loadFont(e.font);
      Object.assign(d.style, {display: 'flex', alignItems: 'center', justifyContent: {left: 'flex-start', center: 'center', right: 'flex-end'}[e.align], textAlign: e.align,
        fontFamily: e.font === 'auto' ? "'Noto Sans', sans-serif" : `'${e.font}', 'Noto Sans', sans-serif`, fontSize: e.size + 'px', fontWeight: e.weight, color: e.color, lineHeight: e.lh,
        letterSpacing: e.ls + 'px', textTransform: e.upper ? 'uppercase' : '', whiteSpace: 'pre-wrap', overflowWrap: 'anywhere'});
      const s = document.createElement('span'); s.textContent = sample(e) || '(empty)'; d.appendChild(s);
    } else if (e.type === 'image') {
      d.style.borderRadius = e.radius + 'px'; d.style.background = '#cfc7bf';
      if (e.src === 'static' && e.path) { d.style.background = `url('/${e.path}') center/${e.fit} no-repeat`; }
      else { d.style.display = 'flex'; d.style.alignItems = 'center'; d.style.justifyContent = 'center'; d.style.color = '#6b625a'; d.style.fontSize = '36px'; d.textContent = e.src === 'field' ? '[' + (byKey[e.field]?.label || 'photo') + ']' : 'image'; }
    } else {
      d.style.background = e.fill; d.style.borderRadius = e.shape === 'ellipse' ? '50%' : e.radius + 'px';
      if (e.strokeW) d.style.border = e.strokeW + 'px solid ' + e.stroke;
    }
    cv.appendChild(d);
    if (e.id === sel) ['nw', 'ne', 'sw', 'se'].forEach(c => {
      const h = document.createElement('div'); h.className = 'hd'; h.dataset.h = c; h.style.cursor = c + '-resize';
      h.style.left = (c.includes('w') ? -9 : e.w - 5) + 'px'; h.style.top = (c.includes('n') ? -9 : e.h - 5) + 'px';
      h.style.transform = 'scale(' + 1 / scale + ')'; h.style.transformOrigin = 'center'; d.appendChild(h);
    });
  });
}

function drawFrames() {
  const box = $('#frames'); box.innerHTML = '';
  design.frames.forEach((f, i) => {
    const d = document.createElement('div'); d.className = 'fr' + (i === fi ? ' sel' : '');
    const w = 100, h = Math.round(100 * design.canvas.h / design.canvas.w);
    d.innerHTML = `<div style="width:${w}px;height:${Math.min(h, 170)}px;margin:0 auto 4px;background:${bgCss(f.bg)};border:1px solid #ccc"></div>Scene ${i + 1} · ${f.elements.length}`;
    d.onclick = () => { fi = i; sel = null; render(); }; box.appendChild(d);
  });
  const add = document.createElement('button'); add.textContent = '+ Scene'; add.onclick = () => { snap(); design.frames.push(newFrame()); fi = design.frames.length - 1; sel = null; render(); };
  const dup = document.createElement('button'); dup.textContent = 'Copy scene'; dup.onclick = () => { snap(); const c = JSON.parse(JSON.stringify(frame())); c.id = uid(); c.elements.forEach(e => e.id = uid()); design.frames.splice(fi + 1, 0, c); fi++; sel = null; render(); };
  const rm = document.createElement('button'); rm.textContent = 'Delete scene'; rm.onclick = () => { if (design.frames.length < 2 || !confirm('Delete this scene?')) return; snap(); design.frames.splice(fi, 1); fi = Math.max(0, fi - 1); sel = null; render(); };
  const up = document.createElement('button'); up.textContent = 'Move up'; up.onclick = () => { if (fi < 1) return; snap(); [design.frames[fi - 1], design.frames[fi]] = [design.frames[fi], design.frames[fi - 1]]; fi--; render(); };
  box.append(add, dup, rm, up);
}

function row(label, input) { const l = document.createElement('label'); l.append(label, input); return l; }
function num(obj, key, min, max, step = 1, after) { const i = document.createElement('input'); i.type = 'number'; i.value = obj[key]; i.min = min; i.max = max; i.step = step;
  i.onchange = () => { snap(); obj[key] = Math.max(min, Math.min(max, parseFloat(i.value) || 0)); (after || render)(); }; return i; }
function txt(obj, key, area) { const i = document.createElement(area ? 'textarea' : 'input'); if (!area) i.type = 'text'; i.value = obj[key] ?? ''; i.onchange = () => { snap(); obj[key] = i.value; render(); }; return i; }
function color(obj, key) { const i = document.createElement('input'); i.type = 'color'; i.value = obj[key]; i.onchange = () => { snap(); obj[key] = i.value; render(); }; return i; }
function pick(obj, key, opts, after) { const s = document.createElement('select'); opts.forEach(o => { const op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; s.appendChild(op); }); s.value = obj[key];
  s.onchange = () => { snap(); obj[key] = s.value; (after || render)(); }; return s; }
function heading(t) { const h = document.createElement('h3'); h.textContent = t; return h; }
function upload(cb) { const f = $('#file'); f.value = ''; f.onchange = async () => { if (!f.files[0]) return; const fd = new FormData(); fd.append('file', f.files[0]);
  const r = await fetch(URL_BASE + '/upload', {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': CSRF}}); const j = await r.json(); if (r.ok) { snap(); cb(j.path); render(); } else flash(j.error || 'Upload failed', true); }; f.click(); }

function drawProps() {
  const p = $('#props'); p.innerHTML = '';
  const e = element(), f = frame();
  if (e) {
    p.append(heading(e.type + ' element'));
    p.append(row('X', num(e, 'x', -4000, 4000)), row('Y', num(e, 'y', -4000, 4000)), row('Width', num(e, 'w', 1, 4000)), row('Height', num(e, 'h', 1, 4000)),
             row('Rotate°', num(e, 'rot', -360, 360)), row('Opacity', num(e, 'opacity', 0, 1, 0.05)));
    if (e.type === 'text') {
      p.append(heading('Text'));
      p.append(row('Source', pick(e, 'src', [['static', 'Fixed text'], ['field', 'Customer field']])));
      if (e.src === 'field') p.append(row('Field', pick(e, 'field', textFields.map(x => [x.key, x.label]))));
      else p.append(row('Text', txt(e, 'value', true)));
      p.append(row('Font', pick(e, 'font', [['auto', 'Language font (auto)'], ...FONTS.map(x => [x, x])])), row('Size', num(e, 'size', 8, 600)),
               row('Weight', pick(e, 'weight', [[300, 'Light'], [400, 'Regular'], [600, 'Semibold'], [700, 'Bold']], () => { e.weight = +e.weight; render(); })), row('Colour', color(e, 'color')),
               row('Align', pick(e, 'align', [['left', 'Left'], ['center', 'Centre'], ['right', 'Right']])), row('Line height', num(e, 'lh', 0.8, 3, 0.05)), row('Letter space', num(e, 'ls', -10, 40, 0.5)));
      const u = document.createElement('input'); u.type = 'checkbox'; u.checked = e.upper; u.onchange = () => { snap(); e.upper = u.checked; render(); }; p.append(row('UPPERCASE', u));
    } else if (e.type === 'image') {
      p.append(heading('Image'), row('Source', pick(e, 'src', [['static', 'Fixed image'], ['field', 'Customer photo']])));
      if (e.src === 'field') p.append(row('Field', pick(e, 'field', imageFields.map(x => [x.key, x.label]))));
      else { const b = document.createElement('button'); b.textContent = e.path ? 'Replace image' : 'Upload image'; b.onclick = () => upload(path => e.path = path); p.append(b); }
      p.append(row('Fit', pick(e, 'fit', [['cover', 'Cover'], ['contain', 'Contain']])), row('Corner radius', num(e, 'radius', 0, 2000)));
    } else {
      p.append(heading('Shape'), row('Fill', color(e, 'fill')), row('Corner radius', num(e, 'radius', 0, 2000)), row('Border', num(e, 'strokeW', 0, 100)), row('Border colour', color(e, 'stroke')));
    }
  }
  p.append(heading('Scene ' + (fi + 1)));
  p.append(row('Seconds', num(f, 'seconds', 1, 15, 0.5)), row('Background', pick(f.bg, 'type', [['color', 'Colour'], ['gradient', 'Gradient'], ['image', 'Image']])), row('Colour', color(f.bg, 'color')));
  if (f.bg.type === 'gradient') p.append(row('Colour 2', color(f.bg, 'color2')), row('Angle°', num(f.bg, 'angle', 0, 360)));
  if (f.bg.type === 'image') { const b = document.createElement('button'); b.textContent = f.bg.path ? 'Replace background' : 'Upload background'; b.onclick = () => upload(path => f.bg.path = path); p.append(b); }
}

function render() { fit(); drawCanvas(); drawFrames(); drawProps(); $('#undo').disabled = !hist.length; }

function add(kind) {
  snap(); const W = design.canvas.w, H = design.canvas.h, base = {id: uid(), x: Math.round(W * .1), y: Math.round(H * .4), w: Math.round(W * .8), h: 160, rot: 0, opacity: 1};
  let e;
  if (kind === 'text') e = {...base, type: 'text', src: textFields[0] ? 'field' : 'static', field: textFields[0]?.key || '', value: 'Your text', font: 'auto', size: 72, weight: 600, color: '#3b2a2a', align: 'center', lh: 1.3, ls: 0, upper: false};
  else if (kind === 'image') e = {...base, type: 'image', w: Math.round(W * .6), h: Math.round(W * .6), x: Math.round(W * .2), src: imageFields[0] ? 'field' : 'static', field: imageFields[0]?.key || '', path: '', fit: 'cover', radius: 0};
  else e = {...base, type: 'shape', shape: kind === 'ellipse' ? 'ellipse' : 'rect', w: 300, h: 300, fill: '#d9b99b', stroke: '#000000', strokeW: 0, radius: 0};
  frame().elements.push(e); sel = e.id; render();
}
document.querySelectorAll('[data-add]').forEach(b => b.onclick = () => add(b.dataset.add));
$('#del').onclick = () => { if (!sel) return; snap(); frame().elements = frame().elements.filter(e => e.id !== sel); sel = null; render(); };
$('#dup').onclick = () => { const e = element(); if (!e) return; snap(); const c = {...e, id: uid(), x: e.x + 30, y: e.y + 30}; frame().elements.push(c); sel = c.id; render(); };
$('#fwd').onclick = () => { const a = frame().elements, i = a.findIndex(e => e.id === sel); if (i < 0 || i === a.length - 1) return; snap(); [a[i], a[i + 1]] = [a[i + 1], a[i]]; render(); };
$('#bwd').onclick = () => { const a = frame().elements, i = a.findIndex(e => e.id === sel); if (i < 1) return; snap(); [a[i], a[i - 1]] = [a[i - 1], a[i]]; render(); };
$('#undo').onclick = () => { if (!hist.length) return; design = JSON.parse(hist.pop()); fi = Math.min(fi, design.frames.length - 1); if (!element()) sel = null; render(); };
$('#size').value = design.canvas.w + 'x' + design.canvas.h; if (!$('#size').value) $('#size').value = '1080x1920';
$('#size').onchange = () => { snap(); const [w, h] = $('#size').value.split('x').map(Number); design.canvas = {w, h}; render(); };

function flash(t, bad) { const m = $('#msg'); m.textContent = t; m.style.color = bad ? '#b00020' : '#2d7a3a'; setTimeout(() => m.textContent = '', 3500); }
$('#save').onclick = async () => {
  const r = await fetch(URL_BASE, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF}, body: JSON.stringify(design)});
  if (r.ok) { design = (await r.json()).design; render(); flash('Saved'); } else flash('Save failed (' + r.status + ')', true);
};

// drag, resize, select
let drag = null;
$('#cv').addEventListener('pointerdown', ev => {
  const h = ev.target.closest('.hd'), el = ev.target.closest('.el');
  if (!el) { sel = null; render(); return; }
  sel = el.dataset.id; const e = element(); snap();
  drag = {mode: h ? h.dataset.h : 'move', sx: ev.clientX, sy: ev.clientY, o: {...e}};
  $('#cv').setPointerCapture(ev.pointerId); drawProps(); drawCanvas(); ev.preventDefault();
});
$('#cv').addEventListener('pointermove', ev => {
  if (!drag) return; const e = element(), dx = (ev.clientX - drag.sx) / scale, dy = (ev.clientY - drag.sy) / scale, o = drag.o;
  if (drag.mode === 'move') { e.x = Math.round(o.x + dx); e.y = Math.round(o.y + dy); }
  else {
    if (drag.mode.includes('e')) e.w = Math.max(10, Math.round(o.w + dx)); if (drag.mode.includes('s')) e.h = Math.max(10, Math.round(o.h + dy));
    if (drag.mode.includes('w')) { e.w = Math.max(10, Math.round(o.w - dx)); e.x = Math.round(o.x + o.w - e.w); }
    if (drag.mode.includes('n')) { e.h = Math.max(10, Math.round(o.h - dy)); e.y = Math.round(o.y + o.h - e.h); }
  }
  drawCanvas();
});
const end = () => { if (drag) { drag = null; drawProps(); drawCanvas(); } };
$('#cv').addEventListener('pointerup', end); $('#cv').addEventListener('pointercancel', end);
document.addEventListener('keydown', ev => {
  if (/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) return;
  const e = element(); if ((ev.ctrlKey || ev.metaKey) && ev.key === 'z') { $('#undo').click(); ev.preventDefault(); return; }
  if (!e) return; const s = ev.shiftKey ? 10 : 1;
  if (ev.key === 'Delete' || ev.key === 'Backspace') { $('#del').click(); ev.preventDefault(); }
  else if (ev.key.startsWith('Arrow')) { snap(); if (ev.key === 'ArrowLeft') e.x -= s; if (ev.key === 'ArrowRight') e.x += s; if (ev.key === 'ArrowUp') e.y -= s; if (ev.key === 'ArrowDown') e.y += s; render(); ev.preventDefault(); }
});
window.addEventListener('resize', render);
window.addEventListener('beforeunload', ev => { if (hist.length && JSON.stringify(design) !== lastSaved) { ev.preventDefault(); } });
let lastSaved = JSON.stringify(design);
$('#save').addEventListener('click', () => setTimeout(() => lastSaved = JSON.stringify(design), 800));
render();
})();
</script>
</body>
</html>
