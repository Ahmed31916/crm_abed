<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Exports\DeliveryOrderExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryOrder::query()
            ->with(['salesOrder', 'account', 'contact', 'shippingProviderType', 'creator', 'assignedUser', 'products'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('delivery_number', 'like', '%' . $request->search . '%')
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

        if ($request->has('sales_order_id') && !empty($request->sales_order_id) && $request->sales_order_id !== 'all') {
            $query->where('sales_order_id', $request->sales_order_id);
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
        $allowedSorts=['id', 'delivery_number', 'name', 'delivery_date', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $deliveryOrders = $query->paginate((int)$perPage);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('delivery-orders/index', [
            'deliveryOrders' => $deliveryOrders,
            'accounts' => Account::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'contacts' => Contact::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'salesOrders' => SalesOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name', 'order_number')->get(),
            'products' => $this->getFilteredProducts(),
            'shippingProviderTypes' => ShippingProviderType::where('created_by', createdBy())->select('id', 'name')->active()->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'sales_order_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryOrderId)
            ->where('created_by', createdBy())
            ->with([
                'salesOrder',
                'account',
                'contact',
                'shippingProviderType',
                'creator',
                'assignedUser',
                'products'
            ])
            ->first();

        if (!$deliveryOrder) {
            return redirect()->route('delivery-orders.index')->with('error', __('Delivery order not found.'));
        }

        return Inertia::render('delivery-orders/show', [
            'deliveryOrder' => $deliveryOrder,
        ]);
    }

    public function create()
    {
        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $contacts = Contact::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'order_number')->get();
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('delivery-orders/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $deliveryOrder = DeliveryOrder::with([
            'salesOrder',
            'account',
            'contact',
            'shippingProviderType',
            'creator',
            'assignedUser',
            'products'
        ])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $contacts = Contact::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'order_number')->get();
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('delivery-orders/edit', [
            'deliveryOrder' => $deliveryOrder,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
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
            'contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'required|exists:shipping_provider_types,id',
            'delivery_address' => 'nullable|string',
            'delivery_city' => 'nullable|string',
            'delivery_state' => 'nullable|string',
            'delivery_postal_code' => 'nullable|string',
            'delivery_country' => 'nullable|string',
            'delivery_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:delivery_date',
            'status' => 'nullable|in:pending,in_transit,delivered,cancelled',
            'tracking_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && DeliveryOrder::where('tracking_number', $value)->where('created_by', createdBy())->exists()) {
                    $fail('The tracking number has already been taken.');
                }
            }],
            'delivery_notes' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_weight' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'pending';

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $deliveryOrder = DeliveryOrder::create($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $unitWeight = $product['unit_weight'] ?? 0;
                $totalWeight = $product['quantity'] * $unitWeight;

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_weight' => $unitWeight,
                    'total_weight' => $totalWeight,
                ];
            }
            $deliveryOrder->products()->sync($syncData);
        }

        $deliveryOrder->calculateTotalWeight();

        // Fire DeliveryOrderCreated event for sending email
        if ($deliveryOrder && !IsDemo()) {
            event(new \App\Events\DeliveryOrderCreated($deliveryOrder));
        }

        // Check for email error
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Delivery order created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Delivery order created successfully.'));
    }

    public function update(Request $request, $deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$deliveryOrder) {
            return redirect()->back()->with('error', __('Delivery order not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'required|exists:shipping_provider_types,id',
            'delivery_address' => 'nullable|string',
            'delivery_city' => 'nullable|string',
            'delivery_state' => 'nullable|string',
            'delivery_postal_code' => 'nullable|string',
            'delivery_country' => 'nullable|string',
            'delivery_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:delivery_date',
            'status' => 'nullable|in:pending,in_transit,delivered,cancelled',
            'tracking_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($deliveryOrderId) {
                if ($value && DeliveryOrder::where('tracking_number', $value)->where('created_by', createdBy())->where('id', '!=', $deliveryOrderId)->exists()) {
                    $fail('The Tracking number has already been taken.');
                }
            }],
            'delivery_notes' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_weight' => 'nullable|numeric|min:0',
        ]);

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $deliveryOrder->update($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $unitWeight = $product['unit_weight'] ?? 0;
                $totalWeight = $product['quantity'] * $unitWeight;

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_weight' => $unitWeight,
                    'total_weight' => $totalWeight,
                ];
            }
            $deliveryOrder->products()->sync($syncData);
        } else {
            $deliveryOrder->products()->detach();
        }

        $deliveryOrder->calculateTotalWeight();

        return redirect()->back()->with('success', __('Delivery order updated successfully.'));
    }

    public function destroy($deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$deliveryOrder) {
            return redirect()->back()->with('error', __('Delivery order not found.'));
        }

        $deliveryOrder->products()->detach();
        $deliveryOrder->delete();

        return redirect()->back()->with('success', __('Delivery order deleted successfully.'));
    }

    public function toggleStatus($deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$deliveryOrder) {
            return redirect()->back()->with('error', __('Delivery order not found.'));
        }

        $statusMap = [
            'pending' => 'in_transit',
            'in_transit' => 'delivered',
            'delivered' => 'pending',
            'cancelled' => 'pending'
        ];

        $newStatus = $statusMap[$deliveryOrder->status] ?? 'pending';
        $deliveryOrder->update(['status' => $newStatus]);

        return redirect()->back()->with('success', __('Delivery order status updated successfully.'));
    }

    public function assignUser(Request $request, $deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::where('id', $deliveryOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$deliveryOrder) {
            return redirect()->back()->with('error', __('Delivery order not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $deliveryOrder->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to delivery order successfully.'));
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-delivery-orders')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'delivery_order_' . date('Y-m-d i:h:s');
        return Excel::download(new DeliveryOrderExport(), $name . '.xlsx');
    }

    private function getFilteredProducts()
    {
        if (auth()->user()->type === 'company') {
            return Product::where('created_by', createdBy())->select('id', 'name')->get();
        } else {
            return Product::where('created_by', createdBy())
                ->where('assigned_to', auth()->id())
                ->select('id', 'name')
                ->get();
        }
    }
}
