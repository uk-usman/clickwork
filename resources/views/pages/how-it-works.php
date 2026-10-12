<?php
$heading = 'How CircleWork Africa works';
$lead = 'Every step is recorded so both parties know exactly where a project stands.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'How it works']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h4">1. Create one account</h2>
                <p class="text-muted mb-0">
                    A single CircleWork Africa account can hire freelancers and offer freelance services.
                    You choose how you mainly use the platform, and you can switch between client and
                    freelancer mode from your dashboard whenever you need to.
                </p>
            </div></div>

            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h4">2. Post a project or find work</h2>
                <p class="text-muted">
                    Clients describe the work, choose a category, set a budget range and deadline, and list
                    expected deliverables. A project can be saved as a draft and submitted for moderation when ready.
                </p>
                <p class="text-muted mb-0">
                    Freelancers browse approved public listings and filter by category, budget, deadline and skills.
                    Each freelancer submits one proposal per project, with their own price and delivery estimate.
                </p>
            </div></div>

            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h4">3. Compare proposals and hire</h2>
                <p class="text-muted mb-0">
                    The client reviews proposals, can shortlist candidates and compare bids and delivery times side
                    by side. Accepting one proposal creates a contract and automatically declines the other pending
                    proposals for that project, so two freelancers can never be awarded the same single-award project.
                </p>
            </div></div>

            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h4">4. Accept the same contract terms</h2>
                <p class="text-muted">
                    The contract records the scope, deliverables, amount, currency, deadline, revision terms,
                    payment arrangement, cancellation conditions and dispute process. Both parties review the
                    same version and record their acceptance.
                </p>
                <p class="text-muted mb-0">
                    Each acceptance stores the authenticated account, the timestamp, the accepted version and a
                    SHA-256 hash of the canonical terms. If terms change before activation, a new version is
                    created and both parties must accept again.
                </p>
            </div></div>

            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h4">5. Deliver, review and revise</h2>
                <p class="text-muted mb-0">
                    Freelancers submit deliverables with private file attachments. The client approves the work or
                    requests a revision with written feedback. Every submission is versioned, so there is a clear
                    record of what was delivered and when.
                </p>
            </div></div>

            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h4">6. Pay directly, then review</h2>
                <p class="text-muted">
                    Client and freelancer agree a payment method and pay each other directly. CircleWork Africa does
                    not hold funds, operate escrow or move money. Both parties can record what was paid, upload
                    supporting evidence and confirm or dispute the record.
                </p>
                <p class="text-muted mb-0">
                    Once the contract is completed, both parties can leave a review.
                </p>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h5">What we are</h2>
                <p class="text-muted small mb-0">
                    CircleWork Africa is a marketplace and record-keeping platform operated by
                    Circle World Technologies Africa. We connect clients and freelancers; we are not a party
                    to the work agreement and we do not employ freelancers.
                </p>
            </div></div>

            <div class="cw-notice cw-notice--warning">
                <strong>About contract enforceability.</strong>
                Recording an acceptance creates a clear record of what was agreed on this platform.
                Whether an agreement is legally enforceable depends on applicable law and the circumstances
                of each case. CircleWork Africa does not claim government certification or guaranteed legal
                protection.
            </div>

            <div class="cw-card mt-4"><div class="cw-card-body">
                <h2 class="h5">Ready to start?</h2>
                <a href="<?= e(route('register')) ?>" class="btn btn-primary w-100 mb-2">Create an account</a>
                <a href="<?= e(route('projects.index')) ?>" class="btn btn-outline-primary w-100">Browse projects</a>
            </div></div>
        </div>
    </div>
</div>
