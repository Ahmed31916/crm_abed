<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Models\User;
use App\Services\SlackService;
use Exception;

class SlackLeadCreateListener
{
    public function __construct(
        private SlackService $slackService,
    ) {
        //
    }

    public function handle(LeadAssigned $event): void
    {
        $lead = $event->lead;
        if (isNotificationTemplateEnabled('Lead Create', 'slack', createdBy())) {

            $variables = [
                '{lead_name}' => $lead->name ?? '-',
                '{company_name}' => getCompanyName()
            ];

            try {
                session()->forget('slack_error');

                $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($webhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Lead Create',
                        variables: $variables,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                session()->flash('slack_error', 'Failed to send lead create notification: ' . $e->getMessage());
            }
        }
    }
}
