<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method Not Allowed');
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') === false) {
    json_error(415, 'Content-Type must be application/json');
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    json_error(400, 'Invalid JSON');
}

$identifier = trim((string)($data['username_or_email'] ?? ''));
$password   = (string)($data['password'] ?? '');

if ($identifier === '' || $password === '') {
    json_error(400, 'username_or_email and password are required');
}

try {
    // two DISTINCT placeholders
    $sql = "SELECT id, password
            FROM users
            WHERE username = :username OR email = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $identifier,
        ':email'    => $identifier,
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        json_error(401, 'Invalid credentials');
    }

    if (!password_verify($password, $user['password'])) {
        json_error(401, 'Invalid credentials');
    }

    start_session_if_needed();
    $_SESSION['user_id'] = (int)$user['id'];

    json_ok([
        'ok'      => true,
        'user_id' => (int)$user['id'],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => 'Login failed',
        'details' => $e->getMessage(),
    ]);
    exit;
}
