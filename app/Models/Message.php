<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Message extends Model
{
    protected static string $table = 'messages';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forContract(int $contractId, int $limit, int $offset): array
    {
        return Database::select(
            'SELECT m.*, s.name AS sender_name, s.id AS sender_id
             FROM `messages` m
             INNER JOIN `users` s ON s.id = m.sender_id
             WHERE m.contract_id = ?
             ORDER BY m.created_at ASC, m.id ASC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            [$contractId]
        );
    }

    public static function countForContract(int $contractId): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM `messages` WHERE `contract_id` = ?', [$contractId]);
    }

    public static function markReadFor(int $contractId, int $userId): int
    {
        return Database::affected(
            'UPDATE `messages` SET `read_at` = UTC_TIMESTAMP()
             WHERE `contract_id` = ? AND `recipient_id` = ? AND `read_at` IS NULL',
            [$contractId, $userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `messages` WHERE `recipient_id` = ? AND `read_at` IS NULL',
            [$userId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function conversationSummary(int $userId): array
    {
        return Database::select(
            'SELECT ct.id AS contract_id, ct.reference, ct.status, p.title AS project_title,
                    CASE WHEN ct.client_id = ? THEN fl.name ELSE cl.name END AS counterparty_name,
                    (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.contract_id = ct.id) AS last_message_at,
                    (SELECT COUNT(*) FROM messages m3 WHERE m3.contract_id = ct.id AND m3.recipient_id = ? AND m3.read_at IS NULL) AS unread_count
             FROM `contracts` ct
             INNER JOIN `projects` p ON p.id = ct.project_id
             INNER JOIN `users` cl ON cl.id = ct.client_id
             INNER JOIN `users` fl ON fl.id = ct.freelancer_id
             WHERE ct.client_id = ? OR ct.freelancer_id = ?
             HAVING last_message_at IS NOT NULL OR unread_count > 0
             ORDER BY last_message_at DESC',
            [$userId, $userId, $userId, $userId]
        );
    }
}
