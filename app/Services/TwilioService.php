<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Exception;
use Twilio\Rest\Client;

class TwilioService
{
    public function sendTemplateMessageWithLanguage(string $templateName, array $variables, string $toPhone, string $language = 'en')
    {
        try {
            // Check if Twilio notification is enabled for this template
            if (!$this->isTwilioNotificationEnabled($templateName)) {
                return false;
            }

            // Get notification template
            $template = NotificationTemplate::where('name', $templateName)->where('type','twilio')->first();

            if (!$template) {
                throw new Exception("Notification template '{$templateName}' not found");
            }

            // Get template content for the specified language
            $templateLang = $template->notificationTemplateLangs()
                ->where('lang', $language)
                ->where('created_by', createdBy())
                ->first();

            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->notificationTemplateLangs()
                    ->where('lang', 'en')
                    ->where('created_by', createdBy())
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in content
            $message = $this->replaceVariables($templateLang->content, $variables);

            // Send SMS
            return $this->sendSMS($toPhone, $message);
        } catch (Exception $e) {
            \Log::error('Twilio SMS sending failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function replaceVariables(string $content, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    private function isTwilioNotificationEnabled(string $templateName): bool
    {
        return isNotificationTemplateEnabled($templateName,'twilio', createdBy());
    }

    private function sendSMS(string $toPhone, string $message): bool
    {
        $twilioSid = getSetting('twilio_sid', '', createdBy());
        $twilioToken = getSetting('twilio_token', '', createdBy());
        $twilioFrom = getSetting('twilio_from', '', createdBy());

        if (!$twilioSid || !$twilioToken || !$twilioFrom) {
            throw new Exception("Twilio settings not configured. Please configure Twilio settings.");
        }

        $twilio = new Client($twilioSid, $twilioToken);
        $twilio->messages->create($toPhone, [
            'from' => $twilioFrom,
            'body' => $message
        ]);

        return true;
    }
}
