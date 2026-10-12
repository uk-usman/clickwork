<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

/**
 * Small base model with shared query helpers.
 *
 * All queries use prepared statements through App\Core\Database.
 */
abstract class Model
{
    protected static string $table = '';

    protected static string $primaryKey = 'id';

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::selectOne(
            'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array<string,mixed>|null
     */
    public static function findBy(array $conditions): ?array
    {
        [$where, $params] = static::buildWhere($conditions);

        return Database::selectOne(
            'SELECT * FROM `' . static::$table . '` WHERE ' . $where . ' LIMIT 1',
            $params
        );
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array<int,array<string,mixed>>
     */
    public static function allBy(array $conditions, string $orderBy = 'id DESC', ?int $limit = null): array
    {
        [$where, $params] = static::buildWhere($conditions);
        $sql = 'SELECT * FROM `' . static::$table . '` WHERE ' . $where . ' ORDER BY ' . static::safeOrder($orderBy);

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return Database::select($sql, $params);
    }

    /**
     * @param array<string,mixed> $conditions
     */
    public static function countBy(array $conditions = []): int
    {
        [$where, $params] = static::buildWhere($conditions);

        $sql = 'SELECT COUNT(*) FROM `' . static::$table . '`';
        if ($where !== '1=1') {
            $sql .= ' WHERE ' . $where;
        }

        return (int) Database::scalar($sql, $params);
    }

    /**
     * Insert a row and return the new id.
     *
     * @param array<string,mixed> $data
     */
    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = 'INSERT INTO `' . static::$table . '` (`'
            . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        return Database::insert($sql, $params);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function update(int $id, array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = '`' . $key . '` = :' . $key;
            $params[':' . $key] = $value;
        }

        $params[':pk'] = $id;

        return Database::affected(
            'UPDATE `' . static::$table . '` SET ' . implode(', ', $sets)
            . ' WHERE `' . static::$primaryKey . '` = :pk',
            $params
        );
    }

    /**
     * @param array<string,mixed> $conditions
     */
    public static function deleteBy(array $conditions): int
    {
        [$where, $params] = static::buildWhere($conditions);

        return Database::affected('DELETE FROM `' . static::$table . '` WHERE ' . $where, $params);
    }

    /**
     * Build a safe WHERE clause from a simple equality map.
     *
     * @param  array<string,mixed> $conditions
     * @return array{0:string,1:array<string,mixed>}
     */
    protected static function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['1=1', []];
        }

        $parts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $column)) {
                Logger::warning('Rejected unsafe column name in query builder', ['column' => (string) $column]);
                continue;
            }

            $key = ':' . $column;

            if ($value === null) {
                $parts[] = '`' . $column . '` IS NULL';
                continue;
            }

            $parts[] = '`' . $column . '` = ' . $key;
            $params[$key] = $value;
        }

        if ($parts === []) {
            return ['1=1', []];
        }

        return [implode(' AND ', $parts), $params];
    }

    /**
     * Whitelist ORDER BY fragments to prevent SQL injection.
     */
    protected static function safeOrder(string $orderBy): string
    {
        $allowed = [];
        foreach (explode(',', $orderBy) as $fragment) {
            $fragment = trim($fragment);
            if (preg_match('/^`?[A-Za-z_][A-Za-z0-9_]*`?( (ASC|DESC))?$/i', $fragment) === 1) {
                $allowed[] = $fragment;
            }
        }

        return $allowed === [] ? '`' . static::$primaryKey . '` DESC' : implode(', ', $allowed);
    }

    /**
     * Standard pagination metadata.
     *
     * @return array{page:int,per_page:int,total:int,last_page:int,offset:int}
     */
    public static function paginateMeta(int $total, int $perPage, int $page): array
    {
        $perPage = max(1, min($perPage, 100));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));

        return [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'last_page' => $lastPage,
            'offset'   => ($page - 1) * $perPage,
        ];
    }
}
