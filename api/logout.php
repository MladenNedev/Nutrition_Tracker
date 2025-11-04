<?php
declare(strict_types=1);
require __DIR__.'/auth_check.php';

start_session_if_needed();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
}
session_destroy();

header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
