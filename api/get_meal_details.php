<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

$mealId = isset($_GET['meal_id']) ? (int)$_GET['meal_id'] : 0;
if ($mealId <= 0) {
    json_error(400, 'meal_id required');
}

$sql = "SELECT mi.id,
               mi.quantity_g,
               f.id   AS food_id,
               f.name AS food_name
        FROM meal_items mi
        JOIN foods f ON f.id = mi.food_id
        WHERE mi.meal_id = :mid
        ORDER BY mi.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':mid' => $mealId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_ok([
    'items' => array_map(static function (array $r): array {
        return [
            'id'         => (int)$r['id'],
            'food_id'    => (int)$r['food_id'],
            'food_name'  => (string)$r['food_name'],
            'quantity_g' => (float)$r['quantity_g'],
        ];
    }, $rows),
]);
