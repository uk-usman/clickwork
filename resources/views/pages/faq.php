<?php
$heading = 'Frequently asked questions';
$lead = 'Straight answers about accounts, contracts, payments and reviews.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'FAQ']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';

$faqs = [
    ['Do I need two accounts to hire and to freelance?', 'No. One account can act as a client, a freelancer, or both. You choose your default mode at registration and can switch between client and freelancer mode from your dashboard at any time.'],
    ['Is it free to use?', 'Yes. Basic client and freelancer accounts are free and include profile creation, project browsing, proposal submission, basic project posting and messaging. Optional paid plans add capacity such as more active projects, enhanced profile display and additional portfolio items.'],
    ['Who reviews projects before they go live?', 'An administrator reviews every project submitted for moderation. Only approved public projects appear in public listings. You can save a project as a draft and submit it when you are ready.'],
    ['Can two freelancers be hired for the same project?', 'No. Each project is single-award. When a client accepts one proposal, the contract is created inside a database transaction and all other pending proposals for that project are automatically rejected.'],
    ['How does the contract work?', 'Accepting a proposal generates a contract containing the scope, deliverables, amount, currency, deadline, revision terms, payment arrangement, cancellation conditions and dispute process. Both parties accept the same version. Each acceptance records the authenticated account, the timestamp, the version and a SHA-256 hash of the canonical terms.'],
    ['Can contract terms be changed later?', 'Accepted terms are never edited in place. If terms need to change, a new version is created and both parties must accept it again. A completed or cancelled contract cannot be amended.'],
    ['Does CircleWork Africa hold my money?', 'No. We do not hold funds, operate escrow, move money between users or automatically pay freelancers. Client and freelancer agree a payment method and pay each other directly.'],
    ['What does “Reported” mean on a payment record?', 'It means one party recorded a payment. It is not verified. A record stays “Reported” until the other party confirms it, and either party can mark it as disputed for administrative review. We never claim to have independently verified a bank transfer.'],
    ['Can CircleWork recover money if a payment goes wrong?', 'No. Because payments happen directly between users, we cannot recover funds. We can record the issue, review it, and keep a documented history, and we can suspend accounts that break our community guidelines.'],
    ['When can I leave a review?', 'Reviews open once a contract is completed or cancelled. Only the client and the freelancer on that contract can review each other, only once each, and never themselves. Reviews can be reported and are moderated by an administrator.'],
    ['How are premium features enforced?', 'On the server. Plan features and limits are read from the database for the signed-in account. Changing a URL or a hidden form field cannot unlock a premium feature.'],
    ['Is a contract on CircleWork legally binding?', 'Recording an acceptance creates a clear record of what was agreed here. Whether an agreement is legally enforceable depends on applicable law and the circumstances of each case. We do not claim government certification or guaranteed legal protection.'],
];
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="accordion" id="faqAccordion">
                <?php foreach ($faqs as $index => [$question, $answer]): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqHeading<?= $index ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faqBody<?= $index ?>"
                                    aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                    aria-controls="faqBody<?= $index ?>">
                                <?= e($question) ?>
                            </button>
                        </h2>
                        <div id="faqBody<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                             aria-labelledby="faqHeading<?= $index ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted"><?= e($answer) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cw-card mt-4"><div class="cw-card-body text-center">
                <h2 class="h5">Still need help?</h2>
                <p class="text-muted">Our support pages and reporting form are the fastest route to a tracked answer.</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="<?= e(route('contact')) ?>" class="btn btn-primary">Contact and help</a>
                    <a href="<?= e(route('report.create')) ?>" class="btn btn-outline-primary">Report a problem</a>
                </div>
            </div></div>
        </div>
    </div>
</div>
