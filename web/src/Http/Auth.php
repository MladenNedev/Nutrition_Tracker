<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Central auth helper for the web frontend.
 *
 * Standardized on ONE session shape:
 *
 *   $_SESSION['auth'] = [
 *       'user_id' => 123,
 *   ];
 *
 * The login controller must set that. Everything else should read that.
 */
final class Auth
{
    /**
     * Return current user id or null if not authenticated.
     */
    public static function userId(): ?int
    {
        // if session wasn't started for some reason, return null
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $id = $_SESSION['auth']['user_id'] ?? null;
        if ($id === null) {
            return null;
        }

        return (int) $id;
    }

    /**
     * True if there is a logged-in user.
     */
    public static function check(): bool
    {
        return self::userId() !== null;
    }

    /**
     * Redirect to /login if no user.
     *
     * Use in controllers that need an authenticated user:
     *
     *   \App\Http\Auth::requireUser();
     */
    public static function requireUser(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }
}
