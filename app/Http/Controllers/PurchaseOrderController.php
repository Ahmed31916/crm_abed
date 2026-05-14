<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Models\Tax;
use App\Events\PurchaseOrderCreated;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\PurchaseOrderExport;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query()
            ->with(['salesOrder', 'account', 'billingContact', 'shippingContact', 'shippingProviderType', 'creator', 'assignedUser', 'products.tax'])
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
        $purchaseOrders = $query->paginate((int)$perPage);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
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
            'salesOrders' => SalesOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'products' => $this->getFilteredProducts(),
            'shippingProviderTypes' => ShippingProviderType::where('created_by', createdBy())->select('id', 'name')->active()->get(),
            'taxes' => Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->with([
                'salesOrder',
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

        if (!$purchaseOrder) {
            return redirect()->route('purchase-orders.index')->with('error', __('Purchase order not found.'));
        }

        return Inertia::render('purchase-orders/show', [
            'purchaseOrder' => $purchaseOrder,
            'streamItems' => $purchaseOrder->activities
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
        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
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

        return Inertia::render('purchase-orders/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'salesOrder',
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
        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
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

        return Inertia::render('purchase-orders/edit', [
            'purchaseOrder' => $purchaseOrder,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
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
            'sales_order_id' => 'required|exists:sales_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'billing_contact_id' => 'required|exists:contacts,id',
            'shipping_contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'nullable|exists:shipping_provider_types,id',
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
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'status' => 'nullable|in:draft,sent,confirmed,received,cancelled',
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

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        // Filter out empty product entries
        $products = array_filter($products, function($product) {
            return !empty($product['product_id']) && !empty($product['quantity']) && isset($product['unit_price']);
        });

        $purchaseOrder = PurchaseOrder::create($validated);

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
            $purchaseOrder->products()->sync($syncData);
        }

        $purchaseOrder->calculateTotals();

        // Fire PurchaseOrderCreated event for sending email
        if ($purchaseOrder && !IsDemo()) {
            event(new \App\Events\PurchaseOrderCreated($purchaseOrder));
        }

        // Check for errors
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Purchase order created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Purchase order created successfully.'));
    }

    public function update(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase order not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'billing_contact_id' => 'required|exists:contacts,id',
            'shipping_contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'nullable|exists:shipping_provider_types,id',
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
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'status' => 'nullable|in:draft,sent,confirmed,received,cancelled',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'products.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        // Filter out empty product entries
        $products = array_filter($products, function($product) {
            return !empty($product['product_id']) && !empty($product['quantity']) && isset($product['unit_price']);
        });

        $purchaseOrder->update($validated);

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
            $purchaseOrder->products()->sync($syncData);
        } else {
            $purchaseOrder->products()->detach();
        }

        $purchaseOrder->calculateTotals();

        return redirect()->back()->with('success', __('Purchase order updated successfully.'));
    }

    public function destroy($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase order not found.'));
        }

        $purchaseOrder->products()->detach();
        $purchaseOrder->delete();

        return redirect()->back()->with('success', __('Purchase order deleted successfully.'));
    }

    public function toggleStatus($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase order not found.'));
        }

        $newStatus = $purchaseOrder->status === 'draft' ? 'sent' : 'draft';
        $purchaseOrder->update(['status' => $newStatus]);

        return redirect()->back()->with('success', __('Purchase order status updated successfully.'));
    }

    public function addSalesOrder(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase order not found.'));
        }

        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id'
        ]);

        $salesOrder = SalesOrder::where('id', $validated['sales_order_id'])
            ->where('created_by', createdBy())
            ->with(['billingContact', 'products', 'account'])
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales order not found.'));
        }

        $purchaseOrder->update([
            'sales_order_id' => $salesOrder->id,
            'billing_contact_id' => $salesOrder->billing_contact_id,
            'shipping_contact_id' => $salesOrder->shipping_contact_id,
            'account_id' => $salesOrder->account_id,
            'billing_address' => $salesOrder->billing_address,
            'billing_city' => $salesOrder->billing_city,
            'billing_state' => $salesOrder->billing_state,
            'billing_postal_code' => $salesOrder->billing_postal_code,
            'billing_country' => $salesOrder->billing_country,
            'shipping_address' => $salesOrder->shipping_address,
            'shipping_city' => $salesOrder->shipping_city,
            'shipping_state' => $salesOrder->shipping_state,
            'shipping_postal_code' => $salesOrder->shipping_postal_code,
            'shipping_country' => $salesOrder->shipping_country,
            'shipping_provider_type_id' => $salesOrder->shipping_provider_type_id,
        ]);

        if ($salesOrder->products && $salesOrder->products->count() > 0) {
            $syncData = [];

            foreach ($salesOrder->products as $product) {
                $productId = $product->id;
                $quantity = $product->pivot->quantity ?? 1;
                $unitPrice = $product->pivot->unit_price ?? $product->price ?? 0;
                $lineTotal = $quantity * $unitPrice;
                $discountType = $product->pivot->discount_type;
                $discountValue = $product->pivot->discount_value ?? 0;
                $discountAmount = $product->pivot->discount_amount ?? 0;

                if (isset($syncData[$productId])) {
                    $syncData[$productId]['quantity'] += $quantity;
                    $syncData[$productId]['total_price'] = $syncData[$productId]['quantity'] * $unitPrice;
                    $syncData[$productId]['discount_amount'] = $this->calculateDiscountAmount($syncData[$productId]['total_price'], $discountType, $discountValue);
                } else {
                    $syncData[$productId] = [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'discount_amount' => $discountAmount,
                    ];
                }
            }

            $purchaseOrder->products()->sync($syncData);
            $purchaseOrder->calculateTotals();
        }

        return redirect()->back()->with('success', __('Sales order and its products added to purchase order successfully.'));
    }

    public function assignUser(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase order not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $purchaseOrder->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to purchase order successfully.'));
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

    public function deleteActivities($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase Order not found.'));
        }

        \App\Models\PurchaseOrderActivity::where('purchase_order_id', $purchaseOrder->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($purchaseOrderId, $activityId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase Order not found.'));
        }

        $activity = \App\Models\PurchaseOrderActivity::where('id', $activityId)
            ->where('purchase_order_id', $purchaseOrder->id)
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Activity not found.'));
        }

        $activity->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function getSalesOrderDetails($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->with(['account', 'billingContact', 'shippingContact', 'products'])
            ->first();

        if (!$salesOrder) {
            return response()->json(['error' => 'Sales order not found'], 404);
        }

        return response()->json([
            'account_id' => $salesOrder->account_id,
            'billing_contact_id' => $salesOrder->billing_contact_id,
            'shipping_contact_id' => $salesOrder->shipping_contact_id,
            'billing_address' => $salesOrder->billing_address,
            'billing_city' => $salesOrder->billing_city,
            'billing_state' => $salesOrder->billing_state,
            'billing_postal_code' => $salesOrder->billing_postal_code,
            'billing_country' => $salesOrder->billing_country,
            'shipping_address' => $salesOrder->shipping_address,
            'shipping_city' => $salesOrder->shipping_city,
            'shipping_state' => $salesOrder->shipping_state,
            'shipping_postal_code' => $salesOrder->shipping_postal_code,
            'shipping_country' => $salesOrder->shipping_country,
            'shipping_provider_type_id' => $salesOrder->shipping_provider_type_id,
            'products' => $salesOrder->products->map(function ($product) {
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

    public function fileExport()
    {
        if (!auth()->user()->can('export-purchase-orders')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'purchase_order_' . date('Y-m-d i:h:s');
        return Excel::download(new PurchaseOrderExport(), $name . '.xlsx');
    }
}
