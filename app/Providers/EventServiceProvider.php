<?php

namespace App\Providers;

use App\Events\AccountCreate;
use App\Events\CaseCreated;
use App\Events\UserCreated;
use App\Listeners\SendUserCreatedEmail;
use App\Events\LeadAssigned;
use App\Events\LeadStatusChanged;
use App\Events\QuoteCreated;
use App\Events\QuoteStatusChanged;
use App\Events\SalesOrderCreated;
use App\Events\InvoiceCreated;
use App\Events\DeliveryOrderCreated;
use App\Events\ReturnOrderCreated;
use App\Events\ReceiptOrderCreated;
use App\Events\PurchaseOrderCreated;
use App\Events\TaskAssigned;
use App\Events\MeetingInvitation;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Events\InvoiceReminderSent;
use App\Listeners\SendAssignLeadEmail;
use App\Listeners\SendCaseCreatedEmail;
use App\Listeners\SendLeadStatusChangedEmail;
use App\Listeners\SendQuoteCreatedEmail;
use App\Listeners\SendQuoteStatusChangedEmail;
use App\Listeners\SendSalesOrderCreatedEmail;
use App\Listeners\SendInvoiceCreatedEmail;
use App\Listeners\SendDeliveryOrderCreatedEmail;
use App\Listeners\SendReturnOrderCreatedEmail;
use App\Listeners\SendReceiptOrderCreatedEmail;
use App\Listeners\SendPurchaseOrderCreatedEmail;
use App\Listeners\SendTaskAssignedEmail;
use App\Listeners\SendMeetingInvitationEmail;
use App\Listeners\SendOpportunityCreatedEmail;
use App\Listeners\SendOpportunityStageChangedEmail;
use App\Listeners\SendInvoiceReminderEmail;
use App\Listeners\TwilioAccountCreateListener;
use App\Listeners\TwilioCaseCreateListener;
use App\Listeners\TwilioLeadCreateListener;
use App\Listeners\TwilioMettingCreateListener;
use App\Listeners\TwilioOpportunityCreateListener;
use App\Listeners\TwilioQuoteCreateListener;
use App\Listeners\SlackAccountCreateListener;
use App\Listeners\SlackCaseCreateListener;
use App\Listeners\SlackLeadCreateListener;
use App\Listeners\SlackMeetingCreateListener;
use App\Listeners\SlackOpportunityCreateListener;
use App\Listeners\SlackQuoteCreateListener;
use App\Listeners\WebhookAssignLeadListener;
use App\Listeners\WebhookCaseCreateListener;
use App\Listeners\WebhookMeetingInvitationListener;
use App\Listeners\WebhookOpportunityCreateListener;
use App\Listeners\WebhookQuoteCreateListener;
use App\Listeners\WebhookTaskCreateListener;
use App\Listeners\WebhookUserCreateListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */

    protected $listen = [
        UserCreated::class => [
            SendUserCreatedEmail::class,
            WebhookUserCreateListener::class,
        ],
        LeadAssigned::class => [
            SendAssignLeadEmail::class,
            TwilioLeadCreateListener::class,
            SlackLeadCreateListener::class,
            WebhookAssignLeadListener::class,
        ],
        LeadStatusChanged::class => [
            SendLeadStatusChangedEmail::class,
        ],
        QuoteCreated::class => [
            SendQuoteCreatedEmail::class,
            TwilioQuoteCreateListener::class,
            SlackQuoteCreateListener::class,
            WebhookQuoteCreateListener::class,
        ],
        QuoteStatusChanged::class => [
            SendQuoteStatusChangedEmail::class,
        ],
        SalesOrderCreated::class => [
            SendSalesOrderCreatedEmail::class,
        ],
        InvoiceCreated::class => [
            SendInvoiceCreatedEmail::class,
        ],
        DeliveryOrderCreated::class => [
            SendDeliveryOrderCreatedEmail::class,
        ],
        ReturnOrderCreated::class => [
            SendReturnOrderCreatedEmail::class,
        ],
        ReceiptOrderCreated::class => [
            SendReceiptOrderCreatedEmail::class,
        ],
        PurchaseOrderCreated::class => [
            SendPurchaseOrderCreatedEmail::class,
        ],
        TaskAssigned::class => [
            SendTaskAssignedEmail::class,
            WebhookTaskCreateListener::class,
        ],
        MeetingInvitation::class => [
            SendMeetingInvitationEmail::class,
            TwilioMettingCreateListener::class,
            SlackMeetingCreateListener::class,
            WebhookMeetingInvitationListener::class,
        ],
        CaseCreated::class => [
            SendCaseCreatedEmail::class,
            TwilioCaseCreateListener::class,
            SlackCaseCreateListener::class,
            WebhookCaseCreateListener::class,
        ],
        OpportunityCreated::class => [
            SendOpportunityCreatedEmail::class,
            TwilioOpportunityCreateListener::class,
            SlackOpportunityCreateListener::class,
            WebhookOpportunityCreateListener::class,
        ],
        OpportunityStageChanged::class => [
            SendOpportunityStageChangedEmail::class,
        ],
        AccountCreate::class => [
            TwilioAccountCreateListener::class,
            SlackAccountCreateListener::class,
        ],
        InvoiceReminderSent::class => [
            SendInvoiceReminderEmail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Disable Laravel's event discovery.
        $this->disableEventDiscovery();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
