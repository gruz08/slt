(() => {
  "use strict";

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

  async function apiFetch(url, { method = "GET", body = null } = {}) {
    const options = {
      method,
      credentials: "same-origin",
      headers: {},
    };

    if (method !== "GET") {
      options.headers["Content-Type"] = "application/json";
      options.headers["X-CSRF-Token"] = csrfToken;
      options.body = JSON.stringify({ ...(body || {}), csrf_token: csrfToken });
    }

    const response = await fetch(url, options);

    if (response.status === 401) {
      window.location.href = "index.php";
      throw new Error("Sesja wygasła.");
    }

    let data = {};
    try {
      data = await response.json();
    } catch (e) {
      data = { success: false, error: "Nieprawidłowa odpowiedź serwera." };
    }

    if (!response.ok || !data.success) {
      throw new Error(data.error || "Wystąpił nieoczekiwany błąd.");
    }

    return data;
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
  }

  // Bezpieczne kodowanie/dekodowanie obiektu do atrybutu HTML — odporne na
  // wszystkie znaki specjalne (cudzysłowy, apostrofy, znaki < >, itd.),
  // niezależnie od tego, jakim znakiem jest ograniczony atrybut.
  function encodeForAttribute(value) {
    return encodeURIComponent(JSON.stringify(value));
  }
  function decodeFromAttribute(value) {
    return JSON.parse(decodeURIComponent(value));
  }

  // --- Logowanie ------------------------------------------------------
  const loginForm = document.querySelector("#login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const errorBox = document.querySelector("#login-error");
      errorBox.hidden = true;

      const formData = new FormData(loginForm);
      try {
        await apiFetch("../api/login.php", {
          method: "POST",
          body: {
            username: formData.get("username"),
            password: formData.get("password"),
          },
        });
        window.location.href = "dashboard.php";
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
      }
    });
  }

  // --- Wylogowanie (dostępne na każdej stronie panelu) ------------------
  const logoutButton = document.querySelector("#logout-button");
  if (logoutButton) {
    logoutButton.addEventListener("click", async () => {
      try {
        await apiFetch("../api/logout.php", { method: "POST" });
      } catch (e) {
        // Nawet jeśli żądanie się nie powiedzie, i tak wracamy do logowania.
      }
      window.location.href = "index.php";
    });
  }

  // --- Obsługa modali (dodaj/edytuj) ------------------------------------
  function openModal(id) {
    document.getElementById(id).hidden = false;
  }
  function closeModal(id) {
    document.getElementById(id).hidden = true;
  }
  document.querySelectorAll("[data-close-modal]").forEach((button) => {
    button.addEventListener("click", () => closeModal(button.dataset.closeModal));
  });

  // ======================================================================
  // ZAWODNICY (players.php)
  // ======================================================================
  const playersTableBody = document.querySelector("#players-table-body");
  if (playersTableBody) {
    const playerModal = "player-modal";
    const playerForm = document.querySelector("#player-form");
    const playerFormError = document.querySelector("#player-form-error");
    const playerModalTitle = document.querySelector("#player-modal-title");

    async function loadPlayers() {
      playersTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Wczytywanie…</td></tr>`;
      try {
        const { players } = await apiFetch("../api/players_list.php");
        if (!players.length) {
          playersTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Brak zawodników. Dodaj pierwszego.</td></tr>`;
          return;
        }
        playersTableBody.innerHTML = players
          .map((player) => `
            <tr data-id="${escapeHtml(player.id)}">
              <td>${escapeHtml(player.name)}</td>
              <td>${escapeHtml(player.level || "—")}</td>
              <td>${escapeHtml(player.city || "—")}</td>
              <td><span class="status-badge ${player.active ? "status-scheduled" : "status-inactive"}">${player.active ? "Aktywny" : "Nieaktywny"}</span></td>
              <td>
                <div class="admin-row-actions">
                  <button type="button" class="admin-icon-button" data-edit-player="${encodeForAttribute(player)}">Edytuj</button>
                  <button type="button" class="admin-icon-button danger" data-delete-player="${escapeHtml(player.id)}">Usuń</button>
                </div>
              </td>
            </tr>
          `)
          .join("");
      } catch (error) {
        playersTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Błąd wczytywania: ${escapeHtml(error.message)}</td></tr>`;
      }
    }

    function resetPlayerForm() {
      playerForm.reset();
      playerForm.querySelector('[name="id"]').value = "";
      playerForm.querySelector('[name="active"]').checked = true;
      playerFormError.hidden = true;
    }

    document.querySelector("#add-player-button").addEventListener("click", () => {
      resetPlayerForm();
      playerModalTitle.textContent = "Dodaj zawodnika";
      openModal(playerModal);
    });

    playersTableBody.addEventListener("click", (event) => {
      const editButton = event.target.closest("[data-edit-player]");
      if (editButton) {
        const player = decodeFromAttribute(editButton.dataset.editPlayer);
        resetPlayerForm();
        playerModalTitle.textContent = "Edytuj zawodnika";
        playerForm.querySelector('[name="id"]').value = player.id;
        playerForm.querySelector('[name="name"]').value = player.name || "";
        playerForm.querySelector('[name="level"]').value = player.level || "";
        playerForm.querySelector('[name="city"]').value = player.city || "";
        playerForm.querySelector('[name="active"]').checked = !!player.active;
        openModal(playerModal);
        return;
      }

      const deleteButton = event.target.closest("[data-delete-player]");
      if (deleteButton) {
        const id = deleteButton.dataset.deletePlayer;
        if (!window.confirm("Na pewno usunąć tego zawodnika?")) return;
        apiFetch("../api/players_delete.php", { method: "POST", body: { id } })
          .then(loadPlayers)
          .catch((error) => window.alert(error.message));
      }
    });

    playerForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      playerFormError.hidden = true;
      const formData = new FormData(playerForm);
      try {
        await apiFetch("../api/players_save.php", {
          method: "POST",
          body: {
            id: formData.get("id"),
            name: formData.get("name"),
            level: formData.get("level"),
            city: formData.get("city"),
            active: playerForm.querySelector('[name="active"]').checked,
          },
        });
        closeModal(playerModal);
        loadPlayers();
      } catch (error) {
        playerFormError.textContent = error.message;
        playerFormError.hidden = false;
      }
    });

    loadPlayers();
  }

  // ======================================================================
  // MECZE (matches.php)
  // ======================================================================
  const matchesTableBody = document.querySelector("#matches-table-body");
  if (matchesTableBody) {
    const matchModal = "match-modal";
    const matchForm = document.querySelector("#match-form");
    const matchFormError = document.querySelector("#match-form-error");
    const matchModalTitle = document.querySelector("#match-modal-title");
    const statusSelect = document.querySelector("#match-status-select");
    const scoreField = document.querySelector("#match-score-field");
    const player1Select = matchForm.querySelector('[name="player1Id"]');
    const player2Select = matchForm.querySelector('[name="player2Id"]');

    let playersCache = [];

    function toggleScoreField() {
      scoreField.hidden = statusSelect.value !== "completed";
    }
    statusSelect.addEventListener("change", toggleScoreField);

    function fillPlayerSelects(selectedP1 = "", selectedP2 = "") {
      const options = playersCache
        .map((p) => `<option value="${escapeHtml(p.id)}">${escapeHtml(p.name)}</option>`)
        .join("");
      player1Select.innerHTML = `<option value="">— wybierz —</option>${options}`;
      player2Select.innerHTML = `<option value="">— wybierz —</option>${options}`;
      player1Select.value = selectedP1;
      player2Select.value = selectedP2;
    }

    async function loadPlayersForSelects() {
      const { players } = await apiFetch("../api/players_list.php");
      playersCache = players;
    }

    function statusLabel(match) {
      return match.status === "completed"
        ? `<span class="status-badge status-completed">Zakończony</span>`
        : `<span class="status-badge status-scheduled">Zaplanowany</span>`;
    }

    function formatDate(dateStr) {
      const parts = dateStr.split("-");
      return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : dateStr;
    }

    async function loadMatches() {
      matchesTableBody.innerHTML = `<tr><td colspan="8" class="admin-table-empty">Wczytywanie…</td></tr>`;
      try {
        const [{ matches }] = await Promise.all([
          apiFetch("../api/matches_list.php"),
          loadPlayersForSelects(),
        ]);

        if (!matches.length) {
          matchesTableBody.innerHTML = `<tr><td colspan="8" class="admin-table-empty">Brak meczów. Dodaj pierwszy.</td></tr>`;
          return;
        }

        matchesTableBody.innerHTML = matches
          .map((match) => `
            <tr data-id="${escapeHtml(match.id)}">
              <td>${escapeHtml(formatDate(match.date))}</td>
              <td>${escapeHtml(match.time)}</td>
              <td>Liga ${escapeHtml(match.league)} · k. ${escapeHtml(String(match.round))}</td>
              <td>${escapeHtml(match.player1Name)} vs ${escapeHtml(match.player2Name)}</td>
              <td>${escapeHtml(match.location)}${match.court ? " · " + escapeHtml(match.court) : ""}</td>
              <td>${statusLabel(match)}</td>
              <td>${escapeHtml(match.score || "—")}</td>
              <td>
                <div class="admin-row-actions">
                  <button type="button" class="admin-icon-button" data-edit-match="${encodeForAttribute(match)}">Edytuj</button>
                  <button type="button" class="admin-icon-button danger" data-delete-match="${escapeHtml(match.id)}">Usuń</button>
                </div>
              </td>
            </tr>
          `)
          .join("");
      } catch (error) {
        matchesTableBody.innerHTML = `<tr><td colspan="8" class="admin-table-empty">Błąd wczytywania: ${escapeHtml(error.message)}</td></tr>`;
      }
    }

    function resetMatchForm() {
      matchForm.reset();
      matchForm.querySelector('[name="id"]').value = "";
      fillPlayerSelects();
      toggleScoreField();
      matchFormError.hidden = true;
    }

    document.querySelector("#add-match-button").addEventListener("click", async () => {
      resetMatchForm();
      matchModalTitle.textContent = "Dodaj mecz";
      openModal(matchModal);
    });

    matchesTableBody.addEventListener("click", (event) => {
      const editButton = event.target.closest("[data-edit-match]");
      if (editButton) {
        const match = decodeFromAttribute(editButton.dataset.editMatch);
        resetMatchForm();
        matchModalTitle.textContent = "Edytuj mecz";
        matchForm.querySelector('[name="id"]').value = match.id;
        matchForm.querySelector('[name="league"]').value = match.league;
        matchForm.querySelector('[name="round"]').value = match.round;
        matchForm.querySelector('[name="date"]').value = match.date;
        matchForm.querySelector('[name="time"]').value = match.time;
        matchForm.querySelector('[name="location"]').value = match.location;
        matchForm.querySelector('[name="court"]').value = match.court || "";
        matchForm.querySelector('[name="status"]').value = match.status;
        matchForm.querySelector('[name="score"]').value = match.score || "";
        fillPlayerSelects(match.player1Id, match.player2Id);
        toggleScoreField();
        openModal(matchModal);
        return;
      }

      const deleteButton = event.target.closest("[data-delete-match]");
      if (deleteButton) {
        const id = deleteButton.dataset.deleteMatch;
        if (!window.confirm("Na pewno usunąć ten mecz?")) return;
        apiFetch("../api/matches_delete.php", { method: "POST", body: { id } })
          .then(loadMatches)
          .catch((error) => window.alert(error.message));
      }
    });

    matchForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      matchFormError.hidden = true;
      const formData = new FormData(matchForm);
      try {
        await apiFetch("../api/matches_save.php", {
          method: "POST",
          body: {
            id: formData.get("id"),
            league: formData.get("league"),
            round: Number(formData.get("round")),
            date: formData.get("date"),
            time: formData.get("time"),
            player1Id: formData.get("player1Id"),
            player2Id: formData.get("player2Id"),
            location: formData.get("location"),
            court: formData.get("court"),
            status: formData.get("status"),
            score: formData.get("score"),
          },
        });
        closeModal(matchModal);
        loadMatches();
      } catch (error) {
        matchFormError.textContent = error.message;
        matchFormError.hidden = false;
      }
    });

    loadMatches();
  }

  // ======================================================================
  // WIADOMOŚCI Z FORMULARZA (registrations.php)
  // ======================================================================
  const registrationsTableBody = document.querySelector("#registrations-table-body");
  if (registrationsTableBody) {
    function formatDateTime(isoString) {
      const date = new Date(isoString);
      if (Number.isNaN(date.getTime())) return isoString;
      return date.toLocaleString("pl-PL", { dateStyle: "medium", timeStyle: "short" });
    }

    async function loadRegistrations() {
      registrationsTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Wczytywanie…</td></tr>`;
      try {
        const { registrations } = await apiFetch("../api/registrations_list.php");
        if (!registrations.length) {
          registrationsTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Brak wiadomości.</td></tr>`;
          return;
        }
        registrationsTableBody.innerHTML = registrations
          .map((entry) => `
            <tr data-id="${escapeHtml(entry.id)}">
              <td>${escapeHtml(formatDateTime(entry.submittedAt))}</td>
              <td>${escapeHtml(entry.name)}</td>
              <td>${escapeHtml(entry.email)}${entry.phone ? "<br />" + escapeHtml(entry.phone) : ""}</td>
              <td>${escapeHtml(entry.message || "—")}</td>
              <td>
                <div class="admin-row-actions">
                  <button type="button" class="admin-icon-button danger" data-delete-registration="${escapeHtml(entry.id)}">Usuń</button>
                </div>
              </td>
            </tr>
          `)
          .join("");
      } catch (error) {
        registrationsTableBody.innerHTML = `<tr><td colspan="5" class="admin-table-empty">Błąd wczytywania: ${escapeHtml(error.message)}</td></tr>`;
      }
    }

    registrationsTableBody.addEventListener("click", (event) => {
      const deleteButton = event.target.closest("[data-delete-registration]");
      if (deleteButton) {
        const id = deleteButton.dataset.deleteRegistration;
        if (!window.confirm("Na pewno usunąć tę wiadomość?")) return;
        apiFetch("../api/registrations_delete.php", { method: "POST", body: { id } })
          .then(loadRegistrations)
          .catch((error) => window.alert(error.message));
      }
    });

    loadRegistrations();
  }

  // ======================================================================
  // DASHBOARD (dashboard.php)
  // ======================================================================
  const dashboardStats = document.querySelector("#dashboard-stats");
  if (dashboardStats) {
    (async () => {
      try {
        const [matchesData, registrationsData] = await Promise.all([
          apiFetch("../api/matches_list.php"),
          apiFetch("../api/registrations_list.php"),
        ]);

        const newRegistrations = registrationsData.registrations.filter((r) => r.status === "new").length;

        dashboardStats.querySelector('[data-stat="activePlayers"]').textContent = matchesData.stats.activePlayers;
        dashboardStats.querySelector('[data-stat="totalMatches"]').textContent = matchesData.stats.totalMatches;
        dashboardStats.querySelector('[data-stat="newRegistrations"]').textContent = newRegistrations;
      } catch (error) {
        dashboardStats.querySelectorAll("strong").forEach((el) => (el.textContent = "—"));
      }
    })();
  }
})();
