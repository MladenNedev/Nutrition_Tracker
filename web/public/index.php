<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/../vendor/autoload.php';

use App\View\View;
use App\Http\Router;
use App\Config\Config;
use App\Core\Container;
use App\Service\ApiClient;
use App\Service\ApiMealsService;
use App\Service\MealsService;
use App\Service\DashboardService;
use App\Service\AuthService;
use App\Controller\HomeController;
use App\Controller\AuthController;
use App\Controller\DashboardController;

// 1) load config (if you use it)
$rootPath = dirname(__DIR__);
$config = new Config($rootPath);

// 2) create container
$container = new Container();

// API base for the web app to talk to the api container
$apiBase = rtrim(getenv('API_BASE_URL') ?: 'http://api', '/');
$timeout = (float)(getenv('API_TIMEOUT') ?: 5);

// register shared services
$container->set(ApiClient::class, new ApiClient($apiBase, $timeout));
$container->set(ApiMealsService::class, new ApiMealsService(
    $container->get(ApiClient::class)
));
$container->set(MealsService::class, new MealsService(
    $container->get(ApiMealsService::class)
));
$container->set(DashboardService::class, new DashboardService(
    $container->get(ApiClient::class)
));
$container->set(AuthService::class, new AuthService(
    $container->get(ApiClient::class)
));

// 3) Twig
$viewsPath = __DIR__ . '/../views';
$cachePath = __DIR__ . '/../var/cache/twig';
$debug     = true;

if (!is_dir($cachePath)) {
    @mkdir($cachePath, 0777, true);
}

$view = new View($viewsPath, null, $debug);
$view->addGlobal('auth', [
    'user_id' => $_SESSION['auth']['user_id'] ?? null,
]);

// 4) router
$router = new Router($view, $container);

// 5) routes
$router->get('/', function() {
    if (!empty($_SESSION['auth']['user_id'])) {
        header('Location: /dashboard');
    } else {
        header('Location: /login');
    }
    exit;
});


$router->get('/login',    [AuthController::class, 'showLogin']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/logout',   [AuthController::class, 'logout']);

$router->get('/register',  [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/meals/add', [DashboardController::class, 'add']);   // 👈 important
// foods proxy for searchable dropdown
$router->get('/foods', [DashboardController::class, 'foods']);

// 6) dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

