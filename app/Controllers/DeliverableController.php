<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Contract;
use App\Models\Deliverable;
use App\Models\DeliverableFile;
use App\Services\DeliverableService;

/**
 * Deliverable submission, review and permission-checked private downloads.
 */
final class DeliverableController extends Controller
{
    public function submit(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $files = [];
        foreach ((array) $request->input('_file_keys', []) as $key) {
            $file = $request->file((string) $key);
            if ($file !== null) {
                $files[] = $file;
            }
        }

        // Fall back to a fixed set of file inputs so no client data drives file access.
        foreach (['file_1', 'file_2', 'file_3', 'file_4', 'file_5'] as $key) {
            $file = $request->file($key);
            if ($file !== null && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $files[] = $file;
            }
        }

        $result = DeliverableService::submit(
            $contractId,
            (int) Auth::id(),
            ['message' => $request->input('message', '')],
            $files
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);

        if (!$result['ok'] && $result['errors'] !== []) {
            Session::flashErrors($result['errors']);
        }

        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function approve(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = DeliverableService::approve((int) ($request->route('deliverableId') ?? 0), (int) Auth::id());

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function requestRevision(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = DeliverableService::requestRevision(
            (int) ($request->route('deliverableId') ?? 0),
            (int) Auth::id(),
            $request->string('note')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function complete(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = DeliverableService::completeContract($contractId, (int) Auth::id());

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    /**
     * Private file delivery with a permission check on every request.
     */
    public function downloadFile(Request $request): void
    {
        $fileId = (int) ($request->route('id') ?? 0);
        $file = DeliverableFile::withContract($fileId);

        if ($file === null) {
            Response::status(404);
            echo 'File not found.';
            return;
        }

        $userId = Auth::id();
        $isParticipant = $userId !== null
            && in_array((int) $userId, [(int) $file['client_id'], (int) $file['freelancer_id']], true);

        if (!$isParticipant && !Auth::isAdmin()) {
            Logger::security('Blocked unauthorised deliverable download', [
                'file_id' => $fileId,
                'user_id' => $userId,
            ]);
            Response::status(403);
            echo 'You do not have permission to download this file.';
            return;
        }

        $base = (string) App::configValue('storage.documents', '');
        $path = realpath($base . '/' . $file['stored_name']);
        $baseReal = realpath($base);

        if ($path === false || $baseReal === false || !str_starts_with($path, $baseReal . DIRECTORY_SEPARATOR)) {
            Logger::error('Deliverable file missing or unsafe path', ['file_id' => $fileId]);
            Response::status(404);
            echo 'File not found.';
            return;
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $file['original_name']) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($path);
    }
}
