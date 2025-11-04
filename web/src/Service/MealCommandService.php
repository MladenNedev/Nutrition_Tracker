<?php
namespace App\Service;

use DateTimeImmutable;

final class MealCommandService
{
    public function __construct(private ApiClient $api) {}

    /**
     * @param int $userId
     * @param string $slot     e.g. "Breakfast", "Lunch", "Dinner", "Snacks"
     * @param int $foodId
     * @param float $quantityG
     * @param DateTimeImmutable $date
     */
    public function addMealItem(
        int $userId,
        string $slot,
        int $foodId,
        float $quantityG,
        DateTimeImmutable $date
    ): array {
        // 1) create / get meal
        $r1 = $this->api->postJson('create_meal.php', [
            'user_id' => $userId,
            'name'    => $slot,
            'date'    => $date->format('Y-m-d'),
        ]);

        if (!$r1['ok']) {
            return ['ok' => false, 'error' => $r1['error'] ?? 'Could not create meal'];
        }

        $mealId = (int)($r1['data']['meal_id'] ?? 0);
        if ($mealId <= 0) {
            return ['ok' => false, 'error' => 'Invalid meal_id from API'];
        }

        // 2) add item
        $r2 = $this->api->postJson('add_meal_item.php', [
            'meal_id'    => $mealId,
            'food_id'    => $foodId,
            'quantity_g' => $quantityG,
        ]);

        if (!$r2['ok']) {
            return ['ok' => false, 'error' => $r2['error'] ?? 'Could not add meal item'];
        }

        return ['ok' => true, 'meal_id' => $mealId];
    }
}
