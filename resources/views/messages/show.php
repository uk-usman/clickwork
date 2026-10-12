<?php
/** @var array<string,mixed> $contract */
/** @var string $role */
/** @var array<int,array<string,mixed>> $messages */
/** @var array{page:int,last_page:int,total:int} $meta */
$currentUserId = (int) App\Core\Auth::id();
?>
<div class="cw-card mb-3">
    <div class="cw-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 mb-1"><?= e($contract['title']) ?></h2>
            <p class="text-muted small mb-0">
                Contract <?= e($contract['reference']) ?> ·
                with <?= e($role === 'client' ? $contract['freelancer_name'] : $contract['client_name']) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('messages.index')) ?>">All conversations</a>
        </div>
    </div>
</div>

<div class="cw-card">
    <div class="cw-card-body" style="max-height:520px;overflow-y:auto;" id="messageThread">
        <?php if ($messages === []): ?>
            <p class="text-muted text-center my-4">No messages yet. Start the conversation below.</p>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php $mine = (int) $message['sender_id'] === $currentUserId; ?>
                <div class="d-flex mb-3 <?= $mine ? 'justify-content-end' : '' ?>">
                    <div class="p-3 rounded <?= $mine ? 'text-bg-primary' : 'bg-light' ?>" style="max-width:75%;">
                        <div class="small fw-semibold mb-1">
                            <?= $mine ? 'You' : e($message['sender_name']) ?>
                        </div>
                        <div style="white-space: pre-wrap;"><?= e($message['body']) ?></div>
                        <div class="small <?= $mine ? 'text-white-50' : 'text-muted' ?> mt-1">
                            <?= e(dt((string) $message['created_at'])) ?>
                            <?php if ($mine && ($message['read_at'] ?? null) !== null): ?>
                                · Read
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cw-card-body border-top">
        <form method="post" action="<?= e(route('messages.send', ['id' => (int) $contract['id']])) ?>" data-lock-submit data-loading-text="Sending…">
            <?= csrf_field() ?>
            <div class="mb-2">
                <label class="form-label" for="body">New message</label>
                <textarea class="form-control" id="body" name="body" rows="3" maxlength="5000" required
                          placeholder="Keep project communication on the platform so there is a record if something goes wrong."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send message</button>
            <span class="form-text ms-2">Only you and the other party on this contract can read this conversation.</span>
        </form>
    </div>
</div>

<?php if ($meta['last_page'] > 1): ?>
    <?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
<?php endif; ?>
