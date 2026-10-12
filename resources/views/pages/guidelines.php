<?php
$heading = 'Community Guidelines';
$lead = 'Simple expectations that keep CircleWork Africa useful and safe for everyone.';
$updated = '12 October 2026';
$sections = [
    ['title' => 'Be honest', 'body' => [
        'Describe your project, your skills and your proposal accurately. Do not overstate experience, qualifications or delivery times.',
        'Do not post a project you have no intention of awarding, and do not submit proposals you cannot deliver.',
    ]],
    ['title' => 'Communicate clearly', 'body' => [
        'Keep project conversations on the platform so there is a record if something goes wrong.',
        'Respond to reasonable questions about scope, price and timeline before accepting a contract.',
    ]],
    ['title' => 'Keep payments transparent', 'body' => [
        'Agree the payment method in writing in the contract and record payments accurately.',
        'Do not record a payment that has not happened, and do not confirm a payment you have not received.',
        'If a payment goes wrong, mark the record as disputed so an administrator can review it. We cannot recover funds paid directly between users.',
    ]],
    ['title' => 'Deliver and review fairly', 'body' => [
        'Submit deliverables that match the agreed scope and respond constructively to revision requests.',
        'Approve work that meets the agreed scope, and only request revisions that fall within the agreed revision terms.',
        'Leave reviews that are truthful and relevant. Do not use reviews to threaten or retaliate.',
    ]],
    ['title' => 'Do not misuse the platform', 'body' => [
        'Do not attempt to bypass authentication, permissions, upload restrictions or premium limits.',
        'Do not upload executable files, unrestricted SVG files, or content that infringes someone else’s rights.',
        'Do not harass, impersonate, or attempt to defraud other users.',
        'Do not move communication off-platform in order to avoid the record-keeping that protects both parties.',
    ]],
    ['title' => 'Reporting and enforcement', 'body' => [
        'Report suspicious users, projects, reviews, messages or payments using the reporting form. Reports are tracked with a status an administrator can review.',
        'Depending on the severity, we may remove content, hide a review, suspend an account, or close an account. Moderation actions are recorded in our audit log.',
        'If you believe a moderation decision was wrong, contact support and explain why. We will review it and record the outcome.',
    ]],
];
require CIRCLEWORK_ROOT . '/resources/views/components/legal-page.php';
