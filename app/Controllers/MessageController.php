<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Contract;
use App\Models\Message;
use App\Services\MessagingService;

/**
 * Project conversations between the client and the selected freelancer.
 */
final class MessageController extends Controller
{
    public function index(Request $request): string
    {
        return $this->dashboardView('messages/index', [
            'title'         => 'Messages',
            'conversations' => Message::conversationSummary((int) Auth::id()),
        ]);
    }

    public function show(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That conversation is not available to your account.');
        }

        $userId = (int) Auth::id();

        MessagingService::markRead($contractId, $userId);

        $total = Message::countForContract($contractId);
        $meta = Message::paginateMeta($total, 40, $request->int('page', 1));

        return $this->dashboardView('messages/show', [
            'title'         => 'Conversation — ' . $contract['reference'],
            'contract'      => Contract::detailed($contractId),
            'role'          => Contract::roleOf($contract, $userId),
            'messages'      => Message::forContract($contractId, $meta['per_page'], $meta['offset']),
            'meta'          => $meta,
        ]);
    }

    public function send(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That conversation is not available to your account.');
        }

        $result = MessagingService::send($contractId, (int) Auth::id(), (string) $request->input('body', ''));

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('messages.show', ['id' => $contractId]);
    }
}
