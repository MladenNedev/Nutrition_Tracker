<?php
namespace App\Service;

use DateTimeImmutable;

final class DashboardService
{
    public function __construct(private ApiClient $api) {}

    public function getDailySummary(int $userId, DateTimeImmutable $date): array
    {
        $uri = 'daily_summary.php?' . http_build_query([
            'user_id' => $userId,
            'date'    => $date->format('Y-m-d'),
        ]);

        $res = $this->api->get($uri);

        // if the API failed, return safe defaults
        if (!$res['ok']) {
            return [
                'date'    => $date->format('Y-m-d'),
                'totals'  => [
                    'calories' => 0,
                    'protein'  => 0,
                    'carbs'    => 0,
                    'fat'      => 0,
                    'fiber'    => 0,
                ],
                'by_slot' => [
                    'breakfast' => [],
                    'lunch'     => [],
                    'dinner'    => [],
                    'snacks'    => [],
                ],
            ];
        }

        // <-- THIS is the important line:
        $data = $res['data'] ?? [];

        return [
            'date'    => $data['date']   ?? $date->format('Y-m-d'),
            'totals'  => $data['totals'] ?? [
                'calories' => 0,
                'protein'  => 0,
                'carbs'    => 0,
                'fat'      => 0,
                'fiber'    => 0,
            ],
            'by_slot' => $data['by_slot'] ?? [
                'breakfast' => [],
                'lunch'     => [],
                'dinner'    => [],
                'snacks'    => [],
            ],
        ];
    }
}
