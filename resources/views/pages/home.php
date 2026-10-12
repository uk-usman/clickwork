<?php
/** @var array<int,array<string,mixed>> $categories */
/** @var array<int,array<string,mixed>> $recentProjects */
/** @var array<int,array<string,mixed>> $featuredProjects */
/** @var array<int,array<string,mixed>> $featuredFreelancers */
/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $topSkills */
$city = setting('marketing_city', 'Kano');
$state = setting('marketing_state', 'Kano');
?>

<section class="cw-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="cw-hero__badge mb-3">Circle World Technologies Africa</span>
                <h1 class="mb-3">Find Work. Hire Talent. Build Trust.</h1>
                <p class="lead mb-4">
                    CircleWork Africa connects clients and skilled freelancers across Nigeria.
                    Post a project, compare proposals, agree clear contract terms and record
                    direct payments — all in one place.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="<?= e(route('freelancers.index')) ?>" class="btn btn-light btn-lg">
                        <i class="bi bi-search me-2"></i>Find Work
                    </a>
                    <a href="<?= e(route('register')) ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus me-2"></i>Hire Talent
                    </a>
                </div>

                <div class="row row-cols-3 g-3 text-center">
                    <div class="col">
                        <div class="h4 mb-0 text-white"><?= (int) ($stats['published_projects'] ?? 0) ?></div>
                        <div class="small text-white-50">Open projects</div>
                    </div>
                    <div class="col">
                        <div class="h4 mb-0 text-white"><?= (int) ($stats['freelancers'] ?? 0) ?></div>
                        <div class="small text-white-50">Public freelancers</div>
                    </div>
                    <div class="col">
                        <div class="h4 mb-0 text-white"><?= (int) ($stats['categories'] ?? 0) ?></div>
                        <div class="small text-white-50">Categories</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="cw-hero__panel">
                    <h2 class="h5 mb-3">Search open projects</h2>
                    <form method="get" action="<?= e(route('projects.index')) ?>" class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="hero-q">Keyword</label>
                            <input type="search" class="form-control" id="hero-q" name="q"
                                   value="<?= e($searchQuery ?? '') ?>" placeholder="e.g. logo design, data entry">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="hero-category">Category</label>
                            <select class="form-select" id="hero-category" name="category">
                                <option value="">All categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i>Search projects
                            </button>
                        </div>
                    </form>
                    <p class="small text-muted mb-0 mt-3">
                        Initial focus area: <strong><?= e($city . ', ' . $state) ?></strong>. Online work is welcome nationwide.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($featuredProjects !== []): ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h4 mb-1"><i class="bi bi-star-fill text-primary me-2"></i>Featured projects</h2>
                <p class="text-muted mb-0">Paid featured placements, clearly labelled.</p>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProjects as $project): ?>
                <div class="col-md-6 col-lg-4">
                    <?php require CIRCLEWORK_ROOT . '/resources/views/components/project-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3">Popular freelance categories</h2>
            <p class="text-muted">Find the skill you need, or the work you want.</p>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col">
                    <a class="cw-category-card" href="<?= e(route('projects.index', ['category' => (int) $category['id']])) ?>">
                        <i class="bi <?= e($category['icon'] ?: 'bi-briefcase') ?>"></i>
                        <div class="fw-semibold mt-2"><?= e($category['name']) ?></div>
                        <div class="small text-muted"><?= (int) ($category['project_count'] ?? 0) ?> project(s)</div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h4 mb-1">Recently published projects</h2>
                <p class="text-muted mb-0">Approved by moderation and open for proposals.</p>
            </div>
            <a href="<?= e(route('projects.index')) ?>" class="btn btn-outline-primary btn-sm">View all</a>
        </div>

        <?php if ($recentProjects === []): ?>
            <div class="cw-empty">
                <i class="bi bi-inbox"></i>
                <h3 class="h6 mt-3">No published projects yet</h3>
                <p class="text-muted mb-3">Be the first to post a project on CircleWork Africa.</p>
                <a href="<?= e(route('register')) ?>" class="btn btn-primary">Post a project</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($recentProjects as $project): ?>
                    <div class="col-md-6 col-lg-4">
                        <?php require CIRCLEWORK_ROOT . '/resources/views/components/project-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="h4 mb-1">Featured freelancer profiles</h2>
                <p class="text-muted mb-0">Freelancers who have chosen a public profile.</p>
            </div>
            <a href="<?= e(route('freelancers.index')) ?>" class="btn btn-outline-primary btn-sm">Browse all</a>
        </div>

        <?php if ($featuredFreelancers === []): ?>
            <div class="cw-empty">
                <i class="bi bi-person-plus"></i>
                <h3 class="h6 mt-3">No public freelancer profiles yet</h3>
                <p class="text-muted mb-3">Create a freelancer profile to appear here.</p>
                <a href="<?= e(route('register')) ?>" class="btn btn-primary">Create a profile</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($featuredFreelancers as $freelancer): ?>
                    <div class="col-md-6 col-lg-3">
                        <?php require CIRCLEWORK_ROOT . '/resources/views/components/freelancer-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3">How CircleWork Africa works</h2>
            <p class="text-muted">A clear, documented path from first contact to completed work.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="cw-card h-100"><div class="cw-card-body">
                    <div class="cw-stat__icon mb-3"><i class="bi bi-1-circle"></i></div>
                    <h3 class="h6">Post or find work</h3>
                    <p class="text-muted small mb-0">Clients publish a project. Freelancers browse approved listings and submit a proposal with a bid and delivery estimate.</p>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="cw-card h-100"><div class="cw-card-body">
                    <div class="cw-stat__icon mb-3"><i class="bi bi-2-circle"></i></div>
                    <h3 class="h6">Agree on a contract</h3>
                    <p class="text-muted small mb-0">The client accepts one proposal. A contract is generated from the project and proposal, and both parties accept the same version.</p>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="cw-card h-100"><div class="cw-card-body">
                    <div class="cw-stat__icon mb-3"><i class="bi bi-3-circle"></i></div>
                    <h3 class="h6">Deliver and review</h3>
                    <p class="text-muted small mb-0">Freelancers submit deliverables privately. Clients approve the work or request a revision with clear feedback.</p>
                </div></div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="cw-card h-100"><div class="cw-card-body">
                    <div class="cw-stat__icon mb-3"><i class="bi bi-4-circle"></i></div>
                    <h3 class="h6">Pay directly and review</h3>
                    <p class="text-muted small mb-0">Payment is made directly between client and freelancer. Both record it, and reviews unlock once the contract is completed.</p>
                </div></div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= e(route('how-it-works')) ?>" class="btn btn-outline-primary">Read the full process</a>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="h3 mb-3">Trust and safety</h2>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                        <div>
                            <strong>Moderated listings.</strong>
                            <p class="text-muted mb-0 small">Every project is reviewed by an administrator before it becomes publicly visible.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-file-earmark-lock text-success fs-4"></i>
                        <div>
                            <strong>Versioned contract terms.</strong>
                            <p class="text-muted mb-0 small">Both parties accept the same version, and the accepted terms are stored with a SHA-256 hash, timestamp and account identity.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-cash-stack text-success fs-4"></i>
                        <div>
                            <strong>Direct payments, honestly labelled.</strong>
                            <p class="text-muted mb-0 small">CircleWork Africa does not hold funds or operate escrow. A recorded payment stays marked as “Reported” until the other party confirms it.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-flag text-success fs-4"></i>
                        <div>
                            <strong>Reports and disputes.</strong>
                            <p class="text-muted mb-0 small">Suspicious users, projects or payments can be reported and are tracked to resolution.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="cw-card">
                    <div class="cw-card-header">Popular skills on the platform</div>
                    <div class="cw-card-body">
                        <?php if ($topSkills === []): ?>
                            <p class="text-muted mb-0">Skills will appear once an administrator adds them.</p>
                        <?php else: ?>
                            <?php foreach ($topSkills as $skill): ?>
                                <a class="cw-chip" href="<?= e(route('projects.index', ['skill' => (int) $skill['id']])) ?>">
                                    <?= e($skill['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cw-notice cw-notice--warning mt-4">
                    <strong>Important:</strong>
                    CircleWork Africa is a marketplace and record-keeping platform. We do not employ
                    freelancers, guarantee outcomes, or provide legal certification of contracts.
                    Whether an agreement is legally enforceable depends on applicable law and the
                    circumstances of each case.
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="cw-card h-100" style="border-top: 4px solid var(--cw-royal-500);">
                    <div class="cw-card-body">
                        <h2 class="h4">For clients</h2>
                        <p class="text-muted">Post a project, compare real proposals, and keep every decision documented.</p>
                        <ul class="small text-muted">
                            <li>Free basic client account</li>
                            <li>Shortlist and compare bids and delivery times</li>
                            <li>Approve deliverables or request revisions</li>
                            <li>Optional featured placement for a project</li>
                        </ul>
                        <a href="<?= e(route('register')) ?>" class="btn btn-primary">Create a client account</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="cw-card h-100" style="border-top: 4px solid var(--cw-success);">
                    <div class="cw-card-body">
                        <h2 class="h4">For freelancers</h2>
                        <p class="text-muted">Build a public profile, win work and get reviewed for it.</p>
                        <ul class="small text-muted">
                            <li>Free basic freelancer account</li>
                            <li>Portfolio, skills and availability</li>
                            <li>Submit proposals with your own price and timeline</li>
                            <li>Clear contract terms and revision process</li>
                        </ul>
                        <a href="<?= e(route('register')) ?>" class="btn btn-navy">Create a freelancer account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
