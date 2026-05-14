<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\Quote;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Models\Tax;
use App\Exports\SalesOrderExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::query()
            ->with(['quote', 'account', 'billingContact', 'shippingContact', 'shippingProviderType', 'creator', 'assignedUser', 'products.tax'])
            ->where(function($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('account', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('account_id') && !empty($request->account_id) && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'order_number', 'name', 'order_date', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $salesOrders = $query->paginate((int)$perPage);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('sales-orders/index', [
            'salesOrders' => $salesOrders,
            'accounts' => Account::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'contacts' => Contact::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'quotes' => Quote::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name', 'quote_number')->get(),
            'products' => $this->getFilteredProducts(),
            'shippingProviderTypes' => ShippingProviderType::where('created_by', createdBy())->select('id', 'name')->active()->get(),
            'taxes' => Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
            'publicUrlBase' => config('app.url'),
            'encryptedSalesOrderIds' => $salesOrders->getCollection()->mapWithKeys(function ($salesOrder) {
                return [$salesOrder->id => encrypt($salesOrder->id)];
            }),
        ]);
    }

    public function show($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->with([
                'quote',
                'account',
                'contact',
                'billingContact',
                'shippingContact',
                'shippingProviderType',
                'creator',
                'assignedUser',
                'products.tax',
                'activities.user'
            ])
            ->first();

        if (!$salesOrder) {
            return redirect()->route('sales-orders.index')->with('error', __('Sales order not found.'));
        }

        return Inertia::render('sales-orders/show', [
            'salesOrder' => $salesOrder,
            'streamItems' => $salesOrder->activities
        ]);
    }

    public function create()
    {
        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $contacts = Contact::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $quotes = Quote::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'quote_number')->get();
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();
        $taxes = Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('sales-orders/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'quotes' => $quotes,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $salesOrder = SalesOrder::with([
            'quote',
            'account',
            'contact',
            'billingContact',
            'shippingContact',
            'shippingProviderType',
            'creator',
            'assignedUser',
            'products.tax'
        ])
        ->where('created_by', createdBy())
        ->findOrFail($id);

        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $contacts = Contact::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $quotes = Quote::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'quote_number')->get();
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();
        $taxes = Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('sales-orders/edit', [
            'salesOrder' => $salesOrder,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'quotes' => $quotes,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quote_id' => 'required|exists:quotes,id',
            'account_id' => 'required|exists:accounts,id',
            'billing_contact_id' => 'required|exists:contacts,id',
            'shipping_contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'required|exists:shipping_provider_types,id',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_state' => 'nullable|string',
            'shipping_postal_code' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after:order_date',
            'status' => 'nullable|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'products.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'draft';

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $salesOrder = SalesOrder::create($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $lineTotal = $product['quantity'] * $product['unit_price'];
                $discountAmount = $this->calculateDiscountAmount($lineTotal, $product['discount_type'] ?? null, $product['discount_value'] ?? 0);

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'total_price' => $lineTotal,
                    'discount_type' => $product['discount_type'] ?? null,
                    'discount_value' => $product['discount_value'] ?? 0,
                    'discount_amount' => $discountAmount,
                ];
            }
            $salesOrder->products()->sync($syncData);
        }

        $salesOrder->calculateTotals();

        // Fire SalesOrderCreated event for sending email
        if ($salesOrder && !IsDemo()) {
            event(new \App\Events\SalesOrderCreated($salesOrder));
        }

        // Check for errors
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Sales order created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Sales order created successfully.'));
    }

    public function update(Request $request, $salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales order not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quote_id' => 'required|exists:quotes,id',
            'account_id' => 'required|exists:accounts,id',
            'billing_contact_id' => 'required|exists:contacts,id',
            'shipping_contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'required|exists:shipping_provider_types,id',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'shipping_city' => 'nullable|string',
            'shipping_state' => 'nullable|string',
            'shipping_postal_code' => 'nullable|string',
            'shipping_country' => 'nullable|string',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after:order_date',
            'status' => 'nullable|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'products.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $salesOrder->update($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $lineTotal = $product['quantity'] * $product['unit_price'];
                $discountAmount = $this->calculateDiscountAmount($lineTotal, $product['discount_type'] ?? null, $product['discount_value'] ?? 0);

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'total_price' => $lineTotal,
                    'discount_type' => $product['discount_type'] ?? null,
                    'discount_value' => $product['discount_value'] ?? 0,
                    'discount_amount' => $discountAmount,
                ];
            }
            $salesOrder->products()->sync($syncData);
        } else {
            $salesOrder->products()->detach();
        }

        $salesOrder->calculateTotals();

        return redirect()->back()->with('success', __('Sales order updated successfully.'));
    }

    public function destroy($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales order not found.'));
        }

        $salesOrder->products()->detach();
        $salesOrder->delete();

        return redirect()->back()->with('success', __('Sales order deleted successfully.'));
    }

    public function toggleStatus($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales order not found.'));
        }

        $newStatus = $salesOrder->status === 'draft' ? 'confirmed' : 'draft';
        $salesOrder->update(['status' => $newStatus]);

        return redirect()->back()->with('success', __('Sales order status updated successfully.'));
    }



    public function assignUser(Request $request, $salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales order not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $salesOrder->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to sales order successfully.'));
    }

    private function calculateDiscountAmount($lineTotal, $discountType, $discountValue)
    {
        if (!$discountType || !$discountValue) {
            return 0;
        }

        if ($discountType === 'percentage') {
            return ($lineTotal * $discountValue) / 100;
        }

        if ($discountType === 'fixed') {
            return min($discountValue, $lineTotal);
        }

        return 0;
    }

    private function getFilteredProducts()
    {
        if (auth()->user()->type === 'company') {
            return Product::where('created_by', createdBy())->with('tax')->select('id', 'name', 'price', 'tax_id')->get();
        } else {
            return Product::where('created_by', createdBy())
                ->where('assigned_to', auth()->id())
                ->with('tax')
                ->select('id', 'name', 'price', 'tax_id')
                ->get();
        }
    }

    public function deleteActivities($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales Order not found.'));
        }

        \App\Models\SalesOrderActivity::where('sales_order_id', $salesOrder->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($salesOrderId, $activityId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales Order not found.'));
        }

        $activity = \App\Models\SalesOrderActivity::where('id', $activityId)
            ->where('sales_order_id', $salesOrder->id)
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Activity not found.'));
        }

        $activity->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-sales-orders')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return Excel::download(new SalesOrderExport, 'sales-orders-' . now()->format('Y-m-d-H-i-s') . '.xlsx');
    }

    public function getQuoteDetails($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->with(['account', 'billingContact', 'shippingContact', 'products'])
            ->first();

        if (!$quote) {
            return response()->json(['error' => 'Quote not found'], 404);
        }

        return response()->json([
            'account_id' => $quote->account_id,
            'billing_contact_id' => $quote->billing_contact_id,
            'shipping_contact_id' => $quote->shipping_contact_id,
            'billing_address' => $quote->billing_address,
            'billing_city' => $quote->billing_city,
            'billing_state' => $quote->billing_state,
            'billing_postal_code' => $quote->billing_postal_code,
            'billing_country' => $quote->billing_country,
            'shipping_address' => $quote->shipping_address,
            'shipping_city' => $quote->shipping_city,
            'shipping_state' => $quote->shipping_state,
            'shipping_postal_code' => $quote->shipping_postal_code,
            'shipping_country' => $quote->shipping_country,
            'shipping_provider_type_id' => $quote->shipping_provider_type_id,
            'products' => $quote->products->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity ?? 1,
                    'unit_price' => $product->pivot->unit_price ?? $product->price ?? 0,
                    'discount_type' => $product->pivot->discount_type ?? 'none',
                    'discount_value' => $product->pivot->discount_value ?? 0
                ];
            })
        ]);
    }

    public function publicView($salesOrder)
    {
        try {
            $decryptedId = decrypt($salesOrder);

            $salesOrder = SalesOrder::where('id', $decryptedId)
                ->with([
                    'account',
                    'billingContact',
                    'shippingContact',
                    'products.tax',
                    'creator',
                    'assignedUser',
                    'quote',
                    'shippingProviderType',
                    'activities.user'
                ])
                ->first();

            if (!$salesOrder) {
                abort(404, __('Sales order not found'));
            }

            $settings = settings($salesOrder->created_by);
            $templateId = $settings['salesOrderTemplate'] ?? 'template1';
            $color = $settings['salesOrderColor'] ?? 'ffffff';
            $qrEnabled = ($settings['salesOrderQrEnabled'] ?? 'off') === 'on';
            $themeColor = $settings['themeColor'] ?? 'blue';
            $customColor = $settings['customColor'] ?? '#10b77f';

            if (!empty($settings['salesOrderLogoId'])) {
                $media = Media::find($settings['salesOrderLogoId']);
                $settings['salesOrderLogo'] = $media ? $media->getUrl() : null;
            }

            return Inertia::render('sales-orders/public', [
                'salesOrder' => $salesOrder,
                'templateId' => $templateId,
                'color' => $color,
                'qrEnabled' => $qrEnabled,
                'settings' => $settings,
                'themeColor' => $themeColor,
                'customColor' => $customColor,
            ]);
        } catch (\Exception $e) {
            abort(404, __('Invalid sales order link'));
        }
    }
}
