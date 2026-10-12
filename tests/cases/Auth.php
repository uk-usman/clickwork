<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\User;
use App\Services\AuthService;

/** @var HttpClient $http */
return [
    'registration creates a user with a hashed password' => function (): void {
        $before = Fixtures::tableCount('users');

        $result = AuthService::register([
            'name'         => 'Registration Test',
            'email'        => 'registration.test@example.com',
            'password'     => 'StrongPass123',
            'account_type' => 'freelancer',
        ]);

        assert_true($result['ok'], 'Registration should succeed.');
        assert_equals($before + 1, Fixtures::tableCount('users'), 'Exactly one user should be added.');

        $user = User::find((int) $result['user_id']);
        assert_true($user !== null, 'The registered user should be retrievable.');
        assert_false(str_contains((string) $user['password_hash'], 'StrongPass123'), 'The password must never be stored in plain text.');
        assert_true(password_verify('StrongPass123', (string) $user['password_hash']), 'The stored hash should verify the original password.');
        assert_equals('freelancer', $user['default_mode'], 'Account type should set the default mode.');
    },

    'duplicate email registration is rejected' => function (): void {
        AuthService::register([
            'name'     => 'First Owner',
            'email'    => 'duplicate@example.com',
            'password' => 'StrongPass123',
        ]);

        $second = AuthService::register([
            'name'     => 'Second Owner',
            'email'    => 'duplicate@example.com',
            'password' => 'AnotherPass123',
        ]);

        assert_false($second['ok'], 'A duplicate email must be rejected.');
        assert_true(isset($second['errors']['email']), 'A duplicate email error should be reported for the email field.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `users` WHERE `email` = ?', ['duplicate@example.com']), 'Only one account may exist for an email address.');
    },

    'weak passwords are rejected' => function (): void {
        $short = AuthService::register(['name' => 'Short Pass', 'email' => 'short@example.com', 'password' => 'abc123']);
        assert_false($short['ok'], 'A short password must be rejected.');

        $lettersOnly = AuthService::register(['name' => 'Letters Only', 'email' => 'letters@example.com', 'password' => 'abcdefghij']);
        assert_false($lettersOnly['ok'], 'A password without a number must be rejected.');

        $digitsOnly = AuthService::register(['name' => 'Digits Only', 'email' => 'digits@example.com', 'password' => '1234567890']);
        assert_false($digitsOnly['ok'], 'A password without a letter must be rejected.');
    },

    'invalid email registration is rejected' => function (): void {
        $result = AuthService::register(['name' => 'Bad Email', 'email' => 'not-an-email', 'password' => 'StrongPass123']);
        assert_false($result['ok'], 'An invalid email must be rejected.');
        assert_true(isset($result['errors']['email']), 'An email error should be reported.');
    },

    'login succeeds with correct credentials and fails with a wrong password' => function (): void {
        $user = Fixtures::user(['password' => 'CorrectPass123']);

        $wrong = AuthService::attempt((string) $user['email'], 'WrongPass123', '127.0.0.1');
        assert_false($wrong['ok'], 'A wrong password must not sign the user in.');

        $right = AuthService::attempt((string) $user['email'], 'CorrectPass123', '127.0.0.1');
        assert_true($right['ok'], 'Correct credentials should sign the user in.');
        assert_equals((int) $user['id'], (int) $right['user']['id'], 'The signed-in user should match.');

        $stored = User::find((int) $user['id']);
        assert_true($stored !== null && $stored['last_login_at'] !== null, 'The last login timestamp should be recorded.');
    },

    'login is throttled after repeated failures' => function (): void {
        $user = Fixtures::user(['password' => 'CorrectPass123']);
        $email = (string) $user['email'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            AuthService::attempt($email, 'DefinitelyWrong1', '10.0.0.55');
        }

        $blocked = AuthService::attempt($email, 'CorrectPass123', '10.0.0.55');
        assert_false($blocked['ok'], 'The account should be locked out after repeated failures.');
        assert_contains('Too many failed sign-in attempts', $blocked['message'], 'The lockout message should explain the throttle.');
    },

    'suspended accounts cannot sign in' => function (): void {
        $user = Fixtures::user(['password' => 'CorrectPass123']);
        User::update((int) $user['id'], ['status' => 'suspended', 'suspension_reason' => 'Automated test suspension']);

        $result = AuthService::attempt((string) $user['email'], 'CorrectPass123', '127.0.0.1');
        assert_false($result['ok'], 'A suspended account must not sign in.');
        assert_contains('suspended', strtolower($result['message']), 'The message should explain the suspension.');
    },

    'email verification token is single use' => function (): void {
        $user = Fixtures::user();

        $rawToken = bin2hex(random_bytes(16));
        App\Models\EmailVerificationToken::issue((int) $user['id'], $rawToken, 24);

        $first = AuthService::verifyEmail($rawToken);
        assert_true($first['ok'], 'A valid token should verify the email address.');

        $second = AuthService::verifyEmail($rawToken);
        assert_false($second['ok'], 'A used token must not verify again.');

        $refreshed = User::find((int) $user['id']);
        assert_true($refreshed !== null && $refreshed['email_verified_at'] !== null, 'The verification timestamp should be stored.');
    },

    'password reset token is hashed, single use and rejects a mismatched confirmation' => function (): void {
        $user = Fixtures::user();

        $rawToken = bin2hex(random_bytes(16));
        App\Models\PasswordResetToken::issue((int) $user['id'], $rawToken, 60, '127.0.0.1');

        $stored = Database::selectOne('SELECT `token_hash` FROM `password_reset_tokens` WHERE `user_id` = ?', [(int) $user['id']]);
        assert_true($stored !== null, 'A reset token record should exist.');
        assert_false((string) $stored['token_hash'] === $rawToken, 'The raw token must never be stored.');
        assert_equals(hash('sha256', $rawToken), (string) $stored['token_hash'], 'Only the SHA-256 hash should be stored.');

        $mismatch = AuthService::resetPassword($rawToken, 'NewStrongPass123', 'DifferentPass123');
        assert_false($mismatch['ok'], 'A mismatched confirmation must be rejected.');

        $ok = AuthService::resetPassword($rawToken, 'NewStrongPass123', 'NewStrongPass123');
        assert_true($ok['ok'], 'A valid reset should succeed.');

        $reused = AuthService::resetPassword($rawToken, 'AnotherPass123', 'AnotherPass123');
        assert_false($reused['ok'], 'A used reset token must not work again.');

        $updated = User::find((int) $user['id']);
        assert_true($updated !== null && password_verify('NewStrongPass123', (string) $updated['password_hash']), 'The new password should be stored.');
    },
];
