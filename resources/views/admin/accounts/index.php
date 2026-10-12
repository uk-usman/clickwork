<?php
/** @var array<int,array<string,mixed>> $accounts */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var string $kind */
/** @var string $search */
?>
<form method="get" action="<?= e(route($kind === 'freelancer' ? 'admin.freelancers' : 'admin.clients')) ?>" class="row g-2 align-items-end mb-4">
    <div class="col-md-4">
        <label class="form-label small" for="q">Search name or email</label>
        <input type="search" class="form-control form-control-sm" id="q" name="q" value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-sm btn-primary">Search</button>
        <a class="btn btn-sm btn-link" href="<?= e(route($kind === 'freelancer' ? 'admin.freelancers' : 'admin.clients')) ?>">Reset</a>
    </div>
</form>

<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Name</th><th>Email</th><th>Status</th><th>Location</th>
                    <?php if ($kind === 'freelancer'): ?>
                        <th>Title</th><th>Rating</th><th>Completed</th><th>Visibility</th>
                    <?php endif; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><strong><?= e((string) $account['name']) ?></strong></td>
                    <td class="small"><?= e((string) $account['email']) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $account['status'])) ?>"><?= e(humanize((string) $account['status'])) ?></span></td>
                    <td class="small"><?= e(trim((string) ($account['city'] ?? '') . ', ' . (string) ($account['state'] ?? ''), ', ') ?: '—') ?></td>
                    <?php if ($kind === 'freelancer'): ?>
                        <td class="small"><?= e((string) ($account['freelancer_title'] ?? '—')) ?></td>
                        <td class="small"><?= e(number_format((float) ($account['rating_average'] ?? 0), 1)) ?> (<?= (int) ($account['rating_count'] ?? 0) ?>)</td>
                        <td class="small"><?= (int) ($account['completed_contracts'] ?? 0) ?></td>
                        <td class="small"><?= e(humanize((string) ($account['profile_visibility'] ?? 'public'))) ?></td>
                    <?php endif; ?>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.users.show', ['id' => (int) $account['id']])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($accounts === []): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No accounts found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
