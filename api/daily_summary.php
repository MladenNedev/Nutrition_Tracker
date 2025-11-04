<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

// 1. input
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    json_error(400, 'user_id required');
}

$date = $_GET['date'] ?? date('Y-m-d');

// 2. we fetch ALL meal_items for that user, that day, joined to nutrients
$sql = "
SELECT
    mi.id            AS meal_item_id,
    mi.quantity_g,
    mi.food_id,
    f.name           AS food_name,
    m.name           AS meal_name,
    n.name           AS nutrient_name,
    fn.amount        AS per_100g
FROM meal_items mi
JOIN meals m            ON m.id = mi.meal_id
JOIN foods f            ON f.id = mi.food_id
JOIN food_nutrients fn  ON fn.food_id = mi.food_id
JOIN nutrients n        ON n.id = fn.nutrient_id
WHERE m.user_id = :uid
  AND DATE(m.created_at) = :d
ORDER BY mi.id, n.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':uid' => $userId,
    ':d'   => $date,
]);

// 3. prepare result holders
$totals = [
    'calories' => 0.0,
    'protein'  => 0.0,
    'carbs'    => 0.0,
    'fat'      => 0.0,
    'fiber'    => 0.0,
];

$bySlot = [
    'breakfast' => [],
    'lunch'     => [],
    'dinner'    => [],
    'snacks'    => [],
];

// helper: map meal name to slot
$slotFor = static function (string $mealName): string {
    $n = strtolower($mealName);
    return match (true) {
        str_contains($n, 'break')   => 'breakfast',
        str_contains($n, 'lunch')   => 'lunch',
        str_contains($n, 'din')     => 'dinner',
        str_contains($n, 'snack')   => 'snacks',
        default                     => 'snacks',
    };
};

// 4. aggregate by meal_item_id to avoid duplicates
$itemsByMealItem = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $itemId = (int)$row['meal_item_id'];
    $qtyG   = (float)$row['quantity_g'];     // eaten
    $factor = $qtyG / 100.0;                 // composition is per 100g
    $nut    = $row['nutrient_name'];
    $amt100 = (float)$row['per_100g'];
    
    // Initialize item if not exists
    if (!isset($itemsByMealItem[$itemId])) {
        $itemsByMealItem[$itemId] = [
            'meal_item_id' => $itemId,
            'food_id'      => (int)$row['food_id'],
            'food_name'    => $row['food_name'],
            'meal_name'    => $row['meal_name'],
            'quantity_g'   => $qtyG,
            'calories'     => 0.0,
            'protein'      => 0.0,
            'carbs'        => 0.0,
            'fat'          => 0.0,
            'fiber'        => 0.0,
        ];
    }
    
    // Add nutrient contribution
    $contribution = $amt100 * $factor;
    if (strcasecmp($nut, 'Calories') === 0) {
        $itemsByMealItem[$itemId]['calories'] += $contribution;
        $totals['calories'] += $contribution;
    } elseif (strcasecmp($nut, 'Protein') === 0) {
        $itemsByMealItem[$itemId]['protein'] += $contribution;
        $totals['protein'] += $contribution;
    } elseif (strcasecmp($nut, 'Carbohydrates') === 0) {
        $itemsByMealItem[$itemId]['carbs'] += $contribution;
        $totals['carbs'] += $contribution;
    } elseif (strcasecmp($nut, 'Fat') === 0) {
        $itemsByMealItem[$itemId]['fat'] += $contribution;
        $totals['fat'] += $contribution;
    } elseif (strcasecmp($nut, 'Fiber') === 0) {
        $itemsByMealItem[$itemId]['fiber'] += $contribution;
        $totals['fiber'] += $contribution;
    }
}

// 5. Group items by slot (only include items with at least some calories)
foreach ($itemsByMealItem as $item) {
    // Only add items that have at least some nutritional value
    if ($item['calories'] > 0 || $item['protein'] > 0 || $item['carbs'] > 0 || $item['fat'] > 0) {
        $slot = $slotFor($item['meal_name']);
        // Ensure food_name is always set and not empty
        $foodName = !empty($item['food_name']) ? $item['food_name'] : ($item['meal_name'] ?? 'Unknown food');
        $bySlot[$slot][] = [
            'food_name'   => $foodName,
            'meal_name'   => $item['meal_name'] ?? '',
            'quantity_g'  => $item['quantity_g'],
            'calories'    => round($item['calories'], 1),
            'protein'     => round($item['protein'], 1),
            'carbs'       => round($item['carbs'], 1),
            'fat'         => round($item['fat'], 1),
        ];
    }
}

// 6. respond
json_ok([
    'date'    => $date,
    'totals'  => [
        'calories' => round($totals['calories'], 1),
        'protein'  => round($totals['protein'], 1),
        'carbs'    => round($totals['carbs'], 1),
        'fat'      => round($totals['fat'], 1),
        'fiber'    => round($totals['fiber'], 1),
    ],
    'by_slot' => $bySlot,
]);
