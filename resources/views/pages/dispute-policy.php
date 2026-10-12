<?php
$heading = 'Dispute and Cancellation Policy';
$lead = 'What happens when a project does not go as planned, and what we can and cannot do about it.';
$updated = '12 October 2026';
$sections = [
    ['title' => 'Talk to each other first', 'body' => [
        'Most disagreements are about scope, timing or expectations. Use the project conversation to clarify what was agreed, and refer to the contract terms both parties accepted.',
        'Revision requests are the intended mechanism for work that needs adjustment within the agreed revision terms.',
    ]],
    ['title' => 'Cancelling a contract', 'body' => [
        'Either party may request cancellation before work begins, and the cancellation conditions recorded in the contract apply.',
        'Cancellation is recorded with the reason, the requesting account and a timestamp, and the counterparty is notified.',
        'Cancelling a contract does not delete the contract, its accepted terms, its deliverables or any payment records. Those records are retained.',
    ]],
    ['title' => 'Opening a dispute', 'body' => [
        'A party to a contract may open a dispute with a subject and a written description of at least 20 characters. Only one dispute can be open per contract at a time.',
        'The dispute is given a reference, and the counterparty is notified. Both parties and administrators can add messages to the dispute thread.',
        'Administrators may record internal notes that are not shown to the parties.',
    ]],
    ['title' => 'Payment disputes', 'body' => [
        'Either party may mark a payment record as disputed and give a reason. The record then appears in the administrative review queue.',
        'An administrator reviews the recorded details and any uploaded evidence and records an outcome: reported, confirmed, disputed or reversed, with a note.',
        'A reversed record does not move money. It is a record-keeping outcome only.',
    ]],
    ['title' => 'What CircleWork Africa can do', 'body' => [
        'Keep an accurate, timestamped record of what was agreed, delivered, reviewed and reported.',
        'Review reports and disputes and record a documented outcome.',
        'Hide or remove content that breaches our guidelines, and suspend or close accounts that do.',
        'Provide the record to the parties and, where legally required, to the appropriate authorities.',
    ]],
    ['title' => 'What CircleWork Africa cannot do', 'body' => [
        'We cannot recover money transferred directly between users, because we never hold or move those funds.',
        'We cannot compel a freelancer to complete work or a client to pay.',
        'We cannot verify that a bank transfer occurred, and we never claim to have done so.',
        'We cannot provide legal advice or guarantee that an agreement is legally enforceable.',
    ]],
    ['title' => 'Serious cases', 'body' => [
        'If you believe a crime has been committed, report it to the appropriate Nigerian authorities. We will cooperate with lawful requests and preserve relevant records.',
        'If you believe a user has committed fraud, report it here as well so we can suspend the account while we review the evidence.',
    ]],
];
require CIRCLEWORK_ROOT . '/resources/views/components/legal-page.php';
