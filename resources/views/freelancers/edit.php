<?php
/** @var array<string,mixed> $user */
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,array<string,mixed>> $mySkills */
/** @var array<int,string> $regions */
$mySkillMap = [];
foreach ($mySkills as $row) {
    $mySkillMap[(int) $row['skill_id']] = (string) $row['proficiency'];
}
$avatar = avatar_url($user['avatar_path'] ?? null);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Freelancer profile</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('freelancer.profile.update')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="freelancer_title">Professional title</label>
                            <input type="text" class="form-control" id="freelancer_title" name="freelancer_title"
                                   value="<?= old('freelancer_title', (string) ($user['freelancer_title'] ?? '')) ?>"
                                   placeholder="e.g. Full-stack PHP Developer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="headline">Profile headline</label>
                            <input type="text" class="form-control" id="headline" name="headline" maxlength="180"
                                   value="<?= old('headline', (string) ($user['headline'] ?? '')) ?>"
                                   placeholder="One line that describes what you do">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="freelancer_overview">Overview</label>
                            <textarea class="form-control" id="freelancer_overview" name="freelancer_overview" rows="6"
                                      placeholder="Describe your experience, the kind of work you take on and how you work with clients."><?= old('freelancer_overview', (string) ($user['freelancer_overview'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bio">Short biography</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?= old('bio', (string) ($user['bio'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="state">State</label>
                            <select class="form-select" id="state" name="state">
                                <option value="">Select…</option>
                                <?php foreach ($regions as $region): ?>
                                    <option value="<?= e($region) ?>" <?= old('state', (string) ($user['state'] ?? '')) === $region ? 'selected' : '' ?>><?= e($region) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= old('city', (string) ($user['city'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="hourly_rate">Hourly rate (₦)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="hourly_rate" name="hourly_rate"
                                   value="<?= old('hourly_rate', (string) ($user['hourly_rate'] ?? '')) ?>">
                            <div class="form-text">Optional. You can also price per project in each proposal.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="experience_years">Years of experience</label>
                            <input type="number" min="0" max="70" class="form-control" id="experience_years" name="experience_years"
                                   value="<?= old('experience_years', (string) ($user['experience_years'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="availability">Availability</label>
                            <select class="form-select" id="availability" name="availability">
                                <?php foreach (['available' => 'Available now', 'part_time' => 'Part time', 'busy' => 'Busy', 'unavailable' => 'Not available'] as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= old('availability', (string) ($user['availability'] ?? 'available')) === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="website">Website or portfolio link</label>
                            <input type="url" class="form-control" id="website" name="website"
                                   value="<?= old('website', (string) ($user['website'] ?? '')) ?>" placeholder="https://">
                            <?php $field = 'website'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="languages">Languages</label>
                            <input type="text" class="form-control" id="languages" name="languages"
                                   value="<?= old('languages', (string) ($user['languages'] ?? '')) ?>" placeholder="English, Hausa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="profile_visibility">Profile visibility</label>
                            <select class="form-select" id="profile_visibility" name="profile_visibility">
                                <option value="public" <?= old('profile_visibility', (string) ($user['profile_visibility'] ?? 'public')) === 'public' ? 'selected' : '' ?>>Public — visible in search and listings</option>
                                <option value="private" <?= old('profile_visibility', (string) ($user['profile_visibility'] ?? 'public')) === 'private' ? 'selected' : '' ?>>Private — hidden from public listings</option>
                            </select>
                            <div class="form-text">Private profiles are not shown in public search results.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">Save profile</button>
                </form>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Skills</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('freelancer.profile.skills')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="row g-2" style="max-height:340px;overflow-y:auto;">
                        <?php foreach ($skills as $skill): ?>
                            <?php $skillId = (int) $skill['id']; ?>
                            <div class="col-md-6">
                                <div class="border rounded p-2 d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0" type="checkbox" name="skills[]"
                                           id="skill-<?= $skillId ?>" value="<?= $skillId ?>"
                                           <?= isset($mySkillMap[$skillId]) ? 'checked' : '' ?>>
                                    <label class="form-check-label small flex-grow-1" for="skill-<?= $skillId ?>"><?= e($skill['name']) ?></label>
                                    <select class="form-select form-select-sm" name="proficiency_<?= $skillId ?>" style="width:auto;">
                                        <?php foreach (['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'expert' => 'Expert'] as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= ($mySkillMap[$skillId] ?? 'intermediate') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save skills</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Profile photo</div>
            <div class="cw-card-body text-center">
                <?php if ($avatar !== null): ?>
                    <img src="<?= e($avatar) ?>" alt="" class="cw-avatar cw-avatar--xl mb-3">
                <?php else: ?>
                    <span class="cw-avatar cw-avatar--xl mb-3"><?= e(initials((string) $user['name'])) ?></span>
                <?php endif; ?>

                <form method="post" action="<?= e(route('freelancer.profile.avatar')) ?>" enctype="multipart/form-data"
                      data-lock-submit data-loading-text="Uploading…">
                    <?= csrf_field() ?>
                    <input type="file" class="form-control mb-2" name="avatar" id="avatar"
                           accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <div class="form-text text-start">JPG, PNG, GIF or WebP. Maximum 2 MB. SVG is not accepted.</div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Upload photo</button>
                </form>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Profile strength</div>
            <div class="cw-card-body">
                <?php
                $checks = [
                    'Professional title added' => !empty($user['freelancer_title']),
                    'Overview written' => mb_strlen((string) ($user['freelancer_overview'] ?? '')) > 60,
                    'At least 3 skills selected' => count($mySkillMap) >= 3,
                    'Location provided' => !empty($user['state']),
                    'Profile photo uploaded' => $avatar !== null,
                ];
                $done = count(array_filter($checks));
                $percent = (int) round(($done / max(1, count($checks))) * 100);
                ?>
                <div class="progress mb-3" style="height:8px;">
                    <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                </div>
                <p class="small text-muted"><?= $percent ?>% complete</p>
                <ul class="list-unstyled small mb-0">
                    <?php foreach ($checks as $label => $ok): ?>
                        <li class="mb-1">
                            <i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?> me-2"></i>
                            <?= e($label) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-outline-primary w-100 mt-3" href="<?= e(route('freelancer.portfolio')) ?>">Manage portfolio</a>
            </div>
        </div>
    </div>
</div>
