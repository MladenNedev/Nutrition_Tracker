<?php
declare(strict_types=1);
require __DIR__.'/db.php';
require __DIR__.'/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error(405, 'Method Not Allowed');
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) json_error(415, 'Content-Type must be application/json');

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) json_error(400, 'Invalid JSON');

$username = trim((string)($in['username'] ?? ''));
$email    = trim((string)($in['email'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') json_error(400, 'username and password are required');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_error(400, 'invalid email');

try {
    // Check uniqueness (username and email if provided)
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR (email IS NOT NULL AND email = :e)');
    $stmt->execute([':u'=>$username, ':e'=>$email]);
    if ($stmt->fetch()) json_error(409, 'username or email already exists');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = $email !== ''
        ? 'INSERT INTO users (username, email, password) VALUES (:u, :e, :p)'
        : 'INSERT INTO users (username, password) VALUES (:u, :p)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($email !== '' ? [':u'=>$username, ':e'=>$email, ':p'=>$hash] : [':u'=>$username, ':p'=>$hash]);

    http_response_code(201);
    json_ok(['user_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    json_error(500, 'Registration failed');
}
