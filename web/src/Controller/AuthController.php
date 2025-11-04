<?php
namespace App\Controller;

use App\View\View;
use App\Http\Flash;
use App\Core\Container;

class AuthController
{
    public function __construct(private View $view, private ?Container $c = null) {}

    public function showLogin(): string
    {
        $error = Flash::get('error');
        $success = Flash::get('success');
        return $this->view->render('auth/login.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function login(): void
    {
        $identifier = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            Flash::set('error', 'Email/username and password are required');
            header('Location: /login');
            exit;
        }

        $authService = $this->c
            ? $this->c->get(\App\Service\AuthService::class)
            : new \App\Service\AuthService(new \App\Service\ApiClient('http://api/', 5));

        $result = $authService->login($identifier, $password);

        if ($result['ok']) {
            $_SESSION['auth'] = [
                'user_id' => $result['user_id'],
            ];
            header('Location: /dashboard');
            exit;
        }

        Flash::set('error', $result['error']);
        header('Location: /login');
        exit;
    }

    public function showRegister(): string
    {
        $error = Flash::get('error');
        return $this->view->render('auth/register.twig', [
            'error' => $error,
        ]);
    }

    public function register(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Flash::set('error', 'Username and password are required');
            header('Location: /register');
            exit;
        }

        // call API /register.php
        $api = $this->c
            ? $this->c->get(\App\Service\ApiClient::class)
            : new \App\Service\ApiClient('http://api/', 5);

        $res = $api->postJson('register.php', [
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        if (!$res['ok']) {
            Flash::set('error', $res['error'] ?? 'Registration failed');
            header('Location: /register');
            exit;
        }

        Flash::set('success', 'Account created, please log in');
        header('Location: /login');
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: /login');
        exit;
    }
}
