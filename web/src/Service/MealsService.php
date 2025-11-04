<?php
namespace App\Service;

use DateTimeImmutable;

final class MealsService
{
    public function __construct(private ApiMealsService $impl) {}

    /**
     * @return array{meals: list<array<string,mixed>>}
     */
    public function getMealsForDate(int $userId, DateTimeImmutable $date): array
    {
        return $this->impl->getMealsForDate($userId, $date);
    }
}
    
