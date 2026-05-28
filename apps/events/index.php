<?php
require_once 'db/db_hosted.php';
require_once 'api/auth.php';

// ─── View mode: 'mine' (default) or 'all' ───────────────────────────
$currentUser = $_SESSION['auth_user_name'] ?? null;
$viewMode    = isset($_GET['view']) && $_GET['view'] === 'all' ? 'all' : 'mine';

// If no logged-in user, always fall back to all
if (!$currentUser) $viewMode = 'all';

// ─── Fetch raw data based on mode ───────────────────────────────────
if ($viewMode === 'mine') {
    $stmt = $pdo->prepare("
        SELECT * FROM vw_event_performers_watched_by_user
        WHERE username = ?
        ORDER BY event_StartDate ASC
    ");
    $stmt->execute([$currentUser]);
} else {
    $stmt = $pdo->query("SELECT * FROM vw_full_event ORDER BY event_StartDate ASC");
}
$rows = $stmt->fetchAll();

// ─── Build unified event map ─────────────────────────────────────────
$events = [];
foreach ($rows as $row) {
    $id = $row['event_ID'];
    if (!isset($events[$id])) {
        $events[$id] = [
            'event_ID'        => $id,
            'event_Name'      => $row['event_Name'],
            'event_Year'      => $row['event_Year'],
            'event_StartDate' => $row['event_StartDate'],
            'event_EndDate'   => $row['event_EndDate'],
            'venue_Name'      => $row['venue_Name'],
            'venue_City'      => $row['venue_City'],
            'venue_State'     => $row['venue_State'],
            'performers'      => [],
        ];
    }
    if (!empty($row['performer_Name'])) {
        $events[$id]['performers'][] = [
            'name'         => $row['performer_Name'],
            'is_Headliner' => (bool)$row['is_Headliner'],
            'watched'      => (int)$row['watched'] === 1,
        ];
    }
}

// ─── Aggregate: Performers ────────────────────────────────────────────
// performer_name => [ total_seen, headliner_count, venues[], events[], years[] ]
$performerStats = [];
foreach ($events as $ev) {
    foreach ($ev['performers'] as $p) {
        $name = $p['name'];
        if (!isset($performerStats[$name])) {
            $performerStats[$name] = [
                'name'           => $name,
                'total'          => 0,
                'headliner_count'=> 0,
                'watched_count'  => 0,
                'appearances'    => [],
            ];
        }
        $performerStats[$name]['total']++;
        if ($p['is_Headliner']) $performerStats[$name]['headliner_count']++;
        if ($p['watched'])      $performerStats[$name]['watched_count']++;
        $performerStats[$name]['appearances'][] = [
            'event_Name'  => $ev['event_Name'],
            'event_Year'  => $ev['event_Year'],
            'start_date'  => $ev['event_StartDate'],
            'venue_Name'  => $ev['venue_Name'],
            'venue_City'  => $ev['venue_City'],
            'venue_State' => $ev['venue_State'],
            'is_Headliner'=> $p['is_Headliner'],
            'watched'     => $p['watched'],
        ];
    }
}
uasort($performerStats, fn($a, $b) => $b['total'] <=> $a['total']);

// ─── Aggregate: Venues ───────────────────────────────────────────────
$venueStats = [];
foreach ($events as $ev) {
    $vname = $ev['venue_Name'] ?? 'Unknown Venue';
    if (!isset($venueStats[$vname])) {
        $venueStats[$vname] = [
            'name'       => $vname,
            'city'       => $ev['venue_City'],
            'state'      => $ev['venue_State'],
            'total'      => 0,
            'appearances'=> [],
        ];
    }
    $venueStats[$vname]['total']++;
    $venueStats[$vname]['appearances'][] = [
        'event_Name' => $ev['event_Name'],
        'start_date' => $ev['event_StartDate'],
        'end_date'   => $ev['event_EndDate'],
        'year'       => $ev['event_Year'],
        'performers' => $ev['performers'],
    ];
}
uasort($venueStats, fn($a, $b) => $b['total'] <=> $a['total']);

// ─── Aggregate: Years ────────────────────────────────────────────────
$yearStats = [];
foreach ($events as $ev) {
    $yr = $ev['event_Year'] ?? 'Unknown';
    if (!isset($yearStats[$yr])) {
        $yearStats[$yr] = ['year' => $yr, 'total' => 0, 'events' => []];
    }
    $yearStats[$yr]['total']++;
    $yearStats[$yr]['events'][] = [
        'event_Name'  => $ev['event_Name'],
        'start_date'  => $ev['event_StartDate'],
        'end_date'    => $ev['event_EndDate'],
        'venue_Name'  => $ev['venue_Name'],
        'venue_City'  => $ev['venue_City'],
        'venue_State' => $ev['venue_State'],
        'performers'  => $ev['performers'],
    ];
}
// Sort events within each year by start date
foreach ($yearStats as &$y) {
    usort($y['events'], fn($a, $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));
}
unset($y);
krsort($yearStats);

// ─── All distinct years (for the top year-filter bar) ────────────────
$allYears = array_keys($yearStats); // already krsort'd descending

// ─── Summary stats ───────────────────────────────────────────────────
$totalEvents     = count($events);
$totalPerformers = count($performerStats);
$totalVenues     = count($venueStats);
$totalYears      = count($yearStats);

$eventsJson    = json_encode(array_values($events),        JSON_HEX_TAG | JSON_HEX_AMP);
$performerJson = json_encode(array_values($performerStats), JSON_HEX_TAG | JSON_HEX_AMP);
$venueJson     = json_encode(array_values($venueStats),     JSON_HEX_TAG | JSON_HEX_AMP);
$yearsJson     = json_encode($allYears,                     JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stats – Croven Events</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php
  $currentPage = 'stats';
  $pageTitle   = 'Stats';
  require 'nav.php';
?>

<div class="stats-wrap">

  <!-- View Mode Toggle -->
  <div class="view-mode-bar">
    <span class="view-mode-label">Showing</span>
    <div class="view-mode-toggle">
      <a href="?view=mine"
         class="view-mode-btn <?= $viewMode === 'mine' ? 'active' : '' ?>">
        MY ATTENDANCE
      </a>
      <a href="?view=all"
         class="view-mode-btn <?= $viewMode === 'all' ? 'active' : '' ?>">
        ALL EVENTS
      </a>
    </div>
    <?php if ($viewMode === 'mine' && $currentUser): ?>
      <span class="view-mode-user">Viewing as <?= htmlspecialchars($currentUser) ?></span>
    <?php endif; ?>
  </div>

  <!-- Summary Tiles -->
  <?php
    $top5Performers = array_slice(array_values($performerStats), 0, 5);
    $top5Venues     = array_slice(array_values($venueStats), 0, 5);
  ?>
  <div class="summary-grid" id="summaryGrid">
    <div class="summary-tile">
      <div class="tile-number" id="summaryEvents"><?= $totalEvents ?></div>
      <div class="tile-label">Events</div>
    </div>
    <div class="summary-tile">
      <div class="tile-number" id="summaryPerformers"><?= $totalPerformers ?></div>
      <div class="tile-label">Performers</div>
    </div>
    <div class="summary-tile">
      <div class="tile-number" id="summaryVenues"><?= $totalVenues ?></div>
      <div class="tile-label">Venues</div>
    </div>
  </div><!-- /summaryGrid -->

  <!-- Top Lists Grid -->
  <div class="summary-grid summary-grid--lists">
    <div class="summary-tile summary-tile-list">
      <div class="tile-label">Most Seen Performers</div>
      <div id="topPerformersList">
      <?php foreach ($top5Performers as $i => $tp): ?>
        <div class="tile-list-row">
          <span class="tile-list-rank"><?= $i + 1 ?></span>
          <span class="tile-list-name"><?= htmlspecialchars($tp['name']) ?></span>
          <span class="tile-list-count"><?= $tp['total'] ?>×</span>
        </div>
      <?php endforeach; ?>
      </div><!-- /topPerformersList -->
    </div>
    <div class="summary-tile summary-tile-list">
      <div class="tile-label">Most Visited Venues</div>
      <div id="topVenuesList">
      <?php foreach ($top5Venues as $i => $tv): ?>
        <div class="tile-list-row">
          <span class="tile-list-rank"><?= $i + 1 ?></span>
          <span class="tile-list-name"><?= htmlspecialchars($tv['name']) ?></span>
          <span class="tile-list-count"><?= $tv['total'] ?>×</span>
        </div>
      <?php endforeach; ?>
      </div><!-- /topVenuesList -->
    </div>
  </div><!-- /summary-grid--lists -->

  <!-- Year Filter Bar -->
  <div class="year-filter-bar" id="yearFilterBar">
    <span class="year-filter-label">Year</span>
    <button class="year-pill active" data-year="all">All</button>
    <?php foreach ($allYears as $yr): ?>
      <button class="year-pill" data-year="<?= htmlspecialchars((string)$yr) ?>"><?= htmlspecialchars((string)$yr) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- Mode Tabs -->
  <div class="stats-tabs">
    <button class="stats-tab active" data-mode="performer">PERFORMERS</button>
    <button class="stats-tab" data-mode="venue">VENUES</button>
    <button class="stats-tab" data-mode="year">BY YEAR</button>
  </div>

  <!-- Sort Controls -->
  <div class="sort-controls" id="sortControls">
    <span class="sort-label">Order</span>
    <button class="sort-btn active" id="sortCount" data-sort="count-desc">
      COUNT <span class="sort-arrow" id="sortCountArrow">↓</span>
    </button>
    <button class="sort-btn" id="sortAlpha" data-sort="alpha-asc">
      A → Z <span class="sort-arrow" id="sortAlphaArrow">↑</span>
    </button>
  </div>

  <!-- Search -->
  <div class="stats-search-wrap">
    <div class="stats-search-inner">
      <span class="search-icon">&#9906;</span>
      <input type="text" id="statsSearch" placeholder="Search…" autocomplete="off">
    </div>
    <button class="stats-clear-btn" id="statsClear">Clear</button>
  </div>

  <div class="result-count" id="resultCount"></div>

  <!-- ── Performer Section ───────────────────────────────────────────── -->
  <div class="stats-section active" id="section-performer">
    <?php foreach ($performerStats as $p): ?>
    <?php $pYears = array_unique(array_filter(array_column($p['appearances'], 'event_Year'))); ?>
    <div class="stats-card"
         data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
         data-years="<?= htmlspecialchars(implode(',', $pYears)) ?>"
         data-total="<?= $p['total'] ?>">
      <div class="stats-card-header" onclick="toggleCard(this)" style="position:relative; justify-content:center;">
        <div class="stats-card-title" style="text-align:center;"><?= htmlspecialchars($p['name']) ?></div>
        <span class="chevron" style="position:absolute; right:1rem;">▼</span>
      </div>
      <div class="stats-card-detail">
        <table class="appearance-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Event / Tour</th>
              <th>Venue</th>
              <th>Role</th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Sort appearances by date ascending
              usort($p['appearances'], fn($a, $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));
              foreach ($p['appearances'] as $a):
                $dateStr = $a['start_date'] ? date('M d, Y', strtotime($a['start_date'])) : '—';
                $venue   = array_filter([$a['venue_Name'], $a['venue_City'], $a['venue_State']]);
            ?>
            <tr>
              <td class="td-date"><?= htmlspecialchars($dateStr) ?></td>
              <td class="td-event"><?= htmlspecialchars($a['event_Name']) ?></td>
              <td class="td-venue"><?= htmlspecialchars(implode(', ', $venue)) ?></td>
              <td class="td-badge">
                <?php if ($a['is_Headliner']): ?>
                  <span class="badge-hl">★ Headliner</span>
                <?php else: ?>
                  <span style="opacity:0.4; font-size:0.78rem;">Support</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="no-stats-results" id="noPerformer" style="display:none;">No performers match your search.</div>
  </div>

  <!-- ── Venue Section ───────────────────────────────────────────────── -->
  <div class="stats-section" id="section-venue">
    <?php foreach ($venueStats as $v): ?>
    <?php $vYears = array_unique(array_column($v['appearances'], 'year')); ?>
    <div class="stats-card"
         data-name="<?= htmlspecialchars(strtolower($v['name'] . ' ' . ($v['city'] ?? '') . ' ' . ($v['state'] ?? ''))) ?>"
         data-years="<?= htmlspecialchars(implode(',', $vYears)) ?>"
         data-total="<?= $v['total'] ?>">
      <div class="stats-card-header" onclick="toggleCard(this)" style="position:relative; justify-content:center;">
        <div class="stats-card-title" style="text-align:center;"><?= htmlspecialchars($v['name']) ?></div>
        <span class="chevron" style="position:absolute; right:1rem;">▼</span>
      </div>
      <div class="stats-card-detail">
        <table class="appearance-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Event / Tour</th>
              <th>Performers</th>
            </tr>
          </thead>
          <tbody>
            <?php
              usort($v['appearances'], fn($a, $b) => strcmp($a['start_date'] ?? '', $b['start_date'] ?? ''));
              foreach ($v['appearances'] as $a):
                $dateStr    = $a['start_date'] ? date('M d, Y', strtotime($a['start_date'])) : '—';
                $endStr     = $a['end_date'] && $a['end_date'] !== $a['start_date']
                              ? ' – ' . date('M d, Y', strtotime($a['end_date'])) : '';
                $headliners = array_values(array_filter($a['performers'], fn($p) => $p['is_Headliner']));
                $support    = array_values(array_filter($a['performers'], fn($p) => !$p['is_Headliner']));
            ?>
            <tr>
              <td class="td-date" style="white-space:nowrap;"><?= htmlspecialchars($dateStr . $endStr) ?></td>
              <td class="td-event"><?= htmlspecialchars($a['event_Name']) ?></td>
              <td>
                <?php if (!empty($headliners)): ?>
                  <div style="margin-bottom:2px;">
                    <?php foreach ($headliners as $p): ?>
                      <span style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></span><span class="dot-hl" title="Headliner">★</span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($support)): ?>
                  <div class="ev-performers"><?= htmlspecialchars(implode(', ', array_column($support, 'name'))) ?></div>
                <?php endif; ?>
                <?php if (empty($a['performers'])): ?>
                  <span style="opacity:0.35;font-size:0.78rem;">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="no-stats-results" id="noVenue" style="display:none;">No venues match your search.</div>
  </div>

  <!-- ── Year Section ────────────────────────────────────────────────── -->
  <div class="stats-section" id="section-year">
    <?php foreach ($yearStats as $yr => $y): ?>
    <div class="year-block" data-name="<?= htmlspecialchars((string)$yr) ?>">
      <div class="year-block-header" onclick="toggleCard(this)" style="position:relative; justify-content:center;">
        <div class="year-block-title" style="text-align:center;"><?= htmlspecialchars((string)$yr) ?></div>
        <span class="chevron" style="position:absolute; right:1rem;">▼</span>
      </div>
      <div class="year-block-detail">
        <table class="appearance-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Event / Tour</th>
              <th>Venue</th>
              <th>Performers</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($y['events'] as $ev):
              $dateStr = $ev['start_date'] ? date('M d, Y', strtotime($ev['start_date'])) : '—';
              $endStr  = $ev['end_date'] && $ev['end_date'] !== $ev['start_date']
                         ? ' – ' . date('M d, Y', strtotime($ev['end_date'])) : '';
              $venue   = implode(', ', array_filter([$ev['venue_Name'], $ev['venue_City'], $ev['venue_State']]));
              $headliners = array_values(array_filter($ev['performers'], fn($p) => $p['is_Headliner']));
              $support    = array_values(array_filter($ev['performers'], fn($p) => !$p['is_Headliner']));
            ?>
            <tr>
              <td class="td-date" style="white-space:nowrap;"><?= htmlspecialchars($dateStr . $endStr) ?></td>
              <td class="td-event"><?= htmlspecialchars($ev['event_Name']) ?></td>
              <td class="td-venue"><?= htmlspecialchars($venue ?: '—') ?></td>
              <td>
                <?php if (!empty($headliners)): ?>
                  <div style="margin-bottom:2px;">
                    <?php foreach ($headliners as $p): ?>
                      <span style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></span><span class="dot-hl" title="Headliner">★</span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($support)): ?>
                  <div class="ev-performers"><?= htmlspecialchars(implode(', ', array_column($support, 'name'))) ?></div>
                <?php endif; ?>
                <?php if (empty($ev['performers'])): ?>
                  <span style="opacity:0.35;font-size:0.78rem;">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>
    <div class="no-stats-results" id="noYear" style="display:none;">No years match your search.</div>
  </div>

</div><!-- /stats-wrap -->

<script>
// ── Data from PHP ──────────────────────────────────────────────────
const allEvents    = <?= $eventsJson ?>;
const allPerformers = <?= $performerJson ?>;
const allVenues    = <?= $venueJson ?>;

let activeMode   = 'performer';
let activeYear   = 'all';
let activeSort   = 'count-desc';

// ── Toggle expand/collapse ─────────────────────────────────────────
function toggleCard(headerEl) {
  headerEl.closest('.stats-card, .year-block').classList.toggle('open');
}

// ── Year filter pills ──────────────────────────────────────────────
document.querySelectorAll('.year-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.year-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    activeYear = pill.dataset.year;
    updateSummary();
    applyFiltersAndSort();
  });
});

// ── Update summary tiles based on selected year ────────────────────
function updateSummary() {
  const filtered = activeYear === 'all'
    ? allEvents
    : allEvents.filter(ev => String(ev.event_Year) === String(activeYear));

  // Count unique events, performers, venues in filtered set
  const evCount = filtered.length;

  const perfSet = new Set();
  const venueSet = new Set();
  filtered.forEach(ev => {
    if (ev.venue_Name) venueSet.add(ev.venue_Name);
    (ev.performers || []).forEach(p => perfSet.add(p.name));
  });

  document.getElementById('summaryEvents').textContent     = evCount;
  document.getElementById('summaryPerformers').textContent = perfSet.size;
  document.getElementById('summaryVenues').textContent     = venueSet.size;

  // Recompute top 5 performers for this year
  const perfCounts = {};
  filtered.forEach(ev => {
    (ev.performers || []).forEach(p => {
      perfCounts[p.name] = (perfCounts[p.name] || 0) + 1;
    });
  });
  const sortedPerfs = Object.entries(perfCounts).sort((a,b) => b[1]-a[1]).slice(0,5);
  const perfList = document.getElementById('topPerformersList');
  perfList.innerHTML = sortedPerfs.map(([name, cnt], i) => `
    <div class="tile-list-row">
      <span class="tile-list-rank">${i+1}</span>
      <span class="tile-list-name">${escHtml(name)}</span>
      <span class="tile-list-count">${cnt}×</span>
    </div>`).join('');

  // Recompute top 5 venues for this year
  const venueCounts = {};
  filtered.forEach(ev => {
    if (ev.venue_Name) venueCounts[ev.venue_Name] = (venueCounts[ev.venue_Name] || 0) + 1;
  });
  const sortedVenues = Object.entries(venueCounts).sort((a,b) => b[1]-a[1]).slice(0,5);
  const venueList = document.getElementById('topVenuesList');
  venueList.innerHTML = sortedVenues.map(([name, cnt], i) => `
    <div class="tile-list-row">
      <span class="tile-list-rank">${i+1}</span>
      <span class="tile-list-name">${escHtml(name)}</span>
      <span class="tile-list-count">${cnt}×</span>
    </div>`).join('');
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Mode tabs ──────────────────────────────────────────────────────
document.querySelectorAll('.stats-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.stats-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    activeMode = tab.dataset.mode;
    document.querySelectorAll('.stats-section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + activeMode).classList.add('active');
    document.getElementById('statsSearch').value = '';
    // Show/hide sort controls — only relevant for performer and venue
    document.getElementById('sortControls').classList.toggle('hidden', activeMode === 'year');
    applyFiltersAndSort();
  });
});

// ── Sort buttons (toggle) ─────────────────────────────────────────
const sortCountBtn  = document.getElementById('sortCount');
const sortAlphaBtn  = document.getElementById('sortAlpha');
const sortCountArrow = document.getElementById('sortCountArrow');
const sortAlphaArrow = document.getElementById('sortAlphaArrow');

sortCountBtn.addEventListener('click', () => {
  // Toggle between count-desc and count-asc
  const next = activeSort === 'count-desc' ? 'count-asc' : 'count-desc';
  setSort(next);
});

sortAlphaBtn.addEventListener('click', () => {
  // Toggle between alpha-asc and alpha-desc
  const next = activeSort === 'alpha-asc' ? 'alpha-desc' : 'alpha-asc';
  setSort(next);
});

function setSort(sort) {
  activeSort = sort;

  // Update active state
  sortCountBtn.classList.toggle('active', sort === 'count-desc' || sort === 'count-asc');
  sortAlphaBtn.classList.toggle('active', sort === 'alpha-asc'  || sort === 'alpha-desc');

  // Update arrows
  sortCountArrow.textContent = sort === 'count-asc' ? '↑' : '↓';
  sortAlphaArrow.textContent = sort === 'alpha-desc' ? '↓' : '↑';

  applyFiltersAndSort();
}

// ── Search ─────────────────────────────────────────────────────────
const searchInput = document.getElementById('statsSearch');
const clearBtn    = document.getElementById('statsClear');
const resultCount = document.getElementById('resultCount');

searchInput.addEventListener('input', applyFiltersAndSort);
clearBtn.addEventListener('click', () => {
  searchInput.value = '';
  applyFiltersAndSort();
});

// ── Core filter + sort + render ────────────────────────────────────
function applyFiltersAndSort() {
  const q       = searchInput.value.trim().toLowerCase();
  const section = document.getElementById('section-' + activeMode);
  const noEl    = section.querySelector('.no-stats-results');

  if (activeMode === 'year') {
    // Year section: only search filter, no year-pill filter (year IS the grouping)
    const cards = section.querySelectorAll('.year-block');
    let visible = 0;
    cards.forEach(card => {
      const match = !q || (card.dataset.name || '').includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (noEl) noEl.style.display = visible === 0 ? 'block' : 'none';
    resultCount.textContent = q
      ? `${visible} year${visible !== 1 ? 's' : ''} matching "${q}"`
      : `${visible} year${visible !== 1 ? 's' : ''}`;
    return;
  }

  // Performer or Venue: apply year filter + search + sort
  const cards = Array.from(section.querySelectorAll('.stats-card'));

  // Filter
  const filtered = cards.filter(card => {
    const nameMatch  = !q || (card.dataset.name || '').includes(q);
    const yearMatch  = activeYear === 'all' || (card.dataset.years || '').split(',').includes(String(activeYear));
    return nameMatch && yearMatch;
  });
  const hidden = cards.filter(c => !filtered.includes(c));
  hidden.forEach(c => c.style.display = 'none');

  // Sort
  filtered.sort((a, b) => {
    const totalA = parseInt(a.dataset.total || '0', 10);
    const totalB = parseInt(b.dataset.total || '0', 10);
    const nameA  = (a.querySelector('.stats-card-title')?.textContent || '').toLowerCase();
    const nameB  = (b.querySelector('.stats-card-title')?.textContent || '').toLowerCase();
    switch (activeSort) {
      case 'count-desc': return totalB - totalA || nameA.localeCompare(nameB);
      case 'count-asc':  return totalA - totalB || nameA.localeCompare(nameB);
      case 'alpha-asc':  return nameA.localeCompare(nameB);
      case 'alpha-desc': return nameB.localeCompare(nameA);
      default:           return 0;
    }
  });

  // Re-insert in sorted order before the noEl
  filtered.forEach(card => {
    card.style.display = '';
    section.insertBefore(card, noEl);
  });

  if (noEl) noEl.style.display = filtered.length === 0 ? 'block' : 'none';

  const label = activeMode === 'performer' ? 'performer' : 'venue';
  resultCount.textContent = q || activeYear !== 'all'
    ? `${filtered.length} ${label}${filtered.length !== 1 ? 's' : ''}${q ? ` matching "${q}"` : ''}${activeYear !== 'all' ? ` in ${activeYear}` : ''}`
    : `${filtered.length} ${label}${filtered.length !== 1 ? 's' : ''}`;
}

// ── Init ───────────────────────────────────────────────────────────
applyFiltersAndSort();
</script>

</body>
</html>