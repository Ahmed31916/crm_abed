<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Plan;
use App\Models\Lead;
use App\Models\Account;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Observers\UserObserver;
use App\Observers\PlanObserver;
use App\Observers\LeadObserver;
use App\Observers\AccountObserver;
use App\Observers\OpportunityObserver;
use App\Observers\QuoteObserver;
use App\Observers\SalesOrderObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PurchaseOrderObserver;
use App\Providers\AssetServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\WebhookService::class);

        // Register our AssetServiceProvider
        $this->app->register(AssetServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the UserObserver
        User::observe(UserObserver::class);

        // Register the PlanObserver
        Plan::observe(PlanObserver::class);

        // Register the LeadObserver
        Lead::observe(LeadObserver::class);

        // Register the AccountObserver
        Account::observe(AccountObserver::class);

        // Register the OpportunityObserver
        Opportunity::observe(OpportunityObserver::class);

        // Register the QuoteObserver
        Quote::observe(QuoteObserver::class);

        // Register the SalesOrderObserver
        SalesOrder::observe(SalesOrderObserver::class);

        // Register the InvoiceObserver
        Invoice::observe(InvoiceObserver::class);

        // Register the PurchaseOrderObserver
        PurchaseOrder::observe(PurchaseOrderObserver::class);

        // Configure dynamic storage disks
        try {
            \App\Services\DynamicStorageService::configureDynamicDisks();
        } catch (\Exception $e) {
            // Silently fail during migrations or when database is not ready
        }
    }
}
