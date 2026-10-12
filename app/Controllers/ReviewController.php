<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Services\ReviewService;

/**
 * Reviews for completed contracts.
 */
final class ReviewController extends Controller
{
    public function store(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = ReviewService::create(
            $contractId,
            (int) Auth::id(),
            $request->int('rating'),
            $request->string('comment')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);

        if (!$result['ok'] && $result['errors'] !== []) {
            Session::flashErrors($result['errors']);
        }

        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }
}
