<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\PaymentEvidence;
use App\Models\PaymentRecord;
use App\Services\PaymentService;

/**
 * Direct payment records: reported, confirmed, disputed. No funds are held.
 */
final class PaymentController extends Controller
{
    public function index(Request $request): string
    {
        return $this->dashboardView('payments/index', [
            'title'    => 'Payment records',
            'payments' => PaymentRecord::forUser((int) Auth::id()),
        ]);
    }

    public function forContract(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        return $this->dashboardView('payments/contract', [
            'title'    => 'Payments — ' . $contract['reference'],
            'contract' => \App\Models\Contract::detailed($contractId),
            'payments' => PaymentRecord::forContract($contractId),
            'methods'  => PaymentService::METHODS,
        ]);
    }

    public function report(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $files = [];
        foreach (['evidence_1', 'evidence_2'] as $key) {
            $file = $request->file($key);
            if ($file !== null && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $files[] = $file;
            }
        }

        $result = PaymentService::report($contractId, (int) Auth::id(), [
            'amount'          => $request->input('amount', ''),
            'method'          => $request->string('method', 'bank_transfer'),
            'payer_reference' => $request->string('payer_reference'),
            'paid_on'         => $request->string('paid_on'),
            'note'            => $request->string('note'),
        ], $files);

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);

        if (!$result['ok'] && $result['errors'] !== []) {
            Session::flashErrors($result['errors']);
        }

        $this->redirectToRoute('contracts.payments', ['id' => $contractId]);
    }

    public function confirm(Request $request): mixed
    {
        $contractId = (int) ($request->route('contractId') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = PaymentService::confirm((int) ($request->route('id') ?? 0), (int) Auth::id());

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.payments', ['id' => $contractId]);
    }

    public function dispute(Request $request): mixed
    {
        $contractId = (int) ($request->route('contractId') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = PaymentService::dispute(
            (int) ($request->route('id') ?? 0),
            (int) Auth::id(),
            $request->string('reason')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.payments', ['id' => $contractId]);
    }

    /**
     * Private payment evidence download with a permission check.
     */
    public function downloadEvidence(Request $request): void
    {
        $evidenceId = (int) ($request->route('id') ?? 0);
        $evidence = PaymentEvidence::withContract($evidenceId);

        if ($evidence === null) {
            Response::status(404);
            echo 'File not found.';
            return;
        }

        $userId = Auth::id();
        $isParty = $userId !== null
            && in_array((int) $userId, [(int) $evidence['payer_id'], (int) $evidence['payee_id']], true);

        if (!$isParty && !Auth::isAdmin()) {
            Logger::security('Blocked unauthorised payment evidence download', [
                'evidence_id' => $evidenceId,
                'user_id'     => $userId,
            ]);
            Response::status(403);
            echo 'You do not have permission to download this file.';
            return;
        }

        $base = (string) App::configValue('storage.documents', '');
        $path = realpath($base . '/' . $evidence['stored_name']);
        $baseReal = realpath($base);

        if ($path === false || $baseReal === false || !str_starts_with($path, $baseReal . DIRECTORY_SEPARATOR)) {
            Response::status(404);
            echo 'File not found.';
            return;
        }

        header('Content-Type: ' . $evidence['mime_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $evidence['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($path);
    }
}
