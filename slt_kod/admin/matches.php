<?php
declare(strict_types=1);
$currentAdminPage = 'matches';
require_once __DIR__ . '/includes/guard.php';
$pageTitle = 'Mecze';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/nav.php';
?>
    <div class="admin-topline">
      <div>
        <p class="eyebrow">Terminarz</p>
        <h1>Mecze<br /><em>sezonu.</em></h1>
      </div>
      <button type="button" class="button button-primary" id="add-match-button">Dodaj mecz <span>+</span></button>
    </div>

    <section class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table" id="matches-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Godz.</th>
              <th>Liga / kolejka</th>
              <th>Mecz</th>
              <th>Lokalizacja</th>
              <th>Status</th>
              <th>Wynik</th>
              <th class="admin-table-actions-col">Akcje</th>
            </tr>
          </thead>
          <tbody id="matches-table-body">
            <tr><td colspan="8" class="admin-table-empty">Wczytywanie…</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="admin-modal" id="match-modal" hidden>
      <div class="admin-modal-card admin-modal-card-wide">
        <div class="admin-modal-head">
          <h2 id="match-modal-title">Dodaj mecz</h2>
          <button type="button" class="admin-modal-close" data-close-modal="match-modal" aria-label="Zamknij">✕</button>
        </div>
        <form id="match-form" novalidate>
          <input type="hidden" name="id" value="" />
          <div class="field-grid">
            <label class="field">
              <span>Liga <b>*</b></span>
              <select name="league" required>
                <option value="A">Liga A</option>
                <option value="B">Liga B</option>
              </select>
            </label>
            <label class="field">
              <span>Kolejka <b>*</b></span>
              <input type="number" name="round" min="1" max="100" required />
            </label>
          </div>
          <div class="field-grid">
            <label class="field">
              <span>Data <b>*</b></span>
              <input type="date" name="date" required />
            </label>
            <label class="field">
              <span>Godzina <b>*</b></span>
              <input type="time" name="time" required />
            </label>
          </div>
          <div class="field-grid">
            <label class="field">
              <span>Zawodnik 1 <b>*</b></span>
              <select name="player1Id" required></select>
            </label>
            <label class="field">
              <span>Zawodnik 2 <b>*</b></span>
              <select name="player2Id" required></select>
            </label>
          </div>
          <div class="field-grid">
            <label class="field">
              <span>Lokalizacja <b>*</b></span>
              <input type="text" name="location" required maxlength="120" />
            </label>
            <label class="field">
              <span>Kort</span>
              <input type="text" name="court" maxlength="60" />
            </label>
          </div>
          <div class="field-grid">
            <label class="field">
              <span>Status <b>*</b></span>
              <select name="status" id="match-status-select" required>
                <option value="scheduled">Zaplanowany</option>
                <option value="completed">Zakończony</option>
              </select>
            </label>
            <label class="field" id="match-score-field" hidden>
              <span>Wynik</span>
              <input type="text" name="score" placeholder="np. 6:4, 6:3" maxlength="60" />
            </label>
          </div>
          <p class="admin-form-error" id="match-form-error" role="alert" hidden></p>
          <div class="admin-modal-actions">
            <button type="button" class="button button-outline" data-close-modal="match-modal">Anuluj</button>
            <button type="submit" class="button button-primary">Zapisz <span>↗</span></button>
          </div>
        </form>
      </div>
    </div>
<?php require __DIR__ . '/includes/foot.php'; ?>
