<?php
$heading = 'Terms of Service';
$lead = 'These terms describe how CircleWork Africa, operated by Circle World Technologies Africa, may be used.';
$updated = '12 October 2026';
$sections = [
    ['title' => 'Who may use the platform', 'body' => [
        'You must be at least 18 years old and able to enter into binding agreements to create an account.',
        'You must provide accurate registration details and keep your password confidential. You are responsible for activity carried out through your account.',
        'Public registration is only available for standard user accounts. Administrator and super administrator accounts are provisioned separately and are never created through the public registration page.',
    ]],
    ['title' => 'Your account and roles', 'body' => [
        'A single account may act as a client, a freelancer, or both. You choose a default mode and may switch modes at any time.',
        'You may not create multiple accounts to evade a suspension, a limit, or a moderation decision.',
        'We may suspend or close an account that breaches these terms, our Community Guidelines, or applicable law. Where we do, we record the reason and notify the account holder where it is safe and appropriate to do so.',
    ]],
    ['title' => 'Projects, proposals and contracts', 'body' => [
        'Clients are responsible for the accuracy of the project details they publish, including the description, required skills, budget range, deadline and expected deliverables.',
        'Freelancers are responsible for the accuracy of their proposals, including the bid amount and estimated delivery time.',
        'Each project is single-award. Accepting one proposal creates a contract and rejects the remaining pending proposals for that project.',
        'A contract becomes active only after both parties accept the same contract version. If terms are amended, a new version is created and both parties must accept it again.',
    ]],
    ['title' => 'Acceptance records and legal effect', 'body' => [
        'When you accept a contract version we record your authenticated account, the timestamp, the accepted version and a SHA-256 hash of a canonical representation of the terms.',
        'This creates a clear record of what was agreed on the platform. It is not a government certification, and it is not a guarantee that an agreement is legally enforceable.',
        'Whether an agreement is legally enforceable depends on applicable law and the circumstances of each case. Nothing on this platform constitutes legal advice.',
    ]],
    ['title' => 'Payments between users', 'body' => [
        'CircleWork Africa does not hold funds, operate escrow, provide a stored-value wallet, move money between users, or automatically pay freelancers.',
        'Client and freelancer agree a payment method and pay each other directly. We only record what the parties report.',
        'A payment record marked “Reported” has not been verified by us. It becomes “Confirmed” only when the other party confirms it. Either party may mark a record as disputed for administrative review.',
        'We cannot recover money transferred directly between users.',
    ]],
    ['title' => 'Deliverables and revisions', 'body' => [
        'Freelancers submit deliverables with a description and optional file attachments. Clients may approve a deliverable or request a revision with written feedback.',
        'The revision terms stated in the contract apply. Submissions are versioned so there is a record of what was delivered and when.',
    ]],
    ['title' => 'Reviews', 'body' => [
        'Reviews may only be left by the client and freelancer on a contract, once each, after the contract is completed or cancelled.',
        'You may not review yourself, and you may not post reviews that are abusive, fraudulent or misleading.',
        'Reviews may be reported and are moderated. Moderation actions are recorded.',
    ]],
    ['title' => 'Prohibited conduct', 'body' => [
        'You may not post unlawful, defamatory, deceptive or infringing content, or content that breaches our Community Guidelines.',
        'You may not attempt to bypass authentication, authorisation, upload restrictions, rate limits or premium feature limits.',
        'You may not use the platform to harass other users, to launder money, or to commit fraud.',
    ]],
    ['title' => 'Subscriptions and featured listings', 'body' => [
        'Basic accounts are free. Optional paid plans add capacity or visibility, and featured listings give a clearly labelled placement for a configured duration.',
        'Until an approved payment provider is integrated, subscription and featured payments are recorded and verified manually by an administrator with an audit trail. A paid feature is never activated merely because a user changed a form field or a URL.',
        'Prices, plan features, limits and featured durations are configurable by authorised administrators and may change.',
    ]],
    ['title' => 'Suspension, closure and data retention', 'body' => [
        'We may suspend an account while we investigate a report or dispute.',
        'Closing an account does not delete contracts, payment records or audit logs. We retain those records for as long as necessary for legal, accounting and dispute-resolution purposes, as described in our Privacy Policy.',
    ]],
    ['title' => 'Disclaimers and limits', 'body' => [
        'The platform is provided on an “as available” basis. We do not guarantee that a project will be completed, that a freelancer will be paid, or that a client will receive satisfactory work.',
        'We are not a party to the agreement between a client and a freelancer, and we are not an employment agency.',
        'To the extent permitted by law, we are not liable for indirect or consequential losses arising from work arranged between users.',
    ]],
    ['title' => 'Changes to these terms', 'body' => 'We may update these terms as the platform evolves. Material changes will be reflected in the “last updated” date above and, where appropriate, announced on the platform.'],
];
require CIRCLEWORK_ROOT . '/resources/views/components/legal-page.php';
