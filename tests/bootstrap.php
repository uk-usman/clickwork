<?php

declare(strict_types=1);

/**
 * Test bootstrap.
 *
 * Creates (or recreates) a dedicated test database from database/schema.sql and
 * loads minimal fixtures. Never touches the development or production database.
 */

define('CIRCLEWORK_ROOT', dirname(__DIR__));

require CIRCLEWORK_ROOT . '/app/Core/Autoloader.php';
App\Core\Autoloader::register(CIRCLEWORK_ROOT);

/** Name of the isolated test database. */
const TEST_DB = 'circlework_test';

// Test runs never inherit production credentials from the shell.
putenv('DB_DATABASE=' . TEST_DB);
putenv('APP_ENV=testing');

$config = require CIRCLEWORK_ROOT . '/config/config.php';

// The config array was built before the override in some environments; force it.
$config['database']['database'] = TEST_DB;
$config['app']['env'] = 'testing';

App\Core\App::boot($config);

require CIRCLEWORK_ROOT . '/app/Helpers/functions.php';

use App\Core\Database;

/**
 * Run the schema against the test database.
 */
function cw_test_reset_database(): void
{
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', (string) App\Core\App::configValue('database.host', '127.0.0.1'), (int) App\Core\App::configValue('database.port', 3306)),
        (string) App\Core\App::configValue('database.username'),
        (string) App\Core\App::configValue('database.password'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . TEST_DB . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . TEST_DB . '`');

    $sql = (string) file_get_contents(CIRCLEWORK_ROOT . '/database/schema.sql');

    foreach (preg_split('/;\s*\n/', $sql) ?: [] as $statement) {
        $lines = array_filter(
            explode("\n", $statement),
            static function (string $line): bool {
                $trimmed = ltrim($line);
                return $trimmed !== '' && !str_starts_with($trimmed, '--');
            }
        );

        $statement = trim(implode("\n", $lines));

        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
    }
}

/**
 * Minimal fixtures so tests do not depend on seeder output.
 */
function cw_test_seed(): void
{
    Database::run(
        "INSERT INTO `categories` (`name`, `slug`, `icon`, `is_active`, `sort_order`)
         VALUES ('Web Development', 'web-development', 'bi-code-slash', 1, 1),
                ('Design', 'design', 'bi-palette', 1, 2)"
    );

    Database::run(
        "INSERT INTO `skills` (`category_id`, `name`, `slug`, `is_active`)
         VALUES (1, 'PHP', 'php', 1), (1, 'MySQL', 'mysql', 1), (2, 'Logo Design', 'logo-design', 1)"
    );

    Database::run(
        "INSERT INTO `subscription_plans` (`code`, `name`, `audience`, `price`, `currency`, `billing_period`, `features`, `limits`, `is_active`, `is_free`, `sort_order`)
         VALUES ('free', 'Free', 'both', 0.00, 'NGN', 'monthly', '[]', '{\"active_projects\":3,\"proposals_per_day\":10,\"portfolio_items\":5,\"featured_projects\":0,\"applicant_plain\":0,\"profile_analytics\":0}', 1, 1, 1),
                ('client_pro', 'Client Pro', 'client', 3000.00, 'NGN', 'monthly', '[]', '{\"active_projects\":25,\"proposals_per_day\":50,\"portfolio_items\":5,\"featured_projects\":3,\"applicant_filters\":1,\"profile_analytics\":0}', 1, 0, 2)"
    );

    Database::run(
        "INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `is_public`)
         VALUES ('site_name', 'CircleWork Africa', 'general', 'string', 1),
                ('support_email', 'support@example.com', 'general', 'string', 1),
                ('featured_price', '1000.00', 'general', 'string', 1),
                ('featured_duration_days', '14', 'general', 'string', 1)"
    );
}

cw_test_reset_database();
cw_test_seed();

App\Models\Setting::flush();
