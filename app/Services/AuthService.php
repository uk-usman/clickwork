<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Models\EmailVerificationToken;
use App\Models\LoginAttempt;
use App\Models\PasswordResetToken;
use App\Models\User;

/**
 * Registration, login, email verification and password reset.
 */
final class AuthService
{
    /**
     * @param  array<string,mixed> $input
     * @return array{ok:bool,errors:array<string,string>,user_id?:int}
     */
    public static function register(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $accountType = (string) ($input['account_type'] ?? 'both');

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2) {
            $errors['name'] = 'Please enter your full name.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/[0-9]/', $password) !== 1) {
            $errors['password'] = 'Password must contain at least one letter and one number.';
        }
        if (!in_array($accountType, ['client', 'freelancer', 'both'], true)) {
            $accountType = 'both';
        }

        if ($errors === [] && User::findByEmail($email) !== null) {
            $errors['email'] = 'An account with this email address already exists.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $defaultMode = $accountType === 'freelancer' ? 'freelancer' : 'client';

        $userId = User::createWithProfile(
            [
                'name'          => $name,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => Auth::ROLE_USER,
                'status'        => 'active',
                'default_mode'  => $defaultMode,
                'phone'         => trim((string) ($input['phone'] ?? '')) ?: null,
            ],
            [
                'country' => 'Nigeria',
                'city'    => trim((string) ($input['city'] ?? '')) ?: null,
                'state'   => trim((string) ($input['state'] ?? '')) ?: null,
            ]
        );

        AuditService::record('user.registered', 'New account registered', 'user', $userId, ['email' => $email], $userId, 'user');
        NotificationService::notify(
            $userId,
            'account',
            'Welcome to CircleWork Africa',
            'Complete your profile to start finding work or hiring talent.',
            route('dashboard')
        );

        self::sendVerificationEmail($userId);

        return ['ok' => true, 'errors' => [], 'user_id' => $userId];
    }

    /**
     * @return array{ok:bool,message:string,user?:array<string,mixed>}
     */
    public static function attempt(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));
        $maxAttempts = (int) App::configValue('security.login_max_attempts', 5);
        $lockout = (int) App::configValue('security.login_lockout_seconds', 900);

        $recentFailures = LoginAttempt::recentFailures($email, $ip, $lockout);

        if ($recentFailures >= $maxAttempts) {
            Logger::security('Login blocked by throttling', ['email' => $email, 'ip' => $ip]);

            return [
                'ok'      => false,
                'message' => 'Too many failed sign-in attempts. Please wait about ' . max(1, (int) ceil($lockout / 60)) . ' minutes and try again.',
            ];
        }

        $user = User::findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            LoginAttempt::record($email, $ip, false);
            Logger::security('Failed login attempt', ['email' => $email, 'ip' => $ip]);

            return ['ok' => false, 'message' => 'The email address or password is incorrect.'];
        }

        if ($user['status'] === 'suspended') {
            LoginAttempt::record($email, $ip, false);

            return ['ok' => false, 'message' => 'This account has been suspended. Please contact support for assistance.'];
        }

        if ($user['status'] === 'closed') {
            LoginAttempt::record($email, $ip, false);

            return ['ok' => false, 'message' => 'This account is closed and can no longer be used.'];
        }

        // Upgrade the hash if the algorithm/default cost has changed.
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        LoginAttempt::record($email, $ip, true);
        LoginAttempt::clearFor($email, $ip);

        User::update((int) $user['id'], [
            'last_login_at' => gmdate('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);

        Auth::login($user);
        Auth::setActiveMode((string) ($user['default_mode'] ?? 'client'));

        AuditService::record('user.login', 'User signed in', 'user', (int) $user['id'], [], (int) $user['id'], (string) $user['role']);

        return ['ok' => true, 'message' => 'Signed in successfully.', 'user' => $user];
    }

    public static function sendVerificationEmail(int $userId): void
    {
        $user = User::find($userId);
        if ($user === null || $user['email_verified_at'] !== null) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        EmailVerificationToken::issue($userId, $rawToken, 48);

        $link = app_url(route('verify.email', ['token' => $rawToken]));

        Mailer::send(
            (string) $user['email'],
            (string) $user['name'],
            'Verify your CircleWork Africa email address',
            "Hello " . $user['name'] . ",\n\n"
            . "Please confirm your email address to activate all features of your CircleWork Africa account:\n\n"
            . $link . "\n\n"
            . "This link expires in 48 hours. If you did not create this account you can ignore this message.\n"
        );
    }

    /**
     * @return array{ok:bool,message:string,user_id?:int}
     */
    public static function verifyEmail(string $rawToken): array
    {
        $token = EmailVerificationToken::findUsable($rawToken);

        if ($token === null) {
            return ['ok' => false, 'message' => 'This verification link is invalid or has expired.'];
        }

        $userId = (int) $token['user_id'];

        Database::transaction(static function () use ($token, $userId): void {
            EmailVerificationToken::consume((int) $token['id']);
            User::update($userId, ['email_verified_at' => gmdate('Y-m-d H:i:s')]);
        });

        AuditService::record('user.email_verified', 'Email address verified', 'user', $userId);

        return ['ok' => true, 'message' => 'Your email address has been verified.', 'user_id' => $userId];
    }

    /**
     * Always returns success to avoid disclosing which emails are registered.
     */
    public static function sendPasswordReset(string $email, string $ip): void
    {
        $user = User::findByEmail($email);

        if ($user === null || $user['status'] === 'closed') {
            // Small delay to reduce account-enumeration timing differences.
            usleep(random_int(120000, 260000));
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        PasswordResetToken::issue((int) $user['id'], $rawToken, 60, $ip);

        $link = app_url(route('password.reset.form', ['token' => $rawToken]));

        Mailer::send(
            (string) $user['email'],
            (string) $user['name'],
            'Reset your CircleWork Africa password',
            "Hello " . $user['name'] . ",\n\n"
            . "We received a request to reset your password. Use the link below to choose a new one:\n\n"
            . $link . "\n\n"
            . "This link expires in 60 minutes. If you did not request this, no action is needed.\n"
        );

        AuditService::record('user.password_reset_requested', 'Password reset requested', 'user', (int) $user['id'], [], (int) $user['id'], (string) $user['role']);
    }

    /**
     * @return array{ok:bool,errors:array<string,string>,message:string}
     */
    public static function resetPassword(string $rawToken, string $password, string $confirmation): array
    {
        $errors = [];

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/[0-9]/', $password) !== 1) {
            $errors['password'] = 'Password must contain at least one letter and one number.';
        }

        if ($password !== $confirmation) {
            $errors['password_confirmation'] = 'The password confirmation does not match.';
        }

        $token = PasswordResetToken::findUsable($rawToken);

        if ($token === null) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'This reset link is invalid or has expired. Please request a new one.'];
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Please correct the highlighted fields.'];
        }

        $userId = (int) $token['user_id'];

        Database::transaction(static function () use ($token, $userId, $password): void {
            User::update($userId, ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            PasswordResetToken::consume((int) $token['id']);
        });

        AuditService::record('user.password_reset', 'Password was reset', 'user', $userId);
        NotificationService::notify($userId, 'account', 'Your password was changed', 'If this was not you, please contact support immediately.');

        return ['ok' => true, 'errors' => [], 'message' => 'Your password has been reset. You can now sign in.'];
    }

    public static function changePassword(int $userId, string $current, string $new, string $confirmation): array
    {
        $user = User::find($userId);
        if ($user === null) {
            return ['ok' => false, 'errors' => ['current_password' => 'Account not found.'], 'message' => 'Account not found.'];
        }

        $errors = [];

        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if (mb_strlen($new) < 8) {
            $errors['password'] = 'New password must be at least 8 characters.';
        } elseif (preg_match('/[A-Za-z]/', $new) !== 1 || preg_match('/[0-9]/', $new) !== 1) {
            $errors['password'] = 'New password must contain at least one letter and one number.';
        }
        if ($new !== $confirmation) {
            $errors['password_confirmation'] = 'The new password confirmation does not match.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'message' => 'Please correct the highlighted fields.'];
        }

        User::update($userId, ['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
        Session::regenerate(true);

        AuditService::record('user.password_changed', 'Password changed from account settings', 'user', $userId);

        return ['ok' => true, 'errors' => [], 'message' => 'Your password has been updated.'];
    }

    public static function logout(): void
    {
        $userId = Auth::id();
        if ($userId !== null) {
            AuditService::record('user.logout', 'User signed out', 'user', $userId);
        }
        Auth::logout();
    }

    public static function clientIp(Request $request): string
    {
        return $request->ip();
    }
}
