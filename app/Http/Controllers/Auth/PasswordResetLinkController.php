<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'settings' => settings(),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $configResult = $this->configureMail($request->email);

        if ($configResult === false) {
            return back()->with('error', __('Email configuration is not set. Please contact administrator.'));
        }

        try {
            Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to send reset link. Please try again.'));
        }

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }

    /**
     * Configure mail settings based on user type
     */
    private function configureMail(string $email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return true;
        }

        if ($user->hasRole('company')) {
            $configUser = User::where('id', $user->created_by)->first();
        } else {
            $configUser = User::where('id', $user->created_by)->first();
        }

        if (!$configUser) {
            return true;
        }

        $settings = settings($configUser->id);

        if (!isset($settings['email_driver']) || !isset($settings['email_host']) || !isset($settings['email_port']) ||
            !isset($settings['email_username']) || !isset($settings['email_password']) ||
            !isset($settings['email_from_address']) || !isset($settings['email_from_name'])) {
            return false;
        }

        Config::set([
            'mail.default' => $settings['email_driver'],
            'mail.mailers.smtp.host' => $settings['email_host'],
            'mail.mailers.smtp.port' => $settings['email_port'],
            'mail.mailers.smtp.encryption' => $settings['email_encryption'] === 'none' ? null : $settings['email_encryption'],
            'mail.mailers.smtp.username' => $settings['email_username'],
            'mail.mailers.smtp.password' => $settings['email_password'],
            'mail.from.address' => $settings['email_from_address'],
            'mail.from.name' => $settings['email_from_name'],
        ]);

        return true;
    }
}
