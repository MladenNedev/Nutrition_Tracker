<?php
declare(strict_types=1);

function require_param(array $source, string $key)
{
    if (!isset($source[$key]) || trim((string)$source[$key]) === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => "$key is required"]);
        exit;
    }
    return htmlspecialchars(trim((string)$source[$key]), ENT_QUOTES, 'UTF-8');
}

function optional_param(array $source, string $key, $default = null)
{
    if (isset($source[$key]) && trim((string)$source[$key]) !== '') {
        return htmlspecialchars(trim((string)$source[$key]), ENT_QUOTES, 'UTF-8');
    }
    return $default;
}

function json_ok($data): void
{
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function json_error(int $code, string $message): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}
