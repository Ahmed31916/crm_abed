<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Quote;
use App\Models\Opportunity;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PlanOrder;
use App\Exports\InvoiceExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Update overdue invoices if not in demo mode
        if (!IsDemo()) {
            Invoice::where('created_by', createdBy())
                ->whereIn('status', ['sent', 'pending', 'partially_paid'])
                ->whereDate('due_date', '<', now())
                ->update(['status' => 'overdue']);
        }

        $query = Invoice::query()
            ->with(['salesOrder', 'quote', 'opportunity', 'account', 'contact', 'creator', 'assignedUser', 'products.tax'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
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
        $allowedSorts=['id', 'invoice_number', 'name', 'due_date'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $invoices = $query->paginate((int)$perPage);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
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
            'availableSalesOrders' => SalesOrder::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name', 'order_number')->get(),
            'quotes' => Quote::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name', 'quote_number')->get(),
            'opportunities' => Opportunity::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'products' => $this->getFilteredProducts(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
            'publicUrlBase' => config('app.url'),
            'encryptedInvoiceIds' => $invoices->getCollection()->mapWithKeys(function ($invoice) {
                return [$invoice->id => encrypt($invoice->id)];
            }),
        ]);
    }

    public function show($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->with([
                'salesOrder',
                'quote',
                'opportunity',
                'account',
                'contact',
                'creator',
                'assignedUser',
                'products.tax',
                'payments',
                'activities.user',
                'reminders.sentBy'
            ])
            ->first();

        if (!$invoice) {
            return redirect()->route('invoices.index')->with('error', __('Invoice not found.'));
        }

        // Get pending payments for approval
        $pendingPayments = $invoice->payments()->where('status', 'pending')->get();

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'streamItems' => $invoice->activities,
            'pendingPayments' => $pendingPayments,
            'availableSalesOrders' => SalesOrder::where('created_by', createdBy())->select('id', 'name', 'order_number')->get(),
            'publicUrlBase' => config('app.url'),
        ]);
    }

    public function getReminderHistory($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return response()->json(['error' => __('Invoice not found')], 404);
        }

        $reminders = \App\Models\InvoiceReminder::where('invoice_id', $invoice->id)
            ->with('sentBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['reminders' => $reminders]);
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
        $quotes = Quote::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'quote_number')->get();
        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $products = $this->getFilteredProducts();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('invoices/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
            'quotes' => $quotes,
            'opportunities' => $opportunities,
            'products' => $products,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $invoice = Invoice::with([
            'salesOrder',
            'quote',
            'opportunity',
            'account',
            'contact',
            'creator',
            'assignedUser',
            'products.tax'
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
        $quotes = Quote::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name', 'quote_number')->get();
        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $products = $this->getFilteredProducts();

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'salesOrders' => $salesOrders,
            'quotes' => $quotes,
            'opportunities' => $opportunities,
            'products' => $products,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'quote_id' => 'required|exists:quotes,id',
            'opportunity_id' => 'required|exists:opportunities,id',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'status' => 'nullable|in:draft,sent,pending,paid,partially_paid,overdue,cancelled',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'payment_method' => 'nullable|in:stripe,paypal,skrill,razorpay,mercadopago,paystack,flutterwave,paytabs,coingate,bank_transfer',
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

        // Check inventory if status is not cancelled
        if ($validated['status'] !== 'cancelled') {
            foreach ($products as $product) {
                $productModel = Product::find($product['product_id']);
                if ($productModel && $productModel->stock_quantity < $product['quantity']) {
                    return redirect()->back()->withErrors(['error' => __("Insufficient stock for product: :name. Available: :stock, Required: :required", [
                        'name' => $productModel->name,
                        'stock' => $productModel->stock_quantity,
                        'required' => $product['quantity']
                    ])]);
                }
            }
        }

        unset($validated['products']);

        $invoice = Invoice::create($validated);

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
            $invoice->products()->sync($syncData);
            Invoice::updateInventory($invoice);
        }


        $invoice->calculateTotals();

        // Fire InvoiceCreated event for sending email
        if ($invoice && !IsDemo()) {
            event(new \App\Events\InvoiceCreated($invoice));
        }

        // Check for email error
        $emailError = session()->pull('email_error');

        if ($emailError) {
            $message = __('Invoice created successfully, but ') . __('Email send failed: ') . $emailError;
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Invoice created successfully.'));
    }

    public function update(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->with('products')
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sales_order_id' => 'required|exists:sales_orders,id',
            'quote_id' => 'required|exists:quotes,id',
            'opportunity_id' => 'required|exists:opportunities,id',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'status' => 'nullable|in:draft,sent,pending,paid,partially_paid,overdue,cancelled',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string',
            'billing_state' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_country' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'payment_method' => 'nullable|in:stripe,paypal,skrill,razorpay,mercadopago,paystack,flutterwave,paytabs,coingate,bank_transfer',
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

        $oldStatus = $invoice->status;
        $newStatus = $validated['status'] ?? $oldStatus;
        $products = $validated['products'] ?? [];

        unset($validated['products']);

        DB::beginTransaction();

        try {
            // Restore Old Inventory (if not cancelled)
            if ($oldStatus !== 'cancelled') {
                Invoice::restoreInventory($invoice);
            }
            // Validate New Inventory (if not cancelled)

            if ($newStatus !== 'cancelled') {
                foreach ($products as $product) {
                    $productModel = Product::find($product['product_id']);

                    if ($productModel && $productModel->stock_quantity < $product['quantity']) {
                        DB::rollBack();

                        return redirect()->back()->withErrors(['error' => __("Insufficient stock for product: :name. Available: :stock, Required: :required", [
                            'name' => $productModel->name,
                            'stock' => $productModel->stock_quantity,
                            'required' => $product['quantity']
                        ])]);
                    }
                }
            }

            // Update Invoice
            Invoice::withoutEvents(function () use ($invoice, $validated) {
                $invoice->update($validated);
            });
            // Sync Products
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
            $invoice->products()->sync($syncData);
            // Deduct New Inventory (if not cancelled)
            if ($newStatus !== 'cancelled') {
                $invoice->load('products');
                Invoice::updateInventory($invoice);
            }
            // Recalculate Totals
            $invoice->calculateTotals();

            DB::commit();

            return redirect()->back()->with('success', __('Invoice updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('Something went wrong. Please try again later.'));
        }
    }

    public function destroy($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        Invoice::restoreInventory($invoice);

        $invoice->products()->detach();
        $invoice->delete();

        return redirect()->back()->with('success', __('Invoice deleted successfully.'));
    }

    public function toggleStatus($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $newStatus = $invoice->status === 'draft' ? 'sent' : 'draft';
        $invoice->update(['status' => $newStatus]);

        return redirect()->back()->with('success', __('Invoice status updated successfully.'));
    }



    public function assignUser(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $invoice->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to invoice successfully.'));
    }

    public function deleteActivities($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        \App\Models\InvoiceActivity::where('invoice_id', $invoice->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($invoiceId, $activityId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $activity = \App\Models\InvoiceActivity::where('id', $activityId)
            ->where('invoice_id', $invoice->id)
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Activity not found.'));
        }

        $activity->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function paymentSuccess(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $paymentOrder = PlanOrder::findOrFail($request->order);

        // Update payment order status
        $paymentOrder->update([
            'status' => 'approved',
            'payment_id' => $request->payment_id ?? null,
            'processed_at' => now(),
        ]);

        // Update invoice status if fully paid
        $totalPaid = PlanOrder::where('notes', 'like', "%Invoice #{$invoice->invoice_number}%")
            ->where('status', 'approved')
            ->sum('final_price');

        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update(['status' => 'paid']);
        }

        return redirect()->route('invoices.public', $invoiceId)
            ->with('success', __('Payment processed successfully!'));
    }

    public function paymentCancel(Request $request, $invoiceId)
    {
        $paymentOrder = PlanOrder::findOrFail($request->order);
        $paymentOrder->update(['status' => 'cancelled']);

        return redirect()->route('invoices.public', $invoiceId)
            ->with('error', __('Payment was cancelled.'));
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

    public function publicView($invoiceId)
    {
        // Decrypt the encrypted ID
        try {
            $decryptedId = decrypt($invoiceId);

            $invoice = Invoice::where('id', $decryptedId)
                ->with([
                    'account',
                    'contact',
                    'products.tax',
                    'payments',
                    'creator'
                ])
                ->first();

            if (!$invoice) {
                abort(404, __('Invoice not found'));
            }

            if (($invoice->due_date && $invoice->due_date < now()) && in_array($invoice->status, ['sent', 'pending', 'partially_paid'])) {
                $invoice->update(['status' => 'overdue']);
            }

            // Get company's payment settings like plan subscription
            $companyId = $invoice->created_by;
            $paymentSettings = getPaymentSettings($companyId);

            // Add payment methods to invoice object like plan subscription
            $invoice->paymentMethods = $paymentSettings;


            $settings = settings($companyId);
            $templateId = $settings['invoiceTemplate'] ?? 'template1';
            $color = $settings['invoiceColor'] ?? 'ffffff';
            $qrEnabled = ($settings['invoiceQrEnabled'] ?? 'off') === 'on';
            $themeColor = $settings['themeColor'] ?? 'blue';
            $customColor = $settings['customColor'] ?? '#10b77f';

            if (!empty($settings['invoiceLogoId'])) {
                $media = Media::find($settings['invoiceLogoId']);
                $settings['invoiceLogo'] = $media ? $media->getUrl() : null;
            }

            return Inertia::render('invoices/public', [
                'invoice' => $invoice,
                'templateId' => $templateId,
                'color' => $color,
                'qrEnabled' => $qrEnabled,
                'settings' => $settings,
                'themeColor' => $themeColor,
                'customColor' => $customColor,
            ]);
        } catch (\Exception $e) {
            abort(404, __('Invalid invoice link'));
        }
    }

    public function processPayment(Request $request, $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $validated = $request->validate([
            'payment_method' => 'required|string|in:stripe,paypal,bank,skrill,razorpay,mercadopago,paystack,flutterwave,paytabs,coingate',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:full,partial',
        ]);

        // Validate payment amount and type
        $validation = $invoice->validatePaymentAmount($validated['amount'], $validated['payment_type']);
        if (!$validation['valid']) {
            return back()->withErrors(['error' => $validation['message']]);
        }

        // Validate payment method configuration
        $paymentService = new \App\Services\InvoicePaymentService();
        if (!$paymentService->validatePaymentMethodConfig($validated['payment_method'], $invoice->created_by)) {
            return back()->withErrors(['error' => __(ucfirst($validated['payment_method']) . ' payment method is not configured.')]);
        }

        // Store invoice payment data in session
        session([
            'invoice_payment' => [
                'invoice_id' => $invoiceId,
                'amount' => $validated['amount'],
                'payment_type' => $validated['payment_type'],
                'payment_method' => $validated['payment_method']
            ]
        ]);

        // Redirect to payment method
        switch ($validated['payment_method']) {
            case 'stripe':
                return redirect()->route('stripe.payment');
            case 'paypal':
                return redirect()->route('paypal.payment');
            case 'bank':
                return redirect()->route('bank.payment');
            case 'skrill':
                return redirect()->route('skrill.payment');
            default:
                return back()->withErrors(['error' => __('Invalid payment method')]);
        }
    }



    public function showPaymentPage(Request $request, $method)
    {
        $invoiceId = $request->get('invoice_id');
        $amount = $request->get('amount');
        $paymentType = $request->get('payment_type', 'full');

        if (!$invoiceId || !$amount) {
            abort(400, __('Missing required parameters'));
        }

        $invoice = Invoice::findOrFail($invoiceId);

        // Get company's payment settings using helper function
        $paymentSettings = getPaymentMethodConfig('stripe', $invoice->created_by);

        // Get currency from settings or default
        $currency = getSetting('defaultCurrency', 'USD', $invoice->created_by);

        return \Inertia\Inertia::render('invoices/payment', [
            'invoice' => $invoice,
            'paymentMethod' => $method,
            'amount' => $amount,
            'paymentType' => $paymentType,
            'paymentSettings' => $paymentSettings,
            'currency' => $currency,
        ]);
    }

    private function getEnabledPaymentMethods()
    {
        $settings = getPaymentGatewaySettings();
        $methods = [];

        if (isPaymentMethodEnabled('stripe')) {
            $methods['stripe'] = [
                'name' => 'Stripe',
                'enabled' => true,
            ];
        }

        if (isPaymentMethodEnabled('paypal')) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'enabled' => true,
            ];
        }

        if (isPaymentMethodEnabled('bank')) {
            $methods['bank'] = [
                'name' => 'Bank Transfer',
                'enabled' => true,
                'details' => $settings['payment_settings']['bank_detail'] ?? null,
            ];
        }

        return $methods;
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-invoices')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return Excel::download(new InvoiceExport, 'invoices-' . now()->format('Y-m-d-H-i-s') . '.xlsx');
    }

    public function getSalesOrderDetails($salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->with(['account', 'billingContact', 'shippingContact', 'products', 'quote.opportunity'])
            ->first();

        if (!$salesOrder) {
            return response()->json(['error' => __('Sales order not found')], 404);
        }

        return response()->json([
            'account_id' => $salesOrder->account_id,
            'contact_id' => $salesOrder->billing_contact_id,
            'quote_id' => $salesOrder->quote_id,
            'opportunity_id' => $salesOrder->quote ? $salesOrder->quote->opportunity_id : null,
            'billing_address' => $salesOrder->billing_address,
            'billing_city' => $salesOrder->billing_city,
            'billing_state' => $salesOrder->billing_state,
            'billing_postal_code' => $salesOrder->billing_postal_code,
            'billing_country' => $salesOrder->billing_country,
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

    public function getQuoteDetails($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->with(['account', 'billingContact', 'products'])
            ->first();

        if (!$quote) {
            return response()->json(['error' => __('Quote not found')], 404);
        }

        return response()->json([
            'account_id' => $quote->account_id,
            'contact_id' => $quote->billing_contact_id,
            'billing_address' => $quote->billing_address,
            'billing_city' => $quote->billing_city,
            'billing_state' => $quote->billing_state,
            'billing_postal_code' => $quote->billing_postal_code,
            'billing_country' => $quote->billing_country,
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

    public function getOpportunityDetails($opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->with(['account', 'contact', 'products'])
            ->first();

        if (!$opportunity) {
            return response()->json(['error' => __('Opportunity not found')], 404);
        }

        return response()->json([
            'account_id' => $opportunity->account_id,
            'contact_id' => $opportunity->contact_id,
            'products' => $opportunity->products->map(function ($product) {
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

    public function getPaymentSummary($invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return response()->json(['error' => __('Invoice not found')], 404);
        }

        $paymentService = new \App\Services\InvoicePaymentService();
        $summary = $paymentService->getPaymentSummary($invoiceId);

        return response()->json($summary);
    }

    public function approvePayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = \App\Models\InvoicePayment::where('payment_id', $paymentId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Check if user has permission to approve this payment
            $invoice = $payment->invoice;
            if ($invoice->created_by !== createdBy()) {
                return back()->withErrors(['error' => __('Unauthorized to approve this payment.')]);
            }

            $paymentService = new \App\Services\InvoicePaymentService();
            $paymentService->approvePayment($paymentId, $validated['notes'] ?? null);

            return back()->with('success', __('Payment approved successfully.'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Failed to approve payment.')]);
        }
    }

    public function rejectPayment(Request $request, $paymentId)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $payment = \App\Models\InvoicePayment::where('payment_id', $paymentId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Check if user has permission to reject this payment
            $invoice = $payment->invoice;
            if ($invoice->created_by !== createdBy()) {
                return back()->withErrors(['error' => __('Unauthorized to reject this payment.')]);
            }

            $paymentService = new \App\Services\InvoicePaymentService();
            $paymentService->rejectPayment($paymentId, $validated['reason'] ?? null);

            return back()->with('success', __('Payment rejected successfully.'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('Failed to reject payment.')]);
        }
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

    /**
     * Preview invoice template with color
     */
    public function previewTemplate($templateId, $color)
    {
        $userId = createdBy();
        $settings = settings($userId);

        // Sample invoice data for preview
        $invoice = (object) [
            'invoice_number' => 'INV-2024-001',
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 1000,
            'tax_amount' => 100,
            'total_amount' => 1100,
            'account' => (object) [
                'name' => 'Sample Client',
                'email' => 'client@example.com',
                'phone' => '(555) 123-4567'
            ],
            'billing_address' => '456 Client Avenue',
            'billing_city' => 'Client City',
            'billing_state' => 'State',
            'billing_postal_code' => '67890',
            'products' => [
                (object) [
                    'name' => 'Web Development',
                    'pivot' => (object) ['quantity' => 10, 'unit_price' => 75, 'total_price' => 750],
                    'tax' => (object) ['name' => 'VAT', 'rate' => 10]
                ],
                (object) [
                    'name' => 'Design Services',
                    'pivot' => (object) ['quantity' => 5, 'unit_price' => 50, 'total_price' => 250],
                    'tax' => (object) ['name' => 'VAT', 'rate' => 10]
                ]
            ],
            'notes' => 'Thank you for your business!'
        ];

        $templateColor = '#' . $color;
        $isPreview = true;

        return Inertia::render('Invoices-template/TemplatePreview', [
            'invoice' => $invoice,
            'templateId' => (int) $templateId,
            'templateColor' => $templateColor,
            'settings' => $settings,
            'isPreview' => $isPreview
        ]);
    }
}
