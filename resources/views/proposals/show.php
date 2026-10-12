<?php
/** @var array<string,mixed> $proposal */
/** @var array<string,mixed>|null $contract */
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Proposal <?= e($proposal['reference']) ?></span>
                <span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span>
            </div>
            <div class="cw-card-body">
                <h2 class="h5"><?= e($proposal['project_title']) ?></h2>
                <p class="text-muted small">
                    Submitted <?= e(dt((string) $proposal['created_at'])) ?>
                    · Project status: <?= e(humanize((string) $proposal['project_status'])) ?>
                </p>

                <h3 class="h6 mt-4">Your cover letter</h3>
                <div style="white-space: pre-wrap;"><?= e($proposal['cover_letter']) ?></div>

                <?php if (!empty($proposal['client_note'])): ?>
                    <h3 class="h6 mt-4">Note from the client</h3>
                    <div class="cw-notice"><?= e($proposal['client_note']) ?></div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a class="btn btn-outline-primary" href="<?= e(route('projects.show', ['id' => (int) $proposal['project_id']])) ?>">View project</a>
                    <?php if ($contract !== null): ?>
                        <a class="btn btn-primary" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
                    <?php endif; ?>
                    <?php if (in_array((string) $proposal['status'], ['submitted', 'shortlisted'], true)): ?>
                        <form method="post" action="<?= e(route('freelancer.proposals.withdraw', ['id' => (int) $proposal['id']])) ?>"
                              data-confirm="Withdraw this proposal?">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger">Withdraw proposal</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Your bid</div>
            <div class="cw-card-body">
                <div class="display-6 fw-bold"><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></div>
                <p class="text-muted small mb-0">Estimated delivery: <?= (int) $proposal['estimated_days'] ?> day(s)</p>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Client</div>
            <div class="cw-card-body">
                <strong><?= e($proposal['client_name'] ?? 'Client') ?></strong>
                <p class="small text-muted mb-0">
                    Client details are shared through the contract once your proposal is accepted.
                </p>
            </div>
        </div>
    </div>
</div>
