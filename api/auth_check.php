<?php
declare(strict_types=1);

/**
 * Common auth helpers for the API.

/**
 * Start session if not started.
 */
function start_session_if_needed(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}


function read_json_body(): ?array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}


function require_login(bool $allowFallback = false): int
{
    start_session_if_needed();

    // 1) normal case: user already logged in via /login.php
    $sid = $_SESSION['user_id'] ?? null;
    if ($sid) {
        return (int)$sid;
    }

    // 2) if fallback allowed, try to read user_id from JSON or query
    if ($allowFallback) {
        // try JSON
        $json = read_json_body();
        if ($json && !empty($json['user_id'])) {
            return (int)$json['user_id'];
        }

        // try query
        if (!empty($_GET['user_id'])) {
            return (int)$_GET['user_id'];
        }
    }

    // 3) still nothing → 401
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

/**
 * Lighter helper: try session, else try JSON/query, else return 0.
 *
 * Useful for endpoints that can work unauthenticated.
 */
function current_user_id(): int
{
    start_session_if_needed();

    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }

    $json = read_json_body();
    if ($json && !empty($json['user_id'])) {
        return (int)$json['user_id'];
    }

    if (!empty($_GET['user_id'])) {
        return (int)$_GET['user_id'];
    }

    return 0;
}
