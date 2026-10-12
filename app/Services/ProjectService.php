<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Project;
use App\Models\Skill;

/**
 * Project creation, editing and moderation.
 */
final class ProjectService
{
    public const BUDGET_TYPES = ['fixed', 'hourly'];
    public const VISIBILITIES = ['public', 'private'];

    /**
     * @param  array<string,mixed> $input
     * @return array{ok:bool,errors:array<string,string>,project_id?:int,reference?:string}
     */
    public static function create(int $clientId, array $input, bool $submitForReview): array
    {
        $data = self::validate($input);

        if ($data['errors'] !== []) {
            return ['ok' => false, 'errors' => $data['errors']];
        }

        $reference = self::generateReference('PRJ');

        $projectId = Database::transaction(static function () use ($clientId, $data, $submitForReview, $reference): int {
            $id = Project::create([
                'reference'             => $reference,
                'client_id'             => $clientId,
                'category_id'           => $data['category_id'],
                'title'                 => $data['title'],
                'slug'                  => $data['slug'],
                'description'           => $data['description'],
                'required_skills_text'  => $data['required_skills_text'],
                'budget_type'           => $data['budget_type'],
                'budget_min'            => $data['budget_min'],
                'budget_max'            => $data['budget_max'],
                'currency'              => $data['currency'],
                'deadline'              => $data['deadline'],
                'expected_deliverables' => $data['expected_deliverables'],
                'visibility'            => $data['visibility'],
                'status'                => $submitForReview ? 'pending' : 'draft',
            ]);

            Project::syncSkills($id, $data['skill_ids']);

            return $id;
        });

        AuditService::record(
            $submitForReview ? 'project.submitted' : 'project.drafted',
            $submitForReview ? 'Project submitted for moderation' : 'Project saved as draft',
            'project',
            $projectId,
            ['reference' => $reference]
        );

        return ['ok' => true, 'errors' => [], 'project_id' => $projectId, 'reference' => $reference];
    }

    /**
     * @param  array<string,mixed> $input
     * @return array{ok:bool,errors:array<string,string>}
     */
    public static function update(int $projectId, int $actorId, array $input, bool $submitForReview): array
    {
        $project = Project::find($projectId);

        if ($project === null) {
            return ['ok' => false, 'errors' => ['project' => 'Project not found.']];
        }

        if ((int) $project['client_id'] !== $actorId) {
            return ['ok' => false, 'errors' => ['project' => 'You do not have permission to edit this project.']];
        }

        if (!in_array((string) $project['status'], ['draft', 'rejected', 'pending'], true)) {
            return ['ok' => false, 'errors' => ['project' => 'This project can no longer be edited because it is already published or in progress.']];
        }

        $data = self::validate($input, $projectId);

        if ($data['errors'] !== []) {
            return ['ok' => false, 'errors' => $data['errors']];
        }

        Database::transaction(static function () use ($projectId, $data, $submitForReview): void {
            Project::update($projectId, [
                'category_id'           => $data['category_id'],
                'title'                 => $data['title'],
                'slug'                  => $data['slug'],
                'description'           => $data['description'],
                'required_skills_text'  => $data['required_skills_text'],
                'budget_type'           => $data['budget_type'],
                'budget_min'            => $data['budget_min'],
                'budget_max'            => $data['budget_max'],
                'currency'              => $data['currency'],
                'deadline'              => $data['deadline'],
                'expected_deliverables' => $data['expected_deliverables'],
                'visibility'            => $data['visibility'],
                'status'                => $submitForReview ? 'pending' : 'draft',
                'moderation_note'       => null,
            ]);

            Project::syncSkills($projectId, $data['skill_ids']);
        });

        AuditService::record('project.updated', 'Project details updated', 'project', $projectId);

        return ['ok' => true, 'errors' => []];
    }

    /**
     * @param  array<string,mixed> $input
     * @return array{errors:array<string,string>,title:string,slug:string,description:string,category_id:?int,
     *               required_skills_text:?string,budget_type:string,budget_min:?string,budget_max:?string,
     *               currency:string,deadline:?string,expected_deliverables:?string,visibility:string,skill_ids:array<int,int>}
     */
    public static function validate(array $input, ?int $ignoreProjectId = null): array
    {
        $errors = [];

        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $budgetType = (string) ($input['budget_type'] ?? 'fixed');
        $visibility = (string) ($input['visibility'] ?? 'public');
        $currency = strtoupper(trim((string) ($input['currency'] ?? 'NGN')));
        $deadline = trim((string) ($input['deadline'] ?? ''));
        $requiredSkillsText = trim((string) ($input['required_skills_text'] ?? ''));
        $expectedDeliverables = trim((string) ($input['expected_deliverables'] ?? ''));

        $budgetMinRaw = trim((string) ($input['budget_min'] ?? ''));
        $budgetMaxRaw = trim((string) ($input['budget_max'] ?? ''));

        if ($title === '') {
            $errors['title'] = 'Please enter a project title.';
        } elseif (mb_strlen($title) < 8) {
            $errors['title'] = 'The project title should be at least 8 characters.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'] = 'The project title must not exceed 200 characters.';
        }

        if ($description === '') {
            $errors['description'] = 'Please describe the work required.';
        } elseif (mb_strlen($description) < 40) {
            $errors['description'] = 'Please provide a more detailed description (at least 40 characters).';
        }

        if ($categoryId <= 0) {
            $errors['category_id'] = 'Please choose a category.';
        }

        if (!in_array($budgetType, self::BUDGET_TYPES, true)) {
            $errors['budget_type'] = 'Please choose a valid budget type.';
        }

        if (!in_array($visibility, self::VISIBILITIES, true)) {
            $errors['visibility'] = 'Please choose a valid visibility option.';
        }

        if (!in_array($currency, ['NGN', 'USD', 'GBP', 'EUR'], true)) {
            $errors['currency'] = 'Please choose a supported currency.';
        }

        $budgetMin = null;
        $budgetMax = null;

        if ($budgetType === 'fixed') {
            if ($budgetMinRaw === '' || !is_numeric($budgetMinRaw)) {
                $errors['budget_min'] = 'Please enter the minimum budget for a fixed-price project.';
            }
            if ($budgetMaxRaw === '' || !is_numeric($budgetMaxRaw)) {
                $errors['budget_max'] = 'Please enter the maximum budget for a fixed-price project.';
            }
        }

        if ($budgetMinRaw !== '' && is_numeric($budgetMinRaw)) {
            $budgetMin = number_format((float) $budgetMinRaw, 2, '.', '');
        }
        if ($budgetMaxRaw !== '' && is_numeric($budgetMaxRaw)) {
            $budgetMax = number_format((float) $budgetMaxRaw, 2, '.', '');
        }

        if ($budgetMin !== null && (float) $budgetMin <= 0) {
            $errors['budget_min'] = 'The minimum budget must be greater than zero.';
        }
        if ($budgetMax !== null && (float) $budgetMax <= 0) {
            $errors['budget_max'] = 'The maximum budget must be greater than zero.';
        }
        if ($budgetMin !== null && $budgetMax !== null && (float) $budgetMax < (float) $budgetMin) {
            $errors['budget_max'] = 'The maximum budget cannot be lower than the minimum budget.';
        }

        if ($deadline !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $deadline);
            if ($parsed === false || $parsed->format('Y-m-d') !== $deadline) {
                $errors['deadline'] = 'Please enter a valid deadline date.';
            } elseif ($deadline < gmdate('Y-m-d')) {
                $errors['deadline'] = 'The deadline cannot be in the past.';
            }
        }

        $skillIds = Skill::validIds(is_array($input['skill_ids'] ?? null) ? $input['skill_ids'] : []);

        return [
            'errors'                => $errors,
            'title'                 => $title,
            'slug'                  => self::uniqueSlug($title, $ignoreProjectId),
            'description'           => $description,
            'category_id'           => $categoryId > 0 ? $categoryId : null,
            'required_skills_text'  => $requiredSkillsText !== '' ? mb_substr($requiredSkillsText, 0, 500) : null,
            'budget_type'           => $budgetType,
            'budget_min'            => $budgetMin,
            'budget_max'            => $budgetMax,
            'currency'              => $currency,
            'deadline'              => $deadline !== '' ? $deadline : null,
            'expected_deliverables' => $expectedDeliverables !== '' ? $expectedDeliverables : null,
            'visibility'            => $visibility,
            'skill_ids'             => $skillIds,
        ];
    }

    public static function uniqueSlug(string $title, ?int $ignoreProjectId = null): string
    {
        $base = self::slugify($title);
        $slug = $base;
        $suffix = 2;

        while (true) {
            $sql = 'SELECT COUNT(*) FROM `projects` WHERE `slug` = ?';
            $params = [$slug];

            if ($ignoreProjectId !== null) {
                $sql .= ' AND `id` <> ?';
                $params[] = $ignoreProjectId;
            }

            if ((int) Database::scalar($sql, $params) === 0) {
                return $slug;
            }

            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }

    public static function slugify(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? mb_substr($slug, 0, 180) : 'project';
    }

    public static function generateReference(string $prefix): string
    {
        return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Administrator moderation decision.
     *
     * @return array{ok:bool,message:string}
     */
    public static function moderate(int $projectId, int $adminId, string $decision, string $note = ''): array
    {
        $project = Project::find($projectId);

        if ($project === null) {
            return ['ok' => false, 'message' => 'Project not found.'];
        }

        if (!in_array($decision, ['approve', 'reject'], true)) {
            return ['ok' => false, 'message' => 'Invalid moderation decision.'];
        }

        if (!in_array((string) $project['status'], ['pending', 'rejected'], true)) {
            return ['ok' => false, 'message' => 'Only projects awaiting review can be moderated.'];
        }

        $status = $decision === 'approve' ? 'published' : 'rejected';

        Project::update($projectId, [
            'status'          => $status,
            'moderated_by'    => $adminId,
            'moderated_at'    => gmdate('Y-m-d H:i:s'),
            'moderation_note' => $note !== '' ? mb_substr($note, 0, 500) : null,
            'published_at'    => $decision === 'approve' ? gmdate('Y-m-d H:i:s') : null,
        ]);

        AuditService::record(
            'project.moderated',
            'Project ' . ($decision === 'approve' ? 'approved and published' : 'rejected'),
            'project',
            $projectId,
            ['decision' => $decision, 'note' => $note],
            $adminId,
            'admin'
        );

        NotificationService::notify(
            (int) $project['client_id'],
            'project_moderation',
            $decision === 'approve' ? 'Your project is now live' : 'Your project needs changes',
            $decision === 'approve'
                ? 'Your project "' . $project['title'] . '" has been approved and is now publicly visible.'
                : 'Your project "' . $project['title'] . '" was not approved. ' . ($note !== '' ? 'Reason: ' . $note : 'Please review the community guidelines and try again.'),
            route('client.projects.show', ['id' => $projectId])
        );

        return ['ok' => true, 'message' => $decision === 'approve' ? 'Project approved and published.' : 'Project rejected.'];
    }
}
