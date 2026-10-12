<?php
/** @var array<int,string> $types */
/** @var array<int,string> $reasons */
/** @var string $type */
/** @var int $target */
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="cw-card">
            <div class="cw-card-header">Report a problem</div>
            <div class="cw-card-body">
                <p class="text-muted">
                    Reports are reviewed by an administrator and tracked with a status. Please describe what
                    happened as clearly as you can.
                </p>

                <form method="post" action="<?= e(route('report.store')) ?>" data-lock-submit data-loading-text="Submitting…">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="type">What are you reporting?</label>
                            <select class="form-select" id="type" name="type" required>
                                <?php foreach ($types as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php $field = 'type'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="target_id">Reference ID</label>
                            <input type="number" class="form-control <?= isset($errors['target']) ? 'is-invalid' : '' ?>"
                                   id="target_id" name="target_id" value="<?= (int) $target > 0 ? (int) $target : old('target_id') ?>" required>
                            <div class="form-text">
                                The numeric ID shown in the page address for the user, project, review, message or payment.
                            </div>
                            <?php $field = 'target'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="reason">Reason</label>
                            <select class="form-select" id="reason" name="reason" required>
                                <?php foreach ($reasons as $option): ?>
                                    <option value="<?= e($option) ?>" <?= old('reason') === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php $field = 'reason'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="details">What happened?</label>
                            <textarea class="form-control <?= isset($errors['details']) ? 'is-invalid' : '' ?>"
                                      id="details" name="details" rows="6" maxlength="1500" required
                                      data-counter="#details-count"><?= old('details') ?></textarea>
                            <div class="form-text d-flex justify-content-between">
                                <span>Minimum 10 characters. Include dates and amounts where relevant.</span>
                                <span id="details-count"></span>
                            </div>
                            <?php $field = 'details'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger mt-4">Submit report</button>
                </form>
            </div>
        </div>

        <div class="cw-notice cw-notice--warning mt-4">
            CircleWork Africa does not hold funds and cannot recover money transferred directly between users.
            If you believe a crime has been committed, report it to the appropriate authorities as well.
        </div>
    </div>
</div>
