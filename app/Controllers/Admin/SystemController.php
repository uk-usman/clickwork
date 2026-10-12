<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\AdminAnnouncement;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Platform settings, announcements, audit logs and administrator profile.
 */
final class SystemController extends AdminController
{
    public function settings(Request $request): string
    {
        return $this->adminView('admin/settings', [
            'title'          => 'Platform settings',
            'settings'       => Setting::all(),
            'adminNavActive' => 'settings',
        ]);
    }

    public function saveSettings(Request $request): mixed
    {
        $userId = (int) Auth::id();

        $textKeys = [
            'site_name', 'site_tagline', 'support_email', 'support_phone', 'contact_address',
            'marketing_city', 'marketing_state', 'base_url', 'timezone', 'site_logo_text',
            'featured_price', 'featured_duration_days',
        ];

        foreach ($textKeys as $key) {
            if (!$request->has($key)) {
                continue;
            }
            $value = $request->string($key);
            Setting::set($key, $value !== '' ? $value : null, $userId, 'general', 'string', true);
        }

        foreach (['footer_note', 'homepage_notice'] as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->string($key), $userId, 'content', 'text', true);
            }
        }

        if ($request->has('registration_open')) {
            Setting::set('registration_open', $request->bool('registration_open') ? '1' : '0', $userId, 'general', 'boolean', true);
        }

        AuditService::record('admin.settings_updated', 'Platform settings updated');

        $this->ok('Platform settings have been saved.', 'admin.settings');
    }

    public function announcements(Request $request): string
    {
        $total = AdminAnnouncement::adminCount();
        $meta = AdminAnnouncement::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/announcements', [
            'title'          => 'Announcements',
            'announcements'  => AdminAnnouncement::adminList($meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'adminNavActive' => 'announcements',
        ]);
    }

    public function storeAnnouncement(Request $request): mixed
    {
        $title = $request->string('title');
        $body = $request->string('body');
        $audience = $request->string('audience', 'all');
        $publish = $request->bool('is_published');

        if (mb_strlen($title) < 3 || mb_strlen($body) < 10) {
            $this->err('Please provide an announcement title and body.', 'admin.announcements');
        }

        if (!in_array($audience, ['all', 'clients', 'freelancers', 'admins'], true)) {
            $audience = 'all';
        }

        $id = AdminAnnouncement::create([
            'title'        => mb_substr($title, 0, 180),
            'body'         => $body,
            'audience'     => $audience,
            'is_published' => $publish ? 1 : 0,
            'published_at' => $publish ? gmdate('Y-m-d H:i:s') : null,
            'created_by'   => (int) Auth::id(),
        ]);

        AuditService::record('admin.announcement_created', 'Announcement created', 'admin_announcement', $id);

        if ($publish && $audience !== 'admins') {
            $recipients = \App\Core\Database::select(
                "SELECT id FROM `users` WHERE `role` = 'user' AND `status` = 'active' LIMIT 500"
            );

            foreach ($recipients as $recipient) {
                NotificationService::notify((int) $recipient['id'], 'announcement', $title, str_excerpt($body, 180), route('dashboard'));
            }
        }

        $this->ok('The announcement has been saved.', 'admin.announcements');
    }

    public function auditLogs(Request $request): string
    {
        $filters = $this->filters($request, ['q', 'action']);
        $total = AuditLog::listingCount($filters);
        $meta = AuditLog::paginateMeta($total, 30, $request->int('page', 1));

        return $this->adminView('admin/audit', [
            'title'          => 'Audit logs',
            'logs'           => AuditLog::listing($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'audit',
        ]);
    }

    public function profile(Request $request): string
    {
        return $this->adminView('admin/profile', [
            'title'          => 'Administrator profile',
            'adminNavActive' => 'profile',
        ]);
    }

    public function updateProfile(Request $request): mixed
    {
        $name = $request->string('name');

        if (mb_strlen($name) < 2) {
            $this->err('Please provide your full name.', 'admin.profile');
        }

        User::update((int) Auth::id(), ['name' => $name]);
        Auth::refresh();

        AuditService::record('admin.profile_updated', 'Administrator profile updated');

        $this->ok('Your profile has been updated.', 'admin.profile');
    }
}
