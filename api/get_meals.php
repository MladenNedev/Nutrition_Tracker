<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    json_error(400, 'user_id required');
}

$date = $_GET['date'] ?? null;

try {
    if ($date) {
        $sql = "SELECT id, name, created_at
                FROM meals
                WHERE user_id = :uid
                  AND DATE(created_at) = :d
                ORDER BY created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':d'   => $date,
        ]);
    } else {
        $sql = "SELECT id, name, created_at
                FROM meals
                WHERE user_id = :uid
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_ok([
        'meals' => array_map(static function (array $row): array {
            return [
                'id'         => (int)$row['id'],
                'name'       => (string)$row['name'],
                'created_at' => (string)$row['created_at'],
            ];
        }, $rows),
    ]);

} catch (\Throwable $e) {
    json_error(500, 'Could not fetch meals');
}
