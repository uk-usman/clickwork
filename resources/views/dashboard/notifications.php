<?php
/** @var array<int,array<string,mixed>> $notifications */
/** @var array{page:int,last_page:int,total:int} $meta */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><strong><?= (int) $meta['total'] ?></strong> notification(s)</p>
    <form method="post" action="<?= e(route('account.notifications.read_all')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline-primary">Mark all as read</button>
    </form>
</div>

<?php if ($notifications === []): ?>
    <div class="cw-empty">
        <i class="bi bi-bell-slash"></i>
        <h2 class="h6 mt-3">No notifications yet</h2>
        <p class="text-muted mb-0">You will be notified about proposals, contracts, messages, deliverables and payment updates.</p>
    </div>
<?php else: ?>
    <div class="cw-card">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
                <?php $unread = ($notification['read_at'] ?? null) === null; ?>
                <div class="list-group-item d-flex justify-content-between align-items-start gap-3 <?= $unread ? 'bg-light' : '' ?>">
                    <div class="min-w-0">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($unread): ?>
                                <span class="badge text-bg-primary">New</span>
                            <?php endif; ?>
                            <strong><?= e($notification['title']) ?></strong>
                        </div>
                        <?php if (!empty($notification['body'])): ?>
                            <div class="text-muted small mt-1"><?= e($notification['body']) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-clock"></i> <?= e(dt((string) $notification['created_at'])) ?>
                            <span class="ms-2"><i class="bi bi-tag"></i> <?= e(humanize((string) $notification['type'])) ?></span>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1 align-items-end">
                        <?php if (!empty($notification['link'])): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e((string) $notification['link']) ?>">Open</a>
                        <?php endif; ?>
                        <?php if ($unread): ?>
                            <form method="post" action="<?= e(route('account.notifications.read', ['id' => (int) $notification['id']])) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-link text-muted">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
<?php endif; ?>
