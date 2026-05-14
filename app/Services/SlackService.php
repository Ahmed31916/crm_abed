<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Exception;
use Illuminate\Support\Facades\Http;

class SlackService
{
    public function sendTemplateMessageWithLanguage(string $templateName, array $variables, string $language = 'en')
    {
        try {
            if (!$this->isSlackNotificationEnabled($templateName)) {
                return false;
            }

            $template = NotificationTemplate::where('name', $templateName)->where('type','slack')->first();

            if (!$template) {
                throw new Exception("Notification template '{$templateName}' not found");
            }

            $templateLang = $template->notificationTemplateLangs()
                ->where('lang', $language)
                ->where('created_by', createdBy())
                ->first();

            if (!$templateLang) {
                $templateLang = $template->notificationTemplateLangs()
                    ->where('lang', 'en')
                    ->where('created_by', createdBy())
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            $message = $this->replaceVariables($templateLang->content, $variables);

            return $this->sendMessage($message);
        } catch (Exception $e) {
            \Log::error('Slack message sending failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function replaceVariables(string $content, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    private function isSlackNotificationEnabled(string $templateName): bool
    {
        return isNotificationTemplateEnabled($templateName, 'slack', createdBy());
    }

    private function sendMessage(string $message): bool
    {
        $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

        if (!$webhookUrl) {
            throw new Exception("Slack webhook URL not configured. Please configure Slack settings.");
        }

        $response = Http::post($webhookUrl, [
            'text' => $message
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to send Slack message: " . $response->body());
        }

        return true;
    }
}
