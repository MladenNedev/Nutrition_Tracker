<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class ApiClient
{
    private Client $http;
    private CookieJar $cookies;

    public function __construct(string $baseUrl, float $timeoutSeconds = 5.0)
    {
        $this->cookies = new CookieJar();
        $this->http = new Client ([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => $timeoutSeconds,
            'http_errors' => false,
            'cookies' => $this->cookies,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    public function get(string $uri, array $headers = []): array
    {
        return $this->requestJson('GET', $uri, ['headers' => $headers]);
    }

    public function postJson(string $uri, array $payload, array $headers = []): array
    {
        return $this->requestJson('POST', $uri, [
            'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
            'body' => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function requestJson(string $method, string $uri, array $options = []): array
    {
        try {
            $res = $this->http->request($method, ltrim($uri, '/'), $options);
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => 'Network error'];
        }

        $status = $res->getStatusCode();
        $raw    = (string) $res->getBody();
        $data   = json_decode($raw, true);

        // Deffensive: handle malformed JSON
        if ($data === null && $raw !== '' && json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'status' => $status, 'error' => 'Invalid JSON'];
        }

        if ($status >= 200 && $status <300) {
            return ['ok' => true, 'status' => $status, 'data' => $data];
        }

        return ['ok' => false, 'status' => $status, 'error' => $data['error'] ?? 'API error'];
    }
}