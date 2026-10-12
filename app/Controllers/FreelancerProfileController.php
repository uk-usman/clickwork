<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Services\AuditService;
use App\Services\SubscriptionService;
use App\Services\UploadService;

/**
 * Freelancer profile, skills and portfolio management.
 */
final class FreelancerProfileController extends Controller
{
    public function edit(Request $request): string
    {
        $userId = (int) Auth::id();

        return $this->dashboardView('freelancers/edit', [
            'title'      => 'Freelancer profile',
            'user'       => User::withProfile($userId),
            'skills'     => Skill::activeList(),
            'mySkills'   => UserSkill::forUser($userId),
            'regions'    => \App\Controllers\AuthController::nigerianStates(),
        ]);
    }

    public function update(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $errors = [];

        $headline = $request->string('headline');
        $title = $request->string('freelancer_title');
        $overview = $request->string('freelancer_overview');
        $bio = $request->string('bio');
        $city = $request->string('city');
        $state = $request->string('state');
        $website = $request->string('website');
        $hourlyRate = $request->string('hourly_rate');
        $experience = $request->string('experience_years');
        $availability = $request->string('availability', 'available');
        $visibility = $request->string('profile_visibility', 'public');
        $languages = $request->string('languages');

        if (mb_strlen($headline) > 180) {
            $errors['headline'] = 'Headline must not exceed 180 characters.';
        }

        if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
            $errors['website'] = 'Please enter a valid website URL.';
        } elseif ($website !== '' && !in_array(strtolower((string) parse_url($website, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $errors['website'] = 'Website URLs must start with http:// or https://.';
        }

        if ($hourlyRate !== '' && (!is_numeric($hourlyRate) || (float) $hourlyRate < 0)) {
            $errors['hourly_rate'] = 'Please enter a valid hourly rate.';
        }

        if ($experience !== '' && (filter_var($experience, FILTER_VALIDATE_INT) === false || (int) $experience < 0 || (int) $experience > 70)) {
            $errors['experience_years'] = 'Please enter a valid number of years of experience.';
        }

        if (!in_array($availability, ['available', 'part_time', 'busy', 'unavailable'], true)) {
            $errors['availability'] = 'Please choose a valid availability status.';
        }

        if (!in_array($visibility, ['public', 'private'], true)) {
            $errors['profile_visibility'] = 'Please choose a valid visibility option.';
        }

        if ($errors !== []) {
            $this->backWithErrors('Please correct the highlighted fields.', $errors, [], '/freelancer/profile');
        }

        UserProfile::ensureForUser($userId);

        Database::affected(
            'UPDATE `user_profiles` SET
                `headline` = ?, `bio` = ?, `city` = ?, `state` = ?, `website` = ?,
                `freelancer_title` = ?, `freelancer_overview` = ?, `experience_years` = ?,
                `hourly_rate` = ?, `availability` = ?, `profile_visibility` = ?, `languages` = ?
             WHERE `user_id` = ?',
            [
                $headline !== '' ? $headline : null,
                $bio !== '' ? $bio : null,
                $city !== '' ? $city : null,
                $state !== '' ? $state : null,
                $website !== '' ? $website : null,
                $title !== '' ? $title : null,
                $overview !== '' ? $overview : null,
                $experience !== '' ? (int) $experience : null,
                $hourlyRate !== '' ? number_format((float) $hourlyRate, 2, '.', '') : null,
                $availability,
                $visibility,
                $languages !== '' ? $languages : null,
                $userId,
            ]
        );

        AuditService::record('freelancer.profile_updated', 'Freelancer profile updated', 'user', $userId);

        $this->success('Your freelancer profile has been saved.', 'freelancer.profile');
    }

    public function updateSkills(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $skillIds = Skill::validIds((array) $request->input('skills', []));

        $rows = [];
        foreach ($skillIds as $skillId) {
            $rows[] = [
                'skill_id'    => $skillId,
                'proficiency' => in_array($request->string('proficiency_' . $skillId), ['beginner', 'intermediate', 'expert'], true)
                    ? $request->string('proficiency_' . $skillId)
                    : 'intermediate',
            ];
        }

        UserSkill::sync($userId, $rows);
        AuditService::record('freelancer.skills_updated', 'Freelancer skills updated', 'user', $userId);

        $this->success('Your skills have been updated.', 'freelancer.profile');
    }

    public function updateAvatar(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $file = $request->file('avatar');

        if ($file === null) {
            Session::flash('danger', 'Please choose an image file to upload.');
            $this->redirectToRoute('freelancer.profile');
        }

        $result = UploadService::store(
            $file,
            (string) App::configValue('storage.uploads', sys_get_temp_dir()),
            UploadService::IMAGE_EXTENSIONS,
            (int) App::configValue('security.portfolio_max_bytes', 2097152)
        );

        if (!$result['ok']) {
            Session::flash('danger', $result['error'] ?? 'The image could not be uploaded.');
            $this->redirectToRoute('freelancer.profile');
        }

        UserProfile::ensureForUser($userId);

        $old = Database::scalar('SELECT `avatar_path` FROM `user_profiles` WHERE `user_id` = ?', [$userId]);

        Database::affected(
            'UPDATE `user_profiles` SET `avatar_path` = ? WHERE `user_id` = ?',
            [$result['data']['stored_name'], $userId]
        );

        if (is_string($old) && $old !== '') {
            UploadService::delete((string) App::configValue('storage.uploads', ''), $old);
        }

        AuditService::record('freelancer.avatar_updated', 'Profile photo updated', 'user', $userId);

        $this->success('Your profile photo has been updated.', 'freelancer.profile');
    }

    public function portfolio(Request $request): string
    {
        $userId = (int) Auth::id();

        return $this->dashboardView('freelancers/portfolio', [
            'title'      => 'Portfolio',
            'items'      => PortfolioItem::forUser($userId),
            'categories' => Category::activeList(),
            'limits'     => SubscriptionService::limitsFor($userId),
        ]);
    }

    public function storePortfolio(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $errors = [];

        $title = $request->string('title');
        $description = $request->string('description');
        $projectUrl = $request->string('project_url');
        $categoryId = $request->int('category_id');

        if (mb_strlen($title) < 3) {
            $errors['title'] = 'Please enter a portfolio title.';
        }

        if ($projectUrl !== '' && !in_array(strtolower((string) parse_url($projectUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $errors['project_url'] = 'Project links must start with http:// or https://.';
        } elseif ($projectUrl !== '' && filter_var($projectUrl, FILTER_VALIDATE_URL) === false) {
            $errors['project_url'] = 'Please enter a valid project URL.';
        }

        $limits = SubscriptionService::limitsFor($userId);
        if (PortfolioItem::countForUser($userId) >= (int) $limits['portfolio_items']) {
            $errors['limit'] = 'Your plan allows up to ' . (int) $limits['portfolio_items'] . ' portfolio items. Upgrade to add more.';
        }

        if ($errors !== []) {
            $this->backWithErrors('Please correct the highlighted fields.', $errors, [], '/freelancer/portfolio');
        }

        $imagePath = null;
        $file = $request->file('image');

        if ($file !== null && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $result = UploadService::store(
                $file,
                (string) App::configValue('storage.uploads', sys_get_temp_dir()),
                UploadService::IMAGE_EXTENSIONS,
                (int) App::configValue('security.portfolio_max_bytes', 2097152)
            );

            if (!$result['ok']) {
                $this->backWithErrors('The image could not be uploaded.', ['image' => $result['error'] ?? 'Upload failed.'], [], '/freelancer/portfolio');
            }

            $imagePath = $result['data']['stored_name'];
        }

        PortfolioItem::create([
            'user_id'     => $userId,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'title'       => mb_substr($title, 0, 180),
            'description' => $description !== '' ? $description : null,
            'project_url' => $projectUrl !== '' ? $projectUrl : null,
            'image_path'  => $imagePath,
            'is_visible'  => 1,
        ]);

        AuditService::record('portfolio.created', 'Portfolio item added', 'portfolio_item', null);

        $this->success('Your portfolio item has been added.', 'freelancer.portfolio');
    }

    public function deletePortfolio(Request $request): never
    {
        $userId = (int) Auth::id();
        $itemId = (int) ($request->route('id') ?? 0);
        $item = PortfolioItem::ownedBy($itemId, $userId);

        if ($item === null) {
            $this->failure('That portfolio item could not be found.', 'freelancer.portfolio');
        }

        PortfolioItem::deleteBy(['id' => $itemId, 'user_id' => $userId]);

        if (is_string($item['image_path']) && $item['image_path'] !== '') {
            UploadService::delete((string) App::configValue('storage.uploads', ''), $item['image_path']);
        }

        AuditService::record('portfolio.deleted', 'Portfolio item removed', 'portfolio_item', $itemId);

        $this->success('Portfolio item removed.', 'freelancer.portfolio');
    }

    public function togglePortfolioVisibility(Request $request): never
    {
        $userId = (int) Auth::id();
        $itemId = (int) ($request->route('id') ?? 0);
        $item = PortfolioItem::ownedBy($itemId, $userId);

        if ($item === null) {
            $this->failure('That portfolio item could not be found.', 'freelancer.portfolio');
        }

        PortfolioItem::update($itemId, ['is_visible' => (int) $item['is_visible'] === 1 ? 0 : 1]);

        $this->success('Portfolio visibility updated.', 'freelancer.portfolio');
    }
}
