<?php
namespace App\Service;

final class AuthService
{
    public function __construct(private ApiClient $api) {}

    public function login(string $identifier, string $password): array
    {
        $res = $this->api->postJson('login.php', [
            'username_or_email' => $identifier,
            'password'          => $password,
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'] ?? 'Login failed'];
        }

        $user_id = (int)($res['data']['user_id'] ?? 0);
        if ($user_id <= 0) {
            return ['ok' => false, 'error' => 'Malformed login response'];
        }

        return ['ok' => true, 'user_id' => $user_id];
    }

    public function register(string $username, string $email, string $password): array
    {
        $payload = [
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ];

        $res = $this->api->postJson('register.php', $payload);

        // API will send {ok:false, error:"..."} for 400/409
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'] ?? 'Registration failed'];
        }

        $user_id = (int)($res['data']['user_id'] ?? 0);
        if ($user_id <= 0) {
            return ['ok' => false, 'error' => 'Malformed registration response'];
        }

        return ['ok' => true, 'user_id' => $user_id];
    }
}
