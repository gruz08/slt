<?php
/**
 * config.php
 * Centralna konfiguracja backendu Śląskiej Ligi Tenisa.
 */

declare(strict_types=1);

// --- Środowisko / błędy -------------------------------------------------
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../data/php-error.log');

date_default_timezone_set('Europe/Warsaw');

// --- Ścieżki do danych ---------------------------------------------------
define('DATA_DIR', rtrim(realpath(__DIR__ . '/../data') ?: __DIR__ . '/../data', '/'));
define('PLAYERS_FILE', DATA_DIR . '/players.json');
define('MATCHES_FILE', DATA_DIR . '/matches.json');
define('REGISTRATIONS_FILE', DATA_DIR . '/registrations.json');
define('ADMIN_FILE', DATA_DIR . '/admin.json');
define('LOGIN_ATTEMPTS_FILE', DATA_DIR . '/login_attempts.json');
define('REGISTRATION_ATTEMPTS_FILE', DATA_DIR . '/registration_attempts.json');

// --- Bezpieczeństwo logowania ---------------------------------------------
define('SESSION_TIMEOUT_SECONDS', 1800); // 30 minut

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900); // 15 minut

// --- Ochrona formularza zgłoszeniowego przed spamem -----------------------
define('REGISTRATION_MIN_INTERVAL_SECONDS', 45);
define('MAX_REGISTRATIONS_PER_WINDOW', 8);
define('REGISTRATION_WINDOW_SECONDS', 3600); // 1 godzina

// --- Ochrona przed zbyt dużymi żądaniami (DoS) -----------------------------
// Twardy limit rozmiaru ciała żądania JSON. 200 KB to duży zapas ponad
// realne potrzeby (największe pole to wiadomość ze zgłoszenia, max 1000
// znaków), a jednocześnie chroni przed prostym zalewaniem endpointów
// ogromnymi żądaniami.
define('MAX_REQUEST_BODY_BYTES', 200 * 1024);

// Sekret jednorazowego ustawienia hasła admina (patrz setup.php).
// WAŻNE: PRZED WGRANIEM NA SERWER ZMIEŃ TĘ WARTOŚĆ NA WŁASNĄ, LOSOWĄ.
define('SETUP_SECRET', 'K7mQ2xR9vL4pT8zN6wY3cH1sF5dB');

// --- Sesje ----------------------------------------------------------------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_name('slt_admin_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Wspólne nagłówki bezpieczeństwa dla API ------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// --- Bezpieczny odczyt/zapis JSON -----------------------------------------

function read_json_file(string $path, array $default = []): array
{
    if (!is_file($path)) {
        return $default;
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return $default;
    }

    $data = $default;
    if (flock($handle, LOCK_SH)) {
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        if ($contents !== false && trim($contents) !== '') {
            $decoded = json_decode($contents, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }
    fclose($handle);

    return $data;
}

function write_json_file(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        return false;
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    $lockHandle = fopen($path, 'c+b');
    if ($lockHandle === false) {
        return false;
    }

    $success = false;
    if (flock($lockHandle, LOCK_EX)) {
        $tmpPath = $path . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($tmpPath, $json) !== false) {
            $success = rename($tmpPath, $path);
        }
        if (!$success && is_file($tmpPath)) {
            @unlink($tmpPath);
        }
        flock($lockHandle, LOCK_UN);
    }
    fclose($lockHandle);

    return $success;
}

function generate_id(): string
{
    return bin2hex(random_bytes(8));
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Odczytuje i dekoduje JSON z ciała żądania, z twardym limitem rozmiaru
 * (MAX_REQUEST_BODY_BYTES). Jeśli ciało przekracza limit, od razu kończy
 * żądanie odpowiedzią 413 — endpoint wywołujący tę funkcję nie musi o tym
 * pamiętać.
 */
function read_json_body(): array
{
    $stream = fopen('php://input', 'rb');
    if ($stream === false) {
        return [];
    }

    // Czytamy o jeden bajt więcej niż limit, żeby wykryć przekroczenie
    // bez wczytywania całego (potencjalnie ogromnego) ciała do pamięci.
    $raw = stream_get_contents($stream, MAX_REQUEST_BODY_BYTES + 1);
    fclose($stream);

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    if (strlen($raw) > MAX_REQUEST_BODY_BYTES) {
        json_response(['success' => false, 'error' => 'Żądanie jest zbyt duże.'], 413);
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
