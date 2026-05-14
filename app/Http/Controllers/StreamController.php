<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\AccountActivity;
use App\Models\InvoiceActivity;
use App\Models\LeadActivity;
use App\Models\OpportunityActivity;
use App\Models\PurchaseOrderActivity;
use App\Models\QuoteActivity;
use App\Models\SalesOrderActivity;

class StreamController extends Controller
{
    public function index()
    {
        $modules = [
            'account_activities' => 'Account Activities',
            'lead_activities' => 'Lead Activities',
            'opportunity_activities' => 'Opportunity Activities',
            'invoice_activities' => 'Invoice Activities',
            'purchase_order_activities' => 'Purchase Order Activities',
            'quote_activities' => 'Quote Activities',
            'sales_order_activities' => 'Sales Order Activities',
        ];

        return Inertia::render('streams/index', [
            'modules' => $modules
        ]);
    }

    // Account Activities
    public function accountActivities()
    {
        $query = AccountActivity::with(['user', 'account'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('account', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'account_activities',
            'moduleTitle' => 'Account Activities',
            'streams' => $streams
        ]);
    }

    public function deleteAccountActivity($id)
    {
        $query = AccountActivity::with(['account'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('account', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Invoice Activities
    public function invoiceActivities()
    {
        $query = InvoiceActivity::with(['user', 'invoice'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('invoice', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'invoice_activities',
            'moduleTitle' => 'Invoice Activities',
            'streams' => $streams
        ]);
    }

    public function deleteInvoiceActivity($id)
    {
        $query = InvoiceActivity::with(['invoice'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('invoice', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Lead Activities
    public function leadActivities()
    {
        $query = LeadActivity::with(['user', 'lead'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('lead', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'lead_activities',
            'moduleTitle' => 'Lead Activities',
            'streams' => $streams
        ]);
    }

    public function deleteLeadActivity($id)
    {
        $query = LeadActivity::with(['lead'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('lead', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Opportunity Activities
    public function opportunityActivities()
    {
        $query = OpportunityActivity::with(['user', 'opportunity'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('opportunity', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'opportunity_activities',
            'moduleTitle' => 'Opportunity Activities',
            'streams' => $streams
        ]);
    }

    public function deleteOpportunityActivity($id)
    {
        $query = OpportunityActivity::with(['opportunity'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('opportunity', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Purchase Order Activities
    public function purchaseOrderActivities()
    {
        $query = PurchaseOrderActivity::with(['user', 'purchaseOrder'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('purchaseOrder', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'purchase_order_activities',
            'moduleTitle' => 'Purchase Order Activities',
            'streams' => $streams
        ]);
    }

    public function deletePurchaseOrderActivity($id)
    {
        $query = PurchaseOrderActivity::with(['purchaseOrder'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('purchaseOrder', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Quote Activities
    public function quoteActivities()
    {
        $query = QuoteActivity::with(['user', 'quote'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('quote', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'quote_activities',
            'moduleTitle' => 'Quote Activities',
            'streams' => $streams
        ]);
    }

    public function deleteQuoteActivity($id)
    {
        $query = QuoteActivity::with(['quote'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('quote', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }

    // Sales Order Activities
    public function salesOrderActivities()
    {
        $query = SalesOrderActivity::with(['user', 'salesOrder'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->whereHas('salesOrder', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $streams = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('streams/show', [
            'module' => 'sales_order_activities',
            'moduleTitle' => 'Sales Order Activities',
            'streams' => $streams
        ]);
    }

    public function deleteSalesOrderActivity($id)
    {
        $query = SalesOrderActivity::with(['salesOrder'])
            ->where('created_by', createdBy());

        if (auth()->user()->type !== 'company') {
            $query->whereHas('salesOrder', function ($q) {
                $q->where('assigned_to', auth()->id());
            });
        }

        $stream = $query->find($id);

        if (!$stream) {
            return back()->with('error', __('Permission denied.'));
        }

        $stream->delete();
        return back()->with('success', __('Activity deleted successfully.'));
    }
}
