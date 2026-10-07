<?php
declare(strict_types=1);
$currentAdminPage = 'players';
require_once __DIR__ . '/includes/guard.php';
$pageTitle = 'Zawodnicy';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/nav.php';
?>
    <div class="admin-topline">
      <div>
        <p class="eyebrow">Baza zawodników</p>
        <h1>Zawodnicy<br /><em>ligi.</em></h1>
      </div>
      <button type="button" class="button button-primary" id="add-player-button">Dodaj zawodnika <span>+</span></button>
    </div>

    <section class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table" id="players-table">
          <thead>
            <tr>
              <th>Imię i nazwisko</th>
              <th>Poziom</th>
              <th>Miasto</th>
              <th>Status</th>
              <th class="admin-table-actions-col">Akcje</th>
            </tr>
          </thead>
          <tbody id="players-table-body">
            <tr><td colspan="5" class="admin-table-empty">Wczytywanie…</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="admin-modal" id="player-modal" hidden>
      <div class="admin-modal-card">
        <div class="admin-modal-head">
          <h2 id="player-modal-title">Dodaj zawodnika</h2>
          <button type="button" class="admin-modal-close" data-close-modal="player-modal" aria-label="Zamknij">✕</button>
        </div>
        <form id="player-form" novalidate>
          <input type="hidden" name="id" value="" />
          <label class="field">
            <span>Imię i nazwisko <b>*</b></span>
            <input type="text" name="name" required maxlength="120" />
          </label>
          <label class="field">
            <span>Poziom gry</span>
            <select name="level">
              <option value="">— nie wybrano —</option>
              <option value="Początkujący">Początkujący</option>
              <option value="Średniozaawansowany">Średniozaawansowany</option>
              <option value="Zaawansowany">Zaawansowany</option>
            </select>
          </label>
          <label class="field">
            <span>Miasto</span>
            <input type="text" name="city" maxlength="80" />
          </label>
          <label class="check-field">
            <input type="checkbox" name="active" checked />
            <span>Zawodnik aktywny w bieżącym sezonie</span>
          </label>
          <p class="admin-form-error" id="player-form-error" role="alert" hidden></p>
          <div class="admin-modal-actions">
            <button type="button" class="button button-outline" data-close-modal="player-modal">Anuluj</button>
            <button type="submit" class="button button-primary">Zapisz <span>↗</span></button>
          </div>
        </form>
      </div>
    </div>
<?php require __DIR__ . '/includes/foot.php'; ?>
