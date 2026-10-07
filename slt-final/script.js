document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const currentPage = body.dataset.page;

  document.querySelectorAll("[data-nav]").forEach((link) => {
    if (link.dataset.nav === currentPage) link.classList.add("active");
  });

  document.querySelectorAll("[data-year]").forEach((element) => {
    element.textContent = new Date().getFullYear();
  });

  const menuToggle = document.querySelector(".menu-toggle");
  const primaryNav = document.querySelector(".primary-nav");
  if (menuToggle && primaryNav) {
    menuToggle.addEventListener("click", () => {
      const isOpen = body.classList.toggle("nav-open");
      menuToggle.setAttribute("aria-expanded", String(isOpen));
    });
    primaryNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        body.classList.remove("nav-open");
        menuToggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  const revealItems = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08 }
    );
    revealItems.forEach((item) => revealObserver.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  }

  // --- Liczniki (data-count) --------------------------------------------
  // Wydzielone do funkcji, bo na stronie głównej najpierw chcemy podmienić
  // data-count na wartości pobrane z API, a dopiero potem uruchomić animację.
  function initCounters() {
    const counters = document.querySelectorAll("[data-count]");
    if (!counters.length || !("IntersectionObserver" in window)) return;

    const counterObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const target = Number(entry.target.dataset.count);
          const duration = 900;
          const start = performance.now();
          const update = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            entry.target.textContent = Math.floor(progress * target);
            if (progress < 1) requestAnimationFrame(update);
          };
          requestAnimationFrame(update);
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.6 }
    );
    counters.forEach((counter) => counterObserver.observe(counter));
  }

  // ======================================================================
  // Wspólny helper do publicznych, tylko-do-odczytu endpointów API.
  // ======================================================================
  async function fetchJson(url) {
    const response = await fetch(url, { credentials: "same-origin" });
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error((data && data.error) || "Błąd wczytywania danych.");
    }
    return data;
  }

  const MONTH_ABBR = ["STY", "LUT", "MAR", "KWI", "MAJ", "CZE", "LIP", "SIE", "WRZ", "PAŹ", "LIS", "GRU"];
  const MONTH_GENITIVE = [
    "stycznia", "lutego", "marca", "kwietnia", "maja", "czerwca",
    "lipca", "sierpnia", "września", "października", "listopada", "grudnia",
  ];
  const WEEKDAY_ABBR = ["NDZ", "PON", "WT", "ŚR", "CZW", "PT", "SOB"];
  const WEEKDAY_FULL = ["NIEDZIELA", "PONIEDZIAŁEK", "WTOREK", "ŚRODA", "CZWARTEK", "PIĄTEK", "SOBOTA"];

  function parseMatchDate(dateStr) {
    // "T00:00:00" wymusza lokalną strefę czasową zamiast UTC, żeby dzień
    // tygodnia się nie przesunął.
    return new Date(`${dateStr}T00:00:00`);
  }

  function statusLabel(match) {
    return match.status === "completed"
      ? `<span class="game-status">Zakończony${match.score ? " · " + escapeHtml(match.score) : ""}</span>`
      : `<span class="game-status status-open">Zaplanowany</span>`;
  }

  function escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
  }

  // ======================================================================
  // STRONA GŁÓWNA (index.html): statystyki sezonu + najbliższe mecze
  // ======================================================================
  const seasonStatsSection = document.querySelector("[data-stat]");
  const upcomingMatchesList = document.querySelector("#upcoming-matches");

  if (seasonStatsSection || upcomingMatchesList) {
    fetchJson("api/matches_list.php")
      .then(({ matches, stats }) => {
        if (seasonStatsSection) {
          const playersEl = document.querySelector('[data-stat="activePlayers"]');
          const matchesEl = document.querySelector('[data-stat="totalMatches"]');
          if (playersEl) playersEl.dataset.count = String(stats.activePlayers);
          if (matchesEl) matchesEl.dataset.count = String(stats.totalMatches);
        }

        if (upcomingMatchesList) {
          const today = new Date();
          today.setHours(0, 0, 0, 0);

          const upcoming = matches
            .filter((m) => m.status === "scheduled" && parseMatchDate(m.date) >= today)
            .slice(0, 3);

          const toShow = upcoming.length ? upcoming : matches.slice(0, 3);

          if (!toShow.length) {
            upcomingMatchesList.innerHTML = `<article class="match-row"><div class="match-info"><span class="match-label">Brak zaplanowanych meczów. Wróć wkrótce.</span></div></article>`;
          } else {
            upcomingMatchesList.innerHTML = toShow
              .map((match) => {
                const dateObj = parseMatchDate(match.date);
                const day = String(dateObj.getDate()).padStart(2, "0");
                const monthAbbr = MONTH_ABBR[dateObj.getMonth()];
                const weekdayAbbr = WEEKDAY_ABBR[dateObj.getDay()];
                return `
                  <article class="match-row">
                    <div class="match-date"><strong>${day}</strong><span>${monthAbbr}<br />${weekdayAbbr}</span></div>
                    <div class="match-info">
                      <span class="match-label">Liga ${escapeHtml(match.league)} · kolejka ${String(match.round).padStart(2, "0")}</span>
                      <h3>${escapeHtml(match.player1Name)} <i>vs</i> ${escapeHtml(match.player2Name)}</h3>
                      <span class="match-place">${escapeHtml(match.location)}${match.court ? " · " + escapeHtml(match.court) : ""}</span>
                    </div>
                    <span class="match-time">${escapeHtml(match.time)}</span>
                    <a class="round-arrow" href="kalendarz.html" aria-label="Zobacz szczegóły meczu">↗</a>
                  </article>
                `;
              })
              .join("");
          }
        }
      })
      .catch(() => {
        if (upcomingMatchesList) {
          upcomingMatchesList.innerHTML = `<article class="match-row"><div class="match-info"><span class="match-label">Nie udało się wczytać terminarza. Spróbuj odświeżyć stronę.</span></div></article>`;
        }
      })
      .finally(initCounters);
  } else {
    initCounters();
  }

  // ======================================================================
  // KALENDARZ (kalendarz.html): pełny terminarz, filtry, podsumowanie
  // ======================================================================
  const scheduleContainer = document.querySelector("#schedule");
  if (scheduleContainer) {
    const filterButtons = document.querySelectorAll(".filter-tab");
    const monthFilter = document.querySelector("#month-filter");
    const emptyState = document.querySelector("#empty-state");
    let allMatches = [];
    let selectedLeague = "all";
    let selectedMonth = "all";

    function buildMonthOptions(matches) {
      const seen = new Map(); // monthKey -> label, w kolejności pierwszego wystąpienia
      matches.forEach((match) => {
        if (!match.month || seen.has(match.month)) return;
        const dateObj = parseMatchDate(match.date);
        const label = `${capitalize(MONTH_GENITIVE[dateObj.getMonth()])} ${dateObj.getFullYear()}`;
        seen.set(match.month, label);
      });

      monthFilter.innerHTML =
        `<option value="all">Cały sezon</option>` +
        Array.from(seen.entries()).map(([key, label]) => `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`).join("");
    }

    function capitalize(word) {
      return word.charAt(0).toUpperCase() + word.slice(1);
    }

    function renderSchedule() {
      const filtered = allMatches.filter((match) => {
        const monthMatches = selectedMonth === "all" || match.month === selectedMonth;
        const leagueMatches = selectedLeague === "all" || match.league === selectedLeague;
        return monthMatches && leagueMatches;
      });

      if (!filtered.length) {
        scheduleContainer.innerHTML = "";
        if (emptyState) emptyState.hidden = false;
        updateSummary(filtered);
        return;
      }
      if (emptyState) emptyState.hidden = true;

      // Grupowanie po dacie — jedna karta dnia może zawierać kilka meczów.
      const groups = [];
      const groupsByDate = new Map();
      filtered.forEach((match) => {
        if (!groupsByDate.has(match.date)) {
          const group = { date: match.date, matches: [] };
          groupsByDate.set(match.date, group);
          groups.push(group);
        }
        groupsByDate.get(match.date).matches.push(match);
      });

      scheduleContainer.innerHTML = groups
        .map((group) => {
          const dateObj = parseMatchDate(group.date);
          const day = String(dateObj.getDate()).padStart(2, "0");
          const monthFull = MONTH_GENITIVE[dateObj.getMonth()].toUpperCase();
          const weekdayFull = WEEKDAY_FULL[dateObj.getDay()];

          const gameRows = group.matches
            .map(
              (match) => `
                <div class="game-row" data-league="${escapeHtml(match.league)}">
                  ${statusLabel(match)}
                  <div>
                    <small>Liga ${escapeHtml(match.league)} · kolejka ${String(match.round).padStart(2, "0")}</small>
                    <h3>${escapeHtml(match.player1Name)} <i>vs</i> ${escapeHtml(match.player2Name)}</h3>
                    <p>${escapeHtml(match.location)} ${match.court ? `<b>·</b> ${escapeHtml(match.court)}` : ""}</p>
                  </div>
                  <time>${escapeHtml(match.time)}</time>
                </div>
              `
            )
            .join("");

          return `
            <article class="schedule-group" data-month="${escapeHtml(group.matches[0].month)}">
              <div class="schedule-date"><strong>${day}</strong><span>${monthFull}<br />${weekdayFull}</span></div>
              <div class="schedule-games">${gameRows}</div>
            </article>
          `;
        })
        .join("");

      updateSummary(filtered);
    }

    function updateSummary(filtered) {
      const totalEl = document.querySelector("#summary-total");
      const leaguesEl = document.querySelector("#summary-leagues");
      const locationsEl = document.querySelector("#summary-locations");

      if (totalEl) totalEl.textContent = String(filtered.length);
      if (leaguesEl) leaguesEl.textContent = String(new Set(filtered.map((m) => m.league)).size);
      if (locationsEl) locationsEl.textContent = String(new Set(filtered.map((m) => m.location)).size);
    }

    filterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        filterButtons.forEach((item) => item.classList.remove("active"));
        button.classList.add("active");
        selectedLeague = button.dataset.filter;
        renderSchedule();
      });
    });

    monthFilter?.addEventListener("change", (event) => {
      selectedMonth = event.target.value;
      renderSchedule();
    });

    fetchJson("api/matches_list.php")
      .then(({ matches }) => {
        allMatches = matches;
        buildMonthOptions(matches);
        renderSchedule();
      })
      .catch((error) => {
        scheduleContainer.innerHTML = `<p class="empty-state"><span class="empty-mark">—</span><br />Nie udało się wczytać terminarza (${escapeHtml(error.message)}). Spróbuj odświeżyć stronę.</p>`;
      });
  }

  // ======================================================================
  // FORMULARZ KONTAKTOWY (kontakt.html)
  // ======================================================================
  const registrationForm = document.querySelector("#registration-form");
  const formSuccess = document.querySelector("#form-success");
  const formServerError = document.querySelector("#form-server-error");

  if (registrationForm) {
    let csrfToken = "";
    fetchJson("api/csrf_token.php")
      .then((data) => {
        csrfToken = data.csrf_token;
      })
      .catch(() => {
        // Jeśli nie uda się pobrać tokenu, formularz i tak spróbuje wysłać
        // zgłoszenie przy submicie — API po prostu odrzuci je z czytelnym
        // błędem, który pokażemy w formServerError.
      });

    registrationForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      if (formServerError) formServerError.hidden = true;

      const requiredFields = registrationForm.querySelectorAll("[required]");
      let isValid = true;
      requiredFields.forEach((field) => {
        const valid = field.type === "radio"
          ? registrationForm.querySelector(`input[name="${field.name}"]:checked`)
          : field.type === "checkbox"
            ? field.checked
            : field.value.trim() && field.checkValidity();
        if (field.type !== "radio" && !field.checkValidity()) {
          field.closest(".field")?.classList.add("has-error");
        } else if (!valid) {
          field.closest(".field")?.classList.add("has-error");
          if (field.type === "checkbox") field.closest(".check-field")?.classList.add("has-error");
        } else {
          field.closest(".field")?.classList.remove("has-error");
          field.closest(".check-field")?.classList.remove("has-error");
        }
        if (!valid) isValid = false;
      });

      if (!isValid) {
        registrationForm.querySelector(".has-error")?.scrollIntoView({ behavior: "smooth", block: "center" });
        return;
      }

      const formData = new FormData(registrationForm);
      const submitButton = registrationForm.querySelector(".submit-button");
      submitButton.disabled = true;

      try {
        const response = await fetch("api/registrations_save.php", {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: formData.get("name"),
            email: formData.get("email"),
            message: formData.get("message"),
            consent: registrationForm.querySelector('[name="consent"]').checked,
            website: formData.get("website"), // honeypot — u człowieka zawsze puste
            csrf_token: csrfToken,
          }),
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
          throw new Error(data.error || "Nie udało się wysłać wiadomości. Spróbuj ponownie.");
        }

        registrationForm.closest(".form-card").classList.add("submitted");
        registrationForm.hidden = true;
        if (formSuccess) formSuccess.hidden = false;
      } catch (error) {
        if (formServerError) {
          formServerError.textContent = error.message;
          formServerError.hidden = false;
          formServerError.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      } finally {
        submitButton.disabled = false;
      }
    });

    registrationForm.querySelectorAll("input, textarea").forEach((field) => {
      field.addEventListener("input", () => field.closest(".field")?.classList.remove("has-error"));
      field.addEventListener("change", () => field.closest(".field")?.classList.remove("has-error"));
    });
  }
});
