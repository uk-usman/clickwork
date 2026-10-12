<?php
$heading = 'Privacy Policy';
$lead = 'How CircleWork Africa collects, uses and protects personal information.';
$updated = '12 October 2026';
$sections = [
    ['title' => 'Information we collect', 'body' => [
        'Account information you provide: your name, email address, optional phone number, city and state, and your chosen account mode.',
        'Profile and business information: headline, biography, skills, portfolio items, availability, rates, and optional company details.',
        'Platform activity: projects, proposals, contracts, contract acceptances, deliverables, messages, reviews, reports and disputes.',
        'Payment records you choose to enter: contract amount, payer, payee, method, reference, reported amount, reported date, status and any evidence you upload.',
        'Technical information: IP address, user agent and timestamps recorded for security, throttling and audit purposes.',
    ]],
    ['title' => 'How we use information', 'body' => [
        'To operate the marketplace: publishing projects, delivering proposals, generating contracts and enabling messaging.',
        'To protect users: login throttling, CSRF protection, upload validation, object-level authorisation checks and audit logging.',
        'To resolve issues: reviewing reports, disputes and payment records.',
        'To operate optional paid features: recording and manually verifying subscription and featured-listing payments.',
    ]],
    ['title' => 'Legal basis and Nigerian law', 'body' => [
        'We process personal data to perform our contract with you, to comply with legal obligations, and for our legitimate interests in operating a safe marketplace.',
        'We aim to handle personal data consistently with the Nigeria Data Protection Act and the guidance of the Nigeria Data Protection Commission. Where the law requires consent for a specific use, we will ask for it.',
        'This policy describes our practices; it is not legal advice, and specific obligations may depend on your circumstances.',
    ]],
    ['title' => 'What we do not do with payment information', 'body' => [
        'We do not hold funds, operate escrow or process card payments in the core platform.',
        'We do not verify bank transfers. A record marked “Reported” reflects what a user told us, not what we independently confirmed.',
        'Payment evidence you upload is stored privately and is accessible only to the parties on the relevant contract and to authorised administrators.',
    ]],
    ['title' => 'Who can see your information', 'body' => [
        'Public profile information is visible to visitors when your profile visibility is set to public. Private profiles are visible only to you and to authorised administrators.',
        'Project details are public only after moderation approval. Drafts and rejected projects remain private to you.',
        'Private messages, deliverables, payment evidence and dispute threads are visible only to the participants on the relevant contract and to authorised administrators.',
        'We do not sell personal data.',
    ]],
    ['title' => 'Security measures', 'body' => [
        'Passwords are stored using one-way hashing with PHP password_hash, never in plain text.',
        'Database access uses prepared statements to prevent SQL injection.',
        'State-changing requests require a CSRF token, and output is escaped to prevent cross-site scripting.',
        'Uploaded files are validated by content, MIME type, extension and size, stored outside the public web root with randomised names, and delivered only through permission-checked endpoints.',
        'Password reset and email verification tokens are stored only as SHA-256 hashes and expire.',
        'We keep detailed error information in protected server logs rather than exposing it publicly.',
    ]],
    ['title' => 'Retention', 'body' => [
        'We retain contracts, contract acceptances, payment records, disputes and audit logs after an account is closed, because they may be needed for legal, accounting and dispute-resolution purposes.',
        'We do not cascade-delete contracts or payment records when an account is closed. We instead apply an account-closure and data-retention strategy that preserves required records while limiting further processing.',
        'Security records such as login attempts are pruned periodically.',
    ]],
    ['title' => 'Your choices and rights', 'body' => [
        'You can update your account details, profile visibility and portfolio from your dashboard at any time.',
        'You can request access to, correction of, or deletion of personal data that we are not required to retain. Contact us through the contact page and we will respond as required by applicable law.',
        'You can report a privacy concern using the reporting form so that it is tracked and reviewed.',
    ]],
    ['title' => 'Cookies and sessions', 'body' => [
        'We use a single session cookie to keep you signed in. It is set with HttpOnly, SameSite=Lax and, on HTTPS deployments, Secure attributes.',
        'We do not use advertising cookies or third-party tracking scripts in the core platform.',
    ]],
    ['title' => 'Changes to this policy', 'body' => 'We may update this policy as the platform evolves. The “last updated” date above will change when we do.'],
];
require CIRCLEWORK_ROOT . '/resources/views/components/legal-page.php';
