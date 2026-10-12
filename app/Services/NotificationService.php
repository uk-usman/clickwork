<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * In-app notifications (and optional email) for platform events.
 */
final class NotificationService
{
    /**
     * @param array<string,mixed> $data
     */
    public static function notify(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?string $link = null,
        array $data = [],
        bool $sendEmail = false
    ): void {
        Notification::push($userId, $type, $title, $body, $link, $data);

        if ($sendEmail) {
            $user = User::find($userId);
            if ($user !== null && $user['status'] === 'active') {
                Mailer::send(
                    (string) $user['email'],
                    (string) $user['name'],
                    $title,
                    $body . ($link !== null ? "\n\n" . app_url($link) : '')
                );
            }
        }
    }

    /**
     * @param array<int,int> $userIds
     */
    public static function notifyMany(array $userIds, string $type, string $title, string $body = '', ?string $link = null): void
    {
        foreach (array_unique($userIds) as $userId) {
            self::notify((int) $userId, $type, $title, $body, $link);
        }
    }
}
