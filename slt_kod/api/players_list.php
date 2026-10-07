<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

$players = read_json_file(PLAYERS_FILE, []);

// Sortowanie alfabetyczne po nazwisku/nazwie — wygodne do wyświetlenia.
usort($players, static function (array $a, array $b): int {
    return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
});

json_response(['success' => true, 'players' => array_values($players)]);
