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
    json_response(['success' => false, 'error' => 'Brak identyfikatora meczu.'], 400);
}

$matches = read_json_file(MATCHES_FILE, []);

$index = null;
foreach ($matches as $i => $match) {
    if (($match['id'] ?? '') === $id) {
        $index = $i;
        break;
    }
}

if ($index === null) {
    json_response(['success' => false, 'error' => 'Nie znaleziono meczu o podanym id.'], 404);
}

array_splice($matches, $index, 1);

if (!write_json_file(MATCHES_FILE, $matches)) {
    json_response(['success' => false, 'error' => 'Nie udało się usunąć meczu.'], 500);
}

json_response(['success' => true]);
