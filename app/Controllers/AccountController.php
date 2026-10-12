<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuditService;
use App\Services\AuthService;

/**
 * Shared account settings available to every signed-in user.
 */
final class AccountController extends Controller
{
    public function settings(Request $request): string
    {
        return $this->dashboardView('dashboard/account-settings', [
            'title' => 'Account settings',
        ]);
    }

    public function updateProfile(Request $request): mixed
    {
        $userId = (int) Auth::id();

        $name = $request->string('name');
        $phone = $request->string('phone');
        $city = $request->string('city');
        $state = $request->string('state');

        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Please enter your full name.';
        }

        if ($phone !== '' && preg_match('/^[0-9+()\-\s]{7,30}$/', $phone) !== 1) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }

        if ($errors !== []) {
            $this->backWithErrors('Please correct the highlighted fields.', $errors, ['name' => $name], '/account/settings');
        }

        User::update($userId, [
            'name'  => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        UserProfile::ensureForUser($userId);
        \App\Core\Database::affected(
            'UPDATE `user_profiles` SET `city` = ?, `state` = ? WHERE `user_id` = ?',
            [$city !== '' ? $city : null, $state !== '' ? $state : null, $userId]
        );

        AuditService::record('account.profile_updated', 'Account profile updated', 'user', $userId);
        Auth::refresh();

        $this->success('Your account details have been updated.', 'account.settings');
    }

    public function updatePassword(Request $request): mixed
    {
        $result = AuthService::changePassword(
            (int) Auth::id(),
            (string) $request->input('current_password', ''),
            (string) $request->input('password', ''),
            (string) $request->input('password_confirmation', '')
        );

        if (!$result['ok']) {
            $this->backWithErrors($result['message'], $result['errors'], [], '/account/settings');
        }

        $this->success($result['message'], 'account.settings');
    }

    public function notifications(Request $request): string
    {
        $userId = (int) Auth::id();
        $total = Notification::countBy(['user_id' => $userId]);
        $meta = Notification::paginateMeta($total, 20, $request->int('page', 1));

        return $this->dashboardView('dashboard/notifications', [
            'title'         => 'Notifications',
            'notifications' => Notification::forUser($userId, $meta['per_page'], $meta['offset']),
            'meta'          => $meta,
        ]);
    }

    public function markNotificationRead(Request $request): never
    {
        Notification::markRead((int) ($request->route('id') ?? 0), (int) Auth::id());
        $this->redirectToRoute('account.notifications');
    }

    public function markAllNotificationsRead(Request $request): never
    {
        Notification::markAllRead((int) Auth::id());
        Session::flash('success', 'All notifications marked as read.');
        $this->redirectToRoute('account.notifications');
    }
}
