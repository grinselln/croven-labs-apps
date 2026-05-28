<?php
// ─── nav.php ─────────────────────────────────────────────────────────
// Logged-in user (set by auth.php) drives all user-scoped data.
// No more "Viewing as" dropdown — the session user IS the current user.
// ─────────────────────────────────────────────────────────────────────

$authUserName = $authUserName ?? ($_SESSION['auth_user_name'] ?? '');
$selectedUser = $authUserName;

// ─── Fetch favorites for the logged-in user ──────────────────────────
$favorites = [];
try {
    if ($selectedUser !== '') {
        $favStmt = $pdo->prepare("SELECT label, path FROM vw_favorites WHERE user = ? ORDER BY label LIMIT 5");
        $favStmt->execute([$selectedUser]);
    } else {
        $favStmt = $pdo->query("SELECT label, path FROM vw_favorites ORDER BY label");
    }
    $favorites = $favStmt->fetchAll();
} catch (Exception $e) { /* skip */ }

$navItems = [
    'home'      => ['label' => 'Home',      'href' => 'index.php',     'icon' => 'ti-home'],
    'schedule'  => ['label' => 'Schedule',  'href' => 'schedule.php',  'icon' => 'ti-calendar'],
    'new'       => ['label' => 'Add Event', 'href' => 'add_event.php', 'icon' => 'ti-plus'],
    'favorites' => ['label' => 'Favorites', 'href' => 'favorites.php', 'icon' => 'ti-star'],
    'festivals' => ['label' => 'Festivals', 'href' => 'festivals.php', 'icon' => 'ti-flag'],
    'admin'     => ['label' => 'Admin',     'href' => 'admin.php',     'icon' => 'ti-shield'],
];

$currentPage = $currentPage ?? '';
$pageTitle   = $pageTitle   ?? 'Croven Events';
?>

<!-- Tabler Icons (outline) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<header class="site-header">
  <button class="hamburger" id="hamburgerBtn" aria-label="Open menu" aria-expanded="false">
    <span class="ham-bar"></span>
    <span class="ham-bar"></span>
    <span class="ham-bar"></span>
  </button>
  <span class="site-title"><?= htmlspecialchars($pageTitle) ?></span>
</header>

<div class="nav-overlay" id="navOverlay"></div>
<nav class="nav-drawer" id="navDrawer" aria-hidden="true">

  <div class="nav-drawer-header">
    <span class="nav-drawer-label">Menu</span>
    <button class="nav-close-btn" id="navCloseBtn" aria-label="Close menu">&#10005;</button>
  </div>

  <?php if ($authUserName !== ''): ?>
  <div class="nav-auth-badge">
    <span class="nav-auth-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($authUserName, 0, 1))) ?></span>
    <span class="nav-auth-name"><?= htmlspecialchars($authUserName) ?></span>
    <a href="api/logout.php" class="nav-logout-btn">Sign out</a>
  </div>
  <?php endif; ?>

  <div class="nav-divider"></div>

  <ul class="nav-list">
    <?php foreach ($navItems as $key => $item): ?>
      <li>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="nav-item <?= $currentPage === $key ? 'nav-item--active' : '' ?>">
          <i class="ti <?= $item['icon'] ?> nav-item-icon" aria-hidden="true"></i>
          <span class="nav-item-label"><?= htmlspecialchars($item['label']) ?></span>
          <?php if ($currentPage === $key): ?><span class="nav-item-dot"></span><?php endif; ?>
        </a>

        <?php if ($key === 'favorites' && !empty($favorites)): ?>
        <ul class="nav-fav-list">
          <?php foreach ($favorites as $fav): ?>
            <li>
              <a href="<?= htmlspecialchars($fav['path']) ?>" class="nav-fav-item">
                <span class="nav-fav-dot"></span>
                <?= htmlspecialchars($fav['label']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

</nav>


<script>
(function () {
  const hamburger = document.getElementById('hamburgerBtn');
  const drawer    = document.getElementById('navDrawer');
  const overlay   = document.getElementById('navOverlay');
  const closeBtn  = document.getElementById('navCloseBtn');

  function openDrawer() {
    drawer.classList.add('open'); overlay.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true'); drawer.setAttribute('aria-hidden', 'false');
    hamburger.classList.add('is-open');
  }
  function closeDrawer() {
    drawer.classList.remove('open'); overlay.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false'); drawer.setAttribute('aria-hidden', 'true');
    hamburger.classList.remove('is-open');
  }
  hamburger.addEventListener('click', openDrawer);
  closeBtn.addEventListener('click',  closeDrawer);
  overlay.addEventListener('click',   closeDrawer);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
})();
</script>