<?php

/**
 * Seeds categories, skills and default subscription plans.
 * Safe to run repeatedly: existing records are updated, not duplicated.
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

$categories = [
    ['Web & Software Development', 'bi-code-slash', 'Website, web application and software development work.'],
    ['Design & Creative', 'bi-palette', 'Graphic design, branding, illustration and creative work.'],
    ['Writing & Translation', 'bi-pencil-square', 'Copywriting, content writing, editing and translation.'],
    ['Digital Marketing', 'bi-graph-up-arrow', 'Social media, SEO, advertising and growth work.'],
    ['Data & Analytics', 'bi-bar-chart', 'Data entry, analysis, dashboards and reporting.'],
    ['Video & Audio', 'bi-camera-video', 'Video editing, animation, voice-over and audio production.'],
    ['Business & Admin Support', 'bi-briefcase', 'Virtual assistance, project coordination and administrative support.'],
    ['Engineering & Technical Services', 'bi-gear', 'Technical, engineering and field service work.'],
    ['Tutoring & Education', 'bi-mortarboard', 'Teaching, tutoring and course creation.'],
    ['Repairs & Maintenance', 'bi-tools', 'Repair, installation and maintenance services.'],
];

$skills = [
    'Web & Software Development' => ['PHP', 'MySQL', 'JavaScript', 'HTML & CSS', 'Bootstrap', 'WordPress', 'Laravel', 'API Integration', 'Mobile App Development', 'Bug Fixing'],
    'Design & Creative' => ['Logo Design', 'Brand Identity', 'UI/UX Design', 'Flyer Design', 'Illustration', 'Product Packaging Design'],
    'Writing & Translation' => ['Content Writing', 'Copywriting', 'Proofreading', 'Technical Writing', 'Hausa Translation', 'Yoruba Translation', 'Igbo Translation'],
    'Digital Marketing' => ['Social Media Management', 'SEO', 'Facebook Ads', 'Google Ads', 'Email Marketing', 'Content Strategy'],
    'Data & Analytics' => ['Data Entry', 'Excel', 'Data Cleaning', 'Power BI', 'SQL Reporting', 'Survey Analysis'],
    'Video & Audio' => ['Video Editing', 'Motion Graphics', 'Voice Over', 'Audio Mixing', 'Subtitling'],
    'Business & Admin Support' => ['Virtual Assistance', 'Project Coordination', 'Customer Support', 'Bookkeeping', 'Business Plan Writing'],
    'Engineering & Technical Services' => ['Solar Installation', 'Electrical Wiring', 'CCTV Installation', 'CAD Drafting'],
    'Tutoring & Education' => ['Mathematics Tutoring', 'English Tutoring', 'Exam Preparation', 'Course Design'],
    'Repairs & Maintenance' => ['Phone Repair', 'Laptop Repair', 'Generator Servicing', 'Air Conditioner Servicing'],
];

$plans = [
    [
        'code' => 'free', 'name' => 'Free', 'audience' => 'both', 'price' => '0.00', 'billing_period' => 'monthly',
        'description' => 'Everything you need to get started on CircleWork Africa.',
        'features' => ['Basic profile', 'Browse projects', 'Submit proposals', 'Basic project posting', 'Basic messaging'],
        'limits' => ['active_projects' => 3, 'proposals_per_day' => 10, 'portfolio_items' => 5, 'featured_projects' => 0, 'applicant_filters' => 0, 'profile_analytics' => 0],
        'is_free' => 1, 'sort_order' => 1,
    ],
    [
        'code' => 'client_pro', 'name' => 'Client Pro', 'audience' => 'client', 'price' => '3000.00', 'billing_period' => 'monthly',
        'description' => 'For clients running several projects at once.',
        'features' => ['Additional project management tools', 'Higher project posting limits', 'Enhanced applicant filters', 'Priority support'],
        'limits' => ['active_projects' => 25, 'proposals_per_day' => 50, 'portfolio_items' => 5, 'featured_projects' => 3, 'applicant_filters' => 1, 'profile_analytics' => 0],
        'is_free' => 0, 'sort_order' => 2,
    ],
    [
        'code' => 'freelancer_pro', 'name' => 'Freelancer Pro', 'audience' => 'freelancer', 'price' => '2000.00', 'billing_period' => 'monthly',
        'description' => 'For freelancers who want more visibility and capacity.',
        'features' => ['Enhanced profile display', 'Additional portfolio features', 'Profile analytics', 'Priority proposal visibility'],
        'limits' => ['active_projects' => 3, 'proposals_per_day' => 60, 'portfolio_items' => 40, 'featured_projects' => 0, 'applicant_filters' => 0, 'profile_analytics' => 1],
        'is_free' => 0, 'sort_order' => 3,
    ],
];

$categoryIds = [];

foreach ($categories as $index => [$name, $icon, $description]) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '', '-'));

    Database::run(
        'INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `is_active`, `sort_order`)
         VALUES (?, ?, ?, ?, 1, ?)
         ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`),
                                 `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`)',
        [$name, $slug, $description, $icon, $index + 1]
    );

    $row = Database::selectOne('SELECT id FROM `categories` WHERE slug = ?', [$slug]);
    $categoryIds[$name] = (int) $row['id'];
}

cw_line('Categories seeded: ' . count($categoryIds));

$skillCount = 0;
foreach ($skills as $categoryName => $names) {
    $categoryId = $categoryIds[$categoryName] ?? null;

    foreach ($names as $name) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '', '-'));

        Database::run(
            'INSERT INTO `skills` (`category_id`, `name`, `slug`, `is_active`)
             VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `category_id` = VALUES(`category_id`)',
            [$categoryId, $name, $slug]
        );

        $skillCount++;
    }
}

cw_line('Skills seeded: ' . $skillCount);

foreach ($plans as $plan) {
    Database::run(
        'INSERT INTO `subscription_plans`
            (`code`, `name`, `audience`, `price`, `currency`, `billing_period`, `description`, `features`, `limits`, `is_active`, `is_free`, `sort_order`)
         VALUES (?, ?, ?, ?, \'NGN\', ?, ?, ?, ?, 1, ?, ?)
         ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `audience` = VALUES(`audience`), `price` = VALUES(`price`),
                                 `billing_period` = VALUES(`billing_period`), `description` = VALUES(`description`),
                                 `features` = VALUES(`features`), `limits` = VALUES(`limits`),
                                 `is_free` = VALUES(`is_free`), `sort_order` = VALUES(`sort_order`)',
        [
            $plan['code'],
            $plan['name'],
            $plan['audience'],
            $plan['price'],
            $plan['billing_period'],
            $plan['description'],
            json_encode($plan['features'], JSON_UNESCAPED_UNICODE),
            json_encode($plan['limits'], JSON_UNESCAPED_UNICODE),
            $plan['is_free'],
            $plan['sort_order'],
        ]
    );
}

cw_line('Subscription plans seeded: ' . count($plans));

// Default platform settings (only inserted when missing).
$settings = [
    'site_name' => 'CircleWork Africa',
    'site_tagline' => 'Find Work. Hire Talent. Build Trust.',
    'support_email' => 'support@circlework.africa',
    'support_phone' => '',
    'contact_address' => '',
    'marketing_city' => 'Kano',
    'marketing_state' => 'Kano',
    'featured_price' => '1000.00',
    'featured_duration_days' => '14',
    'registration_open' => '1',
];

foreach ($settings as $key => $value) {
    Database::run(
        'INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `is_public`)
         VALUES (?, ?, \'general\', \'string\', 1)
         ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`',
        [$key, $value]
    );
}

cw_line('Site settings ensured: ' . count($settings));

cw_line('Done. Categories, skills, plans and settings are ready.');
