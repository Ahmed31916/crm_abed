<?php

namespace App\Http\Controllers;

use App\Models\ReceiptOrder;
use App\Models\PurchaseOrder;
use App\Models\Account;
use App\Models\ReturnOrder;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Tax;
use App\Exports\ReceiptOrderExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReceiptOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ReceiptOrder::query()
            ->with(['purchaseOrder', 'account', 'returnOrder', 'contact', 'creator', 'assignedUser', 'products.tax'])
            ->where(function($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('receipt_number', 'like', '%' . $request->search . '%')
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
        $allowedSorts=['id', 'receipt_number', 'receipt_date'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $receiptOrders = $query->paginate((int)$perPage);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('receipt-orders/index', [
            'receiptOrders' => $receiptOrders,
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
            'purchaseOrders' => PurchaseOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'returnOrders' => ReturnOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'products' => $this->getFilteredProducts(),
            'taxes' => Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($receiptOrderId)
    {
        $receiptOrder = ReceiptOrder::where('id', $receiptOrderId)
            ->where('created_by', createdBy())
            ->with([
                'purchaseOrder',
                'account',
                'returnOrder',
                'contact',
                'creator',
                'assignedUser',
                'products.tax'
            ])
            ->first();

        if (!$receiptOrder) {
            return redirect()->route('receipt-orders.index')->with('error', __('Receipt order not found.'));
        }

        return Inertia::render('receipt-orders/show', [
            'receiptOrder' => $receiptOrder
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
        $purchaseOrders = PurchaseOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $returnOrders = ReturnOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $products = $this->getFilteredProducts();
        $taxes = Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('receipt-orders/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'purchaseOrders' => $purchaseOrders,
            'returnOrders' => $returnOrders,
            'products' => $products,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $receiptOrder = ReceiptOrder::with([
            'purchaseOrder',
            'account',
            'returnOrder',
            'contact',
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
        $purchaseOrders = PurchaseOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $returnOrders = ReturnOrder::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $products = $this->getFilteredProducts();
        $taxes = Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('receipt-orders/edit', [
            'receiptOrder' => $receiptOrder,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'purchaseOrders' => $purchaseOrders,
            'returnOrders' => $returnOrders,
            'products' => $products,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'return_order_id' => 'nullable|exists:return_orders,id',
            'contact_id' => 'required|exists:contacts,id',
            'receipt_date' => 'required|date',
            'expected_date' => 'nullable|date|after:receipt_date',
            'status' => 'nullable|in:pending,received,partial,completed,cancelled',
            'notes' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'products.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        if (empty($validated['purchase_order_id']) && empty($validated['return_order_id'])) {
            return redirect()->back()->withErrors(['purchase_order_id' => __('Either Purchase Order or Return Order is required.')])->withInput();
        }

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'pending';

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $receiptOrder = ReceiptOrder::create($validated);

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
            $receiptOrder->products()->sync($syncData);
        }

        $receiptOrder->calculateTotals();

        // Fire ReceiptOrderCreated event for sending email
        if ($receiptOrder && !IsDemo()) {
            event(new \App\Events\ReceiptOrderCreated($receiptOrder));
        }

        // Check for errors
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Receipt order created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Receipt order created successfully.'));
    }

    public function update(Request $request, $receiptOrderId)
    {
        $receiptOrder = ReceiptOrder::where('id', $receiptOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$receiptOrder) {
            return redirect()->back()->with('error', __('Receipt order not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'account_id' => 'required|exists:accounts,id',
            'return_order_id' => 'nullable|exists:return_orders,id',
            'contact_id' => 'required|exists:contacts,id',
            'receipt_date' => 'required|date',
            'expected_date' => 'nullable|date|after:receipt_date',
            'status' => 'nullable|in:pending,received,partial,completed,cancelled',
            'notes' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount_type' => 'nullable|in:percentage,fixed,none',
            'products.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        if (empty($validated['purchase_order_id']) && empty($validated['return_order_id'])) {
            return redirect()->back()->withErrors(['purchase_order_id' => __('Either Purchase Order or Return Order is required.')])->withInput();
        }

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $receiptOrder->update($validated);

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
            $receiptOrder->products()->sync($syncData);
        } else {
            $receiptOrder->products()->detach();
        }

        $receiptOrder->calculateTotals();

        return redirect()->back()->with('success', __('Receipt order updated successfully.'));
    }

    public function destroy($receiptOrderId)
    {
        $receiptOrder = ReceiptOrder::where('id', $receiptOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$receiptOrder) {
            return redirect()->back()->with('error', __('Receipt order not found.'));
        }

        $receiptOrder->products()->detach();
        $receiptOrder->delete();

        return redirect()->back()->with('success', __('Receipt order deleted successfully.'));
    }

    public function toggleStatus($receiptOrderId)
    {
        $receiptOrder = ReceiptOrder::where('id', $receiptOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$receiptOrder) {
            return redirect()->back()->with('error', __('Receipt order not found.'));
        }

        $newStatus = $receiptOrder->status === 'pending' ? 'received' : 'pending';
        $receiptOrder->update(['status' => $newStatus]);

        return redirect()->back()->with('success', __('Receipt order status updated successfully.'));
    }



    public function assignUser(Request $request, $receiptOrderId)
    {
        $receiptOrder = ReceiptOrder::where('id', $receiptOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$receiptOrder) {
            return redirect()->back()->with('error', __('Receipt order not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $receiptOrder->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to receipt order successfully.'));
    }



    public function getPurchaseOrderDetails($purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::with(['account', 'contact', 'products'])
            ->where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return response()->json(['error' => 'Purchase order not found'], 404);
        }

        return response()->json([
            'account_id' => $purchaseOrder->account_id,
            'contact_id' => $purchaseOrder->contact_id,
            'products' => $purchaseOrder->products->map(function ($product) {
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



    public function getReturnOrderDetails($returnOrderId)
    {
        $returnOrder = ReturnOrder::with(['account', 'contact', 'products'])
            ->where('id', $returnOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$returnOrder) {
            return response()->json(['error' => 'Return order not found'], 404);
        }

        return response()->json([
            'account_id' => $returnOrder->account_id,
            'contact_id' => $returnOrder->contact_id,
            'products' => $returnOrder->products->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'quantity' => $product->pivot->quantity ?? 1,
                    'unit_price' => $product->pivot->unit_price ?? $product->price ?? 0,
                    'discount_type' => 'none',
                    'discount_value' => 0
                ];
            })
        ]);
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

    public function fileExport()
    {
        if (!auth()->user()->can('export-receipt-orders')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'receipt_order_' . date('Y-m-d i:h:s');
        return Excel::download(new ReceiptOrderExport(), $name . '.xlsx');
    }
}
