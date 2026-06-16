<?php
// ─── DB CONFIG ────────────────────────────────────────────────────────────────
// NOTE: this file depends on these DB objects existing already:
//   views:      vw_countdowns_items, vw_countdowns_counts, vw_countdowns_calendar_usage
//   procedures: sp_countdowns_add_item, sp_countdowns_delete_calendar
require_once 'db/db_hosted.php';
// $pdo is provided by db_hosted.php

// ─── LOAD ICONS FROM DB ───────────────────────────────────────────────────────
$iconList = [];
try {
    $iconList = $pdo->query(
        "SELECT class, label FROM icons ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // icons table may not exist yet; gracefully ignore
}

// ─── LOAD CALENDARS FOR DROPDOWN ─────────────────────────────────────────────
$calendars = [];
try {
    $calendars = $pdo->query(
        "SELECT id, label, item_count FROM vw_countdowns_calendar_usage ORDER BY label"
    )->fetchAll();
} catch (Exception $e) {
    // view may not exist yet; gracefully ignore
}

// ─── AJAX HANDLERS ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── ADD / EDIT ────────────────────────────────────────────────────────────
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

        if ($fields['title'] === '') {
            echo json_encode(['error' => 'Title required']); exit;
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("CALL sp_countdowns_add_item(?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute(array_values($fields));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // release the proc's result set before further queries
            $fields['id'] = (int)($row['id'] ?? 0);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE countdowns_items SET
                title=?,location=?,icon=?,color=?,start_Date=?,start_Time=?,
                end_Date=?,end_Time=?,Calendar=?,Guests=?,Notes=? WHERE id=?");
            $stmt->execute([...array_values($fields), $id]);
            $fields['id'] = $id;
        }

        echo json_encode(['success' => true, 'item' => $fields]);
        exit;
    }

    // ── CALENDAR ADD ──────────────────────────────────────────────────────────
    if ($action === 'cal_add') {
        $label = trim($_POST['label'] ?? '');
        if ($label === '') { echo json_encode(['error' => 'Label required']); exit; }
        $stmt = $pdo->prepare("INSERT INTO countdowns_calendar (label) VALUES (?)");
        $stmt->execute([$label]);
        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;
    }

    // ── CALENDAR DELETE ───────────────────────────────────────────────────────
    if ($action === 'cal_delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("CALL sp_countdowns_delete_calendar(?)");
        $stmt->execute([$id]);
        $stmt->closeCursor();
        echo json_encode(['success' => true]);
        exit;
    }

    // ── EVENT DELETE ──────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM countdowns_items WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── EVENT GET ─────────────────────────────────────────────────────────────
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM vw_countdowns_items WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        echo json_encode($row ?: ['error' => 'Not found']);
        exit;
    }
}

// ─── LOAD ALL ITEMS ───────────────────────────────────────────────────────────
$items = $pdo->query(
    "SELECT * FROM vw_countdowns_items ORDER BY start_Date ASC, id DESC"
)->fetchAll();

// ─── FUTURE / PAST BADGE COUNTS ───────────────────────────────────────────────
$futureCount = 0;
$pastCount   = 0;
try {
    $counts = $pdo->query("SELECT future_count, past_count FROM vw_countdowns_counts")->fetch();
    if ($counts) {
        $futureCount = (int)$counts['future_count'];
        $pastCount   = (int)$counts['past_count'];
    }
} catch (Exception $e) {
    // view may not exist yet; gracefully ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Countdowns</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1/dist/iconify-icon.min.js"></script>

  <!-- Coloris colour picker -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css">
  <script src="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js"></script>

  <!-- App styles -->
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────────────────────────────── -->
<header>
  <div class="menu-wrap">
    <select class="menu-select" id="calendarFilter">
      <option value="">ALL</option>
      <?php foreach ($calendars as $cal): ?>
        <option value="<?= $cal['id'] ?>"><?= htmlspecialchars($cal['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <span class="menu-arrow">▾</span>
  </div>
  <span class="page-title">Countdowns</span>
</header>

<!-- ── MAIN CARD LIST ─────────────────────────────────────────────────────────── -->
<main>
  <div class="cards" id="cardList">
    <?php if (empty($items)): ?>
      <div class="empty" id="emptyState">
        <div class="empty-icon">🗓️</div>
        <div class="empty-text">No upcoming events yet</div>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item):
        $isPast    = (bool)$item['is_past'];
        $daysNum   = $item['days_until'] ?? '—';
        $iconDisp  = $item['icon']  ?: '📅';
        $colorDisp = $item['color'] ?: '#272c3d';
        $dtDisp    = $item['start_Date']
          ? date('D d M Y', strtotime($item['start_Date']))
            . ($item['start_Time'] ? ' · ' . date('g:i A', strtotime($item['start_Time'])) : '')
          : 'Date TBD';
      ?>
        <div class="card <?= $isPast ? 'is-past' : 'is-future' ?>"
             data-id="<?= $item['id'] ?>"
             style="background:<?= htmlspecialchars($colorDisp) ?>"
             onclick="openViewModal(<?= htmlspecialchars(json_encode($item)) ?>)">

          <div class="card-icon-wrap">
            <?php if ($iconDisp && strpos($iconDisp, 'fa-') === 0): ?>
              <i class="<?= htmlspecialchars($iconDisp) ?>"></i>
            <?php elseif ($iconDisp && strpos($iconDisp, 'iconify:') === 0): ?>
              <iconify-icon icon="<?= htmlspecialchars(substr($iconDisp, 8)) ?>" width="20" height="20" style="color:#fff"></iconify-icon>
            <?php else: ?>
              <?= htmlspecialchars($iconDisp) ?>
            <?php endif; ?>
          </div>

          <div class="card-info">
            <div class="card-name"><?= htmlspecialchars($item['title']) ?></div>
            <?php if ($item['location']): ?>
              <div class="card-loc"><?= htmlspecialchars($item['location']) ?></div>
            <?php endif; ?>
            <div class="card-dt">
              <i class="fa-regular fa-bell"></i><?= $dtDisp ?>
            </div>
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

<!-- ── FOOTER ─────────────────────────────────────────────────────────────────── -->
<footer>
  <div class="footer-tabs">
    <button class="foot-tab"        data-filter="past">Past <span id="pastBadge"><?= $pastCount ?></span></button>
    <button class="foot-tab active" data-filter="future">Future <span id="futureBadge"><?= $futureCount ?></span></button>
  </div>

  <!-- FAB tray -->
  <div class="fab-wrap" id="fabWrap">
    <div class="fab-tray" id="fabTray">
      <button class="fab-tray-item" onclick="closeFab(); openCalendarModal()">
        <span class="fab-tray-icon"><i class="fa-solid fa-calendar-alt"></i></span>
        <span class="fab-tray-label">Calendar</span>
      </button>
      <button class="fab-tray-item" onclick="closeFab(); openAddModal()">
        <span class="fab-tray-icon"><i class="fa-solid fa-stopwatch"></i></span>
        <span class="fab-tray-label">Countdown</span>
      </button>
    </div>
    <button class="add-btn" id="fabBtn" onclick="toggleFab()" title="Add">＋</button>
  </div>
</footer>

<!-- FAB backdrop (closes tray on outside click) -->
<div id="fabBackdrop" onclick="closeFab()"></div>

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
        <div class="view-title"    id="vTitle">—</div>
        <div class="view-location" id="vLocation">—</div>
      </div>
    </div>

    <div class="view-body">

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

      <div class="view-block">
        <div class="view-row">
          <span class="view-row-icon">👥</span>
          <div class="view-row-content">
            <div class="view-row-label">Guests</div>
            <div class="view-row-value" id="vGuests">—</div>
          </div>
        </div>
      </div>

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
      <button class="view-edit-btn"  onclick="switchToEdit()">✎ Edit Event</button>
    </div>

  </div>
</div>

<!-- ── EVENT MODAL (Add + Edit) ───────────────────────────────────────────────── -->
<div class="overlay" id="eventOverlay" onclick="closeIfOutside(event,'eventOverlay')">
  <div class="modal">

    <div class="modal-topbar">
      <button class="modal-x"     onclick="closeModal('eventOverlay')">✕</button>
      <span   class="modal-topbar-title" id="modalTitle">New Event</span>
      <button class="modal-check" onclick="submitEvent()" title="Save">✓</button>
    </div>

    <!-- Icon + colour trigger -->
    <div style="display:flex; align-items:center; padding:16px 20px 4px; gap:14px;">
      <button class="hero-icon-btn" id="heroIconBtn" onclick="togglePicker(event)"
              style="background:#c0392b" title="Change icon & color">
        <i id="heroIconDisplay" class="fa-solid fa-calendar-days"></i>
        <span class="color-dot" id="heroColorDot" style="background:#c0392b"></span>
      </button>
      <span style="font-size:0.78rem; color:var(--text-muted); letter-spacing:.05em;">Tap to change icon &amp; color</span>
    </div>

    <!-- Icon + colour picker panel (hidden by default) -->
    <div class="picker-panel" id="pickerPanel"
         style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);">
      <h4>Choose Icon</h4>
      <input class="picker-search" type="text" id="iconSearch"
             placeholder="Search icons…" autocomplete="off">
      <div class="icon-grid" id="iconGrid"></div>
      <h4>Background Color</h4>
      <div class="color-row" id="colorSwatches"></div>
      <!-- Hidden input Coloris attaches to -->
      <input type="text" id="colorisInput"
             style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;" readonly>
    </div>

    <!-- Scrollable form fields -->
    <div class="modal-body">

      <div class="field-box">
        <label class="field-box-label" for="fTitle">Title</label>
        <input class="field-box-input" type="text" id="fTitle"
               placeholder="Event name" autocomplete="off">
      </div>

      <div class="dt-row">
        <div class="field-box">
          <label class="field-box-label" for="fLocation">Location</label>
          <input class="field-box-input" type="text" id="fLocation"
                 placeholder="e.g. HOB Orlando" autocomplete="off">
        </div>
        <div class="field-box">
          <label class="field-box-label" for="fCalendar">📅 Calendar</label>
          <div class="menu-wrap" style="margin-top:2px;">
            <select class="field-box-input" id="fCalendar"
                    style="padding:0; font-size:0.9rem; font-weight:600; cursor:pointer; color-scheme:dark;">
              <option value="">None</option>
              <?php foreach ($calendars as $cal): ?>
                <option value="<?= $cal['id'] ?>"><?= htmlspecialchars($cal['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

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

      <button class="end-date-toggle" id="endDateToggle" onclick="toggleEndDate()">＋ Add End Date</button>

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

      <div class="field-box">
        <div class="field-box-label">👥 Guests</div>
        <div class="guest-list" id="guestList"></div>
        <button type="button" class="add-guest-btn" onclick="addGuestField('')">＋ Add Guest</button>
      </div>

      <div class="field-box">
        <label class="field-box-label" for="fNotes">📝 Notes</label>
        <textarea class="form-textarea" id="fNotes"
                  placeholder="e.g. Birthday, Anniversary etc"
                  rows="4" maxlength="250"
                  oninput="updateCharCount()"></textarea>
        <div class="notes-footer">
          <span class="char-count" id="charCount">0 / 250</span>
        </div>
      </div>

    </div><!-- /modal-body -->
  </div>
</div>

<!-- ── CALENDAR MODAL ──────────────────────────────────────────────────────────── -->
<div class="overlay" id="calendarOverlay" onclick="closeIfOutside(event,'calendarOverlay')">
  <div class="modal" style="max-height:75vh;">
    <div class="modal-topbar">
      <button class="modal-x" onclick="closeModal('calendarOverlay')">✕</button>
      <span class="modal-topbar-title">Calendars</span>
      <span style="width:40px"></span>
    </div>
    <div class="modal-body" id="calendarListWrap" style="gap:8px;padding-top:16px;">
      <!-- populated by JS -->
    </div>
    <div style="padding:0 20px 20px;">
      <div class="cal-add-row" id="calAddRow">
        <input type="text" id="calNewName" class="guest-input"
               placeholder="Calendar name…" autocomplete="off"
               onkeydown="if(event.key==='Enter') saveNewCalendar()">
        <button class="add-btn"
                style="width:36px;height:36px;font-size:1.1rem;flex-shrink:0;"
                onclick="saveNewCalendar()" title="Save">✓</button>
      </div>
      <button class="add-guest-btn" id="showCalAddBtn"
              onclick="showCalAddForm()" style="margin-top:4px;">＋ Add Calendar</button>
    </div>
  </div>
</div>

<!-- ── TOAST ───────────────────────────────────────────────────────────────────── -->
<div class="toast" id="toast"></div>

<!-- ═══════════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════════════════════════ -->
<script>
// ── STATE ─────────────────────────────────────────────────────────────────────
let currentFilter   = 'future';
let currentCalendar = '';
let items           = <?= json_encode(array_values($items)) ?>;
let calendars       = <?= json_encode(array_values($calendars)) ?>;
let editingId       = null;
let currentIcon     = 'fa-solid fa-calendar-days';
let currentColor    = '#c0392b';
let fabOpen         = false;
let viewingItem     = null;

// ── CONSTANTS ─────────────────────────────────────────────────────────────────
const ICON_LIST = <?= json_encode($iconList) ?>;

const COLORS = [
  '#c0392b','#e74c3c','#e67e22','#f39c12','#27ae60',
  '#2ecc71','#2980b9','#3498db','#8e44ad','#9b59b6',
  '#16a085','#2a9d8f','#d35400','#c0392b','#1a1a2e','#2c3e50'
];

// ── HELPERS ───────────────────────────────────────────────────────────────────
function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = `toast ${type}`;
  void t.offsetWidth;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2800);
}

function fmtTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':').map(Number);
  return `${h % 12 || 12}:${String(m).padStart(2,'0')} ${h >= 12 ? 'PM' : 'AM'}`;
}

function closeModal(id)             { document.getElementById(id).classList.remove('open'); }
function closeIfOutside(e, id)      { if (e.target.id === id) closeModal(id); }

// ── ICON RENDER HELPER ────────────────────────────────────────────────────────
function iconHtmlFor(cls, size) {
  return cls.startsWith('iconify:')
    ? `<iconify-icon icon="${cls.slice(8)}" width="${size}" height="${size}"></iconify-icon>`
    : `<i class="${cls}"></i>`;
}

// ── ICON GRID ─────────────────────────────────────────────────────────────────
function buildIconGrid(filter) {
  const grid = document.getElementById('iconGrid');
  grid.innerHTML = '';
  const list = filter
    ? ICON_LIST.filter(ic =>
        ic.label.toLowerCase().includes(filter.toLowerCase()) ||
        ic.class.toLowerCase().includes(filter.toLowerCase()))
    : ICON_LIST;
  list.forEach(ic => {
    const b = document.createElement('button');
    b.className = 'icon-btn' + (ic.class === currentIcon ? ' selected' : '');
    b.type      = 'button';
    b.title     = ic.label;
    b.innerHTML = `${iconHtmlFor(ic.class, 26)}<span>${ic.label}</span>`;
    b.onclick   = () => { setIcon(ic.class); buildIconGrid(document.getElementById('iconSearch').value); };
    grid.appendChild(b);
  });
}

buildIconGrid('');
document.getElementById('iconSearch').addEventListener('input', e => buildIconGrid(e.target.value));

// ── COLOR SWATCHES ────────────────────────────────────────────────────────────
const swatchRow = document.getElementById('colorSwatches');
COLORS.forEach(c => {
  const s = document.createElement('div');
  s.className       = 'color-swatch';
  s.style.background = c;
  s.dataset.color   = c;
  s.onclick         = () => setColor(c);
  swatchRow.appendChild(s);
});

// "Custom…" rainbow button triggers Coloris
const customBtn = document.createElement('button');
customBtn.type       = 'button';
customBtn.title      = 'Custom color';
customBtn.style.cssText = 'width:26px;height:26px;border-radius:50%;cursor:pointer;border:2px dashed rgba(255,255,255,0.5);background:conic-gradient(red,yellow,lime,cyan,blue,magenta,red);flex-shrink:0;padding:0;';
customBtn.onclick = (e) => {
  e.stopPropagation();
  const inp = document.getElementById('colorisInput');
  inp.value = currentColor;
  inp.dispatchEvent(new MouseEvent('click', { bubbles: true }));
};
swatchRow.appendChild(customBtn);

Coloris({
  el: '#colorisInput',
  theme: 'polaroid',
  themeMode: 'dark',
  format: 'hex',
  alpha: false,
  swatches: COLORS,
  onChange: (color) => setColor(color)
});

function setIcon(cls) {
  currentIcon = cls;
  const btn = document.getElementById('heroIconBtn');
  const old = document.getElementById('heroIconDisplay');
  if (old) old.remove();
  let el;
  if (cls.startsWith('iconify:')) {
    el = document.createElement('iconify-icon');
    el.setAttribute('icon', cls.slice(8));
    el.setAttribute('width', '26');
    el.setAttribute('height', '26');
    el.style.color = '#fff';
    el.style.pointerEvents = 'none';
  } else {
    el = document.createElement('i');
    el.className = cls;
    el.style.pointerEvents = 'none';
  }
  el.id = 'heroIconDisplay';
  btn.insertBefore(el, document.getElementById('heroColorDot'));
}

function setColor(c) {
  currentColor = c;
  document.getElementById('heroIconBtn').style.background = c;
  document.getElementById('heroColorDot').style.background = c;
  document.querySelectorAll('.color-swatch').forEach(s =>
    s.classList.toggle('selected', s.dataset.color === c)
  );
}

function togglePicker(e) {
  e.stopPropagation();
  const p = document.getElementById('pickerPanel');
  p.style.display = p.style.display === 'none' ? 'block' : 'none';
}

// Close picker on outside click
document.addEventListener('click', e => {
  const p = document.getElementById('pickerPanel');
  if (!p.contains(e.target) && !e.target.closest('#heroIconBtn')) {
    p.style.display = 'none';
  }
});

// ── CALENDAR FILTER ───────────────────────────────────────────────────────────
document.getElementById('calendarFilter').addEventListener('change', function() {
  currentCalendar = this.value;
  applyFilter();
});

// ── TAB FILTER ────────────────────────────────────────────────────────────────
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
      isFuture = new Date(item.start_Date + 'T00:00:00') >= today;
    }
    const passesCalendar = !currentCalendar || String(item?.Calendar) === String(currentCalendar);
    if (passesCalendar) { if (isFuture) futureN++; else pastN++; }
    const passesTab = currentFilter === 'future' ? isFuture : !isFuture;
    c.style.display = (passesTab && passesCalendar) ? '' : 'none';
  });
  document.getElementById('futureBadge').textContent = futureN;
  document.getElementById('pastBadge').textContent   = pastN;

  const empty = document.getElementById('emptyState');
  if (empty) {
    const visibleCards = document.querySelectorAll('.card:not([style*="display: none"])').length;
    empty.style.display = visibleCards === 0 ? '' : 'none';
  }
}

// ── VIEW MODAL ────────────────────────────────────────────────────────────────
function openViewModal(item) {
  viewingItem = item;
  const icon  = item.icon  || 'fa-solid fa-calendar-days';
  const color = item.color || '#272c3d';

  const vCircle = document.getElementById('vIconCircle');
  vCircle.style.background = color;
  if (icon.startsWith('fa-')) {
    vCircle.innerHTML = `<i class="${icon}" style="color:#fff;font-size:1.7rem"></i>`;
  } else if (icon.startsWith('iconify:')) {
    vCircle.innerHTML = `<iconify-icon icon="${icon.slice(8)}" width="34" height="34" style="color:#fff"></iconify-icon>`;
  } else {
    vCircle.textContent = icon;
  }

  document.getElementById('vTitle').textContent    = item.title    || '—';
  document.getElementById('vLocation').textContent = item.location || '—';

  let startStr = 'Date TBD';
  if (item.start_Date) {
    startStr = new Date(item.start_Date + 'T00:00:00').toDateString();
    if (item.start_Time) startStr += ' · ' + fmtTime(item.start_Time);
  }
  document.getElementById('vStartDT').textContent = startStr;

  if (item.end_Date) {
    let endStr = new Date(item.end_Date + 'T00:00:00').toDateString();
    if (item.end_Time) endStr += ' · ' + fmtTime(item.end_Time);
    document.getElementById('vEndDT').textContent    = endStr;
    document.getElementById('vEndRow').style.display = '';
  } else {
    document.getElementById('vEndRow').style.display = 'none';
  }

  const guests = item.Guests || item.guests || '';
  const notes  = item.Notes  || item.notes  || '';

  const vGuests = document.getElementById('vGuests');
  if (guests) {
    vGuests.textContent = guests.split('||').map(g => g.trim()).filter(Boolean).join(', ');
    vGuests.className   = 'view-row-value';
  } else {
    vGuests.textContent = 'None';
    vGuests.className   = 'view-row-value empty';
  }

  const vNotes = document.getElementById('vNotes');
  vNotes.textContent = notes || 'None';
  vNotes.className   = 'view-row-value' + (notes ? '' : ' empty');

  document.getElementById('viewOverlay').classList.add('open');
}

function switchToEdit() {
  closeModal('viewOverlay');
  if (viewingItem) openEditModal(viewingItem);
}

// ── ADD / EDIT MODAL ──────────────────────────────────────────────────────────
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
  document.getElementById('fCalendar').value  = item.Calendar || '';
  updateCharCount();

  const rawGuests = item.Guests || item.guests || '';
  if (rawGuests.trim()) {
    rawGuests.split('||').map(g => g.trim()).filter(Boolean).forEach(addGuestField);
  }

  if (item.end_Date) {
    document.getElementById('endDateBlock').style.display = '';
    const toggle = document.getElementById('endDateToggle');
    toggle.textContent = '— Remove End Date';
    toggle.classList.add('active');
  }

  setIcon(item.icon  || 'fa-solid fa-calendar-days');
  setColor(item.color || '#c0392b');
  document.getElementById('eventOverlay').classList.add('open');
}

function resetForm() {
  ['fTitle','fLocation','fStartDate','fStartTime','fEndDate','fEndTime','fNotes']
    .forEach(id => { document.getElementById(id).value = ''; });
  document.getElementById('fCalendar').value          = '';
  document.getElementById('endDateBlock').style.display = 'none';
  const toggle = document.getElementById('endDateToggle');
  toggle.textContent = '＋ Add End Date';
  toggle.classList.remove('active');
  document.getElementById('pickerPanel').style.display = 'none';
  document.getElementById('guestList').innerHTML       = '';
  updateCharCount();
  setIcon('fa-solid fa-calendar-days');
  setColor('#c0392b');
}

// ── GUESTS ────────────────────────────────────────────────────────────────────
function addGuestField(value) {
  const list = document.getElementById('guestList');
  const row  = document.createElement('div');
  row.className = 'guest-row';
  row.innerHTML = `
    <input type="text" class="guest-input" placeholder="Guest name"
           value="${escHtml(value)}" autocomplete="off">
    <button type="button" class="guest-remove-btn" title="Remove"
            onclick="this.closest('.guest-row').remove()">✕</button>
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
  count.className   = 'char-count' + (len >= 250 ? ' over' : len >= 200 ? ' warn' : '');
}

// ── END DATE TOGGLE ───────────────────────────────────────────────────────────
function toggleEndDate() {
  const block    = document.getElementById('endDateBlock');
  const toggle   = document.getElementById('endDateToggle');
  const isHidden = block.style.display === 'none';
  block.style.display  = isHidden ? '' : 'none';
  toggle.textContent   = isHidden ? '— Remove End Date' : '＋ Add End Date';
  toggle.classList.toggle('active', isHidden);
  if (!isHidden) {
    document.getElementById('fEndDate').value = '';
    document.getElementById('fEndTime').value = '';
  }
}

// ── SUBMIT (ADD or EDIT) ──────────────────────────────────────────────────────
function submitEvent() {
  const title = document.getElementById('fTitle').value.trim();
  if (!title) { showToast('Event name is required', 'error'); return; }

  const guestsValue = Array.from(document.querySelectorAll('#guestList .guest-input'))
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
        const idx = items.findIndex(i => i.id == editingId);
        if (idx > -1) items[idx] = data.item;
        const card = document.querySelector(`.card[data-id="${editingId}"]`);
        if (card) card.replaceWith(buildCardEl(data.item));
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

// ── BUILD CARD ELEMENT ────────────────────────────────────────────────────────
function buildCardEl(item) {
  const today = new Date(); today.setHours(0,0,0,0);
  const icon  = item.icon  || '📅';
  const color = item.color || '#272c3d';
  let isPast = false;
  let daysHtml = '<span class="cd-days">—</span><span class="cd-label">days</span>';

  if (item.start_Date) {
    const d = new Date(item.start_Date + 'T00:00:00');
    isPast   = d < today;
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
    : icon.startsWith('iconify:')
      ? `<iconify-icon icon="${escHtml(icon.slice(8))}" width="20" height="20" style="color:#fff"></iconify-icon>`
      : escHtml(icon);

  const div = document.createElement('div');
  div.className    = 'card ' + (isPast ? 'is-past' : 'is-future');
  div.dataset.id   = item.id;
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
  div.onclick = () => openViewModal(item);
  div.querySelector('.card-delete').onclick = e => deleteEvent(e, item.id);
  return div;
}

// ── DELETE ────────────────────────────────────────────────────────────────────
function deleteEvent(e, id) {
  e.stopPropagation();
  if (!confirm('Remove this event?')) return;
  const fd = new FormData();
  fd.append('action','delete');
  fd.append('id', id);
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
            empty.id        = 'emptyState';
            empty.className = 'empty';
            empty.innerHTML = '<div class="empty-icon">🗓️</div><div class="empty-text">No upcoming events yet</div>';
            document.getElementById('cardList').appendChild(empty);
          }
          empty.style.display = '';
        }
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

// ── CALENDAR MODAL ────────────────────────────────────────────────────────────
function openCalendarModal() {
  renderCalendarList();
  document.getElementById('calAddRow').style.display     = 'none';
  document.getElementById('showCalAddBtn').style.display = '';
  document.getElementById('calNewName').value = '';
  document.getElementById('calendarOverlay').classList.add('open');
}

function renderCalendarList() {
  const wrap = document.getElementById('calendarListWrap');
  wrap.innerHTML = '';
  if (!calendars.length) {
    const empty = document.createElement('div');
    empty.style.cssText = 'text-align:center;color:var(--text-muted);font-size:0.82rem;padding:20px 0;';
    empty.textContent   = 'No calendars yet';
    wrap.appendChild(empty);
    return;
  }
  calendars.forEach(cal => {
    const row   = document.createElement('div');
    row.className = 'cal-row';
    const count = items.filter(i => i.Calendar == cal.id).length;
    row.innerHTML = `
      <span class="cal-row-label">
        ${escHtml(cal.label)} <span class="cal-row-count">(${count})</span>
      </span>
      <button class="cal-trash-btn" title="Delete"
              onclick="deleteCalendar(${cal.id}, ${count}, this)">
        <i class="fa-solid fa-trash"></i>
      </button>
    `;
    wrap.appendChild(row);
  });
}

function showCalAddForm() {
  document.getElementById('calAddRow').style.display     = 'flex';
  document.getElementById('showCalAddBtn').style.display = 'none';
  setTimeout(() => document.getElementById('calNewName').focus(), 50);
}

function saveNewCalendar() {
  const name = document.getElementById('calNewName').value.trim();
  if (!name) { showToast('Enter a name', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'cal_add');
  fd.append('label',  name);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (data.error) { showToast(data.error, 'error'); return; }
      calendars.push({ id: data.id, label: name });
      renderCalendarList();
      // Add to event-form dropdown
      const opt = document.createElement('option');
      opt.value = data.id; opt.textContent = name;
      document.getElementById('fCalendar').appendChild(opt);
      // Add to header filter dropdown
      const hopt = document.createElement('option');
      hopt.value = data.id; hopt.textContent = name;
      document.getElementById('calendarFilter').appendChild(hopt);
      document.getElementById('calAddRow').style.display     = 'none';
      document.getElementById('showCalAddBtn').style.display = '';
      document.getElementById('calNewName').value = '';
      showToast('Calendar added ✓', 'success');
    })
    .catch(() => showToast('Network error', 'error'));
}

function deleteCalendar(id, count, btn) {
  const msg = count > 0
    ? `Delete this calendar? ${count} event${count === 1 ? '' : 's'} will be unassigned (kept, not deleted).`
    : 'Delete this calendar?';
  if (!confirm(msg)) return;
  const fd = new FormData();
  fd.append('action', 'cal_delete');
  fd.append('id', id);
  fetch('', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { showToast('Error deleting', 'error'); return; }
      calendars = calendars.filter(c => c.id != id);
      // sp_countdowns_delete_calendar sets Calendar=NULL rather than deleting events
      items.forEach(i => { if (i.Calendar == id) i.Calendar = null; });
      renderCalendarList();
      document.querySelectorAll(`#fCalendar option[value="${id}"]`).forEach(o => o.remove());
      document.querySelectorAll(`#calendarFilter option[value="${id}"]`).forEach(o => o.remove());
      showToast('Calendar deleted', 'success');
    })
    .catch(() => showToast('Network error', 'error'));
}

// ── FAB TRAY ─────────────────────────────────────────────────────────────────
function toggleFab() { fabOpen ? closeFab() : openFab(); }
function openFab() {
  fabOpen = true;
  document.getElementById('fabTray').classList.add('open');
  document.getElementById('fabBtn').classList.add('active');
  document.getElementById('fabBackdrop').classList.add('active');
}
function closeFab() {
  fabOpen = false;
  document.getElementById('fabTray').classList.remove('open');
  document.getElementById('fabBtn').classList.remove('active');
  document.getElementById('fabBackdrop').classList.remove('active');
}

// ── GLOBAL KEY HANDLER ────────────────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeModal('eventOverlay');
    closeModal('viewOverlay');
  }
});

// ── INIT ──────────────────────────────────────────────────────────────────────
applyFilter();
</script>

</body>
</html>