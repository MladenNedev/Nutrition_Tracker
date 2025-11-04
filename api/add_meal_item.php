<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method Not Allowed');
}

$userId = require_login(true); // accept session OR JSON user_id

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$mealId    = (int)($data['meal_id'] ?? 0);
$name      = trim((string)($data['name'] ?? ''));
$dateIn    = trim((string)($data['date'] ?? ''));
$foodId    = (int)($data['food_id'] ?? 0);
$foodName  = trim((string)($data['food_name'] ?? ''));
$quantity  = (float)($data['quantity_g'] ?? 0);


// prefer session
if ($userId <= 0 && $sessionUserId > 0) {
    $userId = $sessionUserId;
}

// If food_id is not provided, try to look it up by name
if ($foodId <= 0 && $foodName !== '') {
    try {
        $findFood = $pdo->prepare("SELECT id FROM foods WHERE name = :name LIMIT 1");
        $findFood->execute([':name' => $foodName]);
        $foodRow = $findFood->fetch(PDO::FETCH_ASSOC);
        if ($foodRow) {
            $foodId = (int)$foodRow['id'];
        } else {
            json_error(400, 'Food not found: ' . $foodName);
        }
    } catch (Throwable $e) {
        json_error(500, 'Failed to look up food');
    }
}

if ($foodId <= 0 || $quantity <= 0) {
    json_error(400, 'food_id (or food_name) and quantity_g are required');
}

// CASE 1: meal_id is provided → just insert
if ($mealId > 0) {
    try {
        $ins = $pdo->prepare("
            INSERT INTO meal_items (meal_id, food_id, quantity_g)
            VALUES (:mid, :fid, :qty)
        ");
        $ins->execute([
            ':mid' => $mealId,
            ':fid' => $foodId,
            ':qty' => $quantity,
        ]);

        json_ok([
            'ok'       => true,
            'meal_id'  => $mealId,
            'food_id'  => $foodId,
            'quantity' => $quantity,
        ]);
    } catch (Throwable $e) {
        json_error(500, 'add_meal_item failed');
    }
    exit;
}

// CASE 2: no meal_id → we need name + user_id, date optional
if ($name === '' || $userId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required or name missing']);
    exit;
}

if ($dateIn === '') {
    $dateIn = (new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d');
}

try {
    // try to find the meal for this user+name+date
    $find = $pdo->prepare("
        SELECT id
        FROM meals
        WHERE user_id = :uid
          AND name    = :name
          AND DATE(created_at) = :d
        LIMIT 1
    ");
    $find->execute([
        ':uid'  => $userId,
        ':name' => $name,
        ':d'    => $dateIn,
    ]);
    $row = $find->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $mealId = (int)$row['id'];
    } else {
        // create it on the fly
        $create = $pdo->prepare("
            INSERT INTO meals (user_id, name, created_at)
            VALUES (:uid, :name, :dt)
        ");
        $create->execute([
            ':uid'  => $userId,
            ':name' => $name,
            ':dt'   => $dateIn . ' 00:00:00',
        ]);
        $mealId = (int)$pdo->lastInsertId();
    }

    // insert item
    $ins = $pdo->prepare("
        INSERT INTO meal_items (meal_id, food_id, quantity_g)
        VALUES (:mid, :fid, :qty)
    ");
    $ins->execute([
        ':mid' => $mealId,
        ':fid' => $foodId,
        ':qty' => $quantity,
    ]);

    json_ok([
        'ok'       => true,
        'meal_id'  => $mealId,
        'food_id'  => $foodId,
        'quantity' => $quantity,
    ]);

} catch (Throwable $e) {
    json_error(500, 'add_meal_item failed');
}
