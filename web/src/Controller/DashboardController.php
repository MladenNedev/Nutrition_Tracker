<?php
declare(strict_types=1);

namespace App\Controller;

use App\View\View;
use App\Http\Auth;
use App\Core\Container;
use App\Service\DashboardService;
use App\Service\ApiClient;

final class DashboardController
{
    public function __construct(
        private View $view,
        private ?Container $c = null
    ) {}

    public function index(): string
    {
        Auth::requireUser();

        $userId = (int)($_SESSION['auth']['user_id'] ?? 0);
        if ($userId <= 0) { header('Location: /login'); exit; }

        $date = $this->resolveDate($_GET['date'] ?? null);

        $svc     = $this->dashboardSvc();
        $summary = $svc->getDailySummary(
            $userId,
            new \DateTimeImmutable($date, new \DateTimeZone('Europe/Amsterdam'))
        );

        $prev = (new \DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
        $next = (new \DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');

        // Default goals (can be moved to user settings later)
        $goals = [
            'calories' => 2000,
            'protein'  => 120,
            'carbs'    => 220,
            'fat'      => 70,
            'fiber'    => 25,
        ];

        // Fetch foods list for the dropdown
        /** @var ApiClient $api */
        $api = $this->c ? $this->c->get(ApiClient::class) : new ApiClient('http://api/', 5);
        $foodsRes = $api->get('get_foods.php');
        $foods = [];
        if ($foodsRes['ok'] && isset($foodsRes['data']['foods'])) {
            $foods = $foodsRes['data']['foods'];
        }

        return $this->view->render('dashboard/index.twig', [
            'date'      => $date,
            'prev_date' => $prev,
            'next_date' => $next,
            'totals'    => $summary['totals'] ?? [],
            'goals'     => $goals,
            'by_slot'   => $summary['by_slot'] ?? [],
            'foods'    => $foods,
        ]);
    }

    public function foods(): void
    {
        Auth::requireUser();

        header('Content-Type: application/json');

        /** @var ApiClient $api */
        $api = $this->c ? $this->c->get(ApiClient::class) : new ApiClient('http://api/', 5);

        $search = isset($_GET['search']) ? (string)$_GET['search'] : '';
        $uri = 'get_foods.php' . ($search !== '' ? ('?' . http_build_query(['search' => $search])) : '');
        $res = $api->get($uri);

        if (!$res['ok']) {
            http_response_code(500);
            echo json_encode(['foods' => []]);
            return;
        }

        echo json_encode(['foods' => $res['data']['foods'] ?? []]);
    }

    public function add(): void
    {
        Auth::requireUser();

        $userId = (int)($_SESSION['auth']['user_id'] ?? 0);
        if ($userId <= 0) { header('Location: /login'); exit; }

        $slot     = trim((string)($_POST['slot'] ?? ''));
        $foodId   = (int)($_POST['food_id'] ?? 0);
        $quantity = (float)($_POST['quantity_g'] ?? 0);
        $date     = $this->resolveDate($_POST['date'] ?? null);

        $redirect = (string)($_POST['redirect'] ?? ('/dashboard?date=' . urlencode($date)));
        if (!str_starts_with($redirect, '/dashboard')) {
            $redirect = '/dashboard?date=' . urlencode($date);
        }

        if ($slot === '' || $foodId <= 0 || $quantity <= 0) {
            header('Location: ' . $redirect); exit;
        }

        /** @var ApiClient $api */
        $api = $this->c ? $this->c->get(ApiClient::class) : new ApiClient('http://api/', 5);

        $ensure = $api->postJson('create_meal.php', [
            'user_id' => $userId,
            'name'    => $slot,
            'date'    => $date,
        ]);
        $mealId = (int)($ensure['data']['meal_id'] ?? 0);

        $api->postJson('add_meal_item.php', [
            'user_id'    => $userId,
            'meal_id'    => $mealId,
            'name'       => $slot,
            'date'       => $date,
            'food_id'    => $foodId,
            'quantity_g' => $quantity,
        ]);

        header('Location: ' . $redirect); exit;
    }

    private function resolveDate(?string $raw): string
    {
        if (is_string($raw) && \DateTime::createFromFormat('Y-m-d', $raw) !== false) {
            return $raw;
        }
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Amsterdam')))->format('Y-m-d');
    }

    private function dashboardSvc(): DashboardService
    {
        if ($this->c) return $this->c->get(DashboardService::class);
        $apiClient = new ApiClient('http://api/', 5);
        return new DashboardService($apiClient);
    }
}

