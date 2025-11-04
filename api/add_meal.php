<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth_check.php';

// POST + JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method Not Allowed');
}

$ct = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') === false) {
    json_error(415, 'Content-Type must be application/json');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_error(400, 'Invalid JSON');
}

// try session first
$userIdFromSession = require_login(false);

$user_id    = (int)($input['user_id'] ?? 0);
$name       = trim((string)($input['name'] ?? ''));
$created_at = $input['created_at'] ?? null;   // optional
$date       = $input['date'] ?? null;         // optional (preferred)
$items      = $input['items'] ?? null;

if ($user_id <= 0 && $userIdFromSession > 0) {
    $user_id = $userIdFromSession;
}

if ($user_id <= 0) {
    json_error(400, 'user_id must be provided (or be logged in)');
}
if ($name === '') {
    json_error(400, 'name is required');
}
if (!is_array($items) || count($items) === 0) {
    json_error(400, 'items must be a non-empty array');
}

// validate items
foreach ($items as $i => $item) {
    if (!isset($item['food_id'], $item['quantity_g'])) {
        json_error(400, "items[$i] must contain food_id and quantity_g");
    }
    if ((int)$item['food_id'] <= 0) {
        json_error(400, "items[$i].food_id must be a positive integer");
    }
    if ((float)$item['quantity_g'] <= 0) {
        json_error(400, "items[$i].quantity_g must be a positive number");
    }
}

// normalize date
$effectiveDate = null;
if ($date) {
    // YYYY-MM-DD
    $dt = \DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt) {
        json_error(400, 'date must be YYYY-MM-DD');
    }
    $effectiveDate = $date;
} elseif ($created_at) {
    // already full datetime, we will extract the date part
    $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $created_at);
    if (!$dt) {
        json_error(400, 'created_at must be Y-m-d H:i:s');
    }
    $effectiveDate = $dt->format('Y-m-d');
} else {
    // nothing given → today
    $effectiveDate = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Amsterdam')))
        ->format('Y-m-d');
}

try {
    $pdo->beginTransaction();

    // 1. check if meal already exists for user + name + date
    $sel = $pdo->prepare("
        SELECT id
        FROM meals
        WHERE user_id = :uid
          AND name = :name
          AND DATE(created_at) = :d
        LIMIT 1
    ");
    $sel->execute([
        ':uid'  => $user_id,
        ':name' => $name,
        ':d'    => $effectiveDate,
    ]);
    $row = $sel->fetch(\PDO::FETCH_ASSOC);

    if ($row) {
        // reuse meal
        $meal_id = (int)$row['id'];
    } else {
        // create meal
        $insertMealSql = "INSERT INTO meals (user_id, name, created_at) VALUES (:uid, :name, :dt)";
        $stmtMeal = $pdo->prepare($insertMealSql);
        $stmtMeal->execute([
            ':uid'  => $user_id,
            ':name' => $name,
            ':dt'   => $effectiveDate . ' 00:00:00',
        ]);
        $meal_id = (int)$pdo->lastInsertId();
    }

    // 2. insert items
    $stmtItem = $pdo->prepare("
        INSERT INTO meal_items (meal_id, food_id, quantity_g)
        VALUES (:mid, :fid, :q)
    ");

    $inserted = 0;
    foreach ($items as $item) {
        $stmtItem->execute([
            ':mid' => $meal_id,
            ':fid' => (int)$item['food_id'],
            ':q'   => (float)$item['quantity_g'],
        ]);
        $inserted++;
    }

    $pdo->commit();

    http_response_code(201);
    json_ok([
        'ok'             => true,
        'meal_id'        => $meal_id,
        'inserted_items' => $inserted,
        'date'           => $effectiveDate,
    ]);

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_error(500, 'Failed to insert meal');
}
