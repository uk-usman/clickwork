<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Project;
use App\Models\Proposal;
use App\Services\ProjectService;
use App\Services\ProposalService;

return [
    'project budget validation rejects a maximum below the minimum' => function (): void {
        $client = Fixtures::user();

        $result = ProjectService::create((int) $client['id'], [
            'title'        => 'Budget validation project for automated testing',
            'category_id'  => 1,
            'description'  => 'This description exists purely to satisfy the minimum length requirement for validation.',
            'budget_type'  => 'fixed',
            'budget_min'   => '50000',
            'budget_max'   => '10000',
            'currency'     => 'NGN',
            'visibility'   => 'public',
        ], false);

        assert_false($result['ok'], 'An inverted budget range must be rejected.');
        assert_true(isset($result['errors']['budget_max']), 'The maximum budget field should report the error.');
    },

    'project validation rejects short descriptions and missing categories' => function (): void {
        $client = Fixtures::user();

        $result = ProjectService::create((int) $client['id'], [
            'title'       => 'Short',
            'category_id' => 0,
            'description' => 'Too short.',
            'budget_type' => 'fixed',
            'budget_min'  => '1000',
            'budget_max'  => '2000',
        ], false);

        assert_false($result['ok'], 'Invalid project details must be rejected.');
        assert_true(isset($result['errors']['title']), 'The title should be validated.');
        assert_true(isset($result['errors']['description']), 'The description should be validated.');
        assert_true(isset($result['errors']['category_id']), 'The category should be validated.');
    },

    'a project can be saved as a draft and submitted for moderation' => function (): void {
        $client = Fixtures::user();

        $payload = [
            'title'        => 'Draft and submit project for automated testing',
            'category_id'  => 1,
            'description'  => 'This description exists purely to satisfy the minimum length requirement for validation.',
            'budget_type'  => 'fixed',
            'budget_min'   => '10000',
            'budget_max'   => '20000',
            'currency'     => 'NGN',
            'visibility'   => 'public',
        ];

        $draft = ProjectService::create((int) $client['id'], $payload, false);
        assert_true($draft['ok'], 'Saving a draft should succeed.');
        assert_equals('draft', (string) Project::find((int) $draft['project_id'])['status'], 'A saved draft should have draft status.');

        $submitted = ProjectService::create((int) $client['id'], $payload, true);
        assert_true($submitted['ok'], 'Submitting for moderation should succeed.');
        assert_equals('pending', (string) Project::find((int) $submitted['project_id'])['status'], 'A submitted project should be pending.');
    },

    'draft projects are not publicly visible' => function (): void {
        $client = Fixtures::user();
        $draft = Fixtures::project((int) $client['id'], ['status' => 'draft', 'visibility' => 'public']);

        $http = new HttpClient('http://127.0.0.1:8123');
        $response = $http->get('/projects/' . (int) $draft['id']);

        assert_equals(404, $response['status'], 'A draft project must not be publicly viewable.');

        $list = $http->get('/projects');
        assert_not_contains((string) $draft['reference'], $list['body'], 'Draft projects must not appear in public listings.');
    },

    'only approved projects appear in the public listing' => function (): void {
        $client = Fixtures::user();
        $published = Fixtures::project((int) $client['id'], ['status' => 'published']);

        $http = new HttpClient('http://127.0.0.1:8123');
        $list = $http->get('/projects');

        assert_contains((string) $published['title'], $list['body'], 'A published project should appear in the listing.');
    },

    'moderation approves and publishes a pending project' => function (): void {
        $client = Fixtures::user();
        $admin = Fixtures::admin();

        $project = Fixtures::project((int) $client['id'], ['status' => 'pending', 'published_at' => null]);

        $approved = ProjectService::moderate((int) $project['id'], (int) $admin['id'], 'approve', 'Looks good.');
        assert_true($approved['ok'], 'Moderation approval should succeed.');

        $updated = Project::find((int) $project['id']);
        assert_equals('published', (string) $updated['status'], 'The project should be published.');
        assert_true($updated['published_at'] !== null, 'A publication timestamp should be recorded.');

        $rejectedProject = Fixtures::project((int) $client['id'], ['status' => 'pending', 'published_at' => null]);
        $rejected = ProjectService::moderate((int) $rejectedProject['id'], (int) $admin['id'], 'reject', 'Needs more detail.');
        assert_true($rejected['ok'], 'Moderation rejection should succeed.');
        assert_equals('rejected', (string) Project::find((int) $rejectedProject['id'])['status'], 'The project should be rejected.');
    },

    'a freelancer cannot submit two proposals for the same project' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $project = Fixtures::project((int) $client['id']);

        $first = ProposalService::submit((int) $freelancer['id'], (int) $project['id'], [
            'cover_letter'   => 'This is my first proposal and it is comfortably longer than forty characters in total.',
            'bid_amount'     => '20000',
            'estimated_days' => '7',
        ]);
        assert_true($first['ok'], 'The first proposal should be accepted.');

        $second = ProposalService::submit((int) $freelancer['id'], (int) $project['id'], [
            'cover_letter'   => 'This is my second proposal and it is comfortably longer than forty characters in total.',
            'bid_amount'     => '18000',
            'estimated_days' => '5',
        ]);
        assert_false($second['ok'], 'A duplicate proposal must be rejected.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `proposals` WHERE `project_id` = ? AND `freelancer_id` = ?', [(int) $project['id'], (int) $freelancer['id']]), 'Only one proposal should exist.');
    },

    'a client cannot submit a proposal on their own project' => function (): void {
        $client = Fixtures::user();
        $project = Fixtures::project((int) $client['id']);

        $result = ProposalService::submit((int) $client['id'], (int) $project['id'], [
            'cover_letter'   => 'I am the client trying to bid on my own project which should not be allowed at all.',
            'bid_amount'     => '1000',
            'estimated_days' => '1',
        ]);

        assert_false($result['ok'], 'A client must not propose on their own project.');
    },

    'proposals cannot be submitted on unpublished projects' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $project = Fixtures::project((int) $client['id'], ['status' => 'draft']);

        $result = ProposalService::submit((int) $freelancer['id'], (int) $project['id'], [
            'cover_letter'   => 'Attempting to bid on a draft project which should be rejected by the server.',
            'bid_amount'     => '5000',
            'estimated_days' => '3',
        ]);

        assert_false($result['ok'], 'A draft project must not accept proposals.');
    },

    'invalid proposal input is rejected' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $project = Fixtures::project((int) $client['id']);

        $result = ProposalService::submit((int) $freelancer['id'], (int) $project['id'], [
            'cover_letter'   => 'Too short',
            'bid_amount'     => '0',
            'estimated_days' => '0',
        ]);

        assert_false($result['ok'], 'Invalid proposal input must be rejected.');
        assert_true(isset($result['errors']['cover_letter']), 'The cover letter should be validated.');
        assert_true(isset($result['errors']['bid_amount']), 'The bid amount should be validated.');
        assert_true(isset($result['errors']['estimated_days']), 'The delivery estimate should be validated.');
    },

    'a freelancer can withdraw an eligible proposal' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $project = Fixtures::project((int) $client['id']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);

        $result = ProposalService::withdraw((int) $proposal['id'], (int) $freelancer['id']);
        assert_true($result['ok'], 'Withdrawing a submitted proposal should succeed.');
        assert_equals('withdrawn', (string) Proposal::find((int) $proposal['id'])['status'], 'The proposal should be withdrawn.');

        $other = Fixtures::proposal((int) $project['id'], (int) Fixtures::user()['id']);
        $notOwner = ProposalService::withdraw((int) $other['id'], (int) $freelancer['id']);
        assert_false($notOwner['ok'], 'A freelancer must not withdraw someone else proposal.');
    },

    'a client can shortlist and reject proposals they own' => function (): void {
        $client = Fixtures::user();
        $otherClient = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);

        $notOwner = ProposalService::decide((int) $proposal['id'], (int) $otherClient['id'], 'shortlist');
        assert_false($notOwner['ok'], 'Another client must not shortlist the proposal.');

        $shortlist = ProposalService::decide((int) $proposal['id'], (int) $client['id'], 'shortlist');
        assert_true($shortlist['ok'], 'The owning client should be able to shortlist.');
        assert_equals('shortlisted', (string) Proposal::find((int) $proposal['id'])['status'], 'The proposal should be shortlisted.');
    },
];
