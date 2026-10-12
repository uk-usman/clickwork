<?php
/** @var array<int,array<string,mixed>> $contracts */
/** @var string $role */
?>
<?php if ($contracts === []): ?>
    <div class="cw-empty">
        <i class="bi bi-file-earmark-text"></i>
        <h2 class="h6 mt-3">No contracts yet</h2>
        <p class="text-muted mb-3">
            <?= $role === 'client'
                ? 'Contracts are created when you accept a proposal on one of your projects.'
                : 'Contracts appear here when a client accepts one of your proposals.' ?>
        </p>
        <?php if ($role === 'client'): ?>
            <a href="<?= e(route('client.projects')) ?>" class="btn btn-primary">View my projects</a>
        <?php else: ?>
            <a href="<?= e(route('projects.index')) ?>" class="btn btn-primary">Browse projects</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($contracts as $contract): ?>
            <div class="col-lg-6">
                <div class="cw-card h-100">
                    <div class="cw-card-header d-flex justify-content-between align-items-center gap-2">
                        <span><?= e($contract['reference']) ?></span>
                        <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
                    </div>
                    <div class="cw-card-body">
                        <h2 class="h6"><?= e(str_excerpt((string) $contract['title'], 70)) ?></h2>
                        <dl class="row g-2 small mb-3">
                            <dt class="col-6 text-muted fw-normal"><?= $role === 'client' ? 'Freelancer' : 'Client' ?></dt>
                            <dd class="col-6 text-end"><?= e($role === 'client' ? $contract['freelancer_name'] : $contract['client_name']) ?></dd>
                            <dt class="col-6 text-muted fw-normal">Agreed amount</dt>
                            <dd class="col-6 text-end fw-semibold"><?= e(money($contract['amount'], (string) $contract['currency'])) ?></dd>
                            <dt class="col-6 text-muted fw-normal">Deadline</dt>
                            <dd class="col-6 text-end"><?= ($contract['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $contract['deadline']))) : 'Flexible' ?></dd>
                            <dt class="col-6 text-muted fw-normal">Deliverables</dt>
                            <dd class="col-6 text-end"><?= (int) ($contract['deliverable_count'] ?? 0) ?></dd>
                        </dl>
                        <a class="btn btn-primary w-100" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
