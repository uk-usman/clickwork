<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\User;

/**
 * End-to-end lifecycle over HTTP: register, post, moderate, propose, award,
 * accept, deliver, approve, complete, review.
 */
return [
    'the complete client and freelancer lifecycle works end to end' => function (): void {
        $base = 'http://127.0.0.1:8123';

        // ------------------------------------------------------------------
        // 1. Register a client and a freelancer through the public form.
        // ------------------------------------------------------------------
        $clientHttp = new HttpClient($base);
        $clientEmail = 'workflow.client.' . bin2hex(random_bytes(4)) . '@example.com';

        $register = $clientHttp->post('/register', [
            'name'                  => 'Workflow Client',
            'email'                 => $clientEmail,
            'password'              => 'WorkflowPass123',
            'password_confirmation' => 'WorkflowPass123',
            'account_type'          => 'client',
            'state'                 => 'Kano',
            'city'                  => 'Kano',
        ]);

        assert_equals(302, $register['status'], 'Registration should redirect.');
        $clientUser = User::findByEmail($clientEmail);
        assert_true($clientUser !== null, 'The registered client should exist.');

        $freelancerHttp = new HttpClient($base);
        $freelancerEmail = 'workflow.freelancer.' . bin2hex(random_bytes(4)) . '@example.com';

        $freelancerHttp->post('/register', [
            'name'                  => 'Workflow Freelancer',
            'email'                 => $freelancerEmail,
            'password'              => 'WorkflowPass123',
            'password_confirmation' => 'WorkflowPass123',
            'account_type'          => 'freelancer',
            'state'                 => 'Kano',
            'city'                  => 'Kano',
        ]);

        $freelancerUser = User::findByEmail($freelancerEmail);
        assert_true($freelancerUser !== null, 'The registered freelancer should exist.');

        // ------------------------------------------------------------------
        // 2. The client posts a project and submits it for moderation.
        // ------------------------------------------------------------------
        $create = $clientHttp->post('/client/projects', [
            'action'      => 'submit',
            'title'       => 'Workflow project: build a small business website',
            'category_id' => '1',
            'description' => 'We need a five page responsive website with a contact form and a simple gallery.',
            'budget_type' => 'fixed',
            'budget_min'  => '40000',
            'budget_max'  => '80000',
            'currency'    => 'NGN',
            'deadline'    => gmdate('Y-m-d', time() + 86400 * 21),
            'visibility'  => 'public',
            'skill_ids'   => ['1', '2'],
        ]);

        assert_equals(302, $create['status'], 'Creating the project should redirect.');

        $project = Database::selectOne(
            'SELECT * FROM `projects` WHERE `client_id` = ? ORDER BY `id` DESC LIMIT 1',
            [(int) $clientUser['id']]
        );
        assert_true($project !== null, 'The project should be stored.');
        assert_equals('pending', (string) $project['status'], 'A submitted project should await moderation.');

        // The project must not be public yet.
        $guest = new HttpClient($base);
        assert_equals(404, $guest->get('/projects/' . (int) $project['id'])['status'], 'A pending project must not be public.');

        // ------------------------------------------------------------------
        // 3. An administrator approves it.
        // ------------------------------------------------------------------
        $admin = Fixtures::admin();
        User::update((int) $admin['id'], ['password_hash' => password_hash('WorkflowAdmin123', PASSWORD_DEFAULT)]);

        $adminHttp = new HttpClient($base);
        $adminHttp->post('/login', ['email' => (string) $admin['email'], 'password' => 'WorkflowAdmin123']);

        $approve = $adminHttp->post('/admin/projects/' . (int) $project['id'] . '/moderate', [
            'decision' => 'approve',
            'note'     => 'Approved during the end-to-end workflow test.',
        ]);
        assert_equals(302, $approve['status'], 'Moderation should redirect.');

        $published = Database::selectOne('SELECT * FROM `projects` WHERE `id` = ?', [(int) $project['id']]);
        assert_equals('published', (string) $published['status'], 'The project should now be published.');
        assert_equals(200, $guest->get('/projects/' . (int) $project['id'])['status'], 'The published project should be public.');

        // ------------------------------------------------------------------
        // 4. The freelancer submits a proposal.
        // ------------------------------------------------------------------
        $proposal = $freelancerHttp->post('/projects/' . (int) $project['id'] . '/proposals', [
            'cover_letter'   => 'I have built many similar business websites and can deliver this in two weeks with two revision rounds.',
            'bid_amount'     => '65000',
            'estimated_days' => '14',
        ]);
        assert_equals(302, $proposal['status'], 'Submitting a proposal should redirect.');

        $proposalRow = Database::selectOne(
            'SELECT * FROM `proposals` WHERE `project_id` = ? AND `freelancer_id` = ?',
            [(int) $project['id'], (int) $freelancerUser['id']]
        );
        assert_true($proposalRow !== null, 'The proposal should be stored.');
        assert_equals('submitted', (string) $proposalRow['status'], 'The proposal should be submitted.');

        // A second proposal by the same freelancer must be refused.
        $freelancerHttp->post('/projects/' . (int) $project['id'] . '/proposals', [
            'cover_letter'   => 'This is a duplicate proposal attempt that the server must reject outright.',
            'bid_amount'     => '50000',
            'estimated_days' => '10',
        ]);
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `proposals` WHERE `project_id` = ? AND `freelancer_id` = ?', [(int) $project['id'], (int) $freelancerUser['id']]), 'Only one proposal may exist per freelancer per project.');

        // ------------------------------------------------------------------
        // 5. The client accepts the proposal, creating a contract.
        // ------------------------------------------------------------------
        $award = $clientHttp->post('/client/proposals/' . (int) $proposalRow['id'] . '/award', []);
        assert_equals(302, $award['status'], 'Awarding should redirect to the contract.');

        $contract = Database::selectOne('SELECT * FROM `contracts` WHERE `project_id` = ?', [(int) $project['id']]);
        assert_true($contract !== null, 'A contract should be created.');
        assert_equals('awaiting_acceptance', (string) $contract['status'], 'The contract should await acceptance.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `contracts` WHERE `project_id` = ?', [(int) $project['id']]), 'Exactly one contract may exist.');

        // ------------------------------------------------------------------
        // 6. Both parties accept the same version.
        // ------------------------------------------------------------------
        $clientHttp->post('/contracts/' . (int) $contract['id'] . '/accept', ['confirm_acceptance' => '1']);
        assert_equals('awaiting_acceptance', (string) Database::selectOne('SELECT `status` FROM `contracts` WHERE `id` = ?', [(int) $contract['id']])['status'], 'One acceptance must not activate the contract.');

        $freelancerHttp->post('/contracts/' . (int) $contract['id'] . '/accept', ['confirm_acceptance' => '1']);
        assert_equals('active', (string) Database::selectOne('SELECT `status` FROM `contracts` WHERE `id` = ?', [(int) $contract['id']])['status'], 'Both acceptances should activate the contract.');

        // ------------------------------------------------------------------
        // 7. Messages between the two participants.
        // ------------------------------------------------------------------
        $message = $clientHttp->post('/messages/' . (int) $contract['id'], [
            'body' => 'Welcome aboard. Please share the first draft when ready.',
        ]);
        assert_equals(302, $message['status'], 'Sending a message should redirect.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `messages` WHERE `contract_id` = ?', [(int) $contract['id']]), 'The message should be stored.');

        // ------------------------------------------------------------------
        // 8. The freelancer submits a deliverable, the client requests a revision,
        //    then approves the revised version.
        // ------------------------------------------------------------------
        $submit = $freelancerHttp->post('/contracts/' . (int) $contract['id'] . '/deliverables', [
            'message' => 'First version of the website with all five pages and the contact form.',
        ]);
        assert_equals(302, $submit['status'], 'Submitting a deliverable should redirect.');

        $deliverable = Database::selectOne('SELECT * FROM `deliverables` WHERE `contract_id` = ? ORDER BY `version` DESC LIMIT 1', [(int) $contract['id']]);
        assert_true($deliverable !== null, 'The deliverable should be stored.');
        assert_equals('submitted', (string) $deliverable['status'], 'The deliverable should await review.');

        $clientHttp->post('/contracts/' . (int) $contract['id'] . '/deliverables/' . (int) $deliverable['id'] . '/revision', [
            'note' => 'Please increase the font size on the gallery captions and resend.',
        ]);
        assert_equals('revision_requested', (string) Database::selectOne('SELECT `status` FROM `contracts` WHERE `id` = ?', [(int) $contract['id']])['status'], 'The contract should reflect the revision request.');

        $freelancerHttp->post('/contracts/' . (int) $contract['id'] . '/deliverables', [
            'message' => 'Second version with larger gallery captions as requested by the client.',
        ]);

        $revised = Database::selectOne('SELECT * FROM `deliverables` WHERE `contract_id` = ? ORDER BY `version` DESC LIMIT 1', [(int) $contract['id']]);
        assert_equals(2, (int) $revised['version'], 'The revised submission should be version two.');

        $clientHttp->post('/contracts/' . (int) $contract['id'] . '/deliverables/' . (int) $revised['id'] . '/approve', []);
        assert_equals('approved', (string) Database::selectOne('SELECT `status` FROM `deliverables` WHERE `id` = ?', [(int) $revised['id']])['status'], 'The deliverable should be approved.');

        // ------------------------------------------------------------------
        // 9. Direct payment is recorded and confirmed by the other party.
        // ------------------------------------------------------------------
        $payment = $clientHttp->post('/contracts/' . (int) $contract['id'] . '/payments', [
            'amount'          => '65000',
            'method'          => 'bank_transfer',
            'payer_reference' => 'WF-TRX-9001',
            'paid_on'         => gmdate('Y-m-d'),
        ]);
        assert_equals(302, $payment['status'], 'Recording a payment should redirect.');

        $paymentRow = Database::selectOne('SELECT * FROM `payment_records` WHERE `contract_id` = ? ORDER BY `id` DESC LIMIT 1', [(int) $contract['id']]);
        assert_true($paymentRow !== null, 'The payment record should be stored.');
        assert_equals('reported', (string) $paymentRow['status'], 'A new payment must be reported, not verified.');

        $freelancerHttp->post('/contracts/' . (int) $contract['id'] . '/payments/' . (int) $paymentRow['id'] . '/confirm', []);
        assert_equals('confirmed', (string) Database::selectOne('SELECT `status` FROM `payment_records` WHERE `id` = ?', [(int) $paymentRow['id']])['status'], 'The counterparty confirmation should be recorded.');

        // ------------------------------------------------------------------
        // 10. The client completes the contract and both parties review.
        // ------------------------------------------------------------------
        $clientHttp->post('/contracts/' . (int) $contract['id'] . '/complete', []);
        assert_equals('completed', (string) Database::selectOne('SELECT `status` FROM `contracts` WHERE `id` = ?', [(int) $contract['id']])['status'], 'The contract should be completed.');

        $clientHttp->post('/contracts/' . (int) $contract['id'] . '/review', ['rating' => '5', 'comment' => 'Excellent communication and a clean final website.']);
        $freelancerHttp->post('/contracts/' . (int) $contract['id'] . '/review', ['rating' => '5', 'comment' => 'Clear brief and prompt confirmation of the payment.']);

        assert_equals(2, (int) Database::scalar('SELECT COUNT(*) FROM `reviews` WHERE `contract_id` = ?', [(int) $contract['id']]), 'Both reviews should be stored.');

        $profile = App\Models\UserProfile::forUser((int) $freelancerUser['id']);
        assert_true($profile !== null, 'The freelancer profile should exist.');
        assert_equals(5.0, (float) $profile['rating_average'], 'The freelancer rating should reflect the real review.');
        assert_equals(1, (int) $profile['completed_contracts'], 'The completed contract count should be real, not invented.');

        // ------------------------------------------------------------------
        // 11. The audit trail recorded the sensitive actions.
        // ------------------------------------------------------------------
        foreach (['project.moderated', 'proposal.submitted', 'contract.accepted', 'payment.reported', 'payment.confirmed'] as $action) {
            assert_true(
                (int) Database::scalar('SELECT COUNT(*) FROM `audit_logs` WHERE `action` = ?', [$action]) > 0,
                'The audit log should contain an entry for ' . $action . '.'
            );
        }
    },
];
