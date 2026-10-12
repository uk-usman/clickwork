<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Contract;
use App\Models\ContractAcceptance;
use App\Models\ContractVersion;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ContractService;
use App\Services\DeliverableService;

return [
    'accepting a proposal creates exactly one contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);

        $result = ContractService::awardProposal((int) $proposal['id'], (int) $client['id']);

        assert_true($result['ok'], 'Awarding the proposal should succeed.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `contracts` WHERE `project_id` = ?', [(int) $project['id']]), 'Exactly one contract should exist.');
        assert_equals('accepted', (string) Proposal::find((int) $proposal['id'])['status'], 'The proposal should be accepted.');
        assert_equals('in_progress', (string) Project::find((int) $project['id'])['status'], 'The project should move to in progress.');

        $contract = Contract::find((int) $result['contract_id']);
        assert_true($contract !== null, 'The contract should be retrievable.');
        assert_equals('awaiting_acceptance', (string) $contract['status'], 'A new contract should await acceptance.');
        assert_equals(1, (int) $contract['current_version'], 'The first contract version should be 1.');
    },

    'accepting one proposal rejects the competing proposals' => function (): void {
        $client = Fixtures::user();
        $winner = Fixtures::user();
        $loser = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $winnerProposal = Fixtures::proposal((int) $project['id'], (int) $winner['id']);
        $loserProposal = Fixtures::proposal((int) $project['id'], (int) $loser['id']);

        $result = ContractService::awardProposal((int) $winnerProposal['id'], (int) $client['id']);
        assert_true($result['ok'], 'Awarding should succeed.');

        assert_equals('accepted', (string) Proposal::find((int) $winnerProposal['id'])['status'], 'The winner should be accepted.');
        assert_equals('rejected', (string) Proposal::find((int) $loserProposal['id'])['status'], 'The competing proposal should be rejected.');
    },

    'a second award attempt for the same project is refused' => function (): void {
        $client = Fixtures::user();
        $first = Fixtures::user();
        $second = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $firstProposal = Fixtures::proposal((int) $project['id'], (int) $first['id']);
        $secondProposal = Fixtures::proposal((int) $project['id'], (int) $second['id']);

        assert_true(ContractService::awardProposal((int) $firstProposal['id'], (int) $client['id'])['ok'], 'The first award should succeed.');

        $secondAward = ContractService::awardProposal((int) $secondProposal['id'], (int) $client['id']);
        assert_false($secondAward['ok'], 'A second award must be refused.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `contracts` WHERE `project_id` = ?', [(int) $project['id']]), 'Only one contract may exist per project.');
    },

    'only the owning client can award a proposal' => function (): void {
        $client = Fixtures::user();
        $otherClient = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);

        $result = ContractService::awardProposal((int) $proposal['id'], (int) $otherClient['id']);
        assert_false($result['ok'], 'A non-owner must not award the proposal.');
    },

    'a contract becomes active only after both parties accept' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);
        $contractId = (int) ContractService::awardProposal((int) $proposal['id'], (int) $client['id'])['contract_id'];

        $clientAccept = ContractService::accept($contractId, (int) $client['id'], '127.0.0.1', 'test');
        assert_true($clientAccept['ok'], 'The client acceptance should be recorded.');
        assert_equals('awaiting_acceptance', (string) Contract::find($contractId)['status'], 'The contract must not activate after one acceptance.');

        $freelancerAccept = ContractService::accept($contractId, (int) $freelancer['id'], '127.0.0.1', 'test');
        assert_true($freelancerAccept['ok'], 'The freelancer acceptance should be recorded.');

        $active = Contract::find($contractId);
        assert_equals('active', (string) $active['status'], 'The contract should activate after both acceptances.');
        assert_true($active['activated_at'] !== null, 'An activation timestamp should be recorded.');
        assert_equals(2, (int) Database::scalar('SELECT COUNT(*) FROM `contract_acceptances` WHERE `contract_id` = ?', [$contractId]), 'Two acceptances should be recorded.');
    },

    'acceptances record the account, timestamp and terms hash' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);
        $contractId = (int) ContractService::awardProposal((int) $proposal['id'], (int) $client['id'])['contract_id'];

        ContractService::accept($contractId, (int) $client['id'], '203.0.113.9', 'TestAgent/1.0');

        $acceptance = ContractAcceptance::findBy(['contract_id' => $contractId, 'user_id' => (int) $client['id']]);
        assert_true($acceptance !== null, 'The acceptance should be stored.');
        assert_equals((int) $client['id'], (int) $acceptance['user_id'], 'The accepting account should be recorded.');
        assert_equals('client', (string) $acceptance['party_role'], 'The party role should be recorded.');
        assert_equals('203.0.113.9', (string) $acceptance['ip_address'], 'The IP address should be recorded.');

        $version = ContractVersion::forVersion($contractId, 1);
        assert_true($version !== null, 'A contract version snapshot should exist.');
        assert_equals((string) $version['terms_hash'], (string) $acceptance['terms_hash'], 'The acceptance must reference the exact accepted terms hash.');
        assert_equals(64, strlen((string) $version['terms_hash']), 'The terms hash should be a SHA-256 hex digest.');
    },

    'the same party cannot accept the same version twice' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $again = ContractService::accept((int) $contract['id'], (int) $client['id'], '127.0.0.1', 'test');
        assert_false($again['ok'], 'A repeated acceptance of the same version must be refused.');
    },

    'a non-party cannot accept a contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);
        $contractId = (int) ContractService::awardProposal((int) $proposal['id'], (int) $client['id'])['contract_id'];

        $result = ContractService::accept($contractId, (int) $outsider['id'], '127.0.0.1', 'test');
        assert_false($result['ok'], 'A non-party must not accept the contract.');
    },

    'amending terms creates a new version and requires fresh acceptance' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();

        $project = Fixtures::project((int) $client['id'], ['status' => 'published']);
        $proposal = Fixtures::proposal((int) $project['id'], (int) $freelancer['id']);
        $contractId = (int) ContractService::awardProposal((int) $proposal['id'], (int) $client['id'])['contract_id'];

        ContractService::accept($contractId, (int) $client['id'], '127.0.0.1', 'test');
        ContractService::accept($contractId, (int) $freelancer['id'], '127.0.0.1', 'test');
        assert_equals('active', (string) Contract::find($contractId)['status'], 'The contract should start active.');

        $originalVersionOne = ContractVersion::forVersion($contractId, 1);
        assert_true($originalVersionOne !== null, 'Version 1 should exist.');
        $originalHash = (string) $originalVersionOne['terms_hash'];

        $amended = ContractService::amend($contractId, (int) $client['id'], ['amount' => '31000.00'], 'Client increased the agreed amount.');
        assert_true($amended['ok'], 'Amending should succeed.');

        $updated = Contract::find($contractId);
        assert_equals(2, (int) $updated['current_version'], 'The contract should move to version 2.');
        assert_equals('awaiting_acceptance', (string) $updated['status'], 'The contract should require fresh acceptance.');
        assert_true($updated['client_accepted_at'] === null, 'The client acceptance should be reset for the new version.');

        $originalStillThere = ContractVersion::forVersion($contractId, 1);
        assert_true($originalStillThere !== null, 'The original version must remain in history.');
        assert_equals($originalHash, (string) $originalStillThere['terms_hash'], 'Accepted terms must never be rewritten in place.');

        $newVersion = ContractVersion::forVersion($contractId, 2);
        assert_true($newVersion !== null, 'Version 2 should exist.');
        assert_true((string) $newVersion['terms_hash'] !== $originalHash, 'The amended terms should produce a different hash.');
    },

    'a cancelled contract cannot be amended' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        ContractService::cancel((int) $contract['id'], (int) $client['id'], 'No longer required by the client.');

        $amended = ContractService::amend((int) $contract['id'], (int) $client['id'], ['amount' => '100.00'], 'Attempted amendment.');
        assert_false($amended['ok'], 'A cancelled contract must not be amended.');
    },

    'a non-party cannot cancel a contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $result = ContractService::cancel((int) $contract['id'], (int) $outsider['id'], 'Attempting an unauthorised cancellation.');
        assert_false($result['ok'], 'A non-party must not cancel the contract.');
    },

    'cancelling records the reason and keeps the contract record' => function () {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $result = ContractService::cancel((int) $contract['id'], (int) $client['id'], 'The client cancelled before work started.');
        assert_true($result['ok'], 'The owning client should be able to cancel.');

        $cancelled = Contract::find((int) $contract['id']);
        assert_true($cancelled !== null, 'The contract record must remain after cancellation.');
        assert_equals('cancelled', (string) $cancelled['status'], 'The contract should be cancelled.');
        assert_contains('before work started', (string) $cancelled['cancellation_reason'], 'The reason should be stored.');
    },

    'only the assigned freelancer can submit a deliverable' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $attempt = DeliverableService::submit((int) $contract['id'], (int) $outsider['id'], ['message' => 'Attempting to submit work on someone else contract.'], []);
        assert_false($attempt['ok'], 'A non-party must not submit a deliverable.');

        $clientAttempt = DeliverableService::submit((int) $contract['id'], (int) $client['id'], ['message' => 'The client attempting to submit their own deliverable.'], []);
        assert_false($clientAttempt['ok'], 'The client must not submit the deliverable.');
    },

    'deliverables are versioned and revisions are recorded' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $first = DeliverableService::submit((int) $contract['id'], (int) $freelancer['id'], ['message' => 'First version of the deliverable with all agreed files.'], []);
        assert_true($first['ok'], 'The first submission should succeed.');
        assert_equals('deliverable_submitted', (string) Contract::find((int) $contract['id'])['status'], 'The contract should reflect the submission.');

        $revision = DeliverableService::requestRevision((int) $first['deliverable_id'], (int) $client['id'], 'Please adjust the colour palette and resend.');
        assert_true($revision['ok'], 'A revision request should succeed.');
        assert_equals('revision_requested', (string) Contract::find((int) $contract['id'])['status'], 'The contract should show a revision request.');

        $second = DeliverableService::submit((int) $contract['id'], (int) $freelancer['id'], ['message' => 'Second version with the requested colour changes applied.'], []);
        assert_true($second['ok'], 'The revised submission should succeed.');
        assert_equals(2, (int) Database::scalar('SELECT COUNT(*) FROM `deliverables` WHERE `contract_id` = ?', [(int) $contract['id']]), 'Two deliverable versions should exist.');

        $superseded = Deliverable::find((int) $first['deliverable_id']);
        assert_equals('superseded', (string) $superseded['status'], 'The earlier submission should be superseded.');
    },

    'only the client can approve a deliverable' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $submission = DeliverableService::submit((int) $contract['id'], (int) $freelancer['id'], ['message' => 'Deliverable ready for the client to review and approve.'], []);
        assert_true($submission['ok'], 'The submission should succeed.');

        $byFreelancer = DeliverableService::approve((int) $submission['deliverable_id'], (int) $freelancer['id']);
        assert_false($byFreelancer['ok'], 'The freelancer must not approve their own deliverable.');

        $byClient = DeliverableService::approve((int) $submission['deliverable_id'], (int) $client['id']);
        assert_true($byClient['ok'], 'The client should be able to approve.');
    },

    'a contract cannot be completed before a deliverable is approved' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        $tooEarly = DeliverableService::completeContract((int) $contract['id'], (int) $client['id']);
        assert_false($tooEarly['ok'], 'Completion must require an approved deliverable.');

        $submission = DeliverableService::submit((int) $contract['id'], (int) $freelancer['id'], ['message' => 'Final deliverable submitted for approval by the client.'], []);
        DeliverableService::approve((int) $submission['deliverable_id'], (int) $client['id']);

        $completed = DeliverableService::completeContract((int) $contract['id'], (int) $client['id']);
        assert_true($completed['ok'], 'Completion should succeed after approval.');
        assert_equals('completed', (string) Contract::find((int) $contract['id'])['status'], 'The contract should be completed.');
    },

    'a non-participant cannot download deliverable files' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id']);

        App\Models\DeliverableFile::create([
            'deliverable_id'  => (int) DeliverableService::submit((int) $contract['id'], (int) $freelancer['id'], ['message' => 'Deliverable with an attached file for the download test.'], [])['deliverable_id'],
            'uploaded_by'     => (int) $freelancer['id'],
            'original_name'   => 'deliverable.txt',
            'stored_name'     => 'test/does-not-exist.txt',
            'mime_type'       => 'text/plain',
            'size_bytes'      => 12,
            'checksum_sha256' => str_repeat('a', 64),
        ]);

        $fileId = (int) Database::scalar('SELECT `id` FROM `deliverable_files` ORDER BY `id` DESC LIMIT 1');

        User::update((int) $outsider['id'], ['password_hash' => password_hash('OutsiderPass123', PASSWORD_DEFAULT)]);

        $http = new HttpClient('http://127.0.0.1:8123');
        $http->post('/login', ['email' => (string) $outsider['email'], 'password' => 'OutsiderPass123']);

        $response = $http->get('/deliverable-files/' . $fileId);
        assert_equals(403, $response['status'], 'A non-participant must be denied the private file.');
    },
];
