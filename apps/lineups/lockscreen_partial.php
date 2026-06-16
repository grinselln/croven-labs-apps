<?php
// ─── lockscreen_partial.php ───────────────────────────────────────────────
// Data is loaded client-side via lineups_api.php?action=lockscreen_data
// Listens for the 'festivalChanged' custom event dispatched by index.php
// ─────────────────────────────────────────────────────────────────────────
?>

<style>
/* ── LOCKSCREEN PARTIAL STYLES ─────────────────────────────────────────── */
/* Scoped with .ls- prefix to avoid clashing with index.php tab styles     */

.ls-wrap {
    max-width: 960px;
    margin: 0 auto;
}

.ls-header {
    text-align: center;
    padding: 28px 24px 20px;
    border-bottom: 1px solid #2a2a38;
    background: linear-gradient(180deg, #0d0d1a 0%, transparent 100%);
    border-radius: 12px 12px 0 0;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}
.ls-header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 50%; transform: translateX(-50%);
    width: 500px; height: 1px;
    background: linear-gradient(90deg, transparent, #00e5ff, #b388ff, #ffd740, #ff5252, transparent);
}
.ls-page-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(28px, 5vw, 52px);
    letter-spacing: 0.06em;
    background: linear-gradient(135deg, #fff 0%, #00e5ff 50%, #b388ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 0 20px rgba(0,229,255,0.2));
}
.ls-page-sub {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.3em;
    color: #7a7a99;
    text-transform: uppercase;
    margin-top: 4px;
}

/* ── Inner day tabs ── */
.ls-tab-nav {
    display: flex;
    gap: 4px;
    border-bottom: 1px solid #2a2a38;
    margin-bottom: 0;
    background: #111118;
    border-radius: 0;
}
.ls-tab-btn {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 20px;
    letter-spacing: 0.1em;
    padding: 10px 24px;
    background: none;
    border: none;
    color: #7a7a99;
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
}
.ls-tab-btn::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 0; right: 0;
    height: 2px;
    transform: scaleX(0);
    transition: transform 0.2s ease;
}
.ls-tab-btn:hover { color: #e8e8f0; }
.ls-tab-btn.active { color: #fff; }
.ls-tab-btn.active::after { transform: scaleX(1); background: var(--ls-day-color, #00e5ff); }

/* ── Day panels ── */
.ls-tab-panel { display: none; padding: 24px 0 48px; }
.ls-tab-panel.active { display: block; }

.ls-empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #7a7a99;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 16px;
    letter-spacing: 0.1em;
}

/* ── Two-column layout ── */
.ls-panel-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 700px) {
    .ls-panel-grid { grid-template-columns: 1fr; }
}

/* ── HTML Source box ── */
.ls-source-box {
    background: #111118;
    border: 1px solid #2a2a38;
    border-radius: 12px;
    overflow: hidden;
}
.ls-source-box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #2a2a38;
    background: #18181f;
}
.ls-source-box-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #7a7a99;
}
.ls-copy-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid #2a2a38;
    background: transparent;
    color: #7a7a99;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ls-copy-btn:hover { border-color: #00e5ff; color: #00e5ff; }
.ls-copy-btn.copied { border-color: #1D9E75; color: #1D9E75; }
.ls-source-pre {
    padding: 16px;
    font-family: 'Fira Code', 'Courier New', monospace;
    font-size: 10.5px;
    line-height: 1.6;
    color: #8b9eb5;
    white-space: pre;
    overflow-x: auto;
    max-height: 520px;
    overflow-y: auto;
    background: #0d0d14;
    scrollbar-width: thin;
    scrollbar-color: #2a2a38 transparent;
}

/* ── Image panel ── */
.ls-image-box {
    background: #111118;
    border: 1px solid #2a2a38;
    border-radius: 12px;
    overflow: hidden;
}
.ls-image-box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #2a2a38;
    background: #18181f;
}
.ls-image-box-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #7a7a99;
}
.ls-generate-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid #ffd740;
    background: transparent;
    color: #ffd740;
    cursor: pointer;
    transition: all 0.2s;
}
.ls-generate-btn:hover {
    background: #ffd740;
    color: #000;
    box-shadow: 0 0 16px rgba(255,215,64,0.3);
}
.ls-generate-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.ls-image-body {
    padding: 16px;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
}
.ls-image-placeholder {
    text-align: center;
    color: #7a7a99;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 13px;
    letter-spacing: 0.1em;
}
.ls-image-placeholder svg { display: block; margin: 0 auto 12px; opacity: 0.3; }
.ls-image-preview {
    max-width: 100%;
    border-radius: 8px;
    border: 1px solid #2a2a38;
    display: none;
}
.ls-download-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 9px 22px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #1D9E75, #20B2AA);
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    display: none;
    transition: opacity 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 16px rgba(29,158,117,0.3);
}
.ls-download-btn:hover {
    opacity: 0.85;
    box-shadow: 0 4px 24px rgba(29,158,117,0.5);
}
.ls-generating-msg {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    letter-spacing: 0.1em;
    color: #7a7a99;
    display: none;
}

/* ── Export mode selector ── */
.ls-mode-bar {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 14px 20px;
    background: #0d0d14;
    border-bottom: 1px solid #2a2a38;
    flex-wrap: wrap;
    gap: 8px;
}
.ls-mode-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: #7a7a99;
    margin-right: 4px;
    white-space: nowrap;
}
.ls-mode-options {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.ls-mode-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 6px 14px;
    border-radius: 6px;
    border: 1px solid #2a2a38;
    background: transparent;
    color: #7a7a99;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
}
.ls-mode-btn:hover { border-color: #b388ff; color: #b388ff; }
.ls-mode-btn.active {
    border-color: #b388ff;
    background: rgba(179,136,255,0.12);
    color: #b388ff;
    box-shadow: 0 0 10px rgba(179,136,255,0.15);
}
.ls-mode-hint {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 11px;
    letter-spacing: 0.06em;
    color: #4a4a62;
    margin-left: auto;
    white-space: nowrap;
}

/* ── Off-screen render frames ── */
.ls-render-frame-wrap {
    position: fixed;
    left: -9999px;
    top: 0;
    pointer-events: none;
    z-index: -1;
}
.ls-render-frame { width: 390px; border: none; background: transparent; }

/* ── DB error ── */
.ls-db-error {
    background: rgba(255,82,82,0.08);
    border: 1px solid #ff5252;
    border-radius: 10px;
    padding: 20px 24px;
    margin: 24px 0;
    font-size: 13px;
    color: #ff8a80;
}
</style>

<!-- Load fonts & html2canvas if not already present -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@400;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


<div class="ls-wrap">

  <div class="ls-header">
    <div class="ls-page-title">Lockscreen Export</div>
    <div class="ls-page-sub" id="ls-page-sub">Select a festival to load</div>
  </div>

  <!-- Export mode selector -->
  <div class="ls-mode-bar">
    <span class="ls-mode-label">Export mode</span>
    <div class="ls-mode-options">
      <button class="ls-mode-btn active" data-mode="a" title="Clips to exactly 412×915px — one screen, crisp edge">A · Exact Screen</button>
      <button class="ls-mode-btn" data-mode="c" title="Splits long schedules into multiple 412×915 images">C · Pages</button>
      <button class="ls-mode-btn" data-mode="d" title="Shrinks content until everything fits in 412×915">D · Shrink-fit</button>
    </div>
    <span class="ls-mode-hint" id="ls-mode-hint">412 × 915 · clips content</span>
  </div>

  <div id="ls-loading-state" style="text-align:center;padding:60px 20px;color:#7a7a99;font-family:'Barlow Condensed',sans-serif;font-size:15px;letter-spacing:0.1em;text-transform:uppercase;">
    Select a festival to load the lockscreen
  </div>

  <div id="ls-error-state" style="display:none;" class="ls-db-error"></div>

  <div id="ls-content" style="display:none;">
    <!-- Inner day tab nav — built dynamically -->
    <nav class="ls-tab-nav" id="ls-tab-nav"></nav>

    <!-- Day panels — built dynamically -->
    <div id="ls-panels"></div>
  </div>

</div>

<!-- Off-screen iframes for rendering — built dynamically -->
<div class="ls-render-frame-wrap" id="ls-render-frames"></div>

<script>
(function () {

  let lsDays        = [];
  let lsStageColors = {};
  let lsDayAccents  = {};
  let lsSchedule    = {};
  let lsDayHtmlMap  = {};
  let currentFestId = null;

  const elPageSub     = document.getElementById('ls-page-sub');
  const elLoadState   = document.getElementById('ls-loading-state');
  const elErrorState  = document.getElementById('ls-error-state');
  const elContent     = document.getElementById('ls-content');
  const elTabNav      = document.getElementById('ls-tab-nav');
  const elPanels      = document.getElementById('ls-panels');
  const elFrameWrap   = document.getElementById('ls-render-frames');

  // ── Helpers ───────────────────────────────────────────────────────────────
  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function showState(which) {
    elLoadState.style.display  = which === 'loading' ? 'block' : 'none';
    elErrorState.style.display = which === 'error'   ? 'block' : 'none';
    elContent.style.display    = which === 'data'    ? 'block' : 'none';
  }

  // ── Build the exportable iframe HTML for a single day ────────────────────
  function buildDayHtml(day, bands, stageColors, dayAccents) {
    const accent      = dayAccents[day] || {};
    const accentColor = accent.color || '#ffffff';
    const accentDim   = accent.dim   || '#aaaaaa';
    const bg1         = accent.bg1   || '#0a0a0f';
    const bg2         = accent.bg2   || '#050509';

    let cards = '';
    bands.forEach(band => {
      const sc         = stageColors[band.stage] || stageColors[Object.keys(stageColors)[0]] || {};
      const stageBorder = sc.border    || '#555';
      const stageBg     = sc.stageBg   || '#222';
      const stageText   = sc.stageText || '#fff';
      const stageName   = esc(band.stage      || '');
      const bandName    = esc(band.performer  || '');
      const timeStr     = esc((band.start_time || '') + ' – ' + (band.end_time || ''));

      cards += `
  <div class="card" style="border-left-color:${stageBorder}">
    <div class="card-top">
      <span class="artist">${bandName}</span>
      <span class="stage" style="background:${stageBg};color:${stageText}">${stageName}</span>
    </div>
    <div class="time">${timeStr}</div>
  </div>`;
    });

    return `<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=390">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #0a0a0f; font-family: 'DM Sans', sans-serif; width: 390px; padding: 0 0 32px; }
  .header { background: linear-gradient(135deg, ${bg1} 0%, ${bg2} 100%); padding: 28px 20px 20px; border-bottom: 2px solid ${accentColor}; position: relative; overflow: hidden; }
  .header::before { content: ''; position: absolute; top: -40px; right: -40px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,0.05); }
  .day-label { font-family: 'Bebas Neue', sans-serif; font-size: 48px; color: ${accentColor}; letter-spacing: 3px; line-height: 1; }
  .subtitle { font-size: 12px; color: ${accentDim}; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
  .cards { padding: 16px 16px 0; display: flex; flex-direction: column; gap: 10px; }
  .card { background: #111118; border: 0.5px solid #1e1e2a; border-radius: 14px; padding: 13px 15px; border-left: 3px solid; }
  .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
  .artist { font-size: 14px; font-weight: 600; color: #f0f0f0; flex: 1; }
  .stage { font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 99px; letter-spacing: 0.5px; white-space: nowrap; }
  .time { font-size: 12px; color: #6b7280; margin-top: 4px; }
</style>
</head>
<body>
<div class="header">
  <div class="day-label">${day}</div>
  <div class="subtitle">Festival Schedule</div>
</div>
<div class="cards">${cards}
</div>
</body>
</html>`;
  }

  // ── Render the full lockscreen UI for a festival ─────────────────────────
  function render(data) {
    lsDays        = data.days        || [];
    lsStageColors = data.stage_colors || {};
    lsDayAccents  = data.day_accents  || {};
    lsSchedule    = data.schedule     || {};

    // Build day HTML map
    lsDayHtmlMap = {};
    lsDays.forEach(day => {
      lsDayHtmlMap[day] = buildDayHtml(day, lsSchedule[day] || [], lsStageColors, lsDayAccents);
    });

    elPageSub.textContent = esc(data.festival_name || '') + ' · Schedule Picks';

    // ── Tab nav ────────────────────────────────────────────────────────────
    elTabNav.innerHTML = '';
    lsDays.forEach((day, i) => {
      const btn = document.createElement('button');
      btn.className   = 'ls-tab-btn' + (i === 0 ? ' active' : '');
      btn.dataset.day = day;
      btn.textContent = day;

      // Apply day accent color to the tab underline via inline style
      const accentColor = (lsDayAccents[day] || {}).color || '';
      if (accentColor) {
        btn.style.setProperty('--ls-day-color', accentColor);
      }

      btn.addEventListener('click', () => {
        elTabNav.querySelectorAll('.ls-tab-btn').forEach(b => b.classList.remove('active'));
        elPanels.querySelectorAll('.ls-tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('ls-panel-' + day);
        if (panel) panel.classList.add('active');
      });
      elTabNav.appendChild(btn);
    });

    // ── Panels ─────────────────────────────────────────────────────────────
    elPanels.innerHTML = '';
    elFrameWrap.innerHTML = '';

    lsDays.forEach((day, i) => {
      const panel = document.createElement('div');
      panel.className = 'ls-tab-panel' + (i === 0 ? ' active' : '');
      panel.id        = 'ls-panel-' + day;

      const bands = lsSchedule[day] || [];

      if (bands.length === 0) {
        panel.innerHTML = `<div class="ls-empty-state">No schedule picks for ${esc(day)} yet.</div>`;
      } else {
        panel.innerHTML = `
        <div class="ls-panel-grid">
          <div class="ls-source-box">
            <div class="ls-source-box-header">
              <span class="ls-source-box-title">HTML Source</span>
              <button class="ls-copy-btn" data-day="${esc(day)}">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Copy
              </button>
            </div>
            <pre class="ls-source-pre" id="ls-source-${esc(day)}">${esc(lsDayHtmlMap[day])}</pre>
          </div>
          <div class="ls-image-box">
            <div class="ls-image-box-header">
              <span class="ls-image-box-title">Image Export</span>
              <button class="ls-generate-btn" data-day="${esc(day)}">⚡ Generate Image</button>
            </div>
            <div class="ls-image-body" id="ls-img-body-${esc(day)}">
              <div class="ls-image-placeholder" id="ls-placeholder-${esc(day)}">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Click Generate to render<br>this day as an image
              </div>
              <div class="ls-generating-msg" id="ls-gen-msg-${esc(day)}" style="display:none">⏳ Rendering…</div>
              <img class="ls-image-preview" id="ls-img-preview-${esc(day)}" alt="${esc(day)} schedule">
              <a class="ls-download-btn" id="ls-download-${esc(day)}" download="${esc(day)}_schedule.png">↓ Download PNG</a>
            </div>
          </div>
        </div>`;
      }

      elPanels.appendChild(panel);

      // Off-screen iframe for rendering
      const frame = document.createElement('iframe');
      frame.className = 'ls-render-frame';
      frame.id        = 'ls-frame-' + day;
      frame.title     = 'ls-render-' + day;
      elFrameWrap.appendChild(frame);
    });

    // ── Bind copy buttons ──────────────────────────────────────────────────
    elPanels.querySelectorAll('.ls-copy-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const day  = btn.dataset.day;
        const html = lsDayHtmlMap[day];
        navigator.clipboard.writeText(html).then(() => {
          btn.textContent = '✓ Copied!';
          btn.classList.add('copied');
          setTimeout(() => {
            btn.innerHTML = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copy`;
            btn.classList.remove('copied');
          }, 2000);
        });
      });
    });

    // ── Bind generate buttons ──────────────────────────────────────────────
    elPanels.querySelectorAll('.ls-generate-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const day = btn.dataset.day;
        btn.disabled = true;
        const placeholder = document.getElementById('ls-placeholder-' + day);
        if (placeholder) placeholder.style.display = 'none';
        const genMsg = document.getElementById('ls-gen-msg-' + day);
        if (genMsg) genMsg.style.display = 'block';

        const frame = document.getElementById('ls-frame-' + day);
        lsLoadFrame(day);

        const PW = 412, PH = 915; // target phone dimensions

        // ── Mode A: exact 412×915, clip overflow ────────────────────────────
        function captureA() {
          const doc  = frame.contentDocument || frame.contentWindow.document;
          const body = doc.body;
          frame.style.width  = PW + 'px';
          frame.style.height = PH + 'px';
          html2canvas(body, {
            width: PW, height: PH, scale: 2,
            backgroundColor: '#0a0a0f',
            useCORS: true, allowTaint: true, logging: false,
            windowWidth: PW, windowHeight: PH,
          }).then(canvas => finishSingle(canvas, day, btn, genMsg))
            .catch(err => captureError(genMsg, btn, err));
        }

        // ── Mode C: paginate — slice full content into 412×915 pages ────────
        function captureC() {
          const doc  = frame.contentDocument || frame.contentWindow.document;
          const body = doc.body;
          const fullH = body.scrollHeight;
          frame.style.width  = PW + 'px';
          frame.style.height = fullH + 'px';

          html2canvas(body, {
            width: PW, height: fullH, scale: 2,
            backgroundColor: '#0a0a0f',
            useCORS: true, allowTaint: true, logging: false,
            windowWidth: PW, windowHeight: fullH,
          }).then(srcCanvas => {
            const totalPages = Math.ceil(fullH / PH);
            const imgBody    = document.getElementById('ls-img-body-' + day);
            if (genMsg) genMsg.style.display = 'none';

            // Clear any previous output
            imgBody.querySelectorAll('.ls-page-chip').forEach(el => el.remove());
            const oldPreview = document.getElementById('ls-img-preview-' + day);
            if (oldPreview) oldPreview.style.display = 'none';
            const oldDl = document.getElementById('ls-download-' + day);
            if (oldDl) oldDl.style.display = 'none';

            for (let p = 0; p < totalPages; p++) {
              const pageCanvas = document.createElement('canvas');
              pageCanvas.width  = PW * 2;   // scale:2
              pageCanvas.height = PH * 2;
              const ctx = pageCanvas.getContext('2d');
              ctx.drawImage(srcCanvas,
                0, p * PH * 2, PW * 2, PH * 2,   // source slice
                0, 0,          PW * 2, PH * 2);   // dest full page

              const dataUrl = pageCanvas.toDataURL('image/png');
              const chip    = document.createElement('div');
              chip.className = 'ls-page-chip';
              chip.style.cssText = 'display:flex;align-items:center;gap:10px;margin:8px 0;';

              const img = document.createElement('img');
              img.src   = dataUrl;
              img.style.cssText = 'width:80px;border-radius:6px;border:1px solid #2a2a38;';

              const dl = document.createElement('a');
              dl.href     = dataUrl;
              dl.download = day + '_schedule_p' + (p + 1) + '.png';
              dl.className = 'ls-download-btn';
              dl.style.display = 'inline-block';
              dl.textContent = '↓ Page ' + (p + 1) + ' of ' + totalPages;

              chip.appendChild(img);
              chip.appendChild(dl);
              imgBody.appendChild(chip);
            }
            btn.disabled    = false;
            btn.textContent = '↺ Regenerate';
          }).catch(err => captureError(genMsg, btn, err));
        }

        // ── Mode D: shrink-fit — reduce font scale until content fits ────────
        function captureD() {
          const doc  = frame.contentDocument || frame.contentWindow.document;
          const body = doc.body;
          frame.style.width  = PW + 'px';
          frame.style.height = 'auto';

          // Inject a CSS custom property we'll tweak
          const styleEl = doc.getElementById('ls-scale-override') || (() => {
            const s = doc.createElement('style');
            s.id = 'ls-scale-override';
            doc.head.appendChild(s);
            return s;
          })();

          let scale = 1.0;
          const MIN_SCALE = 0.55;
          const STEP = 0.05;

          function tryScale() {
            styleEl.textContent = `
              body { transform-origin: top left; transform: scale(${scale}); width: ${Math.round(PW / scale)}px !important; }
            `;
            // Force layout recalc
            requestAnimationFrame(() => {
              const scaledH = body.scrollHeight * scale;
              if (scaledH <= PH || scale <= MIN_SCALE) {
                // Good — capture at this scale
                frame.style.width  = PW + 'px';
                frame.style.height = PH + 'px';
                html2canvas(body, {
                  width: PW, height: PH, scale: 2,
                  backgroundColor: '#0a0a0f',
                  useCORS: true, allowTaint: true, logging: false,
                  windowWidth: PW, windowHeight: PH,
                }).then(canvas => finishSingle(canvas, day, btn, genMsg))
                  .catch(err => captureError(genMsg, btn, err));
              } else {
                scale = Math.max(MIN_SCALE, scale - STEP);
                tryScale();
              }
            });
          }
          tryScale();
        }

        // ── Shared helpers ───────────────────────────────────────────────────
        function finishSingle(canvas, day, btn, genMsg) {
          const dataUrl = canvas.toDataURL('image/png');
          const preview = document.getElementById('ls-img-preview-' + day);
          preview.src   = dataUrl;
          preview.style.display = 'block';
          const dlBtn   = document.getElementById('ls-download-' + day);
          dlBtn.href    = dataUrl;
          dlBtn.style.display = 'inline-block';
          if (genMsg) genMsg.style.display = 'none';
          btn.disabled    = false;
          btn.textContent = '↺ Regenerate';
        }

        function captureError(genMsg, btn, err) {
          if (genMsg) genMsg.textContent = '⚠ Render failed — try again';
          btn.disabled = false;
          console.error(err);
        }

        function doCapture() {
          const mode = lsGetMode();
          if      (mode === 'a') captureA();
          else if (mode === 'c') captureC();
          else if (mode === 'd') captureD();
        }

        if (frame.dataset.loaded && frame.contentDocument && frame.contentDocument.readyState === 'complete') {
          setTimeout(doCapture, 600);
        } else {
          frame.onload = () => setTimeout(doCapture, 600);
        }
      });
    });

    showState('data');
    lsPreload();
  }

  // ── Export mode selector ──────────────────────────────────────────────────
  let lsCurrentMode = 'a';
  const modeHints = {
    a: '412 × 915 · clips content',
    c: '412 × 915 per page · multiple downloads',
    d: '412 × 915 · shrinks to fit',
  };

  function lsGetMode() { return lsCurrentMode; }

  document.querySelectorAll('.ls-mode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.ls-mode-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      lsCurrentMode = btn.dataset.mode;
      const hint = document.getElementById('ls-mode-hint');
      if (hint) hint.textContent = modeHints[lsCurrentMode] || '';
    });
  });

  // ── Frame helpers ─────────────────────────────────────────────────────────
  function lsLoadFrame(day) {
    const frame = document.getElementById('ls-frame-' + day);
    if (!frame || frame.dataset.loaded) return;
    frame.srcdoc     = lsDayHtmlMap[day];
    frame.dataset.loaded = '1';
  }

  function lsPreload() {
    lsDays.forEach(lsLoadFrame);
  }

  // ── Load festival data from API ───────────────────────────────────────────
  async function loadFestival(festivalId) {
    if (!festivalId) {
      currentFestId = null;
      elPageSub.textContent = 'Select a festival to load';
      showState('loading');
      return;
    }
    if (festivalId === currentFestId) return;
    currentFestId = festivalId;

    elLoadState.textContent = 'Loading…';
    showState('loading');

    try {
      const res  = await fetch(`api/lineups_api.php?action=lockscreen_data&festival_id=${encodeURIComponent(festivalId)}`);
      const data = await res.json();
      if (!res.ok || !data.success) {
        elErrorState.textContent = '⚠ ' + (data.error || 'Failed to load lockscreen data.');
        showState('error');
        currentFestId = null;
      } else {
        render(data);
      }
    } catch (e) {
      elErrorState.textContent = '⚠ Network error: ' + e.message;
      showState('error');
      currentFestId = null;
    }
  }

  // ── Reload when schedule stars change in the SetList tab ─────────────────
  document.addEventListener('scheduleChanged', () => {
    const fid = currentFestId;
    if (!fid) return;
    currentFestId = null; // force reload
    loadFestival(fid);
  });

  // ── Listen for festival selection ─────────────────────────────────────────
  document.addEventListener('festivalChanged', e => {
    loadFestival(e.detail.id || null);
  });

  // Preload when the outer lockscreen tab becomes visible
  const lsPanel = document.getElementById('panel-lockscreen');
  if (lsPanel) {
    new MutationObserver(() => {
      if (lsPanel.classList.contains('active') && currentFestId) lsPreload();
    }).observe(lsPanel, { attributes: true, attributeFilter: ['class'] });
  }

  // In case festival was already selected before this script ran
  if (window.selectedFestival && window.selectedFestival.id) {
    loadFestival(window.selectedFestival.id);
  }

})();
</script>