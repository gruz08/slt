<?php
declare(strict_types=1);
$currentAdminPage = 'registrations';
require_once __DIR__ . '/includes/guard.php';
$pageTitle = 'Wiadomości';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/nav.php';
?>
    <div class="admin-topline">
      <div>
        <p class="eyebrow">Formularz kontaktowy</p>
        <h1>Wiadomości<br /><em>z formularza.</em></h1>
      </div>
    </div>

    <section class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table" id="registrations-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Imię i nazwisko</th>
              <th>Kontakt</th>
              <th>Wiadomość</th>
              <th class="admin-table-actions-col">Akcje</th>
            </tr>
          </thead>
          <tbody id="registrations-table-body">
            <tr><td colspan="5" class="admin-table-empty">Wczytywanie…</td></tr>
          </tbody>
        </table>
      </div>
    </section>
<?php require __DIR__ . '/includes/foot.php'; ?>
