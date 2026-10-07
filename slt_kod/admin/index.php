<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/auth.php';

// Jeśli ktoś jest już zalogowany, od razu na dashboard.
if (session_is_valid()) {
    header('Location: dashboard.php');
    exit;
}

$csrfToken = issue_csrf_token();
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
  <title>Logowanie — Panel Śląskiej Ligi Tenisa</title>
  <link rel="stylesheet" href="../styles.css" />
  <link rel="stylesheet" href="assets/admin.css" />
</head>
<body class="admin-body admin-body-login">
  <main class="login-screen">
    <div class="login-card">
      <div class="brand login-brand">
        <span class="brand-mark" aria-hidden="true"><span>SL</span><i></i></span>
        <span class="brand-copy"><strong>Śląska Liga</strong><small>Panel administratora</small></span>
      </div>
      <p class="eyebrow">Panel prywatny</p>
      <h1>Zaloguj się<br /><em>do panelu.</em></h1>
      <form id="login-form" novalidate>
        <label class="field">
          <span>Login</span>
          <input type="text" name="username" autocomplete="username" required />
        </label>
        <label class="field">
          <span>Hasło</span>
          <input type="password" name="password" autocomplete="current-password" required />
        </label>
        <p class="login-error" id="login-error" role="alert" hidden></p>
        <button class="button button-primary login-submit" type="submit">Zaloguj się <span>↗</span></button>
      </form>
    </div>
  </main>
  <script src="assets/admin.js"></script>
</body>
</html>
