<?php
// ─── DB CONFIG ────────────────────────────────────────────────────────────────
require_once 'db/db_hosted.php';
// $pdo is provided by db_hosted.php

// ─── LOAD ICONS FROM FILE ────────────────────────────────────────────────────
$iconList = [];
$iconsFile = __DIR__ . '/icons.txt';
if (file_exists($iconsFile)) {
    $lines = file($iconsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('||', $line));
        if (count($parts) >= 2 && $parts[0] !== '') {
            $iconList[] = ['class' => $parts[0], 'label' => $parts[1]];
        }
    }
}

// ─── LOAD CALENDARS FOR DROPDOWN ─────────────────────────────────────────────
$calendars = [];
try {
    $calendars = $pdo->query("SELECT id, name FROM calendars ORDER BY name")->fetchAll();
} catch (Exception $e) {
    // calendars table may not exist yet; gracefully ignore
}

// ─── AJAX HANDLERS ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $fields = [
            'title'      => trim($_POST['title']      ?? ''),
            'location'   => trim($_POST['location']   ?? ''),
            'icon'       => trim($_POST['icon']        ?? ''),
            'color'      => trim($_POST['color']       ?? '#c0392b'),
            'start_Date' => $_POST['start_Date'] ?: null,
            'start_Time' => $_POST['start_Time'] ?: null,
            'end_Date'   => $_POST['end_Date']   ?: null,
            'end_Time'   => $_POST['end_Time']   ?: null,
            'Calendar'   => $_POST['Calendar']   ? (int)$_POST['Calendar'] : null,
            'Guests'     => trim($_POST['Guests']     ?? ''),
            'Notes'      => trim($_POST['Notes']      ?? ''),
        ];
        if ($fields['title'] === '') { echo json_encode(['error' => 'Title required']); exit; }

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO countdowns_items
                (title,location,icon,color,start_Date,start_Time,end_Date,end_Time,Calendar,Guests,Notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute(array_values($fields));
            $fields['id'] = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'item' => $fields]);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE countdowns_items SET
                title=?,location=?,icon=?,color=?,start_Date=?,start_Time=?,
                end_Date=?,end_Time=?,Calendar=?,Guests=?,Notes=? WHERE id=?");
            $stmt->execute([...array_values($fields), $id]);
            $fields['id'] = $id;
            echo json_encode(['success' => true, 'item' => $fields]);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM countdowns_items WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM countdowns_items WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        echo json_encode($row ?: ['error' => 'Not found']);
        exit;
    }
}

// ─── LOAD ALL ITEMS ──────────────────────────────────────────────────────────
$items = $pdo->query("SELECT * FROM countdowns_items ORDER BY start_Date ASC, id DESC")->fetchAll();

$today = new DateTime('today');
$futureCount = 0; $pastCount = 0;
foreach ($items as $item) {
    if ($item['start_Date']) {
        $d = new DateTime($item['start_Date']);
        if ($d >= $today) $futureCount++; else $pastCount++;
    } else {
        $futureCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Countdowns</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:         #0d0f14;
    --surface:    #151820;
    --surface2:   #1c2030;
    --border:     #272c3d;
    --accent:     #e8ff47;
    --accent2:    #47c8ff;
    --text:       #e8eaf0;
    --text-muted: #616880;
    --danger:     #ff4760;
    --teal:       #2a9d8f;
    --radius:     14px;
    --mono:       'DM Mono', monospace;
    --sans:       'Syne', sans-serif;
  }

  body { background:var(--bg); color:var(--text); font-family:var(--sans); min-height:100vh; display:flex; flex-direction:column; max-width:480px; margin:0 auto; width:100%; }

  /* HEADER */
  header { display:flex; align-items:center; padding:10px 12px; border-bottom:1px solid var(--border); gap:10px; position:sticky; top:0; background:var(--bg); z-index:10; }
  .menu-wrap { position:relative; }
  .menu-select { appearance:none; background:var(--surface2); color:var(--text); border:1px solid var(--border); padding:7px 28px 7px 10px; border-radius:8px; font-family:var(--sans); font-size:0.8rem; font-weight:600; cursor:pointer; letter-spacing:0.04em; transition:border-color .2s; }
  .menu-select:hover { border-color:var(--accent); }
  .menu-arrow { position:absolute; right:9px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--text-muted); font-size:0.7rem; }
  .page-title { font-size:1rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase; color:var(--accent); margin-left:auto; margin-right:auto; }

  /* FILTER BAR */
  .filter-bar { display:flex; padding:10px 12px 0; border-bottom:1px solid var(--border); }
  .filter-tab { padding:7px 14px; font-family:var(--sans); font-size:0.76rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-muted); background:none; border:none; border-bottom:2px solid transparent; cursor:pointer; transition:all .2s; position:relative; bottom:-1px; }
  .filter-tab .badge { display:inline-flex; align-items:center; justify-content:center; background:var(--surface2); border-radius:20px; padding:1px 6px; font-size:0.66rem; margin-left:4px; font-family:var(--mono); }
  .filter-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
  .filter-tab.active .badge { background:var(--accent); color:#0d0f14; }
  .filter-tab:hover:not(.active) { color:var(--text); }

  /* MAIN */
  main { flex:1; padding:10px 10px; width:100%; }

  /* CARDS */
  .cards { display:flex; flex-direction:column; gap:7px; }
  .card { border:none; border-radius:14px; padding:10px 12px; display:flex; align-items:center; gap:10px; cursor:pointer; transition:transform .15s, filter .2s; animation:slideIn .3s ease both; position:relative; overflow:hidden; }
  @keyframes slideIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
  .card:hover { transform:scale(1.01); filter:brightness(1.08); }

  /* LEFT: icon */
  .card-icon-wrap { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .card-icon-wrap i { font-size:1.05rem; color:#fff; }

  /* MID: info — 3 lines stacked */
  .card-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
  .card-name  { font-size:0.88rem; font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3; }
  .card-loc   { font-size:0.68rem; color:rgba(255,255,255,0.7); font-family:var(--mono); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .card-dt    { font-size:0.65rem; color:rgba(255,255,255,0.65); font-family:var(--mono); display:flex; align-items:center; gap:3px; }
  .card-dt i  { font-size:0.65rem; opacity:0.7; }

  /* RIGHT: countdown */
  .card-countdown { display:flex; flex-direction:column; align-items:center; flex-shrink:0; min-width:72px; }
  .cd-days  { font-family:var(--sans); font-size:2.1rem; font-weight:800; color:#fff; line-height:1; }
  .cd-label { font-size:0.58rem; color:rgba(255,255,255,0.7); margin-top:2px; text-align:center; white-space:nowrap; }
  .cd-past  { font-size:0.7rem; font-family:var(--mono); color:rgba(255,255,255,0.85); text-transform:uppercase; letter-spacing:.06em; background:rgba(0,0,0,0.2); padding:3px 7px; border-radius:6px; }

  .card-delete { position:absolute; top:5px; right:6px; background:none; border:none; color:rgba(255,255,255,0.4); cursor:pointer; padding:3px 5px; border-radius:6px; font-size:0.8rem; opacity:0; transition:opacity .2s, color .2s, background .2s; z-index:2; }
  .card:hover .card-delete { opacity:1; }
  .card-delete:hover { color:#fff; background:rgba(0,0,0,0.25); }

  .empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
  .empty-icon { font-size:2.5rem; margin-bottom:12px; }
  .empty-text { font-size:0.85rem; letter-spacing:.06em; text-transform:uppercase; }

  /* FOOTER */
  footer { padding:10px 12px 18px; border-top:none; display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; }
  .search-btn { width:40px; height:40px; border-radius:50%; background:var(--surface2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-muted); font-size:0.95rem; transition:border-color .2s, color .2s; flex-shrink:0; }
  .search-btn:hover { border-color:var(--accent); color:var(--accent); }
  .footer-tabs { display:flex; background:var(--surface2); border-radius:24px; padding:3px; gap:2px; flex:1; min-width:0; }
  .foot-tab { flex:1; padding:7px 6px; border-radius:20px; font-family:var(--sans); font-size:0.73rem; font-weight:700; color:var(--text-muted); background:none; border:none; cursor:pointer; transition:all .2s; white-space:nowrap; text-align:center; overflow:hidden; text-overflow:ellipsis; }
  .foot-tab.active { background:#fff; color:#0d0f14; }
  .foot-tab:not(.active):hover { color:var(--text); }
  .add-btn { width:40px; height:40px; border-radius:50%; background:var(--accent); color:#0d0f14; border:none; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:700; cursor:pointer; transition:transform .15s, box-shadow .15s; flex-shrink:0; }
  .add-btn:hover { transform:scale(1.08); box-shadow:0 6px 20px rgba(232,255,71,.35); }

  /* ── MODAL OVERLAY ── */
  .overlay { position:fixed; inset:0; background:rgba(0,0,0,.75); backdrop-filter:blur(6px); z-index:100; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .25s; }
  .overlay.open { opacity:1; pointer-events:all; }

  .modal { background:var(--surface); border:1px solid var(--border); border-radius:20px; width:100%; max-width:520px; margin:16px; transform:translateY(20px) scale(.97); transition:transform .25s cubic-bezier(.34,1.56,.64,1); max-height:90vh; display:flex; flex-direction:column; }
  .overlay.open .modal { transform:translateY(0) scale(1); }

  /* Modal header — mimics screenshot top bar */
  .modal-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 20px 0; }
  .modal-topbar-title { font-size:0.9rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--text); flex:1; text-align:center; }
  .modal-x { background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer; padding:4px 8px; border-radius:6px; transition:color .2s; line-height:1; }
  .modal-x:hover { color:var(--danger); }
  .modal-check { background:none; border:none; color:#2ecc71; font-size:1.4rem; cursor:pointer; padding:4px 8px; border-radius:6px; transition:color .2s; line-height:1; }
  .modal-check:hover { color:#27ae60; }

  /* Icon+Title hero row (mimics screenshot top card) */
  .event-hero { display:flex; align-items:center; gap:16px; margin:16px 20px 0; background:var(--surface2); border:1px solid var(--border); border-radius:14px; padding:16px; }
  .hero-icon-btn { width:58px; height:58px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.8rem; cursor:pointer; border:2px solid rgba(255,255,255,.15); transition:transform .15s; flex-shrink:0; position:relative; }
  .hero-icon-btn i { font-size:1.4rem; color:#fff; pointer-events:none; }
  .hero-icon-btn:hover { transform:scale(1.08); }
  .hero-icon-btn .color-dot { position:absolute; bottom:0; right:0; width:14px; height:14px; border-radius:50%; border:2px solid var(--surface2); }
  .hero-fields { flex:1; min-width:0; }
  .hero-input { background:none; border:none; outline:none; color:var(--text); font-family:var(--sans); font-size:1.05rem; font-weight:700; width:100%; }
  .hero-input::placeholder { color:var(--text-muted); }
  .hero-sublabel { font-size:0.72rem; color:var(--text-muted); margin-top:6px; letter-spacing:.06em; text-transform:uppercase; }
  .hero-sub-input { background:none; border:none; outline:none; color:var(--text-muted); font-family:var(--mono); font-size:0.82rem; width:100%; margin-top:2px; }
  .hero-sub-input::placeholder { color:#3a3f54; }

  /* Modal scrollable body */
  .modal-body { padding:14px 20px 20px; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:10px; }
  .modal-body::-webkit-scrollbar { width:4px; }
  .modal-body::-webkit-scrollbar-track { background:transparent; }
  .modal-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

  /* Row blocks (like screenshot rows) */
  .form-block { background:var(--surface2); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
  .form-row { display:flex; align-items:center; padding:13px 16px; gap:12px; }
  .form-row + .form-row { border-top:1px solid var(--border); }
  .form-row-label { font-size:0.8rem; color:var(--text-muted); min-width:80px; letter-spacing:.04em; }
  .form-row-right { flex:1; display:flex; align-items:center; justify-content:flex-end; gap:8px; }

  /* Date/time pill buttons */
  .pill-input { background:var(--border); border:none; border-radius:8px; padding:7px 13px; color:var(--text); font-family:var(--mono); font-size:0.82rem; cursor:pointer; outline:none; transition:background .2s; }
  .pill-input:focus { background:#2e3348; }
  input[type="date"].pill-input, input[type="time"].pill-input { appearance:none; -webkit-appearance:none; color-scheme:dark; }

  /* Action row buttons (Remove Time / Add End Date style) */
  .action-row { display:flex; gap:0; }
  .action-btn { flex:1; border:none; padding:11px 14px; font-family:var(--sans); font-size:0.78rem; font-weight:700; letter-spacing:.05em; cursor:pointer; transition:opacity .2s; }
  .action-btn:first-child { border-radius:0 0 0 12px; }
  .action-btn:last-child  { border-radius:0 0 12px 0; }
  .action-btn.danger  { background:rgba(192,57,43,.25); color:#e74c3c; }
  .action-btn.teal    { background:rgba(42,157,143,.25); color:#2ecc71; }
  .action-btn:hover   { opacity:.8; }

  /* Select row */
  .row-select { background:none; border:none; color:var(--text); font-family:var(--sans); font-size:0.85rem; text-align:right; cursor:pointer; outline:none; }
  .row-select option { background:var(--surface2); }

  /* Icon row value display */
  .row-icon-preview { font-size:1.1rem; }
  .row-value { font-size:0.85rem; color:var(--text); }
  .row-value.muted { color:var(--text-muted); }

  /* Textarea for notes / guests */
  .form-textarea { background:none; border:none; outline:none; color:var(--text); font-family:var(--mono); font-size:0.82rem; width:100%; min-height:38px; resize:none; line-height:1.5; }
  .form-textarea::placeholder { color:#3a3f54; }

  /* ── NEW SEPARATE-BOX FORM FIELDS ── */
  .field-box { background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:12px 16px; display:flex; flex-direction:column; gap:4px; }
  .field-box-label { font-size:0.7rem; color:var(--text-muted); letter-spacing:.07em; text-transform:uppercase; }
  .field-box-input { background:none; border:none; outline:none; color:var(--text); font-family:var(--sans); font-size:0.9rem; font-weight:600; width:100%; padding:0; }
  .field-box-input::placeholder { color:#3a3f54; font-weight:400; }
  .field-box-input[type="date"],
  .field-box-input[type="time"] { color-scheme:dark; font-family:var(--mono); font-size:0.85rem; font-weight:400; }

  /* Two-column date+time row */
  .dt-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

  /* End date toggle button */
  .end-date-toggle { background:rgba(42,157,143,.18); border:1px dashed rgba(42,157,143,.5); border-radius:10px; padding:11px 16px; color:#2ecc71; font-family:var(--sans); font-size:0.78rem; font-weight:700; letter-spacing:.05em; text-align:center; cursor:pointer; transition:background .2s; }
  .end-date-toggle:hover { background:rgba(42,157,143,.28); }
  .end-date-toggle.active { background:rgba(192,57,43,.18); border-color:rgba(192,57,43,.5); color:#e74c3c; }

  /* Guest rows */
  .guest-list { display:flex; flex-direction:column; gap:6px; }
  .guest-row { display:flex; align-items:center; gap:8px; }
  .guest-input { background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:9px 12px; color:var(--text); font-family:var(--mono); font-size:0.82rem; flex:1; outline:none; transition:border-color .2s; }
  .guest-input:focus { border-color:var(--accent2); }
  .guest-input::placeholder { color:#3a3f54; }
  .guest-remove-btn { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1rem; padding:4px 6px; border-radius:6px; transition:color .2s; flex-shrink:0; line-height:1; }
  .guest-remove-btn:hover { color:var(--danger); }
  .add-guest-btn { margin-top:6px; background:rgba(71,200,255,.12); border:1px dashed rgba(71,200,255,.4); border-radius:8px; padding:9px 14px; color:var(--accent2); font-family:var(--sans); font-size:0.78rem; font-weight:700; letter-spacing:.05em; cursor:pointer; width:100%; transition:background .2s; }
  .add-guest-btn:hover { background:rgba(71,200,255,.2); }

  /* Notes char counter */
  .notes-footer { display:flex; justify-content:flex-end; margin-top:4px; }
  .char-count { font-family:var(--mono); font-size:0.68rem; color:var(--text-muted); }
  .char-count.warn { color:#f39c12; }
  .char-count.over { color:var(--danger); }

  /* ── EMOJI PICKER PANEL ── */
  .picker-panel { position:absolute; z-index:200; background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:14px; width:320px; box-shadow:0 12px 40px rgba(0,0,0,.5); }
  .picker-panel h4 { font-size:0.72rem; letter-spacing:.1em; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px; }
  .picker-search { width:100%; background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:7px 12px; color:var(--text); font-family:var(--mono); font-size:0.8rem; outline:none; margin-bottom:10px; transition:border-color .2s; }
  .picker-search:focus { border-color:var(--accent); }
  .picker-search::placeholder { color:var(--text-muted); }
  .icon-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-bottom:12px; max-height:270px; overflow-y:auto; }
  .icon-grid::-webkit-scrollbar { width:3px; }
  .icon-grid::-webkit-scrollbar-thumb { background:var(--border); border-radius:3px; }
  .icon-btn { background:none; border:1px solid transparent; border-radius:10px; padding:14px 8px; cursor:pointer; transition:background .15s, border-color .15s; display:flex; flex-direction:column; align-items:center; gap:6px; color:var(--text); }
  .icon-btn i { font-size:1.6rem; }
  .icon-btn span { font-size:0.65rem; color:var(--text-muted); text-align:center; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; width:100%; max-width:60px; }
  .icon-btn:hover { background:var(--surface2); border-color:var(--border); }
  .icon-btn.selected { background:var(--surface2); border-color:var(--accent); }
  .icon-btn.selected i { color:var(--accent); }
  .color-row { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
  .color-swatch { width:26px; height:26px; border-radius:50%; cursor:pointer; border:2px solid transparent; transition:transform .15s, border-color .15s; flex-shrink:0; }
  .color-swatch:hover, .color-swatch.selected { transform:scale(1.2); border-color:white; }
  .color-picker-input { width:32px; height:26px; border:none; border-radius:6px; cursor:pointer; padding:0; background:none; }

  /* ── VIEW MODAL ── */
  .view-modal { background:var(--surface); border:1px solid var(--border); border-radius:20px; width:100%; max-width:480px; margin:16px; transform:translateY(20px) scale(.97); transition:transform .25s cubic-bezier(.34,1.56,.64,1); overflow:hidden; }
  .overlay.open .view-modal { transform:translateY(0) scale(1); }

  .view-hero { display:flex; align-items:center; gap:16px; padding:24px 24px 20px; }
  .view-icon-circle { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; flex-shrink:0; }
  .view-icon-circle i { font-size:1.7rem; color:#fff; }
  .view-title { font-size:1.15rem; font-weight:800; line-height:1.25; }
  .view-location { font-size:0.82rem; color:var(--text-muted); font-family:var(--mono); margin-top:4px; }

  .view-body { display:flex; flex-direction:column; gap:10px; padding:0 20px 20px; }
  .view-block { background:var(--surface2); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
  .view-row { display:flex; align-items:flex-start; gap:12px; padding:13px 16px; }
  .view-row + .view-row { border-top:1px solid var(--border); }
  .view-row-icon { font-size:1rem; min-width:22px; line-height:1.4; }
  .view-row-content { flex:1; }
  .view-row-label { font-size:0.7rem; color:var(--text-muted); letter-spacing:.07em; text-transform:uppercase; margin-bottom:3px; }
  .view-row-value { font-size:0.88rem; color:var(--text); font-family:var(--mono); line-height:1.5; }
  .view-row-value.empty { color:var(--text-muted); font-style:italic; }

  .view-footer { display:flex; gap:10px; padding:0 20px 20px; }
  .view-edit-btn { flex:1; background:var(--accent); color:#0d0f14; border:none; border-radius:10px; padding:13px 20px; font-family:var(--sans); font-size:0.85rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; cursor:pointer; transition:transform .15s, box-shadow .15s; }
  .view-edit-btn:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(232,255,71,.25); }
  .view-close-btn { background:var(--surface2); color:var(--text-muted); border:1px solid var(--border); border-radius:10px; padding:13px 20px; font-family:var(--sans); font-size:0.85rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; transition:all .2s; }
  .view-close-btn:hover { border-color:var(--danger); color:var(--danger); }

  /* TOAST */
  .toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px); background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:12px 22px; font-size:0.82rem; font-weight:600; letter-spacing:.04em; z-index:300; transition:transform .3s cubic-bezier(.34,1.56,.64,1); white-space:nowrap; }
  .toast.show { transform:translateX(-50%) translateY(0); }
  .toast.success { border-color:var(--accent); color:var(--accent); }
  .toast.error   { border-color:var(--danger); color:var(--danger); }
</style>
</head>
<body>

<header>
  <div class="menu-wrap">
    <select class="menu-select" id="menuSelect">
      <option value="">MENU</option>
    </select>
    <span class="menu-arrow">▾</span>
  </div>
  <span class="page-title">Countdowns</span>
</header>

<main>
  <div class="cards" id="cardList">
    <?php if (empty($items)): ?>
    <div class="empty" id="emptyState">
      <div class="empty-icon">🗓️</div>
      <div class="empty-text">No upcoming events yet</div>
    </div>
    <?php else: ?>
    <?php
      $todayStr = date('Y-m-d');
      foreach ($items as $item):
        $isPast = $item['start_Date'] && $item['start_Date'] < $todayStr;
        $daysNum = '—';
        if ($item['start_Date']) {
            $diff = (new DateTime($item['start_Date']))->diff(new DateTime($todayStr));
            $daysNum = $isPast ? '-'.$diff->days : $diff->days;
        }
        $iconDisp  = $item['icon']  ?: '📅';
        $colorDisp = $item['color'] ?: '#272c3d';
        $dtDisp    = $item['start_Date'] ? date('D d M Y', strtotime($item['start_Date'])) . ($item['start_Time'] ? ' · '.date('g:i A', strtotime($item['start_Time'])) : '') : 'Date TBD';
    ?>
    <div class="card <?= $isPast ? 'is-past' : 'is-future' ?>" data-id="<?= $item['id'] ?>"
         style="background:<?= htmlspecialchars($colorDisp) ?>"
         onclick="openViewModal(<?= htmlspecialchars(json_encode($item)) ?>)">
      <div class="card-icon-wrap">
        <?php if ($iconDisp && strpos($iconDisp, 'fa-') === 0): ?>
          <i class="<?= htmlspecialchars($iconDisp) ?>"></i>
        <?php else: ?>
          <?= htmlspecialchars($iconDisp) ?>
        <?php endif; ?>
      </div>
      <div class="card-info">
        <div class="card-name"><?= htmlspecialchars($item['title']) ?></div>
        <?php if ($item['location']): ?><div class="card-loc"><?= htmlspecialchars($item['location']) ?></div><?php endif; ?>
        <div class="card-dt"><i class="fa-regular fa-bell"></i><?= $dtDisp ?></div>
      </div>
      <div class="card-countdown">
        <?php if ($isPast): ?>
          <span class="cd-past">PASSED</span>
        <?php else: ?>
          <span class="cd-days"><?= $daysNum ?></span>
          <span class="cd-label">days to go</span>
        <?php endif; ?>
      </div>
      <button class="card-delete" onclick="deleteEvent(event,<?= $item['id'] ?>)" title="Remove">✕</button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<footer>
  <button class="search-btn" onclick="document.getElementById('searchInput')?.focus()" title="Search">🔍</button>
  <div class="footer-tabs">
    <button class="foot-tab" data-filter="past">Past <span id="pastBadge"><?= $pastCount ?></span></button>
    <button class="foot-tab active" data-filter="future">Future <span id="futureBadge"><?= $futureCount ?></span></button>
  </div>
  <button class="add-btn" onclick="openAddModal()" title="Add Event">＋</button>
</footer>

<!-- ── VIEW MODAL ─────────────────────────────────────────────────────────────── -->
<div class="overlay" id="viewOverlay" onclick="closeIfOutside(event,'viewOverlay')">
  <div class="view-modal">

    <div class="modal-topbar">
      <button class="modal-x" onclick="closeModal('viewOverlay')">✕</button>
      <span class="modal-topbar-title">Event Details</span>
      <span style="width:40px"></span>
    </div>

    <div class="view-hero">
      <div class="view-icon-circle" id="vIconCircle">📅</div>
      <div>
        <div class="view-title" id="vTitle">—</div>
        <div class="view-location" id="vLocation">—</div>
      </div>
    </div>

    <div class="view-body">

      <!-- Dates & Times -->
      <div class="view-block">
        <div class="view-row">
          <span class="view-row-icon">📅</span>
          <div class="view-row-content">
            <div class="view-row-label">Start</div>
            <div class="view-row-value" id="vStartDT">—</div>
          </div>
        </div>
        <div class="view-row" id="vEndRow" style="display:none">
          <span class="view-row-icon">🏁</span>
          <div class="view-row-content">
            <div class="view-row-label">End</div>
            <div class="view-row-value" id="vEndDT">—</div>
          </div>
        </div>
      </div>

      <!-- Guests -->
      <div class="view-block">
        <div class="view-row">
          <span class="view-row-icon">👥</span>
          <div class="view-row-content">
            <div class="view-row-label">Guests</div>
            <div class="view-row-value" id="vGuests">—</div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="view-block">
        <div class="view-row">
          <span class="view-row-icon">📝</span>
          <div class="view-row-content">
            <div class="view-row-label">Notes</div>
            <div class="view-row-value" id="vNotes">—</div>
          </div>
        </div>
      </div>

    </div>

    <div class="view-footer">
      <button class="view-close-btn" onclick="closeModal('viewOverlay')">Close</button>
      <button class="view-edit-btn" onclick="switchToEdit()">✎ Edit Event</button>
    </div>

  </div>
</div>

<!-- ── EVENT MODAL (Add + Edit) ─────────────────────────────────────────────── -->
<div class="overlay" id="eventOverlay" onclick="closeIfOutside(event,'eventOverlay')">
  <div class="modal">

    <!-- Top bar -->
    <div class="modal-topbar">
      <button class="modal-x" onclick="closeModal('eventOverlay')">✕</button>
      <span class="modal-topbar-title" id="modalTitle">New Event</span>
      <button class="modal-check" onclick="submitEvent()" title="Save">✓</button>
    </div>

    <!-- Hero: icon + color picker trigger only -->
    <div style="display:flex; align-items:center; padding:16px 20px 4px; gap:14px;">
      <button class="hero-icon-btn" id="heroIconBtn" onclick="togglePicker(event)" style="background:#c0392b" title="Change icon & color">
        <i id="heroIconDisplay" class="fa-solid fa-calendar-days"></i>
        <span class="color-dot" id="heroColorDot" style="background:#c0392b"></span>
      </button>
      <span style="font-size:0.78rem; color:var(--text-muted); letter-spacing:.05em;">Tap to change icon &amp; color</span>
    </div>

    <!-- Icon + Color picker (hidden by default) -->
    <div class="picker-panel" id="pickerPanel" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);">
      <h4>Choose Icon</h4>
      <input class="picker-search" type="text" id="iconSearch" placeholder="Search icons…" autocomplete="off">
      <div class="icon-grid" id="iconGrid"></div>
      <h4>Background Color</h4>
      <div class="color-row" id="colorSwatches"></div>
    </div>

    <!-- Scrollable fields -->
    <div class="modal-body">

      <!-- Title -->
      <div class="field-box">
        <label class="field-box-label" for="fTitle">Title</label>
        <input class="field-box-input" type="text" id="fTitle" placeholder="Event name" autocomplete="off">
      </div>

      <!-- Location -->
      <div class="field-box">
        <label class="field-box-label" for="fLocation">Location</label>
        <input class="field-box-input" type="text" id="fLocation" placeholder="e.g. HOB Orlando" autocomplete="off">
      </div>

      <!-- Start Date + Time -->
      <div class="dt-row">
        <div class="field-box">
          <label class="field-box-label" for="fStartDate">Start Date</label>
          <input class="field-box-input" type="date" id="fStartDate">
        </div>
        <div class="field-box">
          <label class="field-box-label" for="fStartTime">Start Time</label>
          <input class="field-box-input" type="time" id="fStartTime">
        </div>
      </div>

      <!-- Add End Date toggle -->
      <button class="end-date-toggle" id="endDateToggle" onclick="toggleEndDate()">＋ Add End Date</button>

      <!-- End Date + Time (hidden until toggled) -->
      <div id="endDateBlock" style="display:none">
        <div class="dt-row">
          <div class="field-box">
            <label class="field-box-label" for="fEndDate">End Date</label>
            <input class="field-box-input" type="date" id="fEndDate">
          </div>
          <div class="field-box">
            <label class="field-box-label" for="fEndTime">End Time</label>
            <input class="field-box-input" type="time" id="fEndTime">
          </div>
        </div>
      </div>

      <!-- Calendar -->
      <div class="field-box">
        <label class="field-box-label" for="fCalendar">📅 Calendar</label>
        <select class="field-box-input" id="fCalendar" style="cursor:pointer">
          <option value="">None</option>
          <?php foreach ($calendars as $cal): ?>
          <option value="<?= $cal['id'] ?>"><?= htmlspecialchars($cal['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Guests -->
      <div class="field-box">
        <div class="field-box-label">👥 Guests</div>
        <div class="guest-list" id="guestList"></div>
        <button type="button" class="add-guest-btn" onclick="addGuestField('')">＋ Add Guest</button>
      </div>

      <!-- Notes -->
      <div class="field-box">
        <label class="field-box-label" for="fNotes">📝 Notes</label>
        <textarea id="fNotes" placeholder="e.g. Birthday, Anniversary etc" rows="4" maxlength="250"
          style="background:none;border:none;outline:none;color:var(--text);font-family:var(--mono);font-size:0.82rem;width:100%;resize:none;line-height:1.5;"
          oninput="updateCharCount()"></textarea>
        <div class="notes-footer">
          <span class="char-count" id="charCount">0 / 250</span>
        </div>
      </div>

    </div><!-- /modal-body -->
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// ── STATE ────────────────────────────────────────────────────────────────────
let currentFilter = 'future';
let items = <?= json_encode(array_values($items)) ?>;
let editingId = null;
let currentIcon  = 'fa-solid fa-calendar-days';
let currentColor = '#c0392b';

// ── ICON / COLOR DATA ─────────────────────────────────────────────────────────
const ICON_LIST = <?= json_encode($iconList) ?>;

const COLORS = ['#c0392b','#e74c3c','#e67e22','#f39c12','#27ae60',
                '#2ecc71','#2980b9','#3498db','#8e44ad','#9b59b6',
                '#16a085','#2a9d8f','#d35400','#c0392b','#1a1a2e','#2c3e50'];

// Build icon grid
function buildIconGrid(filter) {
  const grid = document.getElementById('iconGrid');
  grid.innerHTML = '';
  const list = filter
    ? ICON_LIST.filter(ic => ic.label.toLowerCase().includes(filter.toLowerCase()) || ic.class.toLowerCase().includes(filter.toLowerCase()))
    : ICON_LIST;
  list.forEach(ic => {
    const b = document.createElement('button');
    b.className = 'icon-btn' + (ic.class === currentIcon ? ' selected' : '');
    b.type = 'button';
    b.title = ic.label;
    b.innerHTML = `<i class="${ic.class}"></i><span>${ic.label}</span>`;
    b.onclick = () => { setIcon(ic.class); buildIconGrid(document.getElementById('iconSearch').value); };
    grid.appendChild(b);
  });
}

buildIconGrid('');

document.getElementById('iconSearch').addEventListener('input', e => {
  buildIconGrid(e.target.value);
});

// Build color swatches
const swatchRow = document.getElementById('colorSwatches');
COLORS.forEach(c => {
  const s = document.createElement('div');
  s.className = 'color-swatch'; s.style.background = c; s.dataset.color = c;
  s.onclick = () => setColor(c);
  swatchRow.appendChild(s);
});
// Custom color input
const customColor = document.createElement('input');
customColor.type = 'color'; customColor.className = 'color-picker-input'; customColor.title = 'Custom color';
customColor.oninput = e => setColor(e.target.value);
swatchRow.appendChild(customColor);

function setIcon(cls) {
  currentIcon = cls;
  const el = document.getElementById('heroIconDisplay');
  el.className = cls;
}
function setColor(c) {
  currentColor = c;
  document.getElementById('heroIconBtn').style.background = c;
  document.getElementById('heroColorDot').style.background = c;
  document.querySelectorAll('.color-swatch').forEach(s => s.classList.toggle('selected', s.dataset.color === c));
}
function togglePicker(e) {
  e.stopPropagation();
  const p = document.getElementById('pickerPanel');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', e => {
  const p = document.getElementById('pickerPanel');
  if (!p.contains(e.target) && e.target.id !== 'heroIconBtn' && !e.target.closest('#heroIconBtn')) {
    p.style.display = 'none';
  }
});

// ── FILTER ───────────────────────────────────────────────────────────────────
document.querySelectorAll('.filter-tab, .stat-btn, .foot-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    const f = btn.dataset.filter;
    if (!f) return;
    currentFilter = f;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
    document.querySelectorAll('.stat-btn').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
    document.querySelectorAll('.foot-tab').forEach(t => t.classList.toggle('active', t.dataset.filter === f));
    applyFilter();
  });
});

function applyFilter() {
  const today = new Date(); today.setHours(0,0,0,0);
  let futureN = 0, pastN = 0;
  document.querySelectorAll('.card').forEach(c => {
    const item = items.find(i => i.id == c.dataset.id);
    let isFuture = true;
    if (item && item.start_Date) {
      const d = new Date(item.start_Date + 'T00:00:00');
      isFuture = d >= today;
    }
    if (isFuture) futureN++; else pastN++;
    const show = (currentFilter === 'future') ? isFuture : !isFuture;
    c.style.display = show ? '' : 'none';
  });
  document.getElementById('futureBadge').textContent  = futureN;
  document.getElementById('pastBadge').textContent    = pastN;

  const empty = document.getElementById('emptyState');
  if (empty) {
    const visibleCards = document.querySelectorAll('.card:not([style*="display: none"])').length;
    empty.style.display = visibleCards === 0 ? '' : 'none';
  }
}

// ── VIEW MODAL ───────────────────────────────────────────────────────────────
let viewingItem = null;

function openViewModal(item) {
  viewingItem = item;

  const icon  = item.icon  || 'fa-solid fa-calendar-days';
  const color = item.color || '#272c3d';

  const vCircle = document.getElementById('vIconCircle');
  vCircle.style.background = color;
  if (icon.startsWith('fa-')) {
    vCircle.innerHTML = `<i class="${icon}"></i>`;
  } else {
    vCircle.textContent = icon;
  }
  document.getElementById('vTitle').textContent        = item.title    || '—';
  document.getElementById('vLocation').textContent     = item.location || '—';

  // Format start
  let startStr = 'Date TBD';
  if (item.start_Date) {
    startStr = new Date(item.start_Date + 'T00:00:00').toDateString();
    if (item.start_Time) startStr += ' · ' + fmtTime(item.start_Time);
  }
  document.getElementById('vStartDT').textContent = startStr;

  // Format end
  if (item.end_Date) {
    let endStr = new Date(item.end_Date + 'T00:00:00').toDateString();
    if (item.end_Time) endStr += ' · ' + fmtTime(item.end_Time);
    document.getElementById('vEndDT').textContent  = endStr;
    document.getElementById('vEndRow').style.display = '';
  } else {
    document.getElementById('vEndRow').style.display = 'none';
  }

  const guests = item.Guests || item.guests || '';
  const notes  = item.Notes  || item.notes  || '';

  const vGuests = document.getElementById('vGuests');
  if (guests) {
    const guestNames = guests.split('||').map(g => g.trim()).filter(Boolean);
    vGuests.textContent = guestNames.join(', ');
    vGuests.className = 'view-row-value';
  } else {
    vGuests.textContent = 'None';
    vGuests.className = 'view-row-value empty';
  }

  const vNotes = document.getElementById('vNotes');
  vNotes.textContent = notes || 'None';
  vNotes.className = 'view-row-value' + (notes ? '' : ' empty');

  document.getElementById('viewOverlay').classList.add('open');
}

function switchToEdit() {
  closeModal('viewOverlay');
  if (viewingItem) openEditModal(viewingItem);
}

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  const ampm = h >= 12 ? 'PM' : 'AM';
  const h12  = h % 12 || 12;
  return `${h12}:${String(m).padStart(2,'0')} ${ampm}`;
}

// ── MODAL OPEN/CLOSE ─────────────────────────────────────────────────────────
function openAddModal() {
  editingId = null;
  document.getElementById('modalTitle').textContent = 'New Event';
  resetForm();
  document.getElementById('eventOverlay').classList.add('open');
  setTimeout(() => document.getElementById('fTitle').focus(), 250);
}

function openEditModal(item) {
  editingId = item.id;
  document.getElementById('modalTitle').textContent = 'Edit Event';
  resetForm();

  document.getElementById('fTitle').value     = item.title      || '';
  document.getElementById('fLocation').value  = item.location   || '';
  document.getElementById('fStartDate').value = item.start_Date || '';
  document.getElementById('fStartTime').value = item.start_Time || '';
  document.getElementById('fEndDate').value   = item.end_Date   || '';
  document.getElementById('fEndTime').value   = item.end_Time   || '';
  document.getElementById('fNotes').value     = item.Notes || item.notes || '';
  updateCharCount();

  const cal = document.getElementById('fCalendar');
  cal.value = item.Calendar || '';

  // Populate guests — split on || into individual fields
  const rawGuests = item.Guests || item.guests || '';
  if (rawGuests.trim()) {
    rawGuests.split('||').map(g => g.trim()).filter(Boolean).forEach(name => addGuestField(name));
  }

  // Show end date block if there's an end date
  if (item.end_Date) {
    document.getElementById('endDateBlock').style.display = '';
    const toggle = document.getElementById('endDateToggle');
    toggle.textContent = '— Remove End Date';
    toggle.classList.add('active');
  }

  const icon  = item.icon  || 'fa-solid fa-calendar-days';
  const color = item.color || '#c0392b';
  setIcon(icon);
  setColor(color);

  document.getElementById('eventOverlay').classList.add('open');
}

function resetForm() {
  ['fTitle','fLocation','fStartDate','fStartTime','fEndDate','fEndTime','fNotes'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('fCalendar').value = '';
  // Reset end date block
  document.getElementById('endDateBlock').style.display = 'none';
  const toggle = document.getElementById('endDateToggle');
  toggle.textContent = '＋ Add End Date';
  toggle.classList.remove('active');
  // Reset picker
  document.getElementById('pickerPanel').style.display = 'none';
  // Reset guests
  document.getElementById('guestList').innerHTML = '';
  // Reset char count
  updateCharCount();
  setIcon('fa-solid fa-calendar-days');
  setColor('#c0392b');
}

// ── GUESTS ───────────────────────────────────────────────────────────────────
function addGuestField(value) {
  const list = document.getElementById('guestList');
  const row  = document.createElement('div');
  row.className = 'guest-row';
  row.innerHTML = `
    <input type="text" class="guest-input" placeholder="Guest name" value="${escHtml(value)}" autocomplete="off">
    <button type="button" class="guest-remove-btn" title="Remove" onclick="this.closest('.guest-row').remove()">✕</button>
  `;
  list.appendChild(row);
  row.querySelector('.guest-input').focus();
}

// ── NOTES CHAR COUNTER ────────────────────────────────────────────────────────
function updateCharCount() {
  const notes = document.getElementById('fNotes');
  const count = document.getElementById('charCount');
  if (!notes || !count) return;
  const len = notes.value.length;
  count.textContent = `${len} / 250`;
  count.className = 'char-count' + (len >= 250 ? ' over' : len >= 200 ? ' warn' : '');
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function closeIfOutside(e, id) { if (e.target.id === id) closeModal(id); }

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal('eventOverlay');
    closeModal('viewOverlay');
  }
});

// ── END DATE TOGGLE ───────────────────────────────────────────────────────────
function toggleEndDate() {
  const block  = document.getElementById('endDateBlock');
  const toggle = document.getElementById('endDateToggle');
  const isHidden = block.style.display === 'none';
  block.style.display = isHidden ? '' : 'none';
  toggle.textContent  = isHidden ? '— Remove End Date' : '＋ Add End Date';
  toggle.classList.toggle('active', isHidden);
  if (!isHidden) {
    document.getElementById('fEndDate').value = '';
    document.getElementById('fEndTime').value = '';
  }
}
function clearTime(fieldId) {
  document.getElementById(fieldId).value = '';
}

// ── SUBMIT (ADD or EDIT) ─────────────────────────────────────────────────────
function submitEvent() {
  const title = document.getElementById('fTitle').value.trim();
  if (!title) { showToast('Event name is required', 'error'); return; }

  // Collect guest fields and join with ||
  const guestInputs = document.querySelectorAll('#guestList .guest-input');
  const guestsValue = Array.from(guestInputs)
    .map(inp => inp.value.trim())
    .filter(Boolean)
    .join('||');

  const fd = new FormData();
  fd.append('action',     editingId ? 'edit' : 'add');
  if (editingId) fd.append('id', editingId);
  fd.append('title',      title);
  fd.append('location',   document.getElementById('fLocation').value.trim());
  fd.append('icon',       currentIcon);
  fd.append('color',      currentColor);
  fd.append('start_Date', document.getElementById('fStartDate').value);
  fd.append('start_Time', document.getElementById('fStartTime').value);
  fd.append('end_Date',   document.getElementById('fEndDate').value);
  fd.append('end_Time',   document.getElementById('fEndTime').value);
  fd.append('Calendar',   document.getElementById('fCalendar').value);
  fd.append('Guests',     guestsValue);
  fd.append('Notes',      document.getElementById('fNotes').value.trim());

  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.error) { showToast(data.error, 'error'); return; }
      closeModal('eventOverlay');

      if (editingId) {
        // Update in-memory
        const idx = items.findIndex(i => i.id == editingId);
        if (idx > -1) items[idx] = data.item;
        // Update card DOM
        const card = document.querySelector(`.card[data-id="${editingId}"]`);
        if (card) { card.replaceWith(buildCardEl(data.item)); }
        showToast('Event updated ✓', 'success');
      } else {
        items.unshift(data.item);
        document.getElementById('cardList').prepend(buildCardEl(data.item));
        const empty = document.getElementById('emptyState');
        if (empty) empty.style.display = 'none';
        showToast('Event added ✓', 'success');
      }
      applyFilter();
    })
    .catch(() => showToast('Network error', 'error'));
}

// ── BUILD CARD ELEMENT ───────────────────────────────────────────────────────
function buildCardEl(item) {
  const today = new Date(); today.setHours(0,0,0,0);
  const icon  = item.icon  || '📅';
  const color = item.color || '#272c3d';
  let isPast = false, daysHtml = '<span class="cd-days">—</span><span class="cd-label">days</span>';

  if (item.start_Date) {
    const d = new Date(item.start_Date + 'T00:00:00');
    isPast = d < today;
    const diff = Math.round(Math.abs(d - today) / 86400000);
    daysHtml = isPast
      ? '<span class="cd-past">PASSED</span>'
      : `<span class="cd-days">${diff}</span><span class="cd-label">days to go</span>`;
  }

  const dtStr = item.start_Date
    ? new Date(item.start_Date + 'T00:00:00').toDateString()
      + (item.start_Time ? ' · ' + item.start_Time.substring(0,5) : '')
    : 'Date TBD';

  const iconHtml = icon.startsWith('fa-')
    ? `<i class="${escHtml(icon)}"></i>`
    : escHtml(icon);

  const div = document.createElement('div');
  div.className = 'card ' + (isPast ? 'is-past' : 'is-future');
  div.dataset.id = item.id;
  div.style.background = color;
  div.innerHTML = `
    <div class="card-icon-wrap">${iconHtml}</div>
    <div class="card-info">
      <div class="card-name">${escHtml(item.title || '')}</div>
      ${item.location ? `<div class="card-loc">${escHtml(item.location)}</div>` : ''}
      <div class="card-dt"><i class="fa-regular fa-bell"></i>${escHtml(dtStr)}</div>
    </div>
    <div class="card-countdown">${daysHtml}</div>
    <button class="card-delete" title="Remove">✕</button>
  `;
  // Fix onclick to pass real object, not attr string
  div.onclick = () => openViewModal(item);
  div.querySelector('.card-delete').onclick = e => deleteEvent(e, item.id);
  return div;
}

// ── DELETE ───────────────────────────────────────────────────────────────────
function deleteEvent(e, id) {
  e.stopPropagation();
  if (!confirm('Remove this event?')) return;
  const fd = new FormData();
  fd.append('action','delete'); fd.append('id', id);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.querySelector(`.card[data-id="${id}"]`)?.remove();
        items = items.filter(i => i.id != id);
        applyFilter();
        showToast('Event removed', 'success');
        if (!document.querySelector('.card')) {
          let empty = document.getElementById('emptyState');
          if (!empty) {
            empty = document.createElement('div');
            empty.id = 'emptyState'; empty.className = 'empty';
            empty.innerHTML = '<div class="empty-icon">🗓️</div><div class="empty-text">No upcoming events yet</div>';
            document.getElementById('cardList').appendChild(empty);
          }
          empty.style.display = '';
        }
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

// ── HELPERS ──────────────────────────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = `toast ${type}`;
  void t.offsetWidth; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

// Init filter
applyFilter();
</script>
</body>
</html>