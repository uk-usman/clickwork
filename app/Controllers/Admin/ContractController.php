<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Models\Contract;
use App\Models\ContractAcceptance;
use App\Models\ContractEvent;
use App\Models\ContractVersion;
use App\Models\Deliverable;

/**
 * Administrative contract inspection.
 *
 * Administrators can read contracts and their version history for dispute
 * review. They cannot silently rewrite accepted terms or impersonate a party.
 */
final class ContractController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $page = max(1, $request->int('page', 1));
        $perPage = 20;

        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], Contract::STATUSES, true)) {
            $conditions[] = 'ct.status = ?';
            $params[] = $filters['status'];
        }

        $where = implode(' AND ', $conditions);
        $total = (int) Database::scalar("SELECT COUNT(*) FROM `contracts` ct WHERE $where", $params);
        $meta = Contract::paginateMeta($total, $perPage, $page);

        $contracts = Database::select(
            "SELECT ct.*, p.title AS project_title, cl.name AS client_name, fl.name AS freelancer_name
             FROM `contracts` ct
             INNER JOIN `projects` p ON p.id = ct.project_id
             INNER JOIN `users` cl ON cl.id = ct.client_id
             INNER JOIN `users` fl ON fl.id = ct.freelancer_id
             WHERE $where
             ORDER BY ct.created_at DESC
             LIMIT " . $meta['per_page'] . ' OFFSET ' . $meta['offset'],
            $params
        );

        return $this->adminView('admin/contracts/index', [
            'title'          => 'Contracts',
            'contracts'      => $contracts,
            'meta'           => $meta,
            'filters'        => $filters,
            'statuses'       => Contract::STATUSES,
            'adminNavActive' => 'contracts',
        ]);
    }

    public function show(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = Contract::detailed($contractId);

        if ($contract === null) {
            return $this->notFound('That contract could not be found.');
        }

        return $this->adminView('admin/contracts/show', [
            'title'          => 'Contract ' . $contract['reference'],
            'contract'       => $contract,
            'versions'       => ContractVersion::history($contractId),
            'acceptances'    => ContractAcceptance::forVersion($contractId, (int) $contract['current_version']),
            'events'         => ContractEvent::timeline($contractId, 50),
            'deliverables'   => Deliverable::forContract($contractId),
            'payments'       => \App\Models\PaymentRecord::forContract($contractId),
            'disputes'       => \App\Models\Dispute::forContract($contractId),
            'adminNavActive' => 'contracts',
        ]);
    }
}
