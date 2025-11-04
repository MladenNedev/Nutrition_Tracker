<?php
declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

final class ApiMealsService
{
    public function __construct(private ApiClient $api) {}

    /**
     * @return array{meals: list<array<string,mixed>>}
     */
    public function getMealsForDate(int $userId, DateTimeImmutable $date): array
    {
        $query = [
            'user_id' => $userId,
            'date'    => $date->format('Y-m-d'),
        ];

        $uri = 'get_meals.php?' . http_build_query($query);
        $res = $this->api->get($uri);

        if (!$res['ok']) {
            return ['meals' => []];
        }

        // API returns { ok: true, meals: [...] }
        $rows = (array)($res['meals'] ?? $res['data']['meals'] ?? []);

        $meals = array_map(static function (array $m): array {
            return [
                'id'         => (int)($m['id'] ?? 0),
                'name'       => (string)($m['name'] ?? ''),
                'created_at' => (string)($m['created_at'] ?? ''),
            ];
        }, $rows);

        return ['meals' => $meals];
    }
}

