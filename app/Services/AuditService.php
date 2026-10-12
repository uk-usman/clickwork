<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Request;
use App\Models\AuditLog;

/**
 * Central audit trail for sensitive actions.
 */
final class AuditService
{
    /**
     * @param array<string,mixed> $metadata
     */
    public static function record(
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?int $actorId = null,
        ?string $actorRole = null
    ): void {
        $actorId ??= Auth::id();
        $actorRole ??= Auth::role();

        $ip = null;
        $ua = null;

        if (PHP_SAPI !== 'cli') {
            try {
                $request = Request::capture();
                $ip = $request->ip();
                $ua = $request->userAgent();
            } catch (\Throwable) {
                // ignore — auditing must never break the request
            }
        }

        AuditLog::record($actorId, $actorRole, $action, $description, $entityType, $entityId, $metadata, $ip, $ua);
    }
}
