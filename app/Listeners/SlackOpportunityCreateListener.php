<?php

namespace App\Listeners;

use App\Events\OpportunityCreated;
use App\Services\SlackService;
use App\Models\User;
use Exception;

class SlackOpportunityCreateListener
{
    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(OpportunityCreated $event): void
    {
        $opportunity = $event->opportunity;
        $account = $opportunity->account;

        if (isNotificationTemplateEnabled('Opportunity create', 'slack', createdBy())) {
            $variables = [
                '{opportunity_name}' => $opportunity->name ?? '-',
                '{amount}' => $opportunity->amount ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{close_date}' => date('Y-m-d', strtotime( $opportunity->close_date)) ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                session()->forget('slack_error');

                $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($webhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Opportunity create',
                        variables: $variables,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                session()->flash('slack_error', 'Failed to send opportunity create notification: ' . $e->getMessage());
            }
        }
    }
}
