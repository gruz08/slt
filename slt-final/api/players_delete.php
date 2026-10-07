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

$id = sanitize_text((string)($body['id'] ?? ''), 64);
if ($id === '') {
    json_response(['success' => false, 'error' => 'Brak identyfikatora zawodnika.'], 400);
}

$players = read_json_file(PLAYERS_FILE, []);
$matches = read_json_file(MATCHES_FILE, []);

// Ochrona integralności danych: nie pozwalamy usunąć zawodnika, który
// występuje w jakimkolwiek meczu — inaczej terminarz miałby "osierocone"
// odwołania do nieistniejącego zawodnika.
foreach ($matches as $match) {
    if (($match['player1Id'] ?? '') === $id || ($match['player2Id'] ?? '') === $id) {
        json_response([
            'success' => false,
            'error' => 'Nie można usunąć zawodnika, który występuje w zaplanowanych meczach. Usuń lub zmień najpierw te mecze.',
        ], 409);
    }
}

$index = null;
foreach ($players as $i => $player) {
    if (($player['id'] ?? '') === $id) {
        $index = $i;
        break;
    }
}

if ($index === null) {
    json_response(['success' => false, 'error' => 'Nie znaleziono zawodnika o podanym id.'], 404);
}

array_splice($players, $index, 1);

if (!write_json_file(PLAYERS_FILE, $players)) {
    json_response(['success' => false, 'error' => 'Nie udało się usunąć zawodnika.'], 500);
}

json_response(['success' => true]);
