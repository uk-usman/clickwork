<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ContractService;

/**
 * Fixture helpers shared by the test cases.
 */
final class Fixtures
{
    private static int $counter = 0;

    /**
     * @param array<string,mixed> $overrides
     */
    public static function user(array $overrides = []): array
    {
        self::$counter++;

        $password = (string) ($overrides['password'] ?? 'TestPass12345');

        unset($overrides['password']);

        $data = array_merge([
            'name'          => 'Test User ' . self::$counter,
            'email'         => 'user' . self::$counter . '@test.local',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'user',
            'status'        => 'active',
            'default_mode'  => 'client',
        ], $overrides);

        $userId = User::createWithProfile($data, [
            'country' => 'Nigeria',
            'city'    => 'Kano',
            'state'   => 'Kano',
            'profile_visibility' => 'public',
        ]);

        $user = User::find($userId);

        if ($user === null) {
            fail('Fixture user could not be created.');
        }

        $user['plain_password'] = $password;

        return $user;
    }

    public static function admin(): array
    {
        return self::user(['role' => 'admin']);
    }

    public static function superAdmin(): array
    {
        return self::user(['role' => 'super_admin']);
    }

    /**
     * @param array<string,mixed> $overrides
     */
    public static function project(int $clientId, array $overrides = []): array
    {
        self::$counter++;

        $data = array_merge([
            'reference'    => 'PRJ-TEST' . self::$counter,
            'client_id'    => $clientId,
            'category_id'  => 1,
            'title'        => 'Test project ' . self::$counter . ' for automated checks',
            'slug'         => 'test-project-' . self::$counter,
            'description'  => 'A detailed test project description that is comfortably longer than forty characters.',
            'budget_type'  => 'fixed',
            'budget_min'   => '10000.00',
            'budget_max'   => '50000.00',
            'currency'     => 'NGN',
            'deadline'     => gmdate('Y-m-d', time() + 86400 * 14),
            'visibility'   => 'public',
            'status'       => 'published',
            'published_at' => gmdate('Y-m-d H:i:s'),
        ], $overrides);

        $projectId = Project::create($data);

        $project = Project::find($projectId);

        if ($project === null) {
            fail('Fixture project could not be created.');
        }

        return $project;
    }

    /**
     * @param array<string,mixed> $overrides
     */
    public static function proposal(int $projectId, int $freelancerId, array $overrides = []): array
    {
        self::$counter++;

        $data = array_merge([
            'reference'      => 'PRO-TEST' . self::$counter,
            'project_id'     => $projectId,
            'freelancer_id'  => $freelancerId,
            'cover_letter'   => 'I can complete this work within the requested timeframe and provide clear updates throughout the project.',
            'bid_amount'     => '25000.00',
            'currency'       => 'NGN',
            'estimated_days' => 10,
            'status'         => 'submitted',
        ], $overrides);

        $proposalId = Proposal::create($data);

        $proposal = Proposal::find($proposalId);

        if ($proposal === null) {
            fail('Fixture proposal could not be created.');
        }

        return $proposal;
    }

    /**
     * Create a proposal and award it, returning the resulting contract.
     *
     * @return array<string,mixed>
     */
    public static function contract(int $clientId, int $freelancerId, string $status = 'active'): array
    {
        $project = self::project($clientId, ['status' => 'in_progress']);
        $proposal = self::proposal((int) $project['id'], $freelancerId);

        $result = ContractService::awardProposal((int) $proposal['id'], $clientId);

        if (!$result['ok']) {
            fail('Fixture contract could not be created: ' . $result['message']);
        }

        $contractId = (int) $result['contract_id'];

        if (in_array($status, ['active', 'in_progress', 'deliverable_submitted', 'revision_requested', 'completed'], true)) {
            ContractService::accept($contractId, $clientId, '127.0.0.1', 'test-agent');
            ContractService::accept($contractId, $freelancerId, '127.0.0.1', 'test-agent');
        }

        if ($status !== 'active') {
            Contract::update($contractId, ['status' => $status]);
        }

        $contract = Contract::find($contractId);

        if ($contract === null) {
            fail('Fixture contract could not be loaded.');
        }

        return $contract;
    }

    public static function tableCount(string $table): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM `' . $table . '`');
    }
}
