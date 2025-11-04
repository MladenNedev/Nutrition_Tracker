<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth_check.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method Not Allowed');
}

$userId = require_login(true); // true = allow JSON/query user_id

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$name   = trim((string)($data['name'] ?? ''));
$dateIn = trim((string)($data['date'] ?? ''));


// prefer session, but allow explicit user_id (for curl / local tests)
if ($userId <= 0 && $sessionUserId > 0) {
    $userId = $sessionUserId;
}

if ($name === '') {
    json_error(400, 'name is required');
}
if ($userId <= 0) {

    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// normalize date
if ($dateIn === '') {
    $dateIn = (new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d');
}

try {
    // check if meal already exists on that day for this user+name
    $sel = $pdo->prepare("
        SELECT id
        FROM meals
        WHERE user_id = :uid
          AND name    = :name
          AND DATE(created_at) = :d
        LIMIT 1
    ");
    $sel->execute([
        ':uid'  => $userId,
        ':name' => $name,
        ':d'    => $dateIn,
    ]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        json_ok([
            'ok'      => true,
            'meal_id' => (int)$row['id'],
        ]);
    }

    // create new meal
    $ins = $pdo->prepare("
        INSERT INTO meals (user_id, name, created_at)
        VALUES (:uid, :name, :dt)
    ");
    $ins->execute([
        ':uid'  => $userId,
        ':name' => $name,
        ':dt'   => $dateIn . ' 00:00:00',
    ]);

    json_ok([
        'ok'      => true,
        'meal_id' => (int)$pdo->lastInsertId(),
    ]);

} catch (Throwable $e) {
    json_error(500, 'create_meal failed');
}
