<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\PaymentRecord;
use App\Services\AuditService;

/**
 * Administrative review of payment records reported directly between users.
 *
 * CircleWork Africa cannot recover funds transferred directly between users and
 * never claims that a reported payment has been independently verified.
 */
final class PaymentController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = $this->filters($request, ['q', 'status']);
        $total = PaymentRecord::adminCount($filters);
        $meta = PaymentRecord::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/payments/index', [
            'title'          => 'Payment records',
            'payments'       => PaymentRecord::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'statuses'       => PaymentRecord::STATUSES,
            'adminNavActive' => 'payments',
        ]);
    }

    public function show(Request $request): mixed
    {
        $paymentId = (int) ($request->route('id') ?? 0);
        $payment = PaymentRecord::detailed($paymentId);

        if ($payment === null) {
            return $this->notFound('That payment record could not be found.');
        }

        return $this->adminView('admin/payments/show', [
            'title'    => 'Payment ' . $payment['reference'],
            'payment'  => $payment,
            'evidence' => \App\Models\PaymentEvidence::forRecord($paymentId),
            'adminNavActive' => 'payments',
        ]);
    }

    public function review(Request $request): mixed
    {
        $paymentId = (int) ($request->route('id') ?? 0);
        $payment = PaymentRecord::find($paymentId);

        if ($payment === null) {
            return $this->notFound('That payment record could not be found.');
        }

        $decision = $request->string('decision');
        $note = $request->string('admin_note');

        $allowed = ['reported', 'confirmed', 'disputed', 'reversed'];

        if (!in_array($decision, $allowed, true)) {
            $this->err('Please choose a valid status.', 'admin.payments.show', ['id' => $paymentId]);
        }

        // Administrators never rewrite who paid whom; they only record a review outcome.
        PaymentRecord::update($paymentId, [
            'status'     => $decision,
            'admin_note' => $note !== '' ? mb_substr($note, 0, 500) : null,
        ]);

        AuditService::record(
            'admin.payment_reviewed',
            'Payment record marked as ' . $decision . ' by administrator review',
            'payment_record',
            $paymentId,
            ['note' => $note]
        );

        \App\Services\NotificationService::notifyMany(
            [(int) $payment['payer_id'], (int) $payment['payee_id']],
            'payment_review',
            'Payment record reviewed',
            'A payment record on your contract was reviewed and marked as ' . $decision . '.' . ($note !== '' ? ' Note: ' . $note : ''),
            route('contracts.payments', ['id' => (int) $payment['contract_id']])
        );

        $this->ok('The payment record has been updated. Administrators record review outcomes only; CircleWork cannot recover funds paid directly between users.', 'admin.payments.show', ['id' => $paymentId]);
    }
}
