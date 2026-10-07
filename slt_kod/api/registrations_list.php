<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

require_login();

$registrations = read_json_file(REGISTRATIONS_FILE, []);

usort($registrations, static function (array $a, array $b): int {
    return strcmp((string)($b['submittedAt'] ?? ''), (string)($a['submittedAt'] ?? ''));
});

json_response(['success' => true, 'registrations' => array_values($registrations)]);
