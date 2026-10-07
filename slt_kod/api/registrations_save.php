<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

$body = read_json_body();

// CSRF wymagany również tu — frontend pobiera token z /api/csrf_token.php
// zanim wyśle formularz.
require_csrf($body);

// --- Honeypot ---------------------------------------------------------
// Pole "website" nie istnieje w prawdziwym formularzu widocznym dla
// człowieka (ukryte w CSS/inline style) — jeśli jest wypełnione, to
// najpewniej bot. Odpowiadamy sukcesem, żeby nie zdradzać mechanizmu,
// ale nic nie zapisujemy.
$honeypot = (string)($body['website'] ?? '');
if ($honeypot !== '') {
    json_response(['success' => true]);
}

$ip = client_ip();

if (registration_session_throttled()) {
    json_response(['success' => false, 'error' => 'Wiadomość została już wysłana przed chwilą. Odczekaj chwilę przed ponowną próbą.'], 429);
}

if (is_registration_ip_limited($ip)) {
    json_response(['success' => false, 'error' => 'Zbyt wiele wiadomości z tego miejsca. Spróbuj ponownie później.'], 429);
}

$name = sanitize_text((string)($body['name'] ?? ''), 120);
$email = sanitize_text((string)($body['email'] ?? ''), 254);
$message = sanitize_text((string)($body['message'] ?? ''), 1000);
$consent = !empty($body['consent']);

$errors = [];
if ($name === '') {
    $errors['name'] = 'Podaj imię i nazwisko.';
}
if (!is_valid_email($email)) {
    $errors['email'] = 'Podaj poprawny adres e-mail.';
}
if ($message === '') {
    $errors['message'] = 'Napisz wiadomość.';
}
if (!$consent) {
    $errors['consent'] = 'Zgoda na kontakt jest wymagana.';
}

if ($errors) {
    json_response(['success' => false, 'error' => 'Nieprawidłowe dane.', 'fields' => $errors], 422);
}

$registrations = read_json_file(REGISTRATIONS_FILE, []);

$registrations[] = [
    'id' => generate_id(),
    'name' => $name,
    'email' => $email,
    'message' => $message,
    'submittedAt' => date('c'),
    'status' => 'new',
];

if (!write_json_file(REGISTRATIONS_FILE, $registrations)) {
    json_response(['success' => false, 'error' => 'Nie udało się zapisać wiadomości. Spróbuj ponownie.'], 500);
}

mark_registration_submitted_in_session();
register_registration_attempt($ip);

json_response(['success' => true]);
