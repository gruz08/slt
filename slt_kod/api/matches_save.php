<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

require_login();
$body = read_json_body();
require_csrf($body);

$id = isset($body['id']) ? sanitize_text((string)$body['id'], 64) : '';
$league = sanitize_text((string)($body['league'] ?? ''), 5);
$round = isset($body['round']) ? (int)$body['round'] : 0;
$date = sanitize_text((string)($body['date'] ?? ''), 10);
$time = sanitize_text((string)($body['time'] ?? ''), 5);
$player1Id = sanitize_text((string)($body['player1Id'] ?? ''), 64);
$player2Id = sanitize_text((string)($body['player2Id'] ?? ''), 64);
$location = sanitize_text((string)($body['location'] ?? ''), 120);
$court = sanitize_text((string)($body['court'] ?? ''), 60);
$status = sanitize_text((string)($body['status'] ?? 'scheduled'), 20);
$score = $body['score'] ?? null;
$score = $score === null ? null : sanitize_text((string)$score, 60);

$errors = [];

if (!is_one_of($league, ['A', 'B'])) {
    $errors['league'] = 'Liga musi mieć wartość A lub B.';
}
if ($round < 1 || $round > 100) {
    $errors['round'] = 'Nieprawidłowy numer kolejki.';
}
if (!is_valid_date($date)) {
    $errors['date'] = 'Nieprawidłowa data (oczekiwany format RRRR-MM-DD).';
}
if (!is_valid_time($time)) {
    $errors['time'] = 'Nieprawidłowa godzina (oczekiwany format GG:MM).';
}
if ($location === '') {
    $errors['location'] = 'Podaj lokalizację.';
}
if (!is_one_of($status, ['scheduled', 'completed'])) {
    $errors['status'] = 'Nieprawidłowy status meczu.';
}
if ($player1Id === '' || $player2Id === '') {
    $errors['players'] = 'Wybierz obu zawodników.';
} elseif ($player1Id === $player2Id) {
    $errors['players'] = 'Zawodnik nie może grać sam ze sobą.';
}

// Sprawdzenie, że wskazani zawodnicy istnieją w bazie.
$players = read_json_file(PLAYERS_FILE, []);
$playerIds = array_column($players, 'id');
if ($player1Id !== '' && !in_array($player1Id, $playerIds, true)) {
    $errors['player1Id'] = 'Wybrany zawodnik (1) nie istnieje.';
}
if ($player2Id !== '' && !in_array($player2Id, $playerIds, true)) {
    $errors['player2Id'] = 'Wybrany zawodnik (2) nie istnieje.';
}

if ($errors) {
    json_response(['success' => false, 'error' => 'Nieprawidłowe dane.', 'fields' => $errors], 422);
}

$month = month_key_from_date($date);

$matchData = [
    'league' => $league,
    'round' => $round,
    'date' => $date,
    'month' => $month,
    'time' => $time,
    'player1Id' => $player1Id,
    'player2Id' => $player2Id,
    'location' => $location,
    'court' => $court,
    'status' => $status,
    'score' => $status === 'completed' ? $score : null,
];

$matches = read_json_file(MATCHES_FILE, []);

if ($id !== '') {
    $found = false;
    foreach ($matches as $index => $match) {
        if (($match['id'] ?? '') === $id) {
            $matches[$index] = array_merge(['id' => $id], $matchData);
            $found = true;
            break;
        }
    }
    if (!$found) {
        json_response(['success' => false, 'error' => 'Nie znaleziono meczu o podanym id.'], 404);
    }
} else {
    $id = generate_id();
    $matches[] = array_merge(['id' => $id], $matchData);
}

if (!write_json_file(MATCHES_FILE, $matches)) {
    json_response(['success' => false, 'error' => 'Nie udało się zapisać meczu.'], 500);
}

json_response(['success' => true, 'match' => array_merge(['id' => $id], $matchData)]);
