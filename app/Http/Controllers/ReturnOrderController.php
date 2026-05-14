<?php

namespace App\Http\Controllers;

use App\Models\ReturnOrder;
use App\Models\SalesOrder;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Events\ReturnOrderCreated;
use App\Exports\ReturnOrderExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnOrder::query()
            ->with(['salesOrder', 'account', 'contact', 'shippingProviderType', 'creator', 'assignedUser', 'products'])
            ->where(function($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('return_number', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('account', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
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
        $allowedSorts=['id', 'return_number', 'name', 'return_date'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $returnOrders = $query->paginate((int)$perPage);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('return-orders/index', [
            'returnOrders' => $returnOrders,
            'salesOrders' => SalesOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name', 'order_number')->get(),
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
            'products' => $this->getFilteredProducts(),
            'shippingProviderTypes' => ShippingProviderType::where('created_by', createdBy())->select('id', 'name')->active()->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($returnOrderId)
    {
        $returnOrder = ReturnOrder::where('id', $returnOrderId)
            ->where('created_by', createdBy())
            ->with(['salesOrder', 'account', 'contact', 'shippingProviderType', 'creator', 'assignedUser', 'products.tax'])
            ->first();

        if (!$returnOrder) {
            return redirect()->route('return-orders.index')->with('error', __('Return order not found.'));
        }

        return Inertia::render('return-orders/show', [
            'returnOrder' => $returnOrder,
        ]);
    }

    public function create()
    {
        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'order_number')->get();
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
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('return-orders/create', [
            'salesOrders' => $salesOrders,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $returnOrder = ReturnOrder::with([
            'salesOrder',
            'account',
            'contact',
            'shippingProviderType',
            'creator',
            'assignedUser',
            'products.tax'
        ])
        ->where('created_by', createdBy())
        ->findOrFail($id);

        $salesOrders = SalesOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'order_number')->get();
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
        $products = $this->getFilteredProducts();
        $shippingProviderTypes = ShippingProviderType::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('return-orders/edit', [
            'returnOrder' => $returnOrder,
            'salesOrders' => $salesOrders,
            'accounts' => $accounts,
            'contacts' => $contacts,
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
            'tracking_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && ReturnOrder::where('tracking_number', $value)->where('created_by', createdBy())->exists()) {
                    $fail('The tracking number has already been taken.');
                }
            }],
            'status' => 'nullable|in:pending,approved,shipped,received,processed,cancelled',
            'reason' => 'nullable|in:defective,wrong_item,damaged,not_needed,other',
            'reason_description' => 'nullable|string',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'pending';

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $returnOrder = ReturnOrder::create($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $totalPrice = $product['quantity'] * $product['unit_price'];

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'total_price' => $totalPrice,
                ];
            }
            $returnOrder->products()->sync($syncData);
        }

        $returnOrder->calculateTotals();

        // Fire ReturnOrderCreated event for sending email
        if ($returnOrder && !IsDemo()) {
            event(new \App\Events\ReturnOrderCreated($returnOrder));
        }

        // Check for errors
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Return order created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Return order created successfully.'));
    }

    public function update(Request $request, $returnOrderId)
    {
        $returnOrder = ReturnOrder::where('id', $returnOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$returnOrder) {
            return redirect()->back()->with('error', __('Return order not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'shipping_provider_type_id' => 'required|exists:shipping_provider_types,id',
            'tracking_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($returnOrderId) {
                if ($value && ReturnOrder::where('tracking_number', $value)->where('created_by', createdBy())->where('id', '!=', $returnOrderId)->exists()) {
                    $fail('The Tracking number has already been taken.');
                }
            }],
            'status' => 'nullable|in:pending,approved,shipped,received,processed,cancelled',
            'reason' => 'nullable|in:defective,wrong_item,damaged,not_needed,other',
            'reason_description' => 'nullable|string',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $returnOrder->update($validated);

        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $totalPrice = $product['quantity'] * $product['unit_price'];

                $syncData[$productId] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'total_price' => $totalPrice,
                ];
            }
            $returnOrder->products()->sync($syncData);
        } else {
            $returnOrder->products()->detach();
        }

        $returnOrder->calculateTotals();

        return redirect()->back()->with('success', __('Return order updated successfully.'));
    }

    public function destroy($returnOrderId)
    {
        $returnOrder = ReturnOrder::where('id', $returnOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$returnOrder) {
            return redirect()->back()->with('error', __('Return order not found.'));
        }

        $returnOrder->products()->detach();
        $returnOrder->delete();

        return redirect()->back()->with('success', __('Return order deleted successfully.'));
    }

    public function getSalesOrderDetails($salesOrderId)
    {
        $salesOrder = SalesOrder::with(['account', 'contact', 'products'])
            ->where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return response()->json(['error' => 'Sales order not found'], 404);
        }

        return response()->json([
            'account_id' => $salesOrder->account_id,
            'contact_id' => $salesOrder->contact_id,
            'products' => $salesOrder->products->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity ?? 1,
                    'unit_price' => $product->pivot->unit_price ?? $product->price ?? 0
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
        if (!auth()->user()->can('export-return-orders')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'return_order_' . date('Y-m-d i:h:s');
        return Excel::download(new ReturnOrderExport(), $name . '.xlsx');
    }
}
