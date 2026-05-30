<?php
// ─── login.php ────────────────────────────────────────────────────────
require_once 'db/db_hosted.php';

// Already logged in → go home
if (!empty($_SESSION['auth_user_id'])) {
    header('Location: index.php');
    exit;
}

$error    = '';
$redirect = $_GET['redirect'] ?? 'index.php';

// ─── Handle POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password_hash FROM users WHERE name = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID on login to prevent fixation
                session_regenerate_id(true);
                $_SESSION['auth_user_id']   = $user['id'];
                $_SESSION['auth_user_name'] = $user['name'];
                $_SESSION['nav_user']       = $user['name'];

                $dest = filter_var($redirect, FILTER_SANITIZE_URL);
                // Only allow relative redirects
                if (!$dest || strpos($dest, '//') !== false || strpos($dest, 'http') === 0) {
                    $dest = 'index.php';
                }
                header('Location: ' . $dest);
                exit;
            } else {
                // Generic message — don't reveal whether username exists
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Croven Events</title>
  <link rel="stylesheet" href="css/styles.css">

</head>
<body class="login-page">

<div class="login-wrap">

  <div class="login-brand">
    <div class="login-brand-icon">🎶</div>
    <div class="login-brand-name">Croven Events</div>
    <div class="login-brand-sub">Sign in to continue</div>
  </div>

  <div class="login-card">
    <div class="login-card-title">Welcome back</div>

    <?php if ($error !== ''): ?>
      <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php<?= $redirect !== 'index.php' ? '?redirect=' . urlencode($redirect) : '' ?>">
      <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="login-field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="Your name" autofocus autocomplete="username" required>
        </div>
        <div class="login-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-submit">Sign In</button>
      </div>
    </form>
  </div>

</div>

<script>
// Apply saved theme on load
(function() {
  const theme = localStorage.getItem('theme') || 'dark';
  document.body.classList.remove('dark', 'red');
  if (theme === 'dark') document.body.classList.add('dark');
  if (theme === 'red')  document.body.classList.add('red');
})();
</script>

</body>
</html>