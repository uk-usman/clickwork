<?php /** @var array<string,string|null> $settings */
$get = static fn (string $key, string $default = ''): string => (string) ($settings[$key] ?? $default);
?>
<form method="post" action="<?= e(route('admin.settings.save')) ?>" data-lock-submit data-loading-text="Saving…">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="cw-card mb-4">
                <div class="cw-card-header">Site identity</div>
                <div class="cw-card-body">
                    <div class="mb-3">
                        <label class="form-label" for="site_name">Site name</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?= e($get('site_name', 'CircleWork Africa')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="site_tagline">Tagline</label>
                        <input type="text" class="form-control" id="site_tagline" name="site_tagline" value="<?= e($get('site_tagline', 'Find Work. Hire Talent. Build Trust.')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="site_logo_text">Logo text / initials</label>
                        <input type="text" class="form-control" id="site_logo_text" name="site_logo_text" value="<?= e($get('site_logo_text')) ?>">
                        <div class="form-text">Used where a text logo is preferable to an image.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="base_url">Base URL</label>
                        <input type="url" class="form-control" id="base_url" name="base_url" value="<?= e($get('base_url', (string) App\Core\App::configValue('app.url', ''))) ?>">
                        <div class="form-text">Displayed for reference. The authoritative base URL is APP_URL in the .env file.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="timezone">Timezone</label>
                        <input type="text" class="form-control" id="timezone" name="timezone" value="<?= e($get('timezone', (string) App\Core\App::configValue('app.timezone', 'Africa/Lagos'))) ?>">
                        <div class="form-text">Timestamps are stored in UTC and displayed in this timezone.</div>
                    </div>
                </div>
            </div>

            <div class="cw-card">
                <div class="cw-card-header">Marketing focus</div>
                <div class="cw-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="marketing_city">Initial marketing city</label>
                            <input type="text" class="form-control" id="marketing_city" name="marketing_city" value="<?= e($get('marketing_city', 'Kano')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="marketing_state">Initial marketing state</label>
                            <input type="text" class="form-control" id="marketing_state" name="marketing_state" value="<?= e($get('marketing_state', 'Kano')) ?>">
                        </div>
                    </div>
                    <p class="form-text mb-0">The platform still supports online work between users in different cities.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="cw-card mb-4">
                <div class="cw-card-header">Contact information</div>
                <div class="cw-card-body">
                    <div class="mb-3">
                        <label class="form-label" for="support_email">Support email</label>
                        <input type="email" class="form-control" id="support_email" name="support_email" value="<?= e($get('support_email')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="support_phone">Support phone</label>
                        <input type="text" class="form-control" id="support_phone" name="support_phone" value="<?= e($get('support_phone')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_address">Address</label>
                        <input type="text" class="form-control" id="contact_address" name="contact_address" value="<?= e($get('contact_address')) ?>">
                    </div>
                </div>
            </div>

            <div class="cw-card mb-4">
                <div class="cw-card-header">Revenue settings</div>
                <div class="cw-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="featured_price">Featured listing price (₦)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="featured_price" name="featured_price" value="<?= e($get('featured_price', '1000.00')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="featured_duration_days">Featured duration (days)</label>
                            <input type="number" min="1" max="365" class="form-control" id="featured_duration_days" name="featured_duration_days" value="<?= e($get('featured_duration_days', '14')) ?>">
                        </div>
                    </div>
                    <p class="form-text mb-0">Featured placements are activated only after manual payment verification.</p>
                </div>
            </div>

            <div class="cw-card">
                <div class="cw-card-header">Registration</div>
                <div class="cw-card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" value="1"
                               <?= $get('registration_open', '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="registration_open">Allow public registration of standard user accounts</label>
                    </div>
                    <p class="form-text mb-0">
                        Administrator and super administrator accounts are never created through public registration.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save platform settings</button>
    </div>
</form>
