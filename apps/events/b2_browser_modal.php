<!-- ═══════════════════════════════════════════════════════════════════
     b2_browser_modal.php
     Drop in your project root (same level as memories_db_modal.php).
     Include once in your page (e.g. schedule.php) just before </body>:
         require 'b2_browser_modal.php';

     Opens via JS: window.openB2Browser(eventId, onSelectCallback)
       • eventId   — integer event ID (determines bucket path)
       • onSelect  — function(url, key) called when user picks an image
     ═══════════════════════════════════════════════════════════════════ -->

<!-- ── B2 Browser Modal Overlay ──────────────────────────────────── -->
<div class="b2b-overlay" id="b2bOverlay">
  <div class="b2b-modal" id="b2bModal">

    <!-- Header -->
    <div class="b2b-header">
      <span class="b2b-title" id="b2bTitle">Media Library</span>
      <div class="b2b-tabs" id="b2bTabs">
        <button class="b2b-tab active" data-tab="browse">Browse</button>
        <button class="b2b-tab"        data-tab="upload">Upload</button>
      </div>
      <button class="b2b-close-btn" id="b2bCloseBtn" title="Close">&#10005;</button>
    </div>

    <!-- Body -->
    <div class="b2b-body">

      <!-- ── BROWSE PANEL ──────────────────────────────────────────── -->
      <div class="b2b-panel" id="b2bPanelBrowse">

        <div class="b2b-browse-loading" id="b2bBrowseLoading">
          <div class="b2b-spinner"></div>
          <span>Loading from bucket…</span>
        </div>

        <div class="b2b-browse-empty" id="b2bBrowseEmpty" style="display:none">
          <span class="b2b-empty-icon">&#128247;</span>
          <span>No images in this folder yet.</span>
          <span class="b2b-empty-hint">Switch to Upload to add some.</span>
        </div>

        <div class="b2b-browse-error" id="b2bBrowseError" style="display:none">
          <span class="b2b-empty-icon">&#9888;</span>
          <span id="b2bBrowseErrorMsg">Could not load files.</span>
        </div>

        <!-- Image grid -->
        <div class="b2b-grid" id="b2bGrid" style="display:none"></div>

      </div><!-- /browse panel -->

      <!-- ── UPLOAD PANEL ──────────────────────────────────────────── -->
      <div class="b2b-panel" id="b2bPanelUpload" style="display:none">

        <div class="b2b-upload-area">

          <!-- Drop zone -->
          <div class="b2b-drop-zone" id="b2bDropZone">
            <input type="file" id="b2bFileInput" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            <div class="b2b-drop-icon">&#128247;</div>
            <p class="b2b-drop-label">Drop images here or <strong>click to browse</strong></p>
            <p class="b2b-drop-hint">JPG · PNG · WEBP · GIF</p>
          </div>

          <!-- Upload queue -->
          <div class="b2b-queue" id="b2bQueue" style="display:none"></div>

          <!-- Upload button -->
          <button class="b2b-upload-btn" id="b2bUploadBtn" style="display:none" disabled>
            Upload Files
          </button>

        </div>

      </div><!-- /upload panel -->

    </div><!-- /b2b-body -->

    <!-- Footer bar: path indicator -->
    <div class="b2b-footer">
      <span class="b2b-path" id="b2bPath">
        <span class="b2b-crumb">Buckets</span>
        <span class="b2b-sep">&#8250;</span>
        <span class="b2b-crumb" id="b2bCrumbBucket"></span>
        <span class="b2b-sep">&#8250;</span>
        <span class="b2b-crumb">concerts</span>
        <span class="b2b-sep">&#8250;</span>
        <span class="b2b-crumb b2b-crumb-active" id="b2bCrumbEvent"></span>
        <span class="b2b-sep">&#8250;</span>
        <span class="b2b-crumb b2b-crumb-active">memories</span>
      </span>
      <span class="b2b-file-count" id="b2bFileCount"></span>
    </div>

  </div>
</div>

<!-- ── Preview lightbox (sits above b2b-overlay) ─────────────────── -->
<div class="b2b-lightbox" id="b2bLightbox">
  <div class="b2b-lightbox-inner">
    <img class="b2b-lightbox-img" id="b2bLightboxImg" src="" alt="">
    <div class="b2b-lightbox-actions">
      <button class="b2b-lb-select-btn" id="b2bLbSelectBtn">&#10003; Use This Image</button>
      <button class="b2b-lb-delete-btn" id="b2bLbDeleteBtn">&#128465; Delete</button>
      <button class="b2b-lb-close-btn"  id="b2bLbCloseBtn">&#10005; Close</button>
    </div>
    <div class="b2b-lb-meta" id="b2bLbMeta"></div>
  </div>
</div>

<style>
/* ═══════════════════════════════════════════════════════════════════
   B2 Browser Modal styles
   ═══════════════════════════════════════════════════════════════════ */

/* ── Overlay ─────────────────────────────────────────────────────── */
.b2b-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.88);
  z-index: 980; /* above memories modal (970) */
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(6px);
}
.b2b-overlay.open { display: flex; }

/* ── Modal shell ─────────────────────────────────────────────────── */
.b2b-modal {
  background: #0d0d0d;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  max-height: 86vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 40px 100px rgba(0,0,0,0.9);
  overflow: hidden;
}

/* ── Header ──────────────────────────────────────────────────────── */
.b2b-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 18px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}
.b2b-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: rgba(255,255,255,0.55);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  flex-shrink: 0;
}

/* ── Tabs ────────────────────────────────────────────────────────── */
.b2b-tabs {
  display: flex;
  gap: 4px;
  flex: 1;
}
.b2b-tab {
  background: none;
  border: 1px solid transparent;
  border-radius: 7px;
  padding: 5px 14px;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(255,255,255,0.35);
  cursor: pointer;
  transition: all 0.15s;
  letter-spacing: 0.03em;
}
.b2b-tab:hover { color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.05); }
.b2b-tab.active {
  color: #fff;
  background: rgba(167,139,250,0.15);
  border-color: rgba(167,139,250,0.35);
}

.b2b-close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #fff;
  opacity: 0.35;
  padding: 2px 8px;
  border-radius: 6px;
  transition: opacity 0.15s;
  flex-shrink: 0;
}
.b2b-close-btn:hover { opacity: 1; }

/* ── Body ────────────────────────────────────────────────────────── */
.b2b-body {
  flex: 1;
  overflow: hidden;
  min-height: 0;
  position: relative;
}
.b2b-panel {
  height: 100%;
  overflow-y: auto;
}

/* ── Browse: loading / empty / error ────────────────────────────── */
.b2b-browse-loading,
.b2b-browse-empty,
.b2b-browse-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 340px;
  gap: 10px;
  color: rgba(255,255,255,0.3);
  font-size: 0.85rem;
  text-align: center;
  padding: 24px;
}
.b2b-empty-icon { font-size: 2.4rem; }
.b2b-empty-hint { font-size: 0.75rem; color: rgba(255,255,255,0.2); margin-top: 2px; }
.b2b-browse-error { color: rgba(248,113,113,0.7); }

.b2b-spinner {
  width: 28px; height: 28px;
  border: 3px solid rgba(255,255,255,0.08);
  border-top-color: rgba(167,139,250,0.7);
  border-radius: 50%;
  animation: b2b-spin 0.75s linear infinite;
}
@keyframes b2b-spin { to { transform: rotate(360deg); } }

/* ── Browse: image grid ──────────────────────────────────────────── */
.b2b-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 8px;
  padding: 16px;
}
.b2b-thumb {
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  background: #1a1a1a;
  border: 2px solid transparent;
  position: relative;
  transition: transform 0.14s, border-color 0.14s;
}
.b2b-thumb:hover {
  transform: scale(1.04);
  border-color: rgba(167,139,250,0.55);
}
.b2b-thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.b2b-thumb-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.15s;
  display: flex;
  align-items: flex-end;
  padding: 7px;
}
.b2b-thumb:hover .b2b-thumb-overlay { opacity: 1; }
.b2b-thumb-name {
  font-size: 0.62rem;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

/* ── Upload panel ────────────────────────────────────────────────── */
.b2b-upload-area {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 340px;
}
.b2b-drop-zone {
  border: 2px dashed rgba(255,255,255,0.14);
  border-radius: 12px;
  padding: 48px 24px;
  text-align: center;
  cursor: pointer;
  position: relative;
  transition: border-color 0.15s, background 0.15s;
}
.b2b-drop-zone:hover,
.b2b-drop-zone.dragover {
  border-color: rgba(167,139,250,0.5);
  background: rgba(167,139,250,0.04);
}
.b2b-drop-zone input[type="file"] {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}
.b2b-drop-icon { font-size: 2.2rem; margin-bottom: 10px; opacity: 0.4; }
.b2b-drop-label { font-size: 0.88rem; color: rgba(255,255,255,0.55); margin-bottom: 4px; }
.b2b-drop-label strong { color: #a78bfa; }
.b2b-drop-hint { font-size: 0.72rem; color: rgba(255,255,255,0.2); letter-spacing: 0.06em; }

/* ── Upload queue ────────────────────────────────────────────────── */
.b2b-queue {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.b2b-queue-item {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.b2b-queue-thumb {
  width: 40px; height: 40px;
  border-radius: 5px;
  object-fit: cover;
  flex-shrink: 0;
  background: #1a1a1a;
}
.b2b-queue-info { flex: 1; min-width: 0; }
.b2b-queue-name {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.8);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-bottom: 4px;
}
.b2b-queue-size { font-size: 0.7rem; color: rgba(255,255,255,0.3); }
.b2b-queue-status {
  font-size: 0.72rem;
  font-weight: 600;
  flex-shrink: 0;
  min-width: 60px;
  text-align: right;
}
.b2b-queue-status.pending  { color: rgba(255,255,255,0.3); }
.b2b-queue-status.uploading { color: #a78bfa; }
.b2b-queue-status.done     { color: #4ade80; }
.b2b-queue-status.error    { color: #f87171; }
.b2b-queue-remove {
  background: none; border: none;
  color: rgba(255,255,255,0.25);
  font-size: 1rem;
  cursor: pointer;
  padding: 2px 6px;
  border-radius: 4px;
  transition: color 0.12s;
  flex-shrink: 0;
}
.b2b-queue-remove:hover { color: #f87171; }

/* Progress bar inside queue item */
.b2b-queue-bar-wrap {
  height: 2px;
  background: rgba(255,255,255,0.08);
  border-radius: 2px;
  margin-top: 5px;
  overflow: hidden;
}
.b2b-queue-bar {
  height: 2px;
  background: #a78bfa;
  width: 0%;
  transition: width 0.2s;
  border-radius: 2px;
}

/* Upload button */
.b2b-upload-btn {
  background: #a78bfa;
  border: none;
  border-radius: 10px;
  padding: 11px 28px;
  font-size: 0.88rem;
  font-weight: 700;
  color: #0d0d0d;
  cursor: pointer;
  align-self: flex-end;
  transition: opacity 0.15s, transform 0.1s;
}
.b2b-upload-btn:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }
.b2b-upload-btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* ── Footer ──────────────────────────────────────────────────────── */
.b2b-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 18px;
  border-top: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}
.b2b-path {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.68rem;
  font-family: monospace;
  letter-spacing: 0.04em;
  overflow: hidden;
  white-space: nowrap;
  min-width: 0;
}
.b2b-crumb {
  color: rgba(255,255,255,0.2);
  overflow: hidden;
  text-overflow: ellipsis;
  flex-shrink: 1;
}
.b2b-crumb.b2b-crumb-active {
  color: rgba(255,255,255,0.5);
}
.b2b-sep {
  color: rgba(255,255,255,0.12);
  flex-shrink: 0;
  font-size: 0.75rem;
}
.b2b-file-count {
  font-size: 0.68rem;
  color: rgba(255,255,255,0.2);
  flex-shrink: 0;
  margin-left: 12px;
}

/* ── Lightbox ────────────────────────────────────────────────────── */
.b2b-lightbox {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 990;
  background: rgba(0,0,0,0.94);
  align-items: center;
  justify-content: center;
  padding: 20px;
  backdrop-filter: blur(12px);
}
.b2b-lightbox.open { display: flex; }
.b2b-lightbox-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  max-width: 90vw;
  max-height: 90vh;
}
.b2b-lightbox-img {
  max-width: 100%;
  max-height: 65vh;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.8);
}
.b2b-lightbox-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}
.b2b-lb-select-btn {
  background: #a78bfa;
  border: none;
  border-radius: 9px;
  padding: 10px 24px;
  font-size: 0.875rem;
  font-weight: 700;
  color: #0d0d0d;
  cursor: pointer;
  transition: opacity 0.15s;
}
.b2b-lb-select-btn:hover { opacity: 0.88; }
.b2b-lb-delete-btn {
  background: rgba(239,68,68,0.12);
  border: 1px solid rgba(239,68,68,0.35);
  border-radius: 9px;
  padding: 10px 20px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #f87171;
  cursor: pointer;
  transition: background 0.15s;
}
.b2b-lb-delete-btn:hover { background: rgba(239,68,68,0.22); }
.b2b-lb-delete-btn.confirming {
  background: rgba(239,68,68,0.3);
  border-color: #f87171;
  color: #fff;
}
.b2b-lb-close-btn {
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 9px;
  padding: 10px 20px;
  font-size: 0.875rem;
  font-weight: 600;
  color: rgba(255,255,255,0.6);
  cursor: pointer;
  transition: background 0.15s;
}
.b2b-lb-close-btn:hover { background: rgba(255,255,255,0.13); color: #fff; }
.b2b-lb-meta {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.25);
  text-align: center;
  font-family: monospace;
}

/* ── Mobile ──────────────────────────────────────────────────────── */
@media (max-width: 600px) {
  .b2b-overlay { padding: 0; }
  .b2b-modal {
    max-width: 100%; max-height: 100dvh;
    height: 100dvh; border-radius: 0; border: none;
  }
  .b2b-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 5px; padding: 10px;
  }
  .b2b-lightbox-actions { flex-direction: column; width: 100%; }
  .b2b-lb-select-btn,
  .b2b-lb-delete-btn,
  .b2b-lb-close-btn { width: 100%; text-align: center; }
}
</style>

<script>
/* ═══════════════════════════════════════════════════════════════════
   B2 Browser Modal — JS
   ═══════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  /* ── API endpoint (same directory as this file) ── */
  var API = 'b2_browser_api.php';

  /* ── Bucket name (injected from PHP) ── */
  var B2_BUCKET_NAME = <?php
  if (!defined('B2_BUCKET')) require_once __DIR__ . '/upload_helper.php';
  echo json_encode(B2_BUCKET);
?>;

  /* ── DOM refs ── */
  var overlay       = document.getElementById('b2bOverlay');
  var closeBtn      = document.getElementById('b2bCloseBtn');
  var titleEl       = document.getElementById('b2bTitle');
  var tabs          = document.querySelectorAll('.b2b-tab');
  var panelBrowse   = document.getElementById('b2bPanelBrowse');
  var panelUpload   = document.getElementById('b2bPanelUpload');
  var browseLoading = document.getElementById('b2bBrowseLoading');
  var browseEmpty   = document.getElementById('b2bBrowseEmpty');
  var browseError   = document.getElementById('b2bBrowseError');
  var browseErrMsg  = document.getElementById('b2bBrowseErrorMsg');
  var grid          = document.getElementById('b2bGrid');
  var pathEl        = document.getElementById('b2bPath');
  var countEl       = document.getElementById('b2bFileCount');

  var dropZone      = document.getElementById('b2bDropZone');
  var fileInput     = document.getElementById('b2bFileInput');
  var queueEl       = document.getElementById('b2bQueue');
  var uploadBtn     = document.getElementById('b2bUploadBtn');

  var lightbox      = document.getElementById('b2bLightbox');
  var lbImg         = document.getElementById('b2bLightboxImg');
  var lbSelectBtn   = document.getElementById('b2bLbSelectBtn');
  var lbDeleteBtn   = document.getElementById('b2bLbDeleteBtn');
  var lbCloseBtn    = document.getElementById('b2bLbCloseBtn');
  var lbMeta        = document.getElementById('b2bLbMeta');

  /* ── State ── */
  var currentEventId   = 0;
  var onSelectCallback = null;
  var bucketFiles      = [];
  var uploadQueue      = [];  // { file, previewUrl, status }
  var lightboxFile     = null; // { key, url, filename, size, last_modified }
  var deleteConfirmTimeout = null;

  /* ══════════════════════════════════════════════════════════════════
     Public API
     ══════════════════════════════════════════════════════════════════ */

  /**
   * Open the B2 browser for a specific event.
   * @param {number}   eventId  - Maps to bucket path concerts/{eventId}/memories/
   * @param {Function} onSelect - Called with (url, key) when user picks an image
   */
  window.openB2Browser = function (eventId, onSelect) {
    currentEventId   = eventId;
    onSelectCallback = onSelect || null;

    document.getElementById('b2bCrumbBucket').textContent = B2_BUCKET_NAME;
    document.getElementById('b2bCrumbEvent').textContent = eventId;
    titleEl.textContent = 'Media Library · Event ' + eventId;

    // Reset to browse tab
    switchTab('browse');
    overlay.classList.add('open');
    loadBrowse();
  };

  /* ══════════════════════════════════════════════════════════════════
     Tab switching
     ══════════════════════════════════════════════════════════════════ */
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      switchTab(tab.dataset.tab);
    });
  });

  function switchTab(name) {
    tabs.forEach(function (t) {
      t.classList.toggle('active', t.dataset.tab === name);
    });
    panelBrowse.style.display = name === 'browse' ? '' : 'none';
    panelUpload.style.display = name === 'upload' ? '' : 'none';
  }

  /* ══════════════════════════════════════════════════════════════════
     Browse — load files from B2
     ══════════════════════════════════════════════════════════════════ */
  function loadBrowse() {
    browseLoading.style.display = 'flex';
    browseEmpty.style.display   = 'none';
    browseError.style.display   = 'none';
    grid.style.display          = 'none';
    grid.innerHTML              = '';
    countEl.textContent         = '';

    fetch(API + '?action=list&event_id=' + encodeURIComponent(currentEventId))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        browseLoading.style.display = 'none';

        if (data.error) {
          browseErrMsg.textContent  = data.error;
          browseError.style.display = 'flex';
          return;
        }

        bucketFiles = data.files || [];

        if (!bucketFiles.length) {
          browseEmpty.style.display = 'flex';
          return;
        }

        buildGrid(bucketFiles);
      })
      .catch(function (err) {
        browseLoading.style.display = 'none';
        browseErrMsg.textContent    = 'Network error: ' + err.message;
        browseError.style.display   = 'flex';
      });
  }

  function buildGrid(files) {
    grid.innerHTML = '';
    files.forEach(function (f) {
      var thumb = document.createElement('div');
      thumb.className   = 'b2b-thumb';
      thumb.dataset.key = f.key;

      var img = document.createElement('img');
      img.src     = f.url;
      img.alt     = f.filename;
      img.loading = 'lazy';

      var ov = document.createElement('div');
      ov.className = 'b2b-thumb-overlay';
      var nm = document.createElement('span');
      nm.className   = 'b2b-thumb-name';
      nm.textContent = f.filename;
      ov.appendChild(nm);

      thumb.appendChild(img);
      thumb.appendChild(ov);
      thumb.addEventListener('click', function () { openLightbox(f); });
      grid.appendChild(thumb);
    });

    grid.style.display  = 'grid';
    countEl.textContent = files.length + ' file' + (files.length !== 1 ? 's' : '');
  }

  /* ══════════════════════════════════════════════════════════════════
     Lightbox
     ══════════════════════════════════════════════════════════════════ */
  function openLightbox(fileObj) {
    lightboxFile = fileObj;
    lbImg.src    = fileObj.url;
    lbMeta.textContent = fileObj.key + '  ·  ' + fmtBytes(fileObj.size);
    resetDeleteConfirm();
    lightbox.classList.add('open');
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    lbImg.src    = '';
    lightboxFile = null;
    resetDeleteConfirm();
  }

  lbCloseBtn.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) closeLightbox();
  });

  // Select image → call callback → close both modals
  lbSelectBtn.addEventListener('click', function () {
    if (!lightboxFile) return;
    if (typeof onSelectCallback === 'function') {
      onSelectCallback(lightboxFile.url, lightboxFile.key);
    }
    closeLightbox();
    closeModal();
  });

  // Delete with two-click confirm
  lbDeleteBtn.addEventListener('click', function () {
    if (!lightboxFile) return;

    if (lbDeleteBtn.classList.contains('confirming')) {
      // Second click: actually delete
      clearTimeout(deleteConfirmTimeout);
      doDelete(lightboxFile.key);
    } else {
      // First click: ask for confirmation
      lbDeleteBtn.classList.add('confirming');
      lbDeleteBtn.textContent = '⚠ Confirm Delete';
      deleteConfirmTimeout = setTimeout(resetDeleteConfirm, 3000);
    }
  });

  function resetDeleteConfirm() {
    clearTimeout(deleteConfirmTimeout);
    lbDeleteBtn.classList.remove('confirming');
    lbDeleteBtn.textContent = '🗑 Delete';
  }

  function doDelete(key) {
    lbDeleteBtn.disabled    = true;
    lbDeleteBtn.textContent = 'Deleting…';

    fetch(API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'delete', key: key }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          // Remove from local list and grid
          bucketFiles = bucketFiles.filter(function (f) { return f.key !== key; });
          var tile = grid.querySelector('[data-key="' + CSS.escape(key) + '"]');
          if (tile) tile.remove();
          countEl.textContent = bucketFiles.length + ' file' + (bucketFiles.length !== 1 ? 's' : '');
          if (!bucketFiles.length) {
            grid.style.display        = 'none';
            browseEmpty.style.display = 'flex';
          }
          lbDeleteBtn.disabled = false;
          closeLightbox();
        } else {
          alert('Delete failed: ' + (data.error || 'Unknown error'));
          lbDeleteBtn.disabled = false;
          resetDeleteConfirm();
        }
      })
      .catch(function (err) {
        alert('Network error during delete: ' + err.message);
        lbDeleteBtn.disabled = false;
        resetDeleteConfirm();
      });
  }

  /* ══════════════════════════════════════════════════════════════════
     Upload
     ══════════════════════════════════════════════════════════════════ */

  // File input change
  fileInput.addEventListener('change', function () {
    addFilesToQueue(Array.from(fileInput.files));
    fileInput.value = '';
  });

  // Drag & drop
  dropZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    dropZone.classList.add('dragover');
  });
  dropZone.addEventListener('dragleave', function () {
    dropZone.classList.remove('dragover');
  });
  dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    var files = Array.from(e.dataTransfer.files).filter(function (f) {
      return f.type.startsWith('image/');
    });
    if (files.length) addFilesToQueue(files);
  });

  function addFilesToQueue(files) {
    files.forEach(function (file) {
      var item = { file: file, status: 'pending', previewUrl: URL.createObjectURL(file) };
      uploadQueue.push(item);
      renderQueueItem(item);
    });
    queueEl.style.display  = uploadQueue.length ? 'flex' : 'none';
    uploadBtn.style.display = uploadQueue.length ? 'inline-block' : 'none';
    uploadBtn.disabled      = false;
  }

  function renderQueueItem(item) {
    var row = document.createElement('div');
    row.className    = 'b2b-queue-item';
    item._rowEl      = row;

    var thumb = document.createElement('img');
    thumb.className  = 'b2b-queue-thumb';
    thumb.src        = item.previewUrl;
    thumb.alt        = '';

    var info = document.createElement('div');
    info.className   = 'b2b-queue-info';

    var name = document.createElement('div');
    name.className   = 'b2b-queue-name';
    name.textContent = item.file.name;

    var size = document.createElement('div');
    size.className   = 'b2b-queue-size';
    size.textContent = fmtBytes(item.file.size);

    var barWrap = document.createElement('div');
    barWrap.className = 'b2b-queue-bar-wrap';
    var bar = document.createElement('div');
    bar.className = 'b2b-queue-bar';
    item._barEl   = bar;
    barWrap.appendChild(bar);

    info.appendChild(name);
    info.appendChild(size);
    info.appendChild(barWrap);

    var status = document.createElement('div');
    status.className   = 'b2b-queue-status pending';
    status.textContent = 'Pending';
    item._statusEl     = status;

    var rm = document.createElement('button');
    rm.className   = 'b2b-queue-remove';
    rm.textContent = '✕';
    rm.title       = 'Remove';
    rm.addEventListener('click', function () {
      uploadQueue = uploadQueue.filter(function (i) { return i !== item; });
      row.remove();
      URL.revokeObjectURL(item.previewUrl);
      if (!uploadQueue.length) {
        queueEl.style.display   = 'none';
        uploadBtn.style.display = 'none';
      }
    });

    row.appendChild(thumb);
    row.appendChild(info);
    row.appendChild(status);
    row.appendChild(rm);
    queueEl.appendChild(row);
  }

  uploadBtn.addEventListener('click', function () {
    var pending = uploadQueue.filter(function (i) { return i.status === 'pending'; });
    if (!pending.length) return;
    uploadBtn.disabled = true;

    var chain = Promise.resolve();
    pending.forEach(function (item) {
      chain = chain.then(function () { return uploadOne(item); });
    });
    chain.then(function () {
      uploadBtn.disabled = false;
      // After all uploads, refresh the browse tab
      var allDone = uploadQueue.every(function (i) { return i.status === 'done' || i.status === 'error'; });
      if (allDone) {
        uploadQueue = [];
        queueEl.innerHTML    = '';
        queueEl.style.display   = 'none';
        uploadBtn.style.display = 'none';
        // Switch to browse to see newly uploaded images
        switchTab('browse');
        loadBrowse();
      }
    });
  });

  function uploadOne(item) {
    return new Promise(function (resolve) {
      item.status = 'uploading';
      item._statusEl.className   = 'b2b-queue-status uploading';
      item._statusEl.textContent = 'Uploading…';

      var fd = new FormData();
      fd.append('action',   'upload');
      fd.append('event_id', currentEventId);
      fd.append('file',     item.file, item.file.name);

      var xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', function (ev) {
        if (ev.lengthComputable) {
          var pct = Math.round((ev.loaded / ev.total) * 100);
          item._barEl.style.width        = pct + '%';
          item._statusEl.textContent     = pct + '%';
        }
      });

      xhr.addEventListener('load', function () {
        // ── Debug: log full server response ─────────────────────────────
        console.log('[B2 Upload] HTTP', xhr.status, '— raw response:', xhr.responseText);
        // ── End debug ─────────────────────────────────────────────
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            item.status               = 'done';
            item._statusEl.className  = 'b2b-queue-status done';
            item._statusEl.textContent = '✓ Done';
            item._barEl.style.width   = '100%';
            console.log('[B2 Upload] Success — key:', data.key, 'url:', data.url);
          } else {
            item.status               = 'error';
            item._statusEl.className  = 'b2b-queue-status error';
            item._statusEl.textContent = 'Error';
            console.error('[B2 Upload] Server error:', data.error, 'detail:', data.detail || '');
            alert('Upload failed:\n\n' + (data.error || 'Unknown error') + (data.detail ? '\n\nDetail:\n' + data.detail : ''));
          }
        } catch (e) {
          item.status               = 'error';
          item._statusEl.className  = 'b2b-queue-status error';
          item._statusEl.textContent = 'Parse error';
          console.error('[B2 Upload] Parse error:', e, 'Raw:', xhr.responseText);
          alert('Upload parse error. Check console for details.\n\nRaw response:\n' + xhr.responseText);
        }
        resolve();
      });

      xhr.addEventListener('error', function () {
        item.status               = 'error';
        item._statusEl.className  = 'b2b-queue-status error';
        item._statusEl.textContent = 'Network error';
        resolve();
      });

      xhr.open('POST', API);
      xhr.send(fd);
    });
  }

  /* ══════════════════════════════════════════════════════════════════
     Close modal
     ══════════════════════════════════════════════════════════════════ */
  function closeModal() {
    overlay.classList.remove('open');
    // Reset upload queue
    uploadQueue.forEach(function (i) { URL.revokeObjectURL(i.previewUrl); });
    uploadQueue             = [];
    queueEl.innerHTML       = '';
    queueEl.style.display   = 'none';
    uploadBtn.style.display = 'none';
    uploadBtn.disabled      = false;
    grid.innerHTML          = '';
    bucketFiles             = [];
  }

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (lightbox.classList.contains('open')) { closeLightbox(); return; }
      if (overlay.classList.contains('open'))  { closeModal(); }
    }
  });

  /* ══════════════════════════════════════════════════════════════════
     Utilities
     ══════════════════════════════════════════════════════════════════ */
  function fmtBytes(bytes) {
    if (!bytes) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

})();
</script>