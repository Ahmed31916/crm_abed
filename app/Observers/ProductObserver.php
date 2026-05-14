<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductEventOutbox;
use App\Models\CompanyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        DB::afterCommit(function () use ($product) {
            $this->createOutboxEvent($product, 'created');

            // If super admin creates a product, notify all companies
            if ($product->created_by == getSuperAdminCompanyId()) {
                $this->notifyCompanies($product, 'created');
            }
        });
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        DB::afterCommit(function () use ($product) {
            $this->createOutboxEvent($product, 'updated');

            // Only notify companies if super admin product was updated
            if ($product->created_by == getSuperAdminCompanyId()) {
                $this->notifyCompanies($product, 'updated');
            }
        });
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        DB::afterCommit(function () use ($product) {
            $this->createOutboxEvent($product, 'deleted');

            if ($product->created_by == getSuperAdminCompanyId()) {
                $this->notifyCompanies($product, 'deleted');
            }
        });
    }

    /**
     * Create an outbox event for reliable message dispatch.
     * Uses create() (not saveQuietly) since ProductEventOutbox has no observer.
     */
    private function createOutboxEvent(Product $product, string $eventType): void
    {
        try {
            ProductEventOutbox::create([
                'product_id' => $product->id,
                'event_type' => $eventType,
                'payload' => $product->toArray(),
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create product outbox event', [
                'product_id' => $product->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify all company users about a super admin product change.
     * Only notifies companies that are NOT the product creator.
     */
    private function notifyCompanies(Product $product, string $type): void
    {
        try {
            $companies = \App\Models\User::where('type', 'company')
                ->where('id', '!=', $product->created_by)
                ->get();

            foreach ($companies as $company) {
                CompanyNotification::create([
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'type' => $type,
                    'title' => "Product {$type}",
                    'message' => "Product '{$product->name}' has been {$type} by super admin",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify companies about product change', [
                'product_id' => $product->id,
                'event_type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
