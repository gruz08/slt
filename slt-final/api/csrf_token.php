<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'error' => 'Niedozwolona metoda.'], 405);
}

$token = issue_csrf_token();

json_response([
    'success' => true,
    'csrf_token' => $token,
    'logged_in' => session_is_valid(),
]);
