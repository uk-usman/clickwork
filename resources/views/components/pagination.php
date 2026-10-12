<?php
/** @var array{page:int,per_page:int,total:int,last_page:int} $meta */
$meta = $meta ?? ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1];
$query = $_GET;

if ($meta['last_page'] <= 1) {
    return;
}

$pageUrl = static function (int $page) use ($query): string {
    $query['page'] = $page;
    return App\Core\Request::capture()->path() . '?' . http_build_query($query);
};

$start = max(1, $meta['page'] - 2);
$end = min($meta['last_page'], $start + 4);
$start = max(1, $end - 4);
?>
<nav class="mt-4" aria-label="Pagination">
    <ul class="pagination justify-content-center mb-1">
        <li class="page-item <?= $meta['page'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($pageUrl(max(1, $meta['page'] - 1))) ?>">Previous</a>
        </li>
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $meta['page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $meta['page'] >= $meta['last_page'] ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($pageUrl(min($meta['last_page'], $meta['page'] + 1))) ?>">Next</a>
        </li>
    </ul>
    <p class="text-center text-muted small mb-0">
        Showing page <?= (int) $meta['page'] ?> of <?= (int) $meta['last_page'] ?> (<?= (int) $meta['total'] ?> total)
    </p>
</nav>
