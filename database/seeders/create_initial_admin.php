<?php

/**
 * Creates or promotes the initial super administrator.
 *
 * This is a protected command-line utility. It is never reachable from the web.
 * Remove or disable this file after deployment.
 *
 * Usage (interactive):
 *   php database/seeders/create_initial_admin.php
 *
 * Usage (non-interactive, for automated provisioning):
 *   php database/seeders/create_initial_admin.php --email=you@example.com --name="Your Name" --password="..."
 *
 * If --password is omitted, you are prompted and the value is not echoed.
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Models\User;
use App\Models\UserProfile;

/** @var array<string,string> $options */
$options = [];
foreach (array_slice($argv, 1) as $argument) {
    if (preg_match('/^--([a-z_]+)=(.*)$/i', $argument, $matches) === 1) {
        $options[strtolower($matches[1])] = $matches[2];
    }
}

$prompt = static function (string $label, bool $hidden = false): string {
    fwrite(STDOUT, $label);

    if (!$hidden) {
        return trim((string) fgets(STDIN));
    }

    // Hide the password where the platform supports it.
    if (DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec') && @shell_exec('command -v stty') !== null) {
        @shell_exec('stty -echo');
        $value = trim((string) fgets(STDIN));
        @shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);

        return $value;
    }

    $value = trim((string) fgets(STDIN));
    fwrite(STDOUT, PHP_EOL);

    return $value;
};

$email = mb_strtolower(trim($options['email'] ?? ''));
$name = trim($options['name'] ?? '');
$password = (string) ($options['password'] ?? '');
$role = strtolower(trim($options['role'] ?? 'super_admin'));

if ($email === '') {
    $email = mb_strtolower($prompt('Administrator email address: '));
}
if ($name === '') {
    $name = $prompt('Administrator full name: ');
}
if ($password === '') {
    $password = $prompt('Password (input hidden): ', true);
    $confirm = $prompt('Confirm password: ', true);

    if ($password !== $confirm) {
        cw_fail('The passwords did not match.');
    }
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    cw_fail('Please provide a valid email address.');
}

if (mb_strlen($name) < 2) {
    cw_fail('Please provide the administrator name.');
}

if (mb_strlen($password) < 12) {
    cw_fail('Administrator passwords must be at least 12 characters. Choose a strong, unique password.');
}

if (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/[0-9]/', $password) !== 1) {
    cw_fail('The password must contain at least one letter and one number.');
}

if (!in_array($role, ['admin', 'super_admin'], true)) {
    cw_fail('Role must be either admin or super_admin.');
}

try {
    $existing = User::findByEmail($email);

    if ($existing !== null) {
        User::update((int) $existing['id'], [
            'role'              => $role,
            'status'            => 'active',
            'password_hash'     => password_hash($password, PASSWORD_DEFAULT),
            'email_verified_at' => $existing['email_verified_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        UserProfile::ensureForUser((int) $existing['id']);

        cw_line('Updated existing account ' . $email . ' and granted the ' . $role . ' role.');
    } else {
        $userId = Database::transaction(static function () use ($email, $name, $password, $role): int {
            $id = User::create([
                'name'              => $name,
                'email'             => $email,
                'password_hash'     => password_hash($password, PASSWORD_DEFAULT),
                'role'              => $role,
                'status'            => 'active',
                'default_mode'      => 'client',
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
            ]);

            UserProfile::create(['user_id' => $id, 'country' => 'Nigeria']);

            return $id;
        });

        cw_line('Created ' . $role . ' account #' . $userId . ' for ' . $email . '.');
    }

    Database::run(
        'INSERT INTO `audit_logs` (`actor_id`, `actor_role`, `action`, `entity_type`, `description`)
         VALUES (NULL, \'system\', \'admin.provisioned\', \'user\', ?)',
        ['Initial administrator provisioned from the protected CLI utility: ' . $email]
    );

    cw_line('Done. You can now sign in at /login and change the password from your account settings.');
    cw_line('IMPORTANT: remove or disable database/seeders/create_initial_admin.php after deployment.');
} catch (Throwable $e) {
    cw_fail('Could not create the administrator: ' . $e->getMessage());
}
