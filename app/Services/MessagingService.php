<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Models\Contract;
use App\Models\Message;

/**
 * Project/contract messaging. Only contract participants may read or write.
 */
final class MessagingService
{
    /**
     * @return array{ok:bool,message:string}
     */
    public static function send(int $contractId, int $senderId, string $body): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }

        $role = Contract::roleOf($contract, $senderId);

        if ($role === null) {
            Logger::security('Blocked message to unrelated contract', ['contract_id' => $contractId, 'user_id' => $senderId]);
            return ['ok' => false, 'message' => 'You are not a participant in this project conversation.'];
        }

        $body = trim($body);

        if ($body === '') {
            return ['ok' => false, 'message' => 'Please enter a message.'];
        }

        if (mb_strlen($body) > 5000) {
            return ['ok' => false, 'message' => 'Messages must not exceed 5000 characters.'];
        }

        $recipientId = Contract::counterpartyId($contract, $senderId);

        Message::create([
            'contract_id'  => $contractId,
            'project_id'   => (int) $contract['project_id'],
            'sender_id'    => $senderId,
            'recipient_id' => $recipientId,
            'body'         => $body,
        ]);

        NotificationService::notify(
            $recipientId,
            'new_message',
            'New message',
            'You have a new message about contract ' . $contract['reference'] . '.',
            route('messages.show', ['id' => $contractId])
        );

        return ['ok' => true, 'message' => 'Message sent.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function markRead(int $contractId, int $userId): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null || Contract::roleOf($contract, $userId) === null) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }

        Message::markReadFor($contractId, $userId);

        return ['ok' => true, 'message' => 'Marked as read.'];
    }
}
