<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

class EmailTemplateService
{
    public function sendTemplateEmail(string $templateName, array $variables, string $toEmail, Business $business = null, string $toName = null)
    {
        try {
            // Get email template
            $template = EmailTemplate::where('name', $templateName)->first();

            if (!$template) {
                throw new Exception("Email template '{$templateName}' not found");
            }

            // Get user's language or default to 'en'
            $language = 'en'; // default
            if ($business && $business->user) {
                $language = $business->user->lang ?? 'en';
            }

            // Get template content for the language
            $templateLang = $template->emailTemplateLangs()
                ->where('lang', $language)
                ->first();

            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->emailTemplateLangs()
                    ->where('lang', 'en')
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in subject and content
            $subject = $this->replaceVariables($templateLang->subject, $variables);
            $content = $this->replaceVariables($templateLang->content, $variables);
            $fromName = $this->replaceVariables($template->from, $variables);

            // Configure SMTP settings
            $this->configureBusinessSMTP($business);

            // Get final email settings
            $fromEmail = getSetting('email_from_address') ?: config('mail.from.address');
            $finalFromName = getSetting('email_from_name') ? $this->replaceVariables(getSetting('email_from_name'), $variables) : $fromName;

            // Send email using NotificationMail class
            Mail::to($toEmail, $toName)->send(new \App\Mail\EmailTemplate($subject, $content, $fromEmail, $finalFromName));

            return true;
        } catch (Exception $e) {
            // \Log::error('Email sending failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function replaceVariables(string $content, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    public function sendTemplateEmailWithLanguage(string $templateName, array $variables, string $toEmail, string $toName = null, string $language = 'en')
    {
        try {
            // \Log::info('=== EMAIL TEMPLATE LANGUAGE DEBUG ===', [
            //     'template_name' => $templateName,
            //     'requested_language' => $language,
            //     'to_email' => $toEmail
            // ]);

            // Check if email template is enabled for the user
            if (!isEmailTemplateEnabled($templateName, createdBy())) {
                // \Log::info("Email template '{$templateName}' is disabled for user");
                return false;
            }

            // Get email template
            $template = EmailTemplate::where('name', $templateName)->first();

            if (!$template) {
                throw new Exception("Email template '{$templateName}' not found");
            }

            // Get template content for the specified language
            $templateLang = $template->emailTemplateLangs()
                ->where('lang', $language)
                ->first();

            // \Log::info('Template language lookup', [
            //     'requested_lang' => $language,
            //     'found_template' => $templateLang ? true : false,
            //     'template_id' => $templateLang?->id ?? null
            // ]);

            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->emailTemplateLangs()
                    ->where('lang', 'en')
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in subject and content
            $subject = $this->replaceVariables($templateLang->subject, $variables);
            $content = $this->replaceVariables($templateLang->content, $variables);
            $fromName = $this->replaceVariables($template->from, $variables);

            // Configure SMTP settings
            $this->configureBusinessSMTP();

            // Get final email settings
            $fromEmail = getSetting('email_from_address') ?: config('mail.from.address');
            $finalFromName = getSetting('email_from_name') ? $this->replaceVariables(getSetting('email_from_name'), $variables) : $fromName;

            // Send email using NotificationMail class
            Mail::to($toEmail, $toName)->send(new \App\Mail\EmailTemplate($subject, $content, $fromEmail, $finalFromName));

            return true;
        } catch (Exception $e) {
            // \Log::error('Email sending failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function configureBusinessSMTP(?Business $business = null)
    {
        // Get email settings from settings table
        $emailDriver = getSetting('email_driver', 'smtp');
        $emailHost = getSetting('email_host');
        $emailUsername = getSetting('email_username');
        $emailPassword = getSetting('email_password');
        $emailPort = getSetting('email_port', 587);
        $emailEncryption = getSetting('email_encryption', 'tls');

        // Check if email settings are configured
        if (!$emailHost || !$emailUsername || !$emailPassword) {
            throw new Exception("Email settings not configured. Please configure email settings in system settings.");
        }

        // Configure mail settings
        Config::set([
            'mail.default' => $emailDriver,
            'mail.mailers.smtp.host' => $emailHost,
            'mail.mailers.smtp.port' => $emailPort,
            'mail.mailers.smtp.username' => $emailUsername,
            'mail.mailers.smtp.password' => $emailPassword,
            'mail.mailers.smtp.encryption' => $emailEncryption,
        ]);
    }
}
