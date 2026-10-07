<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

// Osobna przestrzeń kluczy niż logowanie ("setup:" + IP), żeby nieudane
// próby odgadnięcia SETUP_SECRET nie blokowały (ani nie mieszały się z)
// licznika prób logowania do panelu.
$throttleKey = 'setup:' . client_ip();

if (is_locked_out($throttleKey)) {
    $wait = lockout_seconds_remaining($throttleKey);
    json_response([
        'success' => false,
        'error' => 'Zbyt wiele nieudanych prób. Spróbuj ponownie za ' . ceil($wait / 60) . ' min.',
    ], 429);
}

$admin = read_json_file(ADMIN_FILE, ['username' => 'admin', 'password_hash' => '']);

// Jeśli hasło jest już ustawione, ten endpoint jest trwale nieaktywny —
// nawet ze znajomością SETUP_SECRET nie da się nadpisać istniejącego hasła
// z tego pliku (do zmiany hasła służy panel admina).
if (!empty($admin['password_hash'])) {
    json_response(['success' => false, 'error' => 'Konto administratora zostało już skonfigurowane. Usuń plik setup.php.'], 403);
}

$body = read_json_body();

$providedSecret = (string)($body['setup_secret'] ?? '');
if (!hash_equals(SETUP_SECRET, $providedSecret)) {
    register_failed_attempt($throttleKey);
    // Ta sama odpowiedź niezależnie od przyczyny — nie ujawniamy, czy sekret
    // był bliski poprawnemu.
    json_response(['success' => false, 'error' => 'Brak dostępu.'], 403);
}

$username = trim((string)($body['username'] ?? 'admin'));
$password = (string)($body['password'] ?? '');
$passwordConfirm = (string)($body['password_confirm'] ?? '');

if ($username === '') {
    json_response(['success' => false, 'error' => 'Podaj nazwę użytkownika.'], 400);
}

if (strlen($password) < 10) {
    json_response(['success' => false, 'error' => 'Hasło musi mieć co najmniej 10 znaków.'], 400);
}

if ($password !== $passwordConfirm) {
    json_response(['success' => false, 'error' => 'Hasła nie są identyczne.'], 400);
}

$admin['username'] = $username;
$admin['password_hash'] = password_hash($password, PASSWORD_DEFAULT);

if (!write_json_file(ADMIN_FILE, $admin)) {
    json_response(['success' => false, 'error' => 'Nie udało się zapisać danych administratora.'], 500);
}

reset_attempts($throttleKey);

json_response([
    'success' => true,
    'message' => 'Hasło administratora zostało ustawione. USUŃ TERAZ plik /api/setup.php z serwera.',
]);
