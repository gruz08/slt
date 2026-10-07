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

$allowedLevels = ['', 'Początkujący', 'Średniozaawansowany', 'Zaawansowany'];

$id = isset($body['id']) ? sanitize_text((string)$body['id'], 64) : '';
$name = sanitize_text((string)($body['name'] ?? ''), 120);
$level = sanitize_text((string)($body['level'] ?? ''), 40);
$city = sanitize_text((string)($body['city'] ?? ''), 80);
$active = !empty($body['active']);

$errors = [];
if ($name === '') {
    $errors['name'] = 'Imię i nazwisko jest wymagane.';
}
if (!is_one_of($level, $allowedLevels)) {
    $errors['level'] = 'Nieprawidłowy poziom gry.';
}

if ($errors) {
    json_response(['success' => false, 'error' => 'Nieprawidłowe dane.', 'fields' => $errors], 422);
}

$players = read_json_file(PLAYERS_FILE, []);

if ($id !== '') {
    // Edycja istniejącego zawodnika.
    $found = false;
    foreach ($players as $index => $player) {
        if (($player['id'] ?? '') === $id) {
            $players[$index] = [
                'id' => $id,
                'name' => $name,
                'level' => $level,
                'city' => $city,
                'active' => $active,
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        json_response(['success' => false, 'error' => 'Nie znaleziono zawodnika o podanym id.'], 404);
    }
} else {
    // Nowy zawodnik.
    $id = generate_id();
    $players[] = [
        'id' => $id,
        'name' => $name,
        'level' => $level,
        'city' => $city,
        'active' => $active,
    ];
}

if (!write_json_file(PLAYERS_FILE, $players)) {
    json_response(['success' => false, 'error' => 'Nie udało się zapisać danych zawodnika.'], 500);
}

json_response(['success' => true, 'player' => ['id' => $id, 'name' => $name, 'level' => $level, 'city' => $city, 'active' => $active]]);
