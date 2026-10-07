<?php
declare(strict_types=1);
$currentAdminPage = 'dashboard';
require_once __DIR__ . '/includes/guard.php';
$pageTitle = 'Dashboard';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/nav.php';
?>
    <div class="admin-topline">
      <div>
        <p class="eyebrow">Panel administratora</p>
        <h1>Witaj,<br /><em><?php echo htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8'); ?>.</em></h1>
      </div>
    </div>

    <section class="admin-stat-grid" id="dashboard-stats" aria-live="polite">
      <article class="admin-stat-card">
        <span class="admin-stat-label">Aktywni zawodnicy</span>
        <strong data-stat="activePlayers">—</strong>
      </article>
      <article class="admin-stat-card">
        <span class="admin-stat-label">Mecze w terminarzu</span>
        <strong data-stat="totalMatches">—</strong>
      </article>
      <article class="admin-stat-card">
        <span class="admin-stat-label">Nowe wiadomości</span>
        <strong data-stat="newRegistrations">—</strong>
      </article>
    </section>

    <section class="admin-card">
      <h2>Szybkie skróty</h2>
      <div class="admin-quicklinks">
        <a class="button button-outline" href="players.php">Zarządzaj zawodnikami <span>→</span></a>
        <a class="button button-outline" href="matches.php">Zarządzaj meczami <span>→</span></a>
        <a class="button button-outline" href="registrations.php">Zobacz wiadomości <span>→</span></a>
      </div>
    </section>
<?php require __DIR__ . '/includes/foot.php'; ?>
