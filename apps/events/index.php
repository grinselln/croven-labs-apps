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
  <style>
    /* ══════════════════════════════════════════
       STATS PAGE — GOTHIC MASTER-DETAIL
    ══════════════════════════════════════════ */

    .stats-wrap { max-width: 680px; margin: 0 auto; padding: 0 0 4rem; }

    /* ── View mode bar ── */
    .view-mode-bar {
      display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
      padding: 1rem 1rem .6rem;
      border-bottom: 1px solid var(--border);
    }
    .view-mode-label {
      font-family: 'Cinzel', serif;
      font-size: .68rem; letter-spacing: .18em; text-transform: uppercase;
      color: var(--muted);
    }
    .view-mode-toggle {
      display: flex; border: 1px solid var(--border-strong); border-radius: 4px; overflow: hidden;
    }
    .view-mode-btn {
      padding: .32rem .9rem;
      font-family: 'Cinzel', serif; font-size: .68rem; letter-spacing: .1em; text-transform: uppercase;
      text-decoration: none; color: var(--muted); background: transparent; border: none; cursor: pointer;
      transition: background .15s, color .15s;
    }
    .view-mode-btn.active { background: var(--nav-accent); color: #fff; }
    .view-mode-btn:not(.active):hover { background: rgba(139,0,0,.18); color: var(--text); }
    .view-mode-user {
      font-family: 'Cinzel', serif; font-size: .65rem; letter-spacing: .06em;
      color: var(--nav-text-dim); margin-left: auto;
    }

    /* ── Summary tiles ── */
    .summary-grid {
      display: grid; grid-template-columns: repeat(3, minmax(0,1fr));
      gap: .65rem; padding: .85rem 1rem;
    }
    .summary-tile {
      background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 10px; padding: .9rem .75rem; text-align: center;
    }
    .tile-number {
      font-family: 'Cinzel', serif; font-size: 1.75rem; font-weight: 600;
      line-height: 1; color: var(--accent);
    }
    .tile-label {
      font-family: 'Cinzel', serif; font-size: .6rem; letter-spacing: .12em;
      text-transform: uppercase; color: var(--muted); margin-top: .35rem;
    }
    /* ── Tabbed summary card ── */
    .summary-tabbed-card {
      margin: 0 1rem .85rem;
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
    }
    .summary-tab-header {
      text-align: center;
      padding: .55rem 1rem .45rem;
      border-bottom: 1px solid var(--border);
      font-family: 'Cinzel', serif;
      font-size: .72rem;
      letter-spacing: .28em;
      text-transform: uppercase;
      color: var(--accent);
    }
    .summary-tab-bar {
      display: flex;
      border-bottom: 1px solid var(--border);
    }
    .summary-tab-btn {
      flex: 1; padding: .55rem .4rem;
      background: transparent; border: none;
      font-family: 'Cinzel', serif; font-size: .68rem;
      letter-spacing: .1em; text-transform: uppercase;
      color: var(--muted); cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -1px;
      transition: color .15s, border-color .15s;
    }
    .summary-tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
    .summary-tab-pane { display: none; }
    .summary-tab-pane.active { display: block; }
    .tile-list-row {
      display: flex; align-items: center; gap: .65rem;
      padding: .55rem 1rem;
      border-bottom: 1px solid var(--border);
    }
    .tile-list-row:last-child { border-bottom: none; }
    .tile-list-rank {
      font-family: 'Cinzel', serif; font-size: .62rem;
      color: var(--border-strong); width: 1rem; flex-shrink: 0; text-align: right;
    }
    .tile-list-name {
      flex: 1; font-size: .82rem; overflow: hidden;
      text-overflow: ellipsis; white-space: nowrap; color: var(--text);
    }
    .tile-list-count {
      font-size: .7rem; color: var(--accent);
      background: var(--headliner-bg);
      padding: .15rem .55rem; border-radius: 99px;
      border: 1px solid var(--border-strong);
      flex-shrink: 0;
    }

    /* ── Year filter pills ── */
    .year-filter-bar {
      display: flex; align-items: center; gap: .4rem;
      padding: .5rem 1rem; overflow-x: auto; scrollbar-width: none;
      border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    }
    .year-filter-bar::-webkit-scrollbar { display: none; }
    .year-filter-label {
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .14em;
      text-transform: uppercase; color: var(--muted); flex-shrink: 0;
    }
    .year-pill {
      flex-shrink: 0; padding: .25rem .7rem; border-radius: 99px;
      border: 1px solid var(--border-strong); background: transparent;
      font-family: 'Cinzel', serif; font-size: .65rem; letter-spacing: .06em;
      color: var(--muted); cursor: pointer; transition: background .15s, color .15s, border-color .15s;
    }
    .year-pill.active { background: var(--nav-accent); color: #fff; border-color: var(--nav-accent); }
    .year-pill:not(.active):hover { background: rgba(139,0,0,.18); color: var(--text); }

    /* ── Mode tabs ── */
    .stats-tabs {
      display: flex; border-bottom: 1px solid var(--border); padding: 0 1rem;
    }
    .stats-tab {
      flex: 1; padding: .7rem .4rem; border: none; background: transparent;
      font-family: 'Cinzel', serif; font-size: .68rem; font-weight: 400;
      letter-spacing: .1em; text-transform: uppercase;
      color: var(--muted); cursor: pointer;
      border-bottom: 2px solid transparent; margin-bottom: -1px;
      transition: color .15s, border-color .15s;
    }
    .stats-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
    .stats-tab:not(.active):hover { color: var(--text); }

    /* ── Sort + search bar ── */
    .controls-bar {
      display: flex; flex-direction: column; gap: .45rem;
      padding: .6rem 1rem; border-bottom: 1px solid var(--border);
    }
    .controls-bar-search { display: flex; width: 100%; }
    .controls-bar-sort   { display: flex; gap: .5rem; }
    .sort-btn {
      padding: .28rem .65rem; border-radius: 4px;
      border: 1px solid var(--border-strong); background: transparent;
      font-family: 'Cinzel', serif; font-size: .65rem; letter-spacing: .08em;
      text-transform: uppercase; color: var(--muted); cursor: pointer;
      transition: background .15s, color .15s;
    }
    .sort-btn.active { background: var(--nav-accent); color: #fff; border-color: var(--nav-accent); }
    .sort-btn:not(.active):hover { background: rgba(139,0,0,.18); color: var(--text); }
    .sort-arrow { font-size: .65rem; }
    .search-wrap {
      flex: 1; display: flex; align-items: center; gap: .4rem;
      background: var(--input-bg); border: 1px solid var(--border-strong);
      border-radius: 5px; padding: .32rem .65rem;
      transition: border-color .15s; width: 100%;
    }
    .search-wrap:focus-within { border-color: var(--nav-accent); }
    .search-wrap svg { flex-shrink: 0; color: var(--nav-accent); opacity: .7; }
    .search-wrap input {
      border: none; background: transparent; font-size: .82rem; width: 100%;
      outline: none; color: var(--text); font-family: system-ui, sans-serif;
    }
    .search-wrap input::placeholder { color: #3d0000; }
    .controls-bar.hidden { display: none; }

    /* ── Result count ── */
    .result-count {
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .1em;
      text-transform: uppercase; color: var(--muted);
      padding: .45rem 1rem;
    }

    /* ── List section ── */
    .stats-section { display: none; }
    .stats-section.active { display: block; }

    /* Performer rows — no avatar, full-width name */
    .list-row {
      display: flex; align-items: center; gap: .75rem;
      padding: .9rem 1rem; border-bottom: 1px solid var(--border);
      cursor: pointer; transition: background .12s;
    }
    .list-row:hover { background: rgba(139,0,0,.1); }
    /* Venue rows keep the avatar; performer rows do not render one */
    .list-avatar {
      width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cinzel', serif; font-size: .72rem; font-weight: 600;
      border: 1px solid var(--border-strong);
    }
    .list-info { flex: 1; min-width: 0; }
    .list-name {
      font-family: 'Cinzel', serif; font-size: .9rem; font-weight: 600;
      letter-spacing: .04em; color: var(--text);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .list-meta { font-size: .72rem; color: var(--muted); margin-top: .1rem; }
    .list-right { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
    .count-pill {
      background: var(--headliner-bg); color: var(--nav-text-dim);
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .06em;
      padding: .2rem .6rem; border-radius: 99px;
      border: 1px solid var(--border-strong); white-space: nowrap;
    }
    .chev-icon { color: var(--border-strong); font-size: 1.1rem; line-height: 1; }
    .no-results {
      padding: 2.5rem 1rem; text-align: center;
      font-family: 'Cinzel', serif; font-size: .75rem; letter-spacing: .1em;
      text-transform: uppercase; color: var(--muted); display: none;
    }

    /* ── Year section ── */
    .year-list-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: .55rem 1rem; background: var(--card-bg);
      border-bottom: 1px solid var(--border);
    }
    .year-list-header span {
      font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 600;
      letter-spacing: .06em; color: var(--accent);
    }
    .year-list-header small { font-size: .7rem; color: var(--muted); }
    .appearance-row { padding: .85rem 1rem; border-bottom: 1px solid var(--border); }
    .appearance-row:last-child { border-bottom: none; }
    .app-top-line { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; }
    .app-event-name { font-family: 'Cinzel', serif; font-size: .85rem; font-weight: 600; letter-spacing: .03em; color: var(--text); }
    .app-date { font-size: .75rem; color: var(--muted); margin-bottom: .1rem; }
    .app-venue { font-size: .72rem; color: var(--muted); opacity: .7; }
    .app-performers { font-size: .75rem; color: var(--muted); margin-top: .3rem; }
    .app-performers strong { color: var(--text); font-weight: 600; }

    /* ── Detail overlay — sits below the fixed nav header ── */
    .detail-overlay {
      position: fixed;
      top: var(--nav-header-height); /* 56px — clears the site-header */
      left: 0; right: 0; bottom: 0;
      z-index: 490; /* above page content; below nav header (500), overlay (600), drawer (700) */
      background: var(--bg);
      transform: translateX(100%);
      transition: transform .28s cubic-bezier(.4,0,.2,1);
      overflow-y: auto;
      display: flex; flex-direction: column;
    }
    .detail-overlay.open { transform: translateX(0); }

    /* Back bar — sticky at the top of the overlay (just below nav) */
    .detail-topbar {
      display: flex; align-items: center;
      padding: .75rem 1rem;
      background: var(--nav-bg);
      border-bottom: 2px solid var(--nav-border);
      position: sticky; top: 0; z-index: 10;
      flex-shrink: 0;
    }
    .back-btn {
      display: flex; align-items: center; gap: .45rem;
      background: transparent; border: none; cursor: pointer; padding: 0;
      font-family: 'Cinzel', serif; font-size: .72rem; font-weight: 600;
      letter-spacing: .12em; text-transform: uppercase;
      color: var(--nav-text-dim);
      transition: color .15s;
    }
    .back-btn:hover { color: var(--accent); }
    .back-btn svg { flex-shrink: 0; }

    /* Hero block */
    .detail-hero {
      padding: 1.75rem 1rem 1.4rem; text-align: center;
      border-bottom: 1px solid var(--border);
    }
    .detail-hero-name {
      font-family: 'Cinzel', serif; font-size: 1.4rem; font-weight: 600;
      letter-spacing: .06em; color: var(--text);
    }
    .detail-hero-sub { font-size: .78rem; color: var(--muted); margin-top: .25rem; }
    .detail-divider {
      width: 48px; height: 1px; margin: .85rem auto;
      background: linear-gradient(90deg, transparent, var(--nav-border), transparent);
    }
    .detail-stats { display: flex; justify-content: center; gap: 2.5rem; margin-top: .25rem; }
    .detail-stat { text-align: center; }
    .detail-stat-n {
      font-family: 'Cinzel', serif; font-size: 1.35rem; font-weight: 600;
      color: var(--accent); line-height: 1;
    }
    .detail-stat-l {
      font-family: 'Cinzel', serif; font-size: .58rem; letter-spacing: .12em;
      text-transform: uppercase; color: var(--muted); margin-top: .25rem;
    }

    /* Detail section label */
    .detail-section-label {
      padding: .5rem 1rem .35rem;
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .18em;
      text-transform: uppercase; color: var(--nav-text-dim);
      background: var(--card-bg);
      border-bottom: 1px solid var(--border);
    }

    /* Appearance rows inside detail */
    .detail-appearance-row { padding: .9rem 1rem; border-bottom: 1px solid var(--border); }
    .detail-appearance-row:last-child { border-bottom: none; }
    .detail-app-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; }
    .detail-app-name { font-family: 'Cinzel', serif; font-size: .88rem; font-weight: 600; letter-spacing: .03em; color: var(--text); }
    .badge-hl {
      background: var(--headliner-bg); color: var(--headliner-text);
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .05em;
      padding: .2rem .55rem; border-radius: 99px; white-space: nowrap; flex-shrink: 0;
    }
    .badge-sup {
      background: rgba(255,255,255,.06); color: var(--muted);
      font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: .05em;
      padding: .2rem .55rem; border-radius: 99px; white-space: nowrap; flex-shrink: 0;
    }
    .detail-app-date { font-size: .75rem; color: var(--muted); margin-bottom: .1rem; }
    .detail-app-venue { font-size: .72rem; color: var(--muted); opacity: .7; }
    .detail-app-performers { font-size: .75rem; color: var(--muted); margin-top: .3rem; }
    .detail-app-performers strong { color: var(--text); }
  </style>
</head>
<body>

<?php
  $currentPage = 'stats';
  $pageTitle   = 'Stats';
  require 'nav.php';

  // Pre-compute last-seen date per performer / venue
  $perfLastSeen = [];
  foreach ($performerStats as $name => $p) {
    $dates = array_filter(array_column($p['appearances'], 'start_date'));
    if ($dates) { sort($dates); $perfLastSeen[$name] = end($dates); }
  }
  $venueLastSeen = [];
  foreach ($venueStats as $vname => $v) {
    $dates = array_filter(array_column($v['appearances'], 'start_date'));
    if ($dates) { sort($dates); $venueLastSeen[$vname] = end($dates); }
  }

  // Gothic avatar palettes for venues (dark crimson tones)
  $venuePalettes = [
    ['bg'=>'#2a0000','color'=>'#ff6b6b'],
    ['bg'=>'#1a0a00','color'=>'#ff9944'],
    ['bg'=>'#00102a','color'=>'#6b9fff'],
    ['bg'=>'#0a1a00','color'=>'#6bff88'],
    ['bg'=>'#1a001a','color'=>'#df6bff'],
    ['bg'=>'#001a1a','color'=>'#6bffee'],
    ['bg'=>'#1a1a00','color'=>'#ffee6b'],
  ];
  function avatarInitials(string $name): string {
    $words = preg_split('/\s+/', trim($name));
    if (count($words) >= 2) return strtoupper(mb_substr($words[0],0,1) . mb_substr($words[count($words)-1],0,1));
    return strtoupper(mb_substr($name,0,2));
  }
  $top5Performers = array_slice(array_values($performerStats), 0, 5);
  $top5Venues     = array_slice(array_values($venueStats), 0, 5);
?>

<div class="stats-wrap">

  <!-- View Mode Toggle -->
  <div class="view-mode-bar">
    <span class="view-mode-label">Showing</span>
    <div class="view-mode-toggle">
      <a href="?view=mine" class="view-mode-btn <?= $viewMode === 'mine' ? 'active' : '' ?>">My attendance</a>
      <a href="?view=all"  class="view-mode-btn <?= $viewMode === 'all'  ? 'active' : '' ?>">All events</a>
    </div>
    <?php if ($viewMode === 'mine' && $currentUser): ?>
      <span class="view-mode-user"><?= htmlspecialchars($currentUser) ?></span>
    <?php endif; ?>
  </div>

  <!-- Summary Tiles -->
  <div class="summary-grid">
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
  </div>

  <!-- Top Lists — tabbed card -->
  <div class="summary-tabbed-card">
    <div class="summary-tab-header">Top 5</div>
    <div class="summary-tab-bar">
      <button class="summary-tab-btn active" onclick="switchSummaryTab('perf',this)">Performers</button>
      <button class="summary-tab-btn"        onclick="switchSummaryTab('venue',this)">Venues</button>
    </div>
    <div class="summary-tab-pane active" id="sum-pane-perf">
      <div id="topPerformersList">
        <?php foreach ($top5Performers as $i => $tp): ?>
          <div class="tile-list-row">
            <span class="tile-list-rank"><?= $i+1 ?></span>
            <span class="tile-list-name"><?= htmlspecialchars($tp['name']) ?></span>
            <span class="tile-list-count"><?= $tp['total'] ?>×</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="summary-tab-pane" id="sum-pane-venue">
      <div id="topVenuesList">
        <?php foreach ($top5Venues as $i => $tv): ?>
          <div class="tile-list-row">
            <span class="tile-list-rank"><?= $i+1 ?></span>
            <span class="tile-list-name"><?= htmlspecialchars($tv['name']) ?></span>
            <span class="tile-list-count"><?= $tv['total'] ?>×</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

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
    <button class="stats-tab active" data-mode="performer">Performers</button>
    <button class="stats-tab" data-mode="venue">Venues</button>
    <button class="stats-tab" data-mode="year">By year</button>
  </div>

  <!-- Sort + Search Bar -->
  <div class="controls-bar" id="sortControls">
    <div class="controls-bar-search">
      <div class="search-wrap">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="statsSearch" placeholder="Search…" autocomplete="off">
      </div>
    </div>
    <div class="controls-bar-sort">
      <button class="sort-btn active" id="sortCount">Count <span class="sort-arrow" id="sortCountArrow">↓</span></button>
      <button class="sort-btn" id="sortAlpha">A–Z <span class="sort-arrow" id="sortAlphaArrow">↑</span></button>
    </div>
  </div>

  <div class="result-count" id="resultCount"></div>

  <!-- ── Performer List — no avatars ── -->
  <div class="stats-section active" id="section-performer">
    <?php $pi = 0; foreach ($performerStats as $p):
      $lastDate = isset($perfLastSeen[$p['name']]) ? date('M Y', strtotime($perfLastSeen[$p['name']])) : '';
      $pYears   = array_unique(array_filter(array_column($p['appearances'], 'event_Year')));
    ?>
    <div class="list-row"
         data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
         data-years="<?= htmlspecialchars(implode(',', $pYears)) ?>"
         data-total="<?= $p['total'] ?>"
         onclick="openDetail('performer', <?= $pi ?>)">
      <div class="list-info">
        <div class="list-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="list-meta"><?= $lastDate ? 'Last seen ' . $lastDate : '&nbsp;' ?></div>
      </div>
      <div class="list-right">
        <span class="count-pill"><?= $p['total'] ?> show<?= $p['total'] !== 1 ? 's' : '' ?></span>
        <span class="chev-icon">›</span>
      </div>
    </div>
    <?php $pi++; endforeach; ?>
    <div class="no-results" id="noPerformer">No performers found.</div>
  </div>

  <!-- ── Venue List — keeps avatar icon ── -->
  <div class="stats-section" id="section-venue">
    <?php $vi = 0; foreach ($venueStats as $v):
      $pal      = $venuePalettes[$vi % count($venuePalettes)];
      $initials  = avatarInitials($v['name']);
      $vYears   = array_unique(array_column($v['appearances'], 'year'));
      $location  = implode(', ', array_filter([$v['city'], $v['state']]));
    ?>
    <div class="list-row"
         data-name="<?= htmlspecialchars(strtolower($v['name'] . ' ' . ($v['city'] ?? '') . ' ' . ($v['state'] ?? ''))) ?>"
         data-years="<?= htmlspecialchars(implode(',', $vYears)) ?>"
         data-total="<?= $v['total'] ?>"
         onclick="openDetail('venue', <?= $vi ?>)">
      <div class="list-avatar" style="background:<?= $pal['bg'] ?>;color:<?= $pal['color'] ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div class="list-info">
        <div class="list-name"><?= htmlspecialchars($v['name']) ?></div>
        <div class="list-meta"><?= $location ? htmlspecialchars($location) : '&nbsp;' ?></div>
      </div>
      <div class="list-right">
        <span class="count-pill"><?= $v['total'] ?> show<?= $v['total'] !== 1 ? 's' : '' ?></span>
        <span class="chev-icon">›</span>
      </div>
    </div>
    <?php $vi++; endforeach; ?>
    <div class="no-results" id="noVenue">No venues found.</div>
  </div>

  <!-- ── Year List ── -->
  <div class="stats-section" id="section-year">
    <?php foreach ($yearStats as $yr => $y): ?>
    <div class="year-list-header" data-name="<?= htmlspecialchars((string)$yr) ?>">
      <span><?= htmlspecialchars((string)$yr) ?></span>
      <small><?= $y['total'] ?> event<?= $y['total'] !== 1 ? 's' : '' ?></small>
    </div>
    <?php foreach ($y['events'] as $ev):
      $dateStr    = $ev['start_date'] ? date('M d, Y', strtotime($ev['start_date'])) : '—';
      $endStr     = $ev['end_date'] && $ev['end_date'] !== $ev['start_date'] ? ' – ' . date('M d', strtotime($ev['end_date'])) : '';
      $venue      = implode(', ', array_filter([$ev['venue_Name'], $ev['venue_City'], $ev['venue_State']]));
      $headliners = array_values(array_filter($ev['performers'], fn($p) => $p['is_Headliner']));
      $support    = array_values(array_filter($ev['performers'], fn($p) => !$p['is_Headliner']));
    ?>
    <div class="appearance-row year-event-row" data-year="<?= htmlspecialchars((string)$yr) ?>">
      <div class="app-top-line">
        <span class="app-event-name"><?= htmlspecialchars($ev['event_Name']) ?></span>
      </div>
      <div class="app-date"><?= htmlspecialchars($dateStr . $endStr) ?></div>
      <?php if ($venue): ?><div class="app-venue"><?= htmlspecialchars($venue) ?></div><?php endif; ?>
      <?php if (!empty($headliners) || !empty($support)): ?>
      <div class="app-performers">
        <?php if (!empty($headliners)): ?>
          <?php foreach ($headliners as $hl): ?><strong><?= htmlspecialchars($hl['name']) ?></strong> <span style="color:var(--headliner-text);font-size:.7rem;">★</span><?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($support)): ?>
          <?php if (!empty($headliners)): ?> · <?php endif; ?><?= htmlspecialchars(implode(', ', array_column($support, 'name'))) ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="no-results" id="noYear">No years found.</div>
  </div>

</div><!-- /stats-wrap -->

<!-- ── Detail Overlay — renders below the fixed nav ── -->
<div class="detail-overlay" id="detailOverlay" role="dialog" aria-modal="true" aria-label="Detail view">

  <!-- Back bar — always visible at top of overlay -->
  <div class="detail-topbar">
    <button class="back-btn" id="backBtn" onclick="closeDetail()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      <span id="backLabel">Back to Performers</span>
    </button>
  </div>

  <div id="detailContent"></div>
</div>

<script>
const allEvents     = <?= $eventsJson ?>;
const allPerformers = <?= $performerJson ?>;
const allVenues     = <?= $venueJson ?>;

let activeMode = 'performer';
let activeYear = 'all';
let activeSort = 'count-desc';

// ── Gothic venue avatar palettes (mirrors PHP) ──────────────────────
const VENUE_PALETTES = [
  {bg:'#2a0000',color:'#ff6b6b'},{bg:'#1a0a00',color:'#ff9944'},
  {bg:'#00102a',color:'#6b9fff'},{bg:'#0a1a00',color:'#6bff88'},
  {bg:'#1a001a',color:'#df6bff'},{bg:'#001a1a',color:'#6bffee'},
  {bg:'#1a1a00',color:'#ffee6b'},
];

function initials(name) {
  const w = name.trim().split(/\s+/);
  return w.length >= 2 ? (w[0][0] + w[w.length-1][0]).toUpperCase() : name.slice(0,2).toUpperCase();
}
function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtDate(d) {
  if (!d) return '—';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
}

// ── Open detail overlay ─────────────────────────────────────────────
function openDetail(mode, idx) {
  const overlay = document.getElementById('detailOverlay');
  const content = document.getElementById('detailContent');
  const backLabel = document.getElementById('backLabel');

  if (mode === 'performer') {
    backLabel.textContent = 'Back to Performers';
    const p    = allPerformers[idx];
    const apps = [...p.appearances].sort((a,b) => (b.start_date||'').localeCompare(a.start_date||''));
    const hlCount    = apps.filter(a => a.is_Headliner).length;
    const venueCount = new Set(apps.map(a => a.venue_Name).filter(Boolean)).size;

    content.innerHTML = `
      <div class="detail-hero">
        <div class="detail-hero-name">${esc(p.name)}</div>
        <div class="detail-divider"></div>
        <div class="detail-stats">
          <div class="detail-stat">
            <div class="detail-stat-n">${p.total}</div>
            <div class="detail-stat-l">Shows</div>
          </div>
          <div class="detail-stat">
            <div class="detail-stat-n">${hlCount}</div>
            <div class="detail-stat-l">Headliner</div>
          </div>
          <div class="detail-stat">
            <div class="detail-stat-n">${venueCount}</div>
            <div class="detail-stat-l">Venues</div>
          </div>
        </div>
      </div>
      <div class="detail-section-label">Appearance history</div>
      ${apps.map(a => {
        const venue = [a.venue_Name, a.venue_City, a.venue_State].filter(Boolean).join(', ');
        return `<div class="detail-appearance-row">
          <div class="detail-app-top">
            <span class="detail-app-name">${esc(a.event_Name)}</span>
            <span class="${a.is_Headliner ? 'badge-hl' : 'badge-sup'}">${a.is_Headliner ? '★ Headliner' : 'Support'}</span>
          </div>
          <div class="detail-app-date">${esc(fmtDate(a.start_date))}</div>
          ${venue ? `<div class="detail-app-venue">${esc(venue)}</div>` : ''}
        </div>`;
      }).join('')}`;
  }

  if (mode === 'venue') {
    backLabel.textContent = 'Back to Venues';
    const v    = allVenues[idx];
    const pal  = VENUE_PALETTES[idx % VENUE_PALETTES.length];
    const apps = [...v.appearances].sort((a,b) => (b.start_date||'').localeCompare(a.start_date||''));
    const location = [v.city, v.state].filter(Boolean).join(', ');
    const perfSet  = new Set();
    apps.forEach(a => (a.performers||[]).forEach(p => perfSet.add(p.name)));

    content.innerHTML = `
      <div class="detail-hero">
        <div class="detail-hero-name">${esc(v.name)}</div>
        ${location ? `<div class="detail-hero-sub">${esc(location)}</div>` : ''}
        <div class="detail-divider"></div>
        <div class="detail-stats">
          <div class="detail-stat">
            <div class="detail-stat-n">${v.total}</div>
            <div class="detail-stat-l">Events</div>
          </div>
          <div class="detail-stat">
            <div class="detail-stat-n">${perfSet.size}</div>
            <div class="detail-stat-l">Performers</div>
          </div>
        </div>
      </div>
      <div class="detail-section-label">Event history</div>
      ${apps.map(a => {
        const headliners = (a.performers||[]).filter(p => p.is_Headliner);
        const support    = (a.performers||[]).filter(p => !p.is_Headliner);
        const dateStr = fmtDate(a.start_date);
        const endStr  = a.end_date && a.end_date !== a.start_date ? ' – ' + fmtDate(a.end_date) : '';
        return `<div class="detail-appearance-row">
          <div class="detail-app-top">
            <span class="detail-app-name">${esc(a.event_Name)}</span>
          </div>
          <div class="detail-app-date">${esc(dateStr + endStr)}</div>
          ${headliners.length || support.length ? `<div class="detail-app-performers">
            ${headliners.map(p=>`<strong>${esc(p.name)}</strong> <span style="color:var(--headliner-text);font-size:.7rem">★</span>`).join(' ')}
            ${headliners.length && support.length ? ' · ' : ''}
            ${support.map(p=>esc(p.name)).join(', ')}
          </div>` : ''}
        </div>`;
      }).join('')}`;
  }

  overlay.classList.add('open');
  overlay.scrollTop = 0;
  document.body.style.overflow = 'hidden';
}

function closeDetail() {
  document.getElementById('detailOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });

// ── Year filter pills ───────────────────────────────────────────────
document.querySelectorAll('.year-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.year-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    activeYear = pill.dataset.year;
    updateSummary();
    applyFiltersAndSort();
  });
});

function updateSummary() {
  const filtered = activeYear === 'all' ? allEvents : allEvents.filter(ev => String(ev.event_Year) === String(activeYear));
  const perfSet = new Set(), venueSet = new Set();
  filtered.forEach(ev => {
    if (ev.venue_Name) venueSet.add(ev.venue_Name);
    (ev.performers||[]).forEach(p => perfSet.add(p.name));
  });
  document.getElementById('summaryEvents').textContent     = filtered.length;
  document.getElementById('summaryPerformers').textContent = perfSet.size;
  document.getElementById('summaryVenues').textContent     = venueSet.size;

  const perfCounts = {};
  filtered.forEach(ev => (ev.performers||[]).forEach(p => { perfCounts[p.name] = (perfCounts[p.name]||0)+1; }));
  document.getElementById('topPerformersList').innerHTML = Object.entries(perfCounts).sort((a,b)=>b[1]-a[1]).slice(0,5)
    .map(([name,cnt],i) => `<div class="tile-list-row"><span class="tile-list-rank">${i+1}</span><span class="tile-list-name">${esc(name)}</span><span class="tile-list-count">${cnt}×</span></div>`).join('');

  const venueCounts = {};
  filtered.forEach(ev => { if(ev.venue_Name) venueCounts[ev.venue_Name]=(venueCounts[ev.venue_Name]||0)+1; });
  document.getElementById('topVenuesList').innerHTML = Object.entries(venueCounts).sort((a,b)=>b[1]-a[1]).slice(0,5)
    .map(([name,cnt],i) => `<div class="tile-list-row"><span class="tile-list-rank">${i+1}</span><span class="tile-list-name">${esc(name)}</span><span class="tile-list-count">${cnt}×</span></div>`).join('');
}

// ── Mode tabs ───────────────────────────────────────────────────────
document.querySelectorAll('.stats-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.stats-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    activeMode = tab.dataset.mode;
    document.querySelectorAll('.stats-section').forEach(s => s.classList.remove('active'));
    document.getElementById('section-' + activeMode).classList.add('active');
    document.getElementById('statsSearch').value = '';
    document.getElementById('sortControls').classList.toggle('hidden', activeMode === 'year');
    applyFiltersAndSort();
  });
});

// ── Sort ────────────────────────────────────────────────────────────
const sortCountBtn   = document.getElementById('sortCount');
const sortAlphaBtn   = document.getElementById('sortAlpha');
const sortCountArrow = document.getElementById('sortCountArrow');
const sortAlphaArrow = document.getElementById('sortAlphaArrow');

sortCountBtn.addEventListener('click', () => setSort(activeSort === 'count-desc' ? 'count-asc' : 'count-desc'));
sortAlphaBtn.addEventListener('click', () => setSort(activeSort === 'alpha-asc'  ? 'alpha-desc' : 'alpha-asc'));

function setSort(sort) {
  activeSort = sort;
  sortCountBtn.classList.toggle('active', sort === 'count-desc' || sort === 'count-asc');
  sortAlphaBtn.classList.toggle('active', sort === 'alpha-asc'  || sort === 'alpha-desc');
  sortCountArrow.textContent = sort === 'count-asc' ? '↑' : '↓';
  sortAlphaArrow.textContent = sort === 'alpha-desc' ? '↓' : '↑';
  applyFiltersAndSort();
}

// ── Search ──────────────────────────────────────────────────────────
document.getElementById('statsSearch').addEventListener('input', applyFiltersAndSort);

// ── Filter + sort + render ──────────────────────────────────────────
function applyFiltersAndSort() {
  const q       = document.getElementById('statsSearch').value.trim().toLowerCase();
  const section = document.getElementById('section-' + activeMode);
  const resultEl = document.getElementById('resultCount');

  if (activeMode === 'year') {
    const headers = section.querySelectorAll('.year-list-header');
    let visible = 0;
    headers.forEach(h => {
      const yr   = h.dataset.name || '';
      const rows = section.querySelectorAll(`.year-event-row[data-year="${yr}"]`);
      const match = !q || yr.includes(q);
      h.style.display = match ? '' : 'none';
      rows.forEach(r => r.style.display = match ? '' : 'none');
      if (match) visible++;
    });
    section.querySelector('#noYear').style.display = visible === 0 ? 'block' : 'none';
    resultEl.textContent = `${visible} year${visible !== 1 ? 's' : ''}${q ? ` matching "${q}"` : ''}`;
    return;
  }

  const rows  = Array.from(section.querySelectorAll('.list-row'));
  const noEl  = section.querySelector('.no-results');
  const label = activeMode === 'performer' ? 'performer' : 'venue';

  const filtered = rows.filter(row => {
    const nameMatch = !q || (row.dataset.name || '').includes(q);
    const yearMatch = activeYear === 'all' || (row.dataset.years || '').split(',').includes(String(activeYear));
    return nameMatch && yearMatch;
  });

  rows.filter(r => !filtered.includes(r)).forEach(r => r.style.display = 'none');

  filtered.sort((a, b) => {
    const ta = parseInt(a.dataset.total||'0',10), tb = parseInt(b.dataset.total||'0',10);
    const na = (a.querySelector('.list-name')?.textContent||'').toLowerCase();
    const nb = (b.querySelector('.list-name')?.textContent||'').toLowerCase();
    switch(activeSort) {
      case 'count-desc': return tb - ta || na.localeCompare(nb);
      case 'count-asc':  return ta - tb || na.localeCompare(nb);
      case 'alpha-asc':  return na.localeCompare(nb);
      case 'alpha-desc': return nb.localeCompare(na);
      default: return 0;
    }
  });

  filtered.forEach(r => { r.style.display = ''; section.insertBefore(r, noEl); });
  noEl.style.display = filtered.length === 0 ? 'block' : 'none';
  resultEl.textContent = `${filtered.length} ${label}${filtered.length !== 1 ? 's' : ''}${q ? ` matching "${q}"` : ''}${activeYear !== 'all' ? ` in ${activeYear}` : ''}`;
}

// ── Summary tab switcher ────────────────────────────────────────────
function switchSummaryTab(id, el) {
  document.querySelectorAll('.summary-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.summary-tab-pane').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sum-pane-' + id).classList.add('active');
}

// ── Init ────────────────────────────────────────────────────────────
applyFiltersAndSort();
</script>

</body>
</html>