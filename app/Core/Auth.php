<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Models\Setting;

/**
 * Authentication facade backed by the PHP session.
 *
 * Authorisation is always re-checked on the server; hiding UI is never
 * treated as a security control.
 */
final class Auth
{
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** @var array<string,mixed>|null */
    private static ?array $cachedUser = null;

    private static bool $resolved = false;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$cachedUser;
        }

        self::$resolved = true;

        $id = Session::get('auth_user_id');

        if (!is_int($id) && !is_numeric($id)) {
            self::$cachedUser = null;
            return null;
        }

        $user = User::find((int) $id);

        if ($user === null || $user['status'] !== 'active') {
            Session::forget('auth_user_id');
            self::$cachedUser = null;
            return null;
        }

        self::$cachedUser = $user;

        return $user;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user === null ? null : (string) $user['role'];
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Establish an authenticated session.
     *
     * @param array<string,mixed> $user
     */
    public static function login(array $user): void
    {
        Session::regenerate(true);
        Session::put('auth_user_id', (int) $user['id']);
        Session::put('auth_login_at', time());
        self::$cachedUser = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::flush();
        self::$cachedUser = null;
        self::$resolved = true;
    }

    /**
     * Re-read the current user from the database (after profile updates).
     *
     * @return array<string,mixed>|null
     */
    public static function refresh(): ?array
    {
        self::$resolved = false;
        self::$cachedUser = null;

        return self::user();
    }

    /**
     * Mode switching: a single account may act as client, freelancer, or both.
     */
    public static function activeMode(): string
    {
        $user = self::user();
        if ($user === null) {
            return 'client';
        }

        $mode = Session::get('active_mode');

        if (!in_array($mode, ['client', 'freelancer'], true)) {
            $mode = (string) ($user['default_mode'] ?? 'client');
        }

        if (!in_array($mode, ['client', 'freelancer'], true)) {
            $mode = 'client';
        }

        return $mode;
    }

    public static function setActiveMode(string $mode): void
    {
        if (in_array($mode, ['client', 'freelancer'], true)) {
            Session::put('active_mode', $mode);
        }
    }

    public static function isSuspended(): bool
    {
        $user = self::user();

        return $user !== null && $user['status'] !== 'active';
    }

    public static function siteSetting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}
