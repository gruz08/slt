/* Wynik zapisany w kolejności player1Id, player2Id; mecz do dwóch setów. */
function parseStandingScore(score) {
  if (typeof score !== "string") return null;
  const parts = score.trim().split(/\s*[,;]\s*|\s+/);
  if (parts.length < 2 || parts.length > 3) return null;
  const sets = [];
  let wins1 = 0, wins2 = 0;
  for (const part of parts) {
    const match = /^(\d{1,2})[:-](\d{1,2})(?:\(\d{1,2}\))?$/.exec(part);
    if (!match || wins1 === 2 || wins2 === 2) return null;
    const a = Number(match[1]), b = Number(match[2]);
    const high = Math.max(a, b), low = Math.min(a, b);
    if (!((high === 6 && low <= 4) || (high === 7 && (low === 5 || low === 6)))) return null;
    if (a > b) wins1++; else wins2++;
    sets.push([a, b]);
  }
  if (wins1 !== 2 && wins2 !== 2) return null;
  return { sets, wins1, wins2 };
}

function calculateStandings(players, matches, league = "all") {
  const selected = matches.filter(match => league === "all" || match.league === league);
  const participants = new Set(selected.flatMap(match => [match.player1Id, match.player2Id]));
  const rows = new Map(players.filter(player => participants.has(player.id) || (league === "all" && player.active)).map(player => [player.id, {
    id: player.id, name: player.name, city: player.city, points: 0, played: 0, won: 0, lost: 0,
    setsFor: 0, setsAgainst: 0, gamesFor: 0, gamesAgainst: 0,
  }]));
  let counted = 0, omitted = 0;
  for (const match of selected) {
    if (match.status !== "completed") continue;
    const score = parseStandingScore(match.score);
    const first = rows.get(match.player1Id), second = rows.get(match.player2Id);
    if (!score || !first || !second || first === second) { omitted++; continue; }
    counted++;
    first.played++; second.played++;
    const winner = score.wins1 > score.wins2 ? first : second;
    const loser = winner === first ? second : first;
    winner.won++; winner.points += 3; loser.lost++;
    first.setsFor += score.wins1; first.setsAgainst += score.wins2;
    second.setsFor += score.wins2; second.setsAgainst += score.wins1;
    for (const [a, b] of score.sets) {
      first.gamesFor += a; first.gamesAgainst += b;
      second.gamesFor += b; second.gamesAgainst += a;
    }
  }
  const compareStats = (a, b) => b.points - a.points || b.won - a.won ||
    (b.setsFor - b.setsAgainst) - (a.setsFor - a.setsAgainst) ||
    (b.gamesFor - b.gamesAgainst) - (a.gamesFor - a.gamesAgainst);
  const ranking = [...rows.values()].sort((a, b) => compareStats(a, b) || a.name.localeCompare(b.name, "pl"));
  ranking.forEach((row, i) => { row.place = i > 0 && compareStats(row, ranking[i - 1]) === 0 ? ranking[i - 1].place : i + 1; });
  return { ranking, counted, omitted };
}

if (typeof module !== "undefined") module.exports = { parseStandingScore, calculateStandings };
if (typeof document !== "undefined") document.addEventListener("DOMContentLoaded", async () => {
  const tbody = document.querySelector("#standings-body");
  if (!tbody) return;
  const status = document.querySelector("#standings-status");
  const buttons = [...document.querySelectorAll("[data-standing-filter]")];
  buttons.forEach(button => { button.disabled = true; });
  try {
    const payloads = await Promise.all(["players", "matches"].map(async key => {
      const response = await fetch(`api/${key}_list.php`, { credentials: "same-origin" });
      if (!response.ok) throw new Error("HTTP error");
      const data = await response.json();
      if (!data.success || !Array.isArray(data[key])) throw new Error("Invalid data");
      return data[key];
    }));
    function render(league) {
      const { ranking, counted, omitted } = calculateStandings(...payloads, league);
      tbody.replaceChildren();
      for (const row of ranking) {
        const tr = document.createElement("tr");
        const values = [row.place, row.name, row.points, row.played, row.won, row.lost,
          row.played ? `${Math.round(row.won / row.played * 100)}%` : "—",
          `${row.setsFor}:${row.setsAgainst}`, `${row.gamesFor}:${row.gamesAgainst}`];
        values.forEach((value, i) => {
          const cell = document.createElement(i === 1 ? "th" : "td");
          if (i === 1) cell.scope = "row";
          cell.textContent = String(value); tr.append(cell);
        });
        tbody.append(tr);
      }
      status.textContent = ranking.length ? `Zawodnicy: ${ranking.length} · Rozegrane mecze: ${counted}.` : "Brak zawodników przypisanych do tej ligi.";
      if (ranking.length && counted === 0) status.textContent += " Pierwsze wyniki pojawią się po zakończeniu meczów.";
      if (omitted) status.textContent += ` Pominięte wyniki wymagające uzupełnienia: ${omitted}.`;
      buttons.forEach(button => {
        const active = button.dataset.standingFilter === league;
        button.classList.toggle("active", active); button.setAttribute("aria-pressed", String(active));
      });
    }
    buttons.forEach(button => { button.disabled = false; button.addEventListener("click", () => render(button.dataset.standingFilter)); });
    render("all");
  } catch {
    status.textContent = "Nie udało się wczytać tabeli. Odśwież stronę, aby spróbować ponownie.";
  }
});
