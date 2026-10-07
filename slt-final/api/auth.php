<?php
/**
 * auth.php
 * Funkcje pomocnicze do: weryfikacji sesji admina, tokenów CSRF,
 * ochrony przed atakami brute-force na logowanie oraz throttlingu
 * publicznego formularza zgłoszeniowego.
 *
 * Zawsze dołączać PO config.php.
 */

declare(strict_types=1);

function client_fingerprint(): string
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return hash('sha256', $userAgent);
}

function session_is_valid(): bool
{
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }

    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if (time() - $lastActivity > SESSION_TIMEOUT_SECONDS) {
        destroy_session();
        return false;
    }

    if (($_SESSION['fingerprint'] ?? '') !== client_fingerprint()) {
        destroy_session();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function destroy_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (!session_is_valid()) {
        json_response(['success' => false, 'error' => 'Wymagane zalogowanie.'], 401);
    }
}

function issue_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function incoming_csrf_token(array $jsonBody = []): ?string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (is_string($header) && $header !== '') {
        return $header;
    }
    if (isset($jsonBody['csrf_token']) && is_string($jsonBody['csrf_token'])) {
        return $jsonBody['csrf_token'];
    }
    return null;
}

function require_csrf(array $jsonBody = []): void
{
    $expected = $_SESSION['csrf_token'] ?? null;
    $provided = incoming_csrf_token($jsonBody);

    if (!$expected || !$provided || !hash_equals($expected, $provided)) {
        json_response(['success' => false, 'error' => 'Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.'], 403);
    }
}

// --- Ochrona brute-force (logowanie, setup) ---------------------------------

function is_locked_out(string $key): bool
{
    $attempts = read_json_file(LOGIN_ATTEMPTS_FILE, []);
    $entry = $attempts[$key] ?? null;

    if (!$entry) {
        return false;
    }

    if (($entry['locked_until'] ?? 0) > time()) {
        return true;
    }

    return false;
}

function lockout_seconds_remaining(string $key): int
{
    $attempts = read_json_file(LOGIN_ATTEMPTS_FILE, []);
    $entry = $attempts[$key] ?? null;
    if (!$entry) {
        return 0;
    }
    $remaining = ($entry['locked_until'] ?? 0) - time();
    return $remaining > 0 ? $remaining : 0;
}

function register_failed_attempt(string $key): void
{
    $attempts = read_json_file(LOGIN_ATTEMPTS_FILE, []);
    $entry = $attempts[$key] ?? ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];

    if (time() - ($entry['first_attempt'] ?? time()) > LOGIN_LOCKOUT_SECONDS * 4) {
        $entry = ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];
    }

    $entry['count'] = (int)$entry['count'] + 1;

    if ($entry['count'] >= MAX_LOGIN_ATTEMPTS) {
        $entry['locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
    }

    $attempts[$key] = $entry;
    write_json_file(LOGIN_ATTEMPTS_FILE, $attempts);
}

function reset_attempts(string $key): void
{
    $attempts = read_json_file(LOGIN_ATTEMPTS_FILE, []);
    if (isset($attempts[$key])) {
        unset($attempts[$key]);
        write_json_file(LOGIN_ATTEMPTS_FILE, $attempts);
    }
}

// --- Throttling formularza zgłoszeniowego (publiczny endpoint) -------------

/**
 * Sprawdza, czy bieżąca sesja przeglądarki wysłała już zgłoszenie w ciągu
 * ostatnich REGISTRATION_MIN_INTERVAL_SECONDS sekund (proste zabezpieczenie
 * przed przypadkowym/masowym klikaniem "Wyślij" oraz najprostszymi botami).
 */
function registration_session_throttled(): bool
{
    $last = $_SESSION['last_registration_at'] ?? 0;
    return (time() - $last) < REGISTRATION_MIN_INTERVAL_SECONDS;
}

function mark_registration_submitted_in_session(): void
{
    $_SESSION['last_registration_at'] = time();
}

/**
 * Twardy limit liczby zgłoszeń z jednego adresu IP w oknie czasowym,
 * niezależny od ciasteczek/sesji (chroni przed prostym zalewaniem
 * formularza z jednego źródła, np. skryptem).
 */
function is_registration_ip_limited(string $ip): bool
{
    $data = read_json_file(REGISTRATION_ATTEMPTS_FILE, []);
    $entry = $data[$ip] ?? null;

    if (!$entry) {
        return false;
    }

    if (time() - ($entry['window_start'] ?? 0) > REGISTRATION_WINDOW_SECONDS) {
        return false; // okno czasowe minęło, limit się zresetuje przy zapisie
    }

    return (int)($entry['count'] ?? 0) >= MAX_REGISTRATIONS_PER_WINDOW;
}

function register_registration_attempt(string $ip): void
{
    $data = read_json_file(REGISTRATION_ATTEMPTS_FILE, []);
    $entry = $data[$ip] ?? ['count' => 0, 'window_start' => time()];

    if (time() - ($entry['window_start'] ?? 0) > REGISTRATION_WINDOW_SECONDS) {
        $entry = ['count' => 0, 'window_start' => time()];
    }

    $entry['count'] = (int)$entry['count'] + 1;
    $data[$ip] = $entry;
    write_json_file(REGISTRATION_ATTEMPTS_FILE, $data);
}
