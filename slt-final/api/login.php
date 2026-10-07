<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

$body = read_json_body();

// CSRF sprawdzamy już przy logowaniu (ochrona przed tzw. login CSRF —
// wymuszeniem na ofierze zalogowania się na konto atakującego).
require_csrf($body);

$ipKey = client_ip();

if (is_locked_out($ipKey)) {
    $wait = lockout_seconds_remaining($ipKey);
    json_response([
        'success' => false,
        'error' => 'Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za ' . ceil($wait / 60) . ' min.',
    ], 429);
}

$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['success' => false, 'error' => 'Podaj login i hasło.'], 400);
}

$admin = read_json_file(ADMIN_FILE, []);
$storedHash = $admin['password_hash'] ?? '';

if ($storedHash === '') {
    // Konto administratora nie zostało jeszcze skonfigurowane — patrz setup.php.
    json_response(['success' => false, 'error' => 'Panel nie został jeszcze skonfigurowany.'], 403);
}

$validUsername = hash_equals((string)($admin['username'] ?? ''), $username);
$validPassword = password_verify($password, $storedHash);

if (!$validUsername || !$validPassword) {
    register_failed_attempt($ipKey);
    // Celowo ten sam komunikat niezależnie od tego, czy błędny był login czy
    // hasło — nie ujawniamy atakującemu, która część danych była poprawna.
    json_response(['success' => false, 'error' => 'Nieprawidłowy login lub hasło.'], 401);
}

reset_attempts($ipKey);

// Ochrona przed session fixation: nowy identyfikator sesji po zalogowaniu.
session_regenerate_id(true);

$_SESSION['admin_logged_in'] = true;
$_SESSION['username'] = $admin['username'];
$_SESSION['last_activity'] = time();
$_SESSION['fingerprint'] = client_fingerprint();
// Nowy token CSRF po regeneracji sesji.
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

json_response([
    'success' => true,
    'username' => $admin['username'],
    'csrf_token' => $_SESSION['csrf_token'],
]);
