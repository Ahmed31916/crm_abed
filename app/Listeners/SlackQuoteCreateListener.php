<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Services\SlackService;
use App\Models\User;
use Exception;

class SlackQuoteCreateListener
{
    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(QuoteCreated $event): void
    {
        $quote = $event->quote;
        $account = $quote->account;
        if (isNotificationTemplateEnabled('Quote Create', 'slack', createdBy())) {
            $variables = [
                '{quote_number}' => $quote->quote_number ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{total_amount}' => $quote->total_amount ? number_format($quote->total_amount, 2) : '0.00',
                '{valid_until}' => $quote->valid_until ? date('Y-m-d', strtotime($quote->valid_until)) : '-',
                '{company_name}' => getCompanyName()
            ];

            try {
                session()->forget('slack_error');

                $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($webhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Quote Create',
                        variables: $variables,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                session()->flash('slack_error', 'Failed to send quote create notification: ' . $e->getMessage());
            }
        }
    }
}
