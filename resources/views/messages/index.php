<?php /** @var array<int,array<string,mixed>> $conversations */ ?>
<?php if ($conversations === []): ?>
    <div class="cw-empty">
        <i class="bi bi-chat-dots"></i>
        <h2 class="h6 mt-3">No conversations yet</h2>
        <p class="text-muted mb-0">
            Messaging opens on a contract, so that only the client and the selected freelancer can read the
            conversation. Contracts are created when a proposal is accepted.
        </p>
    </div>
<?php else: ?>
    <div class="cw-card">
        <div class="list-group list-group-flush">
            <?php foreach ($conversations as $conversation): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3"
                   href="<?= e(route('messages.show', ['id' => (int) $conversation['contract_id']])) ?>">
                    <div class="min-w-0">
                        <strong><?= e($conversation['counterparty_name']) ?></strong>
                        <div class="small text-muted"><?= e(str_excerpt((string) $conversation['project_title'], 50)) ?></div>
                        <div class="small text-muted">
                            <?= e($conversation['reference']) ?> ·
                            last message <?= e(dt($conversation['last_message_at'] ?? null)) ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge <?= e(status_badge_class((string) $conversation['status'])) ?>"><?= e(humanize((string) $conversation['status'])) ?></span>
                        <?php if ((int) $conversation['unread_count'] > 0): ?>
                            <div><span class="badge rounded-pill bg-danger mt-1"><?= (int) $conversation['unread_count'] ?> new</span></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
