<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Registration, sign-in, email verification and password reset.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): string
    {
        return $this->view('auth/login', ['title' => 'Sign in'], 'layouts/public');
    }

    public function login(Request $request): mixed
    {
        $email = $request->string('email');
        $password = (string) $request->input('password', '');

        $result = AuthService::attempt($email, $password, $request->ip());

        if (!$result['ok']) {
            Session::flash('danger', $result['message']);
            Session::flashInput(['email' => $email]);

            return $this->view('auth/login', [
                'title' => 'Sign in',
                'email' => $email,
            ], 'layouts/public');
        }

        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        Session::flash('success', 'Welcome back, ' . ($result['user']['name'] ?? 'there') . '.');

        if (is_string($intended) && $intended !== '' && str_starts_with($intended, '/')) {
            \App\Core\Response::redirect($intended);
        }

        return $this->redirectToRoute('dashboard');
    }

    public function showRegister(Request $request): string
    {
        return $this->view('auth/register', [
            'title'   => 'Create your account',
            'regions' => self::nigerianStates(),
        ], 'layouts/public');
    }

    public function register(Request $request): mixed
    {
        $input = [
            'name'         => $request->string('name'),
            'email'        => $request->string('email'),
            'password'     => (string) $request->input('password', ''),
            'phone'        => $request->string('phone'),
            'city'         => $request->string('city'),
            'state'        => $request->string('state'),
            'account_type' => $request->string('account_type', 'both'),
        ];

        if ($input['password'] !== (string) $request->input('password_confirmation', '')) {
            Session::flash('danger', 'The password confirmation does not match.');
            Session::flashErrors(['password_confirmation' => 'The password confirmation does not match.']);
            Session::flashInput($input);

            return $this->view('auth/register', [
                'title'   => 'Create your account',
                'regions' => self::nigerianStates(),
            ], 'layouts/public');
        }

        $result = AuthService::register($input);

        if (!$result['ok']) {
            Session::flash('danger', 'Please correct the highlighted fields.');
            Session::flashErrors($result['errors']);
            Session::flashInput($input);

            return $this->view('auth/register', [
                'title'   => 'Create your account',
                'regions' => self::nigerianStates(),
            ], 'layouts/public');
        }

        $userId = (int) $result['user_id'];
        $user = \App\Models\User::find($userId);

        if ($user !== null) {
            Auth::login($user);
            Auth::setActiveMode((string) ($user['default_mode'] ?? 'client'));
        }

        Session::flash('success', 'Your account has been created. We have sent a verification link to your email address.');

        return $this->redirectToRoute('verify.notice');
    }

    public function logout(Request $request): never
    {
        AuthService::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirectToRoute('home');
    }

    public function verifyNotice(Request $request): string
    {
        $user = Auth::user();

        return $this->view('auth/verify-notice', [
            'title'    => 'Verify your email address',
            'verified' => $user !== null && $user['email_verified_at'] !== null,
        ], 'layouts/public');
    }

    public function verifyEmail(Request $request): string
    {
        $token = (string) ($request->route('token') ?? '');
        $result = AuthService::verifyEmail($token);

        return $this->view('auth/verify-result', [
            'title'   => 'Email verification',
            'success' => $result['ok'],
            'message' => $result['message'],
        ], 'layouts/public');
    }

    public function resendVerification(Request $request): never
    {
        $userId = Auth::id();

        if ($userId !== null) {
            AuthService::sendVerificationEmail((int) $userId);
        }

        Session::flash('success', 'If your email address still needs verification, a new link has been sent.');
        $this->redirectToRoute('verify.notice');
    }

    public function showForgot(Request $request): string
    {
        return $this->view('auth/forgot-password', ['title' => 'Forgot your password?'], 'layouts/public');
    }

    public function sendReset(Request $request): never
    {
        AuthService::sendPasswordReset($request->string('email'), $request->ip());

        Session::flash('success', 'If an account exists for that email address, we have sent a password reset link.');
        $this->redirectToRoute('password.request');
    }

    public function showReset(Request $request): string
    {
        return $this->view('auth/reset-password', [
            'title' => 'Choose a new password',
            'token' => (string) ($request->route('token') ?? ''),
        ], 'layouts/public');
    }

    public function reset(Request $request): mixed
    {
        $token = (string) ($request->route('token') ?? '');

        $result = AuthService::resetPassword(
            $token,
            (string) $request->input('password', ''),
            (string) $request->input('password_confirmation', '')
        );

        if (!$result['ok']) {
            Session::flash('danger', $result['message']);
            Session::flashErrors($result['errors']);

            return $this->view('auth/reset-password', [
                'title' => 'Choose a new password',
                'token' => $token,
            ], 'layouts/public');
        }

        Session::flash('success', $result['message']);
        $this->redirectToRoute('login');
    }

    /**
     * @return array<int,string>
     */
    public static function nigerianStates(): array
    {
        return [
            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
            'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe',
            'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos',
            'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
            'Taraba', 'Yobe', 'Zamfara',
        ];
    }
}
