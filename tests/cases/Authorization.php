<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Contract;
use App\Models\User;

/**
 * Object-level authorisation and access control.
 */
return [
    'signed-out visitors are redirected away from the dashboard' => function (): void {
        $http = new HttpClient('http://127.0.0.1:8123');
        $response = $http->get('/dashboard');

        assert_equals(302, $response['status'], 'A signed-out visitor should be redirected.');
        assert_true(str_contains((string) $response['location'], '/login'), 'The redirect should point at the sign-in page.');
    },

    'signed-out visitors cannot reach admin pages' => function (): void {
        $http = new HttpClient('http://127.0.0.1:8123');

        foreach (['/admin', '/admin/users', '/admin/settings', '/admin/audit-logs'] as $path) {
            $response = $http->get($path);
            assert_equals(302, $response['status'], $path . ' should redirect when signed out.');
        }
    },

    'a standard user is denied the administration area' => function (): void {
        $user = Fixtures::user(['password' => 'UserPass12345']);
        $http = new HttpClient('http://127.0.0.1:8123');

        $http->post('/login', ['email' => (string) $user['email'], 'password' => 'UserPass12345']);

        $response = $http->get('/admin');
        assert_equals(403, $response['status'], 'A standard user must receive 403 from the admin area.');
        assert_contains('permission', strtolower($response['body']), 'The denial page should explain the lack of permission.');
    },

    'an administrator cannot reach super-admin-only pages' => function (): void {
        $admin = Fixtures::admin();
        $admin['plain_password'] = 'AdminPass12345';

        User::update((int) $admin['id'], ['password_hash' => password_hash('AdminPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $admin['email'], 'password' => 'AdminPass12345']);

        $response = $http->get('/admin/administrators');
        assert_equals(403, $response['status'], 'A plain administrator must not reach super-admin pages.');
    },

    'a super administrator can reach super-admin-only pages' => function (): void {
        $super = Fixtures::superAdmin();
        User::update((int) $super['id'], ['password_hash' => password_hash('SuperPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $super['email'], 'password' => 'SuperPass12345']);

        $response = $http->get('/admin/administrators');
        assert_equals(200, $response['status'], 'A super administrator should reach the page.');
    },

    'a non-participant cannot open another contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();

        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        User::update((int) $outsider['id'], ['password_hash' => password_hash('OutsiderPass123', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $outsider['email'], 'password' => 'OutsiderPass123']);

        $response = $http->get('/contracts/' . (int) $contract['id']);
        assert_equals(403, $response['status'], 'A non-participant must be denied the contract.');

        $messages = $http->get('/messages/' . (int) $contract['id']);
        assert_equals(403, $messages['status'], 'A non-participant must be denied the conversation.');

        $payments = $http->get('/contracts/' . (int) $contract['id'] . '/payments');
        assert_equals(403, $payments['status'], 'A non-participant must be denied the payment records.');
    },

    'a client cannot open another client project' => function (): void {
        $owner = Fixtures::user();
        $other = Fixtures::user();

        $project = Fixtures::project((int) $owner['id']);

        User::update((int) $other['id'], ['password_hash' => password_hash('OtherPass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $other['email'], 'password' => 'OtherPass12345']);

        $response = $http->get('/client/projects/' . (int) $project['id']);
        assert_equals(404, $response['status'], 'Another client must not see the project.');

        $edit = $http->get('/client/projects/' . (int) $project['id'] . '/edit');
        assert_equals(404, $edit['status'], 'Another client must not edit the project.');
    },

    'a freelancer cannot read another freelancer proposal' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $otherFreelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);

        User::update((int) $otherFreelancer['id'], ['password_hash' => password_hash('OtherFree12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $otherFreelancer['email'], 'password' => 'OtherFree12345']);

        $response = $http->get('/freelancer/proposals/' . (int) $proposal['id']);
        assert_equals(404, $response['status'], 'Another freelancer must not read the proposal.');
    },

    'suspended accounts are signed out of active sessions' => function (): void {
        $user = Fixtures::user();
        User::update((int) $user['id'], ['password_hash' => password_hash('ActivePass12345', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $login = $http->post('/login', ['email' => (string) $user['email'], 'password' => 'ActivePass12345']);
        assert_equals(302, $login['status'], 'The user should sign in.');

        assert_equals(200, $http->get('/dashboard')['status'], 'The dashboard should load while active.');

        User::update((int) $user['id'], ['status' => 'suspended', 'suspension_reason' => 'Suspended during the test.']);

        $afterSuspension = $http->get('/dashboard');
        assert_equals(302, $afterSuspension['status'], 'A suspended user should be signed out on the next request.');
    },

    'premium limits are enforced on the server' => function (): void {
        $client = Fixtures::user();

        // The free plan fixture allows three active projects.
        for ($i = 0; $i < 3; $i++) {
            Fixtures::project((int) $client['id'], ['status' => 'published']);
        }

        $gate = App\Services\SubscriptionService::canPublishAnotherProject((int) $client['id']);
        assert_false($gate['ok'], 'The free plan limit should block another active project.');

        $limits = App\Services\SubscriptionService::limitsFor((int) $client['id']);
        assert_equals(3, $limits['active_projects'], 'The free plan should report a limit of three active projects.');
    },
];
