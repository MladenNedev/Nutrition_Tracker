<?php
declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

final class Config
{
    private string $rootPath;
    /** @var array<string,mixed> */
    private array $env = [];

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');

        $envFile = $this->rootPath . '/.env';
        if (is_file($envFile)) {
            Dotenv::createImmutable($this->rootPath)->safeLoad();
        } else {
            error_log('WARNING: .env not found.' . $envFile);
        }

        $this->env = $_ENV;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->env)) {
            return $this->env[$key];
        }
        $v = getenv($key);
        return ($v !==false && $v !== null) ? $v : $default;
    }
}