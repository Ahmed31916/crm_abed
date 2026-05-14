<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    public static function setDynamicConfig()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        if ($user->type == 'superadmin') {
            $user = User::where('type', 'superadmin')->first();
        } else if ($user->type == 'company') {
            $user = User::where('id', $user->created_by)->first();
        } else {
            $user = User::where('id', $user->created_by)->first();
        }

        $getSettings = settings($user->id);

        // $settings = [
        //     'driver' => getSetting('email_driver', 'smtp'),
        //     'host' => getSetting('email_host', 'smtp.example.com'),
        //     'port' => getSetting('email_port', '587'),
        //     'username' => getSetting('email_username', ''),
        //     'password' => getSetting('email_password', ''),
        //     'encryption' => getSetting('email_encryption', 'tls'),
        //     'fromAddress' => getSetting('email_from_address', 'noreply@example.com'),
        //     'fromName' => getSetting('email_from_name', 'WorkDo System')
        // ];
        $settings = [
            'driver' => $getSettings['email_driver'] ?? 'smtp',
            'host' => $getSettings['email_host'] ?? 'smtp.example.com',
            'port' => $getSettings['email_port'] ?? '587',
            'username' => $getSettings['email_username'] ?? '',
            'password' => $getSettings['email_password'] ?? '',
            'encryption' => $getSettings['email_encryption'] ?? 'tls',
            'fromAddress' => $getSettings['email_from_address'] ?? 'noreply@example.com',
            'fromName' => $getSettings['email_from_name'] ?? 'WorkDo System'
        ];

        Config::set([
            'mail.default' => $settings['driver'],
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.from.address' => $settings['fromAddress'],
            'mail.from.name' => $settings['fromName'],
        ]);
    }
}
