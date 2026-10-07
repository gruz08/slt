<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

$matches = read_json_file(MATCHES_FILE, []);
$players = read_json_file(PLAYERS_FILE, []);

$playersById = [];
foreach ($players as $player) {
    if (isset($player['id'])) {
        $playersById[$player['id']] = $player['name'] ?? '';
    }
}

$enriched = [];
foreach ($matches as $match) {
    $match['player1Name'] = $playersById[$match['player1Id'] ?? ''] ?? '—';
    $match['player2Name'] = $playersById[$match['player2Id'] ?? ''] ?? '—';
    $enriched[] = $match;
}

// Sortowanie chronologiczne po dacie i godzinie.
usort($enriched, static function (array $a, array $b): int {
    $dateCompare = strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
    if ($dateCompare !== 0) {
        return $dateCompare;
    }
    return strcmp((string)($a['time'] ?? ''), (string)($b['time'] ?? ''));
});

$activePlayers = array_filter($players, static fn(array $p) => !empty($p['active']));

json_response([
    'success' => true,
    'matches' => array_values($enriched),
    'stats' => [
        'activePlayers' => count($activePlayers),
        'totalMatches' => count($enriched),
    ],
]);
