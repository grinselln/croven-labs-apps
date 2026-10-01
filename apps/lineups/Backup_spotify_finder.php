<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Spotify Artist Finder</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

  :root {
    --green:   #1DB954;
    --green2:  #158a3e;
    --dark:    #0d0d0d;
    --surface: #161616;
    --card:    #1c1c1c;
    --card2:   #242424;
    --border:  #2e2e2e;
    --text:    #e8e8e8;
    --muted:   #888;
    --radius:  10px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--dark);
    color: var(--text);
    font-family: 'Inter', system-ui, sans-serif;
    min-height: 100vh;
    padding: 2rem 1rem 4rem;
    font-size: 15px;
  }

  .container { max-width: 800px; margin: 0 auto; }

  /* Header */
  header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;
  }
  .logo {
    display: flex; align-items: center; gap: .6rem;
    font-size: 1.25rem; font-weight: 700; letter-spacing: -.01em;
  }
  .logo svg { width: 32px; height: 32px; flex-shrink: 0; }

  /* Buttons */
  .btn {
    display: inline-block; padding: .55rem 1.3rem;
    background: var(--green); color: #000; font-weight: 600;
    border: none; border-radius: 50px; cursor: pointer;
    font-size: .9rem; text-decoration: none; font-family: inherit;
    transition: background .15s;
  }
  .btn:hover { background: var(--green2); color: #fff; }
  .btn:disabled { opacity: .4; cursor: not-allowed; }
  .btn-ghost {
    background: transparent; color: var(--muted);
    border: 1px solid var(--border); font-weight: 400;
  }
  .btn-ghost:hover { background: var(--card2); color: var(--text); }
  .btn-sm { padding: .35rem .9rem; font-size: .82rem; }

  /* Cards */
  .card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.4rem; margin-bottom: 1.2rem;
  }
  .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 1rem; }

  /* Login */
  .login-box { text-align: center; padding: 3rem 1.5rem; }
  .login-box h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: .6rem; }
  .login-box p { color: var(--muted); margin-bottom: 1.8rem; font-size: .9rem; }
  .login-box .btn { font-size: 1rem; padding: .7rem 2rem; }

  /* Setup notice */
  .notice {
    background: #12201a; border: 1px solid #1a3a28; border-radius: var(--radius);
    padding: .9rem 1.1rem; margin-bottom: 1.2rem; font-size: .85rem; color: #7ecfa8;
  }
  .notice code { background: #0a1a11; padding: .1rem .35rem; border-radius: 4px; font-size: .8rem; }

  /* Textarea */
  textarea {
    width: 100%; height: 130px;
    background: var(--card2); color: var(--text);
    border: 1px solid var(--border); border-radius: 8px;
    padding: .75rem; font-size: .9rem; resize: vertical;
    font-family: inherit; line-height: 1.6;
    transition: border-color .15s;
  }
  textarea:focus { outline: none; border-color: var(--green); }
  .hint { font-size: .78rem; color: var(--muted); margin-top: .45rem; }
  .actions { margin-top: .9rem; display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }

  /* Error */
  .error {
    background: #2a1010; color: #ff8080; border: 1px solid #4a1f1f;
    padding: .8rem 1rem; border-radius: var(--radius); margin-bottom: 1.2rem;
    font-size: .88rem;
  }

  /* Progress */
  #progress {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.1rem 1.3rem; margin-bottom: 1.2rem;
  }
  .progress-label { font-size: .85rem; color: var(--muted); margin-bottom: .6rem; }
  .progress-bar-wrap {
    background: var(--card2); border-radius: 50px; height: 6px; overflow: hidden;
  }
  .progress-bar {
    background: var(--green); height: 100%; border-radius: 50px;
    transition: width .3s ease; width: 0%;
  }
  .progress-detail { font-size: .78rem; color: var(--muted); margin-top: .5rem; }

  /* Scan stats */
  #scan-stats {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: .75rem 1.1rem;
    margin-bottom: 1.2rem; font-size: .82rem; color: var(--muted);
  }
  #scan-stats strong { color: var(--text); }
  .scan-stats-top { display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center; }
  .scan-stats-toggle {
    margin-top: .6rem; padding-top: .6rem; border-top: 1px solid var(--border);
  }
  .scan-stats-toggle summary {
    cursor: pointer; color: var(--green); font-size: .8rem;
    list-style: none; user-select: none;
  }
  .scan-stats-toggle summary::-webkit-details-marker { display: none; }
  .scan-stats-toggle summary::before { content: "▸ "; }
  .scan-stats-toggle[open] summary::before { content: "▾ "; }
  .pl-breakdown { margin-top: .8rem; display: flex; flex-direction: column; gap: .4rem; }
  .pl-row { background: var(--card2); border-radius: 8px; overflow: hidden; }
  .pl-row summary {
    cursor: pointer; padding: .5rem .8rem;
    list-style: none; display: flex; align-items: center; gap: .5rem;
    user-select: none; font-size: .82rem; color: var(--text);
  }
  .pl-row summary::-webkit-details-marker { display: none; }
  .pl-row summary::before { content: "▸"; color: var(--muted); font-size: .7rem; }
  .pl-row[open] summary::before { content: "▾"; }
  .pl-row summary .pl-name { flex: 1; font-weight: 500; }
  .pl-row summary .pl-count { color: var(--muted); font-size: .78rem; }
  .pl-row summary .pl-skipped { color: #e07070; font-size: .75rem; font-style: italic; }
  .pl-track-list {
    padding: .4rem .8rem .7rem 1.5rem;
    border-top: 1px solid var(--border);
    max-height: 260px; overflow-y: auto;
  }
  .pl-track-row {
    padding: .3rem 0; font-size: .8rem; color: var(--text);
    border-bottom: 1px solid #1e1e1e; line-height: 1.4;
  }
  .pl-track-row:last-child { border-bottom: none; }
  .pl-track-artist { color: var(--muted); font-size: .75rem; }
  .pl-owner { color: var(--muted); font-size: .75rem; margin-right: .4rem; }

  /* Results header */
  .results-header {
    display: flex; align-items: baseline; justify-content: space-between;
    margin-bottom: 1rem; flex-wrap: wrap; gap: .5rem;
  }
  .results-summary { font-size: .88rem; color: var(--muted); }
  .results-summary strong { color: var(--text); }

  /* Bucket / Artist group */
  .artist-group { margin-bottom: 1.8rem; }
  .artist-heading {
    font-size: 1rem; font-weight: 700; color: var(--green);
    border-bottom: 1px solid var(--border);
    padding-bottom: .4rem; margin-bottom: .7rem;
    display: flex; align-items: baseline; gap: .5rem;
  }
  .artist-heading .count { font-weight: 400; color: var(--muted); font-size: .82rem; }
  .bucket-other .artist-heading { color: #e0a040; border-color: #3a2e10; }

  /* Accordion buckets */
  .bucket-body { overflow: hidden; max-height: 0; transition: max-height .25s ease; }
  .bucket-body.open { max-height: 9999px; }
  .artist-heading { cursor: pointer; user-select: none; }
  .artist-heading .chevron { font-size: .65rem; color: var(--muted); transition: transform .2s; margin-left: auto; }
  .artist-heading.open .chevron { transform: rotate(90deg); color: var(--green); }

  /* Track card */
  .track-card {
    display: flex; align-items: center; gap: .9rem;
    background: var(--card2); border-radius: 8px;
    padding: .65rem .8rem; margin-bottom: .4rem;
    transition: background .15s;
  }
  .track-card:hover { background: #2e2e2e; }
  .track-thumb {
    width: 44px; height: 44px; border-radius: 4px;
    object-fit: cover; flex-shrink: 0;
  }
  .no-thumb {
    width: 44px; height: 44px; border-radius: 4px;
    background: #333; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
  }
  .track-info { flex: 1; min-width: 0; }
  .track-name {
    font-weight: 600; font-size: .9rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .track-album {
    font-size: .77rem; color: var(--muted); margin-top: .1rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .track-sources { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
  .source-tag {
    font-size: .72rem; border-radius: 50px;
    padding: .12rem .55rem; line-height: 1.5;
  }
  .tag-liked   { background: #3a1f45; color: #c97de0; }
  .tag-playlist { background: #152b15; color: #72c472; }
  .track-link {
    color: var(--green); font-size: .8rem; text-decoration: none;
    flex-shrink: 0; opacity: .7; transition: opacity .15s;
  }
  .track-link:hover { opacity: 1; }

  .no-results {
    text-align: center; color: var(--muted);
    padding: 2.5rem; font-size: .9rem;
  }

  #app-logged-in, #app-login { display: none; }

  /* CSV import */
  #csv-drop-zone {
    border: 2px dashed var(--border); border-radius: 8px;
    transition: border-color .15s, background .15s;
  }
  #csv-drop-zone.drag-over { border-color: var(--green); background: #12201a; }
  #csv-drop-zone.has-files { border-color: var(--green); border-style: solid; background: #0e1e14; }
  #csv-drop-inner {
    padding: 1.5rem; text-align: center; cursor: pointer;
    font-size: .88rem; color: var(--muted);
  }
  #csv-drop-inner:hover { color: var(--text); }
  .csv-item {
    display: flex; align-items: center; gap: .6rem;
    padding: .35rem 0; font-size: .82rem; border-bottom: 1px solid var(--border);
  }
  .csv-item:last-child { border-bottom: none; }
  .csv-item-name { flex: 1; font-weight: 500; }
  .csv-item-count { color: var(--muted); font-size: .78rem; }
  .csv-item-remove { color: #e07070; cursor: pointer; font-size: .8rem; background: none; border: none; padding: 0 .2rem; }

  /* Playlist links */
  .pl-link-entry { border-bottom: 1px solid var(--border); }
  .pl-link-entry:last-child { border-bottom: none; }
  .pl-link-row {
    display: flex; align-items: center; gap: .8rem;
    padding: .5rem 0;
    font-size: .88rem;
  }
  .pl-link-name {
    flex: 1; font-weight: 500; cursor: pointer;
    color: var(--text); transition: color .15s;
    display: flex; align-items: center; gap: .4rem;
  }
  .pl-link-name:hover { color: var(--green); }
  .pl-link-name .pl-chevron { font-size: .65rem; color: var(--muted); transition: transform .2s; }
  .pl-link-name.open .pl-chevron { transform: rotate(90deg); color: var(--green); }
  .pl-link-owner { color: var(--muted); font-size: .78rem; }
  .pl-link-btn {
    font-size: .78rem; padding: .25rem .7rem;
    background: var(--card2); color: var(--green);
    border: 1px solid var(--border); border-radius: 50px;
    cursor: pointer; text-decoration: none; white-space: nowrap;
    transition: background .15s;
  }
  .pl-link-btn:hover { background: #333; }
  /* Accordion track list under playlist row */
  .pl-accordion {
    width: 100%; overflow: hidden;
    max-height: 0; transition: max-height .25s ease;
  }
  .pl-accordion.open { max-height: 400px; }
  .pl-accordion-inner {
    background: var(--card2); border-radius: 8px;
    margin: .4rem 0 .6rem; padding: .5rem .8rem;
    max-height: 300px; overflow-y: auto;
  }
  .pl-acc-track {
    padding: .3rem 0; font-size: .8rem; color: var(--text);
    border-bottom: 1px solid #1e1e1e; line-height: 1.4;
  }
  .pl-acc-track:last-child { border-bottom: none; }
  .pl-acc-artist { color: var(--muted); font-size: .75rem; }
  .pl-acc-empty { color: var(--muted); font-size: .82rem; padding: .5rem 0; text-align: center; }
  .pl-acc-csv-badge {
    display: inline-block; font-size: .68rem; background: #152b15;
    color: #72c472; border-radius: 50px; padding: .1rem .45rem;
    margin-left: .4rem; vertical-align: middle;
  }
</style>
</head>
<body>
<div class="container">

  <header>
    <div class="logo">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="12" fill="#1DB954"/>
        <path d="M17.5 10.5c-3-1.8-8-2-10.9-1.1-.4.1-.9-.1-1-.5-.1-.4.1-.9.5-1C9.2 6.8 14.7 7 18.2 9.1c.4.2.5.7.3 1.1-.3.3-.7.4-1 .3zm-.1 2.9c-.3.4-.8.5-1.2.3-2.5-1.5-6.3-2-9.3-1.1-.4.1-.8-.1-.9-.5-.1-.4.1-.8.5-.9 3.4-.9 7.6-.5 10.5 1.3.4.2.5.7.4 1.1v-.2zm-1.4 2.8c-.2.3-.6.4-1 .2-2.2-1.3-5-1.6-8.2-.9-.3.1-.6-.1-.7-.4-.1-.3.1-.6.4-.7 3.5-.8 6.6-.4 9 1 .4.2.5.6.3.9l.2-.1z" fill="#000"/>
      </svg>
      Spotify Artist Finder
    </div>
    <div id="header-actions"></div>
  </header>

  <div id="app-login">
    <div class="notice">
      ℹ️ <strong>One-time setup:</strong> Register a Spotify app at
      <a href="https://developer.spotify.com/dashboard" target="_blank" style="color:#7ecfa8">developer.spotify.com/dashboard</a>,
      set the Redirect URI to <code id="redirect-hint"></code>, paste your
      <strong>Client ID</strong> below, and make sure the app type allows PKCE (no secret needed).
    </div>
    <div class="card login-box">
      <h2>Connect your Spotify</h2>
      <p>Search your Liked Songs and every playlist for tracks by specific artists.</p>
      <div style="margin-bottom:1.2rem; text-align:left; max-width:360px; margin-left:auto; margin-right:auto;">
        <label style="font-size:.82rem; color:var(--muted); display:block; margin-bottom:.4rem;">Spotify Client ID</label>
        <input id="client-id-input" type="text" placeholder="Paste your Client ID here"
          style="width:100%; background:var(--card2); color:var(--text); border:1px solid var(--border);
                 border-radius:8px; padding:.6rem .8rem; font-size:.9rem; font-family:inherit;">
      </div>
      <button class="btn" onclick="startLogin()">Log in with Spotify</button>
    </div>
  </div>

  <div id="app-logged-in">
    <div class="card">
      <div class="card-title">🎵 Find songs in your library</div>
      <textarea id="artist-input" placeholder="Paste artist names here, one per line...&#10;e.g.&#10;Radiohead&#10;Bon Iver&#10;Mitski"></textarea>
      <p class="hint">One artist per line · case-insensitive</p>
      <div class="actions">
        <button class="btn" id="search-btn" onclick="runSearch()">Search my library</button>
        <button class="btn btn-ghost btn-sm" id="clear-btn" onclick="clearResults()" style="display:none">Clear</button>
        <button class="btn btn-ghost btn-sm" onclick="fetchPlaylistLinks()">📋 Get playlist export links</button>
      </div>
    </div>

    <div id="playlist-links-box" style="display:none" class="card">
      <div class="card-title">📋 Your playlists — export each one for CSV import</div>
      <div style="background:#12201a;border:1px solid #1a3a28;border-radius:8px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#7ecfa8;line-height:1.7">
        <strong>Fastest:</strong> Go to <a href="https://www.chosic.com/spotify-playlist-exporter/" target="_blank" style="color:var(--green)">chosic.com</a>, click <strong>Log in</strong>, connect your Spotify, then export all playlists at once as CSV files.<br>
        <strong>Per playlist:</strong> Click <em>Copy &amp; open chosic</em> below — your playlist link is copied to clipboard. Paste it into chosic, hit Start, download the CSV, then import it here.
      </div>
      <div id="playlist-links-list"></div>
    </div>

    <div id="csv-import-box" class="card" style="display:none">
      <div class="card-title">📂 Import playlist CSVs</div>
      <p style="font-size:.82rem; color:var(--muted); margin-bottom:.9rem;">
        After exporting from chosic, drop your CSV files here. You can import multiple at once.
      </p>
      <div id="csv-drop-zone">
        <input type="file" id="csv-file-input" accept=".csv" multiple style="display:none" onchange="handleCsvFiles(this.files)">
        <div id="csv-drop-inner" onclick="document.getElementById('csv-file-input').click()">
          <div style="font-size:1.8rem;margin-bottom:.4rem">📄</div>
          <div style="font-weight:600;margin-bottom:.2rem">Drop CSV files here or click to browse</div>
          <div style="font-size:.78rem;color:var(--muted)">Export each playlist from chosic.com and import them all at once</div>
        </div>
      </div>
      <div id="csv-loaded-list" style="margin-top:.8rem;display:none">
        <div style="font-size:.82rem;color:var(--muted);margin-bottom:.5rem">Loaded playlists:</div>
        <div id="csv-loaded-items"></div>
        <button class="btn btn-ghost btn-sm" style="margin-top:.6rem" onclick="clearCsvData()">Clear all CSVs</button>
      </div>
    </div>

    <div id="error-box" class="error" style="display:none"></div>
    <div id="progress" style="display:none">
      <div class="progress-label" id="progress-label">Scanning…</div>
      <div class="progress-bar-wrap"><div class="progress-bar" id="progress-bar"></div></div>
      <div class="progress-detail" id="progress-detail"></div>
    </div>
    <div id="scan-stats" style="display:none"></div>
    <div id="results"></div>
  </div>

</div>

<script>
// ─── CONFIG ───────────────────────────────────────────────────────────────────
const HARDCODED_CLIENT_ID = '49fb848606304b638b90065a34c8071d';
const REDIRECT_URI = location.origin + location.pathname;
const SCOPES = 'user-library-read playlist-read-private playlist-read-collaborative';

// ─── PKCE HELPERS ─────────────────────────────────────────────────────────────
function rand(n) {
  const arr = new Uint8Array(n);
  crypto.getRandomValues(arr);
  return arr;
}
function b64url(buf) {
  return btoa(String.fromCharCode(...buf))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}
async function sha256(str) {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
  return b64url(new Uint8Array(buf));
}

// ─── AUTH STATE ───────────────────────────────────────────────────────────────
function getToken()  { return sessionStorage.getItem('sp_token'); }
function getExpiry() { return parseInt(sessionStorage.getItem('sp_expiry') || '0'); }
function isAuthed()  { return getToken() && Date.now() < getExpiry(); }

function saveToken(token, expiresIn) {
  sessionStorage.setItem('sp_token', token);
  sessionStorage.setItem('sp_expiry', Date.now() + (expiresIn - 60) * 1000);
}

function getClientId() {
  return localStorage.getItem('sp_client_id') || HARDCODED_CLIENT_ID || '';
}

async function startLogin() {
  const cid = document.getElementById('client-id-input').value.trim() || HARDCODED_CLIENT_ID;
  if (!cid) { alert('Please enter your Spotify Client ID.'); return; }
  localStorage.setItem('sp_client_id', cid);
  sessionStorage.removeItem('sp_token');
  sessionStorage.removeItem('sp_expiry');

  const verifier  = b64url(rand(32));
  const challenge = await sha256(verifier);
  sessionStorage.setItem('pkce_verifier', verifier);

  const url = 'https://accounts.spotify.com/authorize?' + new URLSearchParams({
    client_id:             cid,
    response_type:         'code',
    redirect_uri:          REDIRECT_URI,
    scope:                 SCOPES,
    code_challenge_method: 'S256',
    code_challenge:        challenge,
  });
  location.href = url;
}

async function handleCallback(code) {
  const verifier = sessionStorage.getItem('pkce_verifier');
  if (!verifier) { showLogin(); showError('PKCE verifier missing — please try logging in again.'); return; }

  const resp = await fetch('https://accounts.spotify.com/api/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      grant_type:    'authorization_code',
      code,
      redirect_uri:  REDIRECT_URI,
      client_id:     getClientId(),
      code_verifier: verifier,
    }),
  });
  const data = await resp.json();
  console.log('Token response:', resp.status, data.error || 'OK');

  if (data.access_token) {
    saveToken(data.access_token, data.expires_in || 3600);
    sessionStorage.removeItem('pkce_verifier');
    history.replaceState({}, '', location.pathname);
    showApp();
  } else {
    showLogin();
    showError(`Login failed (${resp.status}): ${data.error} — ${data.error_description || ''}`);
  }
}

function logout() {
  sessionStorage.clear();
  showLogin();
}

// ─── UI ROUTING ───────────────────────────────────────────────────────────────
function showLogin() {
  document.getElementById('app-login').style.display     = 'block';
  document.getElementById('app-logged-in').style.display = 'none';
  document.getElementById('header-actions').innerHTML    = '';
  document.getElementById('redirect-hint').textContent   = REDIRECT_URI;
  const saved = getClientId();
  if (saved) document.getElementById('client-id-input').value = saved;
}

function showApp() {
  document.getElementById('app-login').style.display     = 'none';
  document.getElementById('app-logged-in').style.display = 'block';
  document.getElementById('header-actions').innerHTML    =
    '<button class="btn btn-ghost btn-sm" onclick="logout()">Log out</button>';
}

// ─── SPOTIFY API ──────────────────────────────────────────────────────────────
async function spotifyGet(url) {
  const token = getToken();
  const resp  = await fetch(url, { headers: { Authorization: 'Bearer ' + token } });
  if (!resp.ok) {
    const err = await resp.json().catch(() => ({}));
    console.error('Spotify API error:', resp.status, url, err);
    throw new Error(err && err.error && err.error.message ? err.error.message : 'HTTP ' + resp.status);
  }
  return resp.json();
}

async function spotifyGetAll(startUrl, onPage) {
  const all  = [];
  let   next = startUrl;
  while (next) {
    const data  = await spotifyGet(next);
    const items = data.items || (data.tracks && data.tracks.items) || [];
    for (const item of items) {
      if (item != null) all.push(item);
    }
    if (onPage) onPage(all.length, data.total || null);
    next = data.next || (data.tracks && data.tracks.next) || null;
  }
  return all;
}

// ─── CSV IMPORT ───────────────────────────────────────────────────────────────
let csvPlaylists = {};

async function copyAndOpen(url, btn) {
  try {
    await navigator.clipboard.writeText(url);
    const orig = btn.textContent;
    btn.textContent = '\u2713 Copied!';
    setTimeout(function() { btn.textContent = orig; }, 1800);
  } catch(e) {}
  window.open('https://www.chosic.com/spotify-playlist-exporter/', '_blank');
}

function handleCsvFiles(files) {
  for (let i = 0; i < files.length; i++) {
    (function(file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const tracks = parseCsv(e.target.result);
        const name   = file.name.replace(/\.csv$/i, '');
        csvPlaylists[name] = tracks;
        renderCsvLoaded();
      };
      reader.readAsText(file);
    })(files[i]);
  }
}

function parseCsv(text) {
  const lines = text.split(/\r?\n/).filter(Boolean);
  if (!lines.length) return [];
  let headerIdx = 0, trackCol = -1, artistCol = -1, urlCol = -1;
  for (let i = 0; i < Math.min(5, lines.length); i++) {
    const cols = splitCsvLine(lines[i]);
    const ti = cols.findIndex(function(c) { return /track.?name|title|song/i.test(c); });
    const ai = cols.findIndex(function(c) { return /^artist/i.test(c); });
    const ui = cols.findIndex(function(c) { return /spotify.*(url|uri|link)|track.*(url|uri|link)|(url|uri|link).*(spotify|track)/i.test(c); });
    if (ti !== -1 && ai !== -1) { headerIdx = i; trackCol = ti; artistCol = ai; urlCol = ui; break; }
  }
  if (trackCol === -1) return [];
  const tracks = [];
  for (let i = headerIdx + 1; i < lines.length; i++) {
    const cols   = splitCsvLine(lines[i]);
    const track  = cols[trackCol]  ? cols[trackCol].trim()  : '';
    const artist = cols[artistCol] ? cols[artistCol].trim() : '';
    let   url    = (urlCol !== -1 && cols[urlCol]) ? cols[urlCol].trim() : '';
    // Convert spotify:track:ID URI to an open.spotify.com URL
    if (url && url.startsWith('spotify:track:')) {
      url = 'https://open.spotify.com/track/' + url.split(':')[2];
    }
    if (track) tracks.push({ track: track, artist: artist, url: url || '' });
  }
  return tracks;
}

function splitCsvLine(line) {
  const cols = [];
  let cur = '', inQ = false;
  for (let i = 0; i < line.length; i++) {
    const ch = line[i];
    if (ch === '"') { inQ = !inQ; }
    else if (ch === ',' && !inQ) { cols.push(cur); cur = ''; }
    else { cur += ch; }
  }
  cols.push(cur);
  return cols.map(function(c) { return c.replace(/^"|"$/g, '').trim(); });
}

function renderCsvLoaded() {
  const names = Object.keys(csvPlaylists);
  const box   = document.getElementById('csv-loaded-list');
  const items = document.getElementById('csv-loaded-items');
  const zone  = document.getElementById('csv-drop-zone');
  const inner = document.getElementById('csv-drop-inner');

  // Update drop zone appearance
  if (names.length) {
    zone.classList.add('has-files');
    inner.innerHTML =
      '<div style="font-size:1.8rem;margin-bottom:.4rem">✅</div>' +
      '<div style="font-weight:600;color:var(--green);margin-bottom:.2rem">' + names.length + ' playlist' + (names.length !== 1 ? 's' : '') + ' loaded</div>' +
      '<div style="font-size:.78rem;color:var(--muted)">Click or drop more files to add · ' +
      names.reduce(function(sum, n) { return sum + csvPlaylists[n].length; }, 0) + ' total tracks</div>';
  } else {
    zone.classList.remove('has-files');
    inner.innerHTML =
      '<div style="font-size:1.8rem;margin-bottom:.4rem">📄</div>' +
      '<div style="font-weight:600;margin-bottom:.2rem">Drop CSV files here or click to browse</div>' +
      '<div style="font-size:.78rem;color:var(--muted)">Export each playlist from chosic.com and import them all at once</div>';
  }

  box.style.display = names.length ? 'block' : 'none';
  items.innerHTML = names.map(function(n) {
    return '<div class="csv-item">' +
      '<span class="csv-item-name">\uD83D\uDCCB ' + esc(n) + '</span>' +
      '<span class="csv-item-count">' + csvPlaylists[n].length + ' tracks</span>' +
      '<button class="csv-item-remove" onclick="removeCsv(decodeURIComponent(\'' + encodeURIComponent(n) + '\'))">&#x2715; remove</button>' +
      '</div>';
  }).join('');

  // Refresh any open playlist accordions so CSV data appears immediately
  refreshOpenAccordions();
}

function removeCsv(name) { delete csvPlaylists[name]; renderCsvLoaded(); }
function clearCsvData()   { csvPlaylists = {}; renderCsvLoaded(); }

document.addEventListener('DOMContentLoaded', function() {
  const zone = document.getElementById('csv-drop-zone');
  if (!zone) return;
  zone.addEventListener('dragover',  function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
  zone.addEventListener('dragleave', function()  { zone.classList.remove('drag-over'); });
  zone.addEventListener('drop', function(e) {
    e.preventDefault();
    zone.classList.remove('drag-over');
    handleCsvFiles(e.dataTransfer.files);
  });
});

// ─── PLAYLIST LINKS ───────────────────────────────────────────────────────────
let fetchedPlaylists = []; // store for accordion use

async function fetchPlaylistLinks() {
  const box  = document.getElementById('playlist-links-box');
  const list = document.getElementById('playlist-links-list');
  box.style.display  = 'block';
  document.getElementById('csv-import-box').style.display = 'block';
  list.innerHTML = '<div style="color:var(--muted);font-size:.85rem">Fetching your playlists\u2026</div>';

  try {
    const playlists = await spotifyGetAll('https://api.spotify.com/v1/me/playlists?limit=50');
    fetchedPlaylists = playlists;
    if (!playlists.length) {
      list.innerHTML = '<div style="color:var(--muted);font-size:.85rem">No playlists found.</div>';
      return;
    }
    renderPlaylistLinks();
  } catch(e) {
    list.innerHTML = '<div style="color:#ff8080;font-size:.85rem">Error: ' + esc(e.message) + '</div>';
  }
}

function renderPlaylistLinks() {
  const list = document.getElementById('playlist-links-list');
  if (!list) return;
  list.innerHTML = fetchedPlaylists.map(function(pl, idx) {
    const url   = (pl.external_urls && pl.external_urls.spotify) || ('https://open.spotify.com/playlist/' + pl.id);
    const owner = (pl.owner && (pl.owner.display_name || pl.owner.id)) || '';
    const csvKey = findCsvMatch(pl.name);
    const csvBadge = csvKey !== null ? '<span class="pl-acc-csv-badge">\uD83D\uDCC2 CSV loaded</span>' : '';
    return '<div class="pl-link-entry" id="pl-row-' + idx + '">' +
      '<div class="pl-link-row">' +
        '<span class="pl-link-name" data-idx="' + idx + '" data-plid="' + esc(pl.id) + '" data-plname="' + esc(pl.name) + '">' +
          '<span class="pl-chevron">\u25B6</span>' + esc(pl.name) + csvBadge +
        '</span>' +
        '<span class="pl-link-owner">\uD83D\uDC64 ' + esc(owner) + '</span>' +
        '<a class="pl-link-btn" href="' + esc(url) + '" target="_blank" rel="noopener">Open in Spotify</a>' +
        '<button class="pl-link-btn" data-copy-url="' + esc(url) + '" title="Copies this playlist URL to clipboard and opens chosic">Copy &amp; open chosic \u2197</button>' +
      '</div>' +
      '<div class="pl-accordion" id="pl-acc-' + idx + '"><div class="pl-accordion-inner" id="pl-acc-inner-' + idx + '"><div class="pl-acc-empty">Loading\u2026</div></div></div>' +
    '</div>';
  }).join('');

  // Attach events via addEventListener to avoid inline onclick attribute quoting bugs
  list.querySelectorAll('.pl-link-name[data-idx]').forEach(function(el) {
    el.addEventListener('click', function() {
      toggleAccordion(+el.dataset.idx, el.dataset.plid, el.dataset.plname);
    });
  });
  list.querySelectorAll('.pl-link-btn[data-copy-url]').forEach(function(el) {
    el.addEventListener('click', function() {
      copyAndOpen(el.dataset.copyUrl, el);
    });
  });
}

// Find a CSV playlist whose name fuzzy-matches the Spotify playlist name
function findCsvMatch(plName) {
  const n = norm(plName);
  const keys = Object.keys(csvPlaylists);
  for (let i = 0; i < keys.length; i++) {
    if (norm(keys[i]) === n || norm(keys[i]).indexOf(n) !== -1 || n.indexOf(norm(keys[i])) !== -1) {
      return keys[i];
    }
  }
  return null;
}

// Track which accordions are open and their loaded state
const openAccordions  = {};
const loadedAccordions = {};

async function toggleAccordion(idx, plId, plName) {
  const acc     = document.getElementById('pl-acc-' + idx);
  const inner   = document.getElementById('pl-acc-inner-' + idx);
  const nameEl  = document.querySelector('#pl-row-' + idx + ' .pl-link-name');
  const isOpen  = openAccordions[idx];

  if (isOpen) {
    acc.classList.remove('open');
    nameEl.classList.remove('open');
    openAccordions[idx] = false;
    return;
  }

  acc.classList.add('open');
  nameEl.classList.add('open');
  openAccordions[idx] = true;

  if (loadedAccordions[idx]) return; // already fetched

  // Check for CSV data first
  const csvKey = findCsvMatch(plName);
  if (csvKey !== null) {
    renderAccordionTracks(inner, csvPlaylists[csvKey], true);
    loadedAccordions[idx] = true;
    return;
  }

  // Otherwise fetch from Spotify API
  inner.innerHTML = '<div class="pl-acc-empty">Loading tracks\u2026</div>';
  try {
    const items = await spotifyGetAll('https://api.spotify.com/v1/playlists/' + plId + '/tracks?limit=100');
    const tracks = items.map(function(item) {
      const t = item && item.track;
      if (!t) return null;
      return {
        track:  t.name || '',
        artist: (t.artists || []).map(function(a) { return a.name; }).join(', '),
      };
    }).filter(Boolean);
    renderAccordionTracks(inner, tracks, false);
    loadedAccordions[idx] = true;
  } catch(e) {
    inner.innerHTML = '<div class="pl-acc-empty" style="color:#ff8080">Failed to load: ' + esc(e.message) + '</div>';
  }
}

function renderAccordionTracks(inner, tracks, fromCsv) {
  if (!tracks || !tracks.length) {
    inner.innerHTML = '<div class="pl-acc-empty">No tracks found.</div>';
    return;
  }
  const source = fromCsv ? ' <span style="font-size:.68rem;color:#72c472">(from CSV)</span>' : '';
  inner.innerHTML =
    '<div style="font-size:.75rem;color:var(--muted);padding:.2rem 0 .5rem;border-bottom:1px solid var(--border);margin-bottom:.3rem">' +
      tracks.length + ' track' + (tracks.length !== 1 ? 's' : '') + source +
    '</div>' +
    tracks.map(function(t) {
      return '<div class="pl-acc-track">' + esc(t.track) +
        (t.artist ? '<div class="pl-acc-artist">' + esc(t.artist) + '</div>' : '') +
        '</div>';
    }).join('');
}

// Called by renderCsvLoaded to refresh any open accordions after CSV import
function refreshOpenAccordions() {
  // Re-render the list to update CSV badges
  if (fetchedPlaylists.length) renderPlaylistLinks();
  // Re-populate any currently open accordions that now have CSV data
  Object.keys(openAccordions).forEach(function(idx) {
    if (!openAccordions[idx]) return;
    const pl    = fetchedPlaylists[idx];
    if (!pl) return;
    const inner  = document.getElementById('pl-acc-inner-' + idx);
    const csvKey = findCsvMatch(pl.name);
    if (csvKey !== null && inner) {
      renderAccordionTracks(inner, csvPlaylists[csvKey], true);
      loadedAccordions[idx] = true;
    }
  });
}

// ─── SEARCH ───────────────────────────────────────────────────────────────────
let searching = false;

function norm(s) {
  return (s || '').toLowerCase().trim().replace(/\s+/g, ' ');
}

function artistMatches(trackArtists, normSet) {
  return (trackArtists || []).some(function(a) { return normSet.has(norm(a.name)); });
}

function pickImage(images) {
  if (!images || !images.length) return '';
  return (images[2] && images[2].url) || (images[1] && images[1].url) || (images[0] && images[0].url) || '';
}

function setProgress(label, pct, detail) {
  document.getElementById('progress-label').textContent  = label;
  document.getElementById('progress-bar').style.width    = pct + '%';
  document.getElementById('progress-detail').textContent = detail || '';
}

async function runSearch() {
  if (searching) return;

  const raw         = document.getElementById('artist-input').value;
  const artistNames = raw.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
  if (!artistNames.length) { showError('Enter at least one artist name.'); return; }

  const normSet = new Set(artistNames.map(norm));

  clearError();
  document.getElementById('results').innerHTML         = '';
  document.getElementById('scan-stats').style.display  = 'none';
  document.getElementById('progress').style.display    = 'flex';
  document.getElementById('progress').style.flexDirection = 'column';
  document.getElementById('clear-btn').style.display   = 'none';
  document.getElementById('search-btn').disabled       = true;
  searching = true;

  try {
    const matched = {};

    function addMatch(trackId, obj, source, bucketArtists) {
      if (!matched[trackId]) {
        matched[trackId] = Object.assign({}, obj, { sources: [source], buckets: bucketArtists ? bucketArtists.slice() : [] });
      } else {
        if (matched[trackId].sources.indexOf(source) === -1) {
          matched[trackId].sources.push(source);
        }
        if (bucketArtists) {
          for (var bi = 0; bi < bucketArtists.length; bi++) {
            if (matched[trackId].buckets.indexOf(bucketArtists[bi]) === -1) {
              matched[trackId].buckets.push(bucketArtists[bi]);
            }
          }
        }
      }
    }

    // Helper: given a track's artists array, return all searched artistNames that match
    function matchingBuckets(trackArtists) {
      var matched = [];
      for (var i = 0; i < artistNames.length; i++) {
        var n = norm(artistNames[i]);
        for (var j = 0; j < (trackArtists || []).length; j++) {
          if (norm(trackArtists[j].name) === n) { matched.push(artistNames[i]); break; }
        }
      }
      return matched;
    }

    // ── 1. Liked Songs ────────────────────────────────────────────────────────
    setProgress('Fetching liked songs\u2026', 5, '');
    const likedItems = await spotifyGetAll(
      'https://api.spotify.com/v1/me/tracks?limit=50',
      function(loaded, total) {
        const pct = total ? Math.round((loaded / total) * 30) + 5 : 15;
        setProgress('Scanning liked songs\u2026', pct, loaded + (total ? ' / ' + total : '') + ' tracks');
      }
    );

    for (let i = 0; i < likedItems.length; i++) {
      const item  = likedItems[i];
      const track = item && item.track;
      if (!track || !track.id) continue;
      if (!artistMatches(track.artists, normSet)) continue;
      addMatch(track.id, {
        track:  track.name,
        artist: track.artists.map(function(a) { return a.name; }).join(', '),
        album:  (track.album && track.album.name) || 'Unknown Album',
        url:    (track.external_urls && track.external_urls.spotify) || '#',
        image:  pickImage(track.album && track.album.images),
      }, '\u2764\uFE0F Liked Songs', matchingBuckets(track.artists));
    }

    // ── 2. CSV Playlists ──────────────────────────────────────────────────────
    const csvNames = Object.keys(csvPlaylists);
    for (let pi = 0; pi < csvNames.length; pi++) {
      const plName = csvNames[pi];
      const tracks = csvPlaylists[plName];
      for (let ti = 0; ti < tracks.length; ti++) {
        const t = tracks[ti];
        if (!normSet.has(norm(t.artist))) continue;
        const key = norm(t.track) + '|' + norm(t.artist);
        // For CSV, artist is a plain string; find which searched names match
        var csvBuckets = artistNames.filter(function(n) { return norm(n) === norm(t.artist); });
        addMatch(key, { track: t.track, artist: t.artist, album: '', url: t.url || '#', image: '' }, '\uD83D\uDCCB ' + plName, csvBuckets);
      }
    }

    // ── 3. API Playlists ──────────────────────────────────────────────────────
    setProgress('Fetching playlists\u2026', 35, '');
    const playlists      = await spotifyGetAll('https://api.spotify.com/v1/me/playlists?limit=50');
    const plTotal        = playlists.length;
    const scannedPlaylists = [];

    for (let i = 0; i < playlists.length; i++) {
      const pl   = playlists[i];
      const plId = pl && pl.id;
      if (!plId) continue;

      const plName = pl.name || 'Unknown Playlist';
      const pct    = 35 + Math.round(((i + 1) / plTotal) * 60);
      setProgress('Scanning playlists\u2026', pct, plName + ' (' + (i + 1) + ' / ' + plTotal + ')');

      let plItems = [];
      try {
        plItems = await spotifyGetAll('https://api.spotify.com/v1/playlists/' + plId + '/tracks?limit=100');
      } catch(e) {
        console.warn('Skipping playlist "' + plName + '" (' + plId + '):', e.message);
        scannedPlaylists.push({ name: plName, tracks: [], skipped: true, owner: pl.owner && (pl.owner.display_name || pl.owner.id) || '?' });
        continue;
      }

      const plTracks = [];
      for (let j = 0; j < plItems.length; j++) {
        const item  = plItems[j];
        const track = item && item.track;
        if (!track || !track.id) continue;
        plTracks.push({
          name:   track.name,
          artist: (track.artists || []).map(function(a) { return a.name; }).join(', '),
        });
        if (!artistMatches(track.artists, normSet)) continue;
        addMatch(track.id, {
          track:  track.name,
          artist: (track.artists || []).map(function(a) { return a.name; }).join(', '),
          album:  (track.album && track.album.name) || 'Unknown Album',
          url:    (track.external_urls && track.external_urls.spotify) || '#',
          image:  pickImage(track.album && track.album.images),
        }, '\uD83D\uDCCB ' + plName, matchingBuckets(track.artists));
      }
      scannedPlaylists.push({ name: plName, tracks: plTracks, skipped: false, owner: pl.owner && (pl.owner.display_name || pl.owner.id) || '?' });
    }

    setProgress('Done!', 100, '');
    setTimeout(function() { document.getElementById('progress').style.display = 'none'; }, 600);

    // ── Stats bar ─────────────────────────────────────────────────────────────
    const statsEl = document.getElementById('scan-stats');
    statsEl.style.display = 'block';

    const plBreakdown = scannedPlaylists.map(function(pl) {
      const ownerLabel = '<span class="pl-owner">\uD83D\uDC64 ' + esc(pl.owner || '?') + '</span>';
      const countLabel = pl.skipped
        ? ownerLabel + ' <span class="pl-skipped">skipped (no access)</span>'
        : ownerLabel + ' <span class="pl-count">' + pl.tracks.length + ' track' + (pl.tracks.length !== 1 ? 's' : '') + '</span>';
      const trackRows = pl.skipped ? '' : pl.tracks.map(function(t) {
        return '<div class="pl-track-row">' + esc(t.name) + '<span class="pl-track-artist"> \u2014 ' + esc(t.artist) + '</span></div>';
      }).join('');
      return '<details class="pl-row">' +
        '<summary><span class="pl-name">' + esc(pl.name) + '</span>' + countLabel + '</summary>' +
        (pl.skipped ? '' : '<div class="pl-track-list">' + (trackRows || '<div class="pl-track-row" style="color:var(--muted)">Empty playlist</div>') + '</div>') +
        '</details>';
    }).join('');

    statsEl.innerHTML =
      '<div class="scan-stats-top">' +
        '<span>\uD83D\uDD0D Scanned</span>' +
        '<span><strong>' + likedItems.length + '</strong> liked songs</span>' +
        (csvNames.length ? '<span><strong>' + csvNames.length + '</strong> imported playlist' + (csvNames.length !== 1 ? 's' : '') + '</span>' : '') +
        '<span><strong>' + scannedPlaylists.length + '</strong> API playlists</span>' +
      '</div>' +
      '<details class="scan-stats-toggle">' +
        '<summary>show playlist breakdown</summary>' +
        '<div class="pl-breakdown">' + plBreakdown + '</div>' +
      '</details>';

    renderResults(matched, artistNames);

  } catch(err) {
    document.getElementById('progress').style.display = 'none';
    showError('Error: ' + err.message);
  } finally {
    searching = false;
    document.getElementById('search-btn').disabled     = false;
    document.getElementById('clear-btn').style.display = 'inline-block';
  }
}

function renderResults(matched, artistNames) {
  const resultsEl = document.getElementById('results');
  const tracks    = Object.values(matched);

  // Build buckets: one per searched artist (in input order) + one "Other"
  // A track can appear in multiple named buckets if multiple searched artists match.
  const buckets = {}; // artistName (original case) -> []
  for (let i = 0; i < artistNames.length; i++) {
    buckets[artistNames[i]] = [];
  }
  const otherBucket = [];

  for (let i = 0; i < tracks.length; i++) {
    const t = tracks[i];
    if (!t.buckets || !t.buckets.length) {
      otherBucket.push(t);
    } else {
      for (let bi = 0; bi < t.buckets.length; bi++) {
        const bname = t.buckets[bi];
        if (buckets[bname]) {
          buckets[bname].push(t);
        } else {
          otherBucket.push(t);
        }
      }
    }
  }

  // Count totals (unique tracks, not bucket-duplicates)
  const totalUnique = tracks.length;

  if (!totalUnique && !artistNames.length) {
    resultsEl.innerHTML = '<div class="no-results">\uD83D\uDE15 No tracks found.</div>';
    return;
  }

  const bucketsWithResults = artistNames.filter(function(n) { return buckets[n].length > 0; }).length
    + (otherBucket.length > 0 ? 1 : 0);

  // Summary line
  let html = '<div class="results-header">' +
    '<div class="results-summary">Found <strong>' + totalUnique + '</strong> unique track' + (totalUnique !== 1 ? 's' : '') +
    ' across <strong>' + bucketsWithResults + '</strong> bucket' + (bucketsWithResults !== 1 ? 's' : '') + '</div>' +
    '</div>';

  // Helper to render a list of track cards
  function trackCardsHtml(list) {
    var out = '';
    for (var ti = 0; ti < list.length; ti++) {
      var t = list[ti];
      var thumb = t.image
        ? '<img class="track-thumb" src="' + esc(t.image) + '" alt="" loading="lazy">'
        : '<div class="no-thumb">\uD83C\uDFB5</div>';
      var tags = t.sources.map(function(src) {
        var cls = src.indexOf('\u2764') !== -1 ? 'tag-liked' : 'tag-playlist';
        return '<span class="source-tag ' + cls + '">' + esc(src) + '</span>';
      }).join('');
      var openBtn = t.url && t.url !== '#'
        ? '<a class="track-link" href="' + esc(t.url) + '" target="_blank" rel="noopener">\u25B6 Open</a>'
        : '';
      out += '<div class="track-card">' +
        thumb +
        '<div class="track-info">' +
          '<div class="track-name">' + esc(t.track) + '</div>' +
          '<div class="track-album">' + esc(t.album) + '</div>' +
          '<div class="track-sources">' + tags + '</div>' +
        '</div>' +
        openBtn +
        '</div>';
    }
    return out;
  }

  // Render named buckets in input order — skip empty ones
  for (let i = 0; i < artistNames.length; i++) {
    const name = artistNames[i];
    const list = buckets[name];
    if (!list.length) continue;
    const headId = 'bh-' + i;
    const bodyId = 'bb-' + i;
    html += '<div class="artist-group">' +
      '<div class="artist-heading" id="' + headId + '" onclick="toggleBucket(\'' + headId + '\',\'' + bodyId + '\')">' +
      esc(name) +
      ' <span class="count">' + list.length + ' track' + (list.length !== 1 ? 's' : '') + '</span>' +
      '<span class="chevron">&#9654;</span></div>' +
      '<div class="bucket-body" id="' + bodyId + '">' + trackCardsHtml(list) + '</div>' +
      '</div>';
  }

  // Render "Other" bucket if any unmatched tracks exist
  if (otherBucket.length > 0) {
    html += '<div class="artist-group bucket-other">' +
      '<div class="artist-heading" id="bh-other" onclick="toggleBucket(\'bh-other\',\'bb-other\')">' +
      'Other' +
      ' <span class="count">' + otherBucket.length + ' track' + (otherBucket.length !== 1 ? 's' : '') + '</span>' +
      '<span class="chevron">&#9654;</span></div>' +
      '<div class="bucket-body" id="bb-other">' + trackCardsHtml(otherBucket) + '</div>' +
      '</div>';
  }

  resultsEl.innerHTML = html;
}

function toggleBucket(headId, bodyId) {
  const head = document.getElementById(headId);
  const body = document.getElementById(bodyId);
  if (!head || !body) return;
  const isOpen = body.classList.contains('open');
  body.classList.toggle('open', !isOpen);
  head.classList.toggle('open', !isOpen);
}

function esc(s) {
  return String(s || '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function showError(msg) {
  const el = document.getElementById('error-box');
  el.textContent  = msg;
  el.style.display = 'block';
}
function clearError() {
  document.getElementById('error-box').style.display = 'none';
}

function clearResults() {
  document.getElementById('results').innerHTML         = '';
  document.getElementById('scan-stats').style.display  = 'none';
  document.getElementById('clear-btn').style.display   = 'none';
  document.getElementById('artist-input').value        = '';
  clearError();
}

// ─── BOOT ─────────────────────────────────────────────────────────────────────
(async function boot() {
  const params = new URLSearchParams(location.search);
  const code   = params.get('code');
  if (code) {
    await handleCallback(code);
  } else if (isAuthed()) {
    showApp();
  } else {
    showLogin();
  }
})();
</script>
</body>
</html>