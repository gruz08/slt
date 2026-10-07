<?php
declare(strict_types=1);

/**
 * guard.php
 * Dołączany na początku KAŻDEJ strony panelu (poza index.php — ekranem
 * logowania). Jeśli sesja admina jest nieważna, przekierowuje na ekran
 * logowania i przerywa dalsze wykonanie strony.
 */

require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../api/auth.php';

if (!session_is_valid()) {
    header('Location: index.php');
    exit;
}

$csrfToken = issue_csrf_token();
$adminUsername = (string)($_SESSION['username'] ?? 'Administrator');
