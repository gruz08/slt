<?php
/**
 * nav.php
 * Oczekuje ustawionych zmiennych: $currentAdminPage, $adminUsername.
 */
?>
<header class="admin-header">
  <div class="admin-shell admin-header-inner">
    <a class="brand" href="dashboard.php" aria-label="Panel — strona główna">
      <span class="brand-mark" aria-hidden="true"><span>SL</span><i></i></span>
      <span class="brand-copy"><strong>Śląska Liga</strong><small>Panel</small></span>
    </a>
    <nav class="admin-nav" aria-label="Nawigacja panelu">
      <a href="dashboard.php" class="<?php echo $currentAdminPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
      <a href="players.php" class="<?php echo $currentAdminPage === 'players' ? 'active' : ''; ?>">Zawodnicy</a>
      <a href="matches.php" class="<?php echo $currentAdminPage === 'matches' ? 'active' : ''; ?>">Mecze</a>
      <a href="registrations.php" class="<?php echo $currentAdminPage === 'registrations' ? 'active' : ''; ?>">Wiadomości</a>
    </nav>
    <div class="admin-user">
      <span class="admin-user-name"><?php echo htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8'); ?></span>
      <button type="button" id="logout-button" class="button button-outline admin-logout-button">Wyloguj</button>
    </div>
  </div>
</header>
<main class="admin-main">
  <div class="admin-shell">
