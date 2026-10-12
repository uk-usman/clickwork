<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Single PDO connection factory. One connection per request (lazy, shared).
 */
final class Database
{
    private static ?PDO $connection = null;

    /** @var array<string,mixed> */
    private static array $config = [];

    private static bool $inTransaction = false;

    /**
     * @param array<string,mixed> $config
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        if (self::$config === []) {
            throw new RuntimeException('Database configuration has not been provided.');
        }

        $host = (string) (self::$config['host'] ?? '127.0.0.1');
        $port = (int) (self::$config['port'] ?? 3306);
        $name = (string) (self::$config['database'] ?? '');
        $charset = (string) (self::$config['charset'] ?? 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$connection = new PDO(
                $dsn,
                (string) (self::$config['username'] ?? ''),
                (string) (self::$config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                    PDO::ATTR_PERSISTENT         => false,
                    // Store and read timestamps in UTC regardless of server locale.
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'",
                ]
            );
        } catch (PDOException $e) {
            // Never leak credentials or DSN details to the browser.
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException(
                'Unable to connect to the database. Please check your configuration.',
                0,
                $e
            );
        }

        return self::$connection;
    }

    /**
     * Run a callback inside a database transaction.
     *
     * Nested calls reuse the outer transaction (savepoint-free simple strategy):
     * only the outermost scope commits or rolls back.
     *
     * @template T
     * @param  callable():T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();

        if (self::$inTransaction) {
            return $callback();
        }

        $pdo->beginTransaction();
        self::$inTransaction = true;

        try {
            $result = $callback();
            $pdo->commit();
            self::$inTransaction = false;

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            self::$inTransaction = false;

            throw $e;
        }
    }

    public static function inTransaction(): bool
    {
        return self::$inTransaction;
    }

    /**
     * Execute a prepared statement and return the statement.
     *
     * @param array<string|int,mixed> $params
     */
    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @param array<string|int,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int,mixed> $params
     */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * @param array<string|int,mixed> $params
     */
    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * @param array<string|int,mixed> $params
     */
    public static function affected(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    /**
     * Reset the shared connection (used by tests).
     */
    public static function reset(): void
    {
        self::$connection = null;
        self::$inTransaction = false;
    }
}
