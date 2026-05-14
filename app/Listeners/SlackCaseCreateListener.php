<?php

namespace App\Listeners;

use App\Events\CaseCreated;
use App\Services\SlackService;
use App\Models\User;
use Exception;

class SlackCaseCreateListener
{
    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(CaseCreated $event): void
    {
        $case = $event->case;
        if (isNotificationTemplateEnabled('Case Create', 'slack', createdBy())) {
            $variables = [
                '{case_subject}' => $case->subject ?? '-',
                '{company_name}' => 'Company Name'
            ];

            try {
                session()->forget('slack_error');

                $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($webhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Case Create',
                        variables: $variables,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                session()->flash('slack_error', 'Failed to send case Create notification: ' . $e->getMessage());
            }
        }
    }
}
