<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Notification extends Model
{
    protected static string $table = 'notifications';

    /**
     * @param array<string,mixed> $data
     */
    public static function push(int $userId, string $type, string $title, string $body = '', ?string $link = null, array $data = []): int
    {
        return self::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body !== '' ? $body : null,
            'link'    => $link,
            'data'    => $data === [] ? null : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId, int $limit = 30, int $offset = 0): array
    {
        return Database::select(
            'SELECT * FROM `notifications` WHERE `user_id` = ? ORDER BY `created_at` DESC, `id` DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            [$userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read_at` IS NULL',
            [$userId]
        );
    }

    public static function markAllRead(int $userId): int
    {
        return Database::affected(
            'UPDATE `notifications` SET `read_at` = UTC_TIMESTAMP() WHERE `user_id` = ? AND `read_at` IS NULL',
            [$userId]
        );
    }

    public static function markRead(int $id, int $userId): int
    {
        return Database::affected(
            'UPDATE `notifications` SET `read_at` = UTC_TIMESTAMP() WHERE `id` = ? AND `user_id` = ? AND `read_at` IS NULL',
            [$id, $userId]
        );
    }
}
