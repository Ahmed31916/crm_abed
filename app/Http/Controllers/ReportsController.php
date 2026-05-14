<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Lead;
use App\Models\SalesOrder;
use App\Models\Product;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function leads(Request $request)
    {
        if(isDemo()){
            $dateFrom = $request->get('date_from', Carbon::parse('2024-01-04')->format('Y-m-d'));
            $dateTo = $request->get('date_to',  Carbon::parse('2025-09-09')->format('Y-m-d'));
        }else{
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        }

        $companyId = Auth::user()->creatorId();

        $summary = [
            'total_leads' => Lead::where('created_by', $companyId)->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'converted_leads' => Lead::where('created_by', $companyId)->whereBetween('created_at', [$dateFrom, $dateTo])->where('is_converted', true)->count(),
            'conversion_rate' => 0,
            'avg_conversion_time' => 0
        ];

        if ($summary['total_leads'] > 0) {
            $summary['conversion_rate'] = ($summary['converted_leads'] / $summary['total_leads']) * 100;
        }

        // Calculate average conversion time for converted leads
        if ($summary['converted_leads'] > 0) {
            $avgConversionTime = Lead::where('created_by', $companyId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('is_converted', true)
                ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
                ->value('avg_days');
            $summary['avg_conversion_time'] = round($avgConversionTime ?? 0, 1);
        }

        $monthlyData = Lead::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $dailyData = Lead::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $leadsBySource = Lead::selectRaw('lead_sources.name, COUNT(*) as total')
            ->join('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->where('leads.created_by', $companyId)
            ->whereBetween('leads.created_at', [$dateFrom, $dateTo])
            ->groupBy('lead_sources.name')
            ->get();

        return Inertia::render('reports/lead-reports', [
            'filters' => compact('dateFrom', 'dateTo'),
            'summary' => $summary,
            'monthlyData' => $monthlyData,
            'dailyData' => $dailyData,
            'leadsBySource' => $leadsBySource
        ]);
    }

    public function sales(Request $request)
    {
        if(isDemo()){
            $dateFrom = $request->get('date_from', Carbon::parse('2024-01-04')->format('Y-m-d'));
            $dateTo = $request->get('date_to',  Carbon::parse('2025-09-09')->format('Y-m-d'));
        }else{
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        }

        $companyId = Auth::user()->creatorId();

        $summary = [
            'total_sales' => SalesOrder::where('created_by', $companyId)->whereBetween('created_at', [$dateFrom, $dateTo])->sum('total_amount'),
            'total_orders' => SalesOrder::where('created_by', $companyId)->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'avg_order_value' => 0,
            'growth_rate' => 0
        ];

        if ($summary['total_orders'] > 0) {
            $summary['avg_order_value'] = $summary['total_sales'] / $summary['total_orders'];
        }

        // Calculate growth rate compared to previous period
        $previousPeriodStart = Carbon::parse($dateFrom)->subDays(Carbon::parse($dateTo)->diffInDays(Carbon::parse($dateFrom)))->format('Y-m-d');
        $previousPeriodEnd = Carbon::parse($dateFrom)->subDay()->format('Y-m-d');

        $previousSales = SalesOrder::where('created_by', $companyId)->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->sum('total_amount');

        if ($previousSales > 0) {
            $summary['growth_rate'] = (($summary['total_sales'] - $previousSales) / $previousSales) * 100;
        } else if ($summary['total_sales'] > 0) {
            $summary['growth_rate'] = 100; // 100% growth if no previous sales
        }

        $monthlyData = SalesOrder::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $dailyData = SalesOrder::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as period, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $salesByStatus = SalesOrder::selectRaw('status, COUNT(*) as total, SUM(total_amount) as amount')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('status')
            ->get();

        return Inertia::render('reports/sales-reports', [
            'filters' => compact('dateFrom', 'dateTo'),
            'summary' => $summary,
            'monthlyData' => $monthlyData,
            'dailyData' => $dailyData,
            'salesByStatus' => $salesByStatus
        ]);
    }

    public function products(Request $request)
    {
        if(isDemo()){
            $dateFrom = $request->get('date_from', Carbon::parse('2024-01-04')->format('Y-m-d'));
            $dateTo = $request->get('date_to',  Carbon::parse('2025-09-09')->format('Y-m-d'));
        }else{
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        }

        $companyId = Auth::user()->creatorId();

        $summary = [
            'total_products' => Product::where('created_by', $companyId)->count(),
            'active_products' => Product::where('created_by', $companyId)->where('status', 'active')->count(),
            'total_revenue' => 0,
            'best_seller' => null
        ];

        $productSales = DB::table('sales_order_products')
            ->join('sales_orders', 'sales_order_products.sales_order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_products.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(sales_order_products.quantity) as quantity, SUM(sales_order_products.total_price) as revenue')
            ->where('sales_orders.created_by', $companyId)
            ->whereBetween('sales_orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('products.id', 'products.name')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => (int) $item->quantity,
                    'revenue' => (float) $item->revenue
                ];
            });

        $summary['total_revenue'] = $productSales->sum('revenue');
        $summary['best_seller'] = $productSales->first()['name'] ?? null;

        return Inertia::render('reports/product-reports', [
            'filters' => compact('dateFrom', 'dateTo'),
            'summary' => $summary,
            'productSales' => $productSales
        ]);
    }

    public function customers(Request $request)
    {
        if(isDemo()){
            $dateFrom = $request->get('date_from', Carbon::parse('2024-01-04')->format('Y-m-d'));
            $dateTo = $request->get('date_to',  Carbon::parse('2025-09-09')->format('Y-m-d'));
        }else{
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        }

        $companyId = Auth::user()->creatorId();

        $summary = [
            'total_contacts' => Contact::where('created_by', $companyId)->count(),
            'new_contacts' => Contact::where('created_by', $companyId)->whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'active_contacts' => Contact::where('created_by', $companyId)->where('status', 'active')->count(),
            'contact_lifetime_value' => 0
        ];

        $monthlyData = Contact::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $dailyData = Contact::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $topContacts = DB::table('contacts')
            ->join('sales_orders', 'contacts.id', '=', 'sales_orders.billing_contact_id')
            ->selectRaw('contacts.name, SUM(sales_orders.total_amount) as total_spent, COUNT(sales_orders.id) as order_count')
            ->where('contacts.created_by', $companyId)
            ->where('sales_orders.created_by', $companyId)
            ->whereBetween('sales_orders.created_at', [$dateFrom, $dateTo])
            ->groupBy('contacts.id', 'contacts.name')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'total_spent' => (float) $item->total_spent,
                    'order_count' => (int) $item->order_count
                ];
            });

        return Inertia::render('reports/customer-reports', [
            'filters' => compact('dateFrom', 'dateTo'),
            'summary' => $summary,
            'monthlyData' => $monthlyData,
            'dailyData' => $dailyData,
            'topContacts' => $topContacts
        ]);
    }

    public function projects(Request $request)
    {
        if(isDemo()){
            $dateFrom = $request->get('date_from', Carbon::parse('2024-01-04')->format('Y-m-d'));
            $dateTo = $request->get('date_to',  Carbon::parse('2025-09-09')->format('Y-m-d'));
        }else{
            $dateFrom = $request->get('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        }

        $companyId = Auth::user()->creatorId();

        $summary = [
            'total_projects' => Project::where('created_by', $companyId)->count(),
            'active_projects' => Project::where('created_by', $companyId)->where('status', 'active')->count(),
            'completed_projects' => Project::where('created_by', $companyId)->where('status', 'completed')->count(),
            'completion_rate' => 0
        ];

        if ($summary['total_projects'] > 0) {
            $summary['completion_rate'] = ($summary['completed_projects'] / $summary['total_projects']) * 100;
        }

        $monthlyData = Project::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $dailyData = Project::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as period, COUNT(*) as count')
            ->where('created_by', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $projectsByStatus = Project::selectRaw('status, COUNT(*) as total')
            ->where('created_by', $companyId)
            ->groupBy('status')
            ->get();

        return Inertia::render('reports/project-reports', [
            'filters' => compact('dateFrom', 'dateTo'),
            'summary' => $summary,
            'monthlyData' => $monthlyData,
            'dailyData' => $dailyData,
            'projectsByStatus' => $projectsByStatus
        ]);
    }
}
