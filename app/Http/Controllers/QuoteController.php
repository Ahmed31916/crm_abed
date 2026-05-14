<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Opportunity;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Models\Tax;
use App\Models\User;
use App\Exports\QuoteExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::query()
            ->with(['opportunity', 'account', 'billingContact', 'shippingContact', 'shippingProviderType', 'creator', 'assignedUser', 'products.tax'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('quote_number', 'like', '%' . $request->search . '%')
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

        if ($request->has('opportunity_id') && !empty($request->opportunity_id) && $request->opportunity_id !== 'all') {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'quote_number', 'name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $quotes = $query->paginate((int)$perPage);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('quotes/index', [
            'quotes' => $quotes,
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
            'opportunities' => Opportunity::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->select('id', 'name')->get(),
            'products' => $this->getFilteredProducts(),
            'shippingProviderTypes' => ShippingProviderType::where('created_by', createdBy())->select('id', 'name')->active()->get(),
            'taxes' => Tax::where('created_by', createdBy())->select('id', 'name', 'rate')->get(),
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'account_id', 'opportunity_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
            'publicUrlBase' => config('app.url'),
            'encryptedQuoteIds' => $quotes->getCollection()->mapWithKeys(function ($quote) {
                return [$quote->id => encrypt($quote->id)];
            }),
        ]);
    }

    public function show($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->with([
                'opportunity',
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

        if (!$quote) {
            return redirect()->route('quotes.index')->with('error', __('Quote not found.'));
        }

        return Inertia::render('quotes/show', [
            'quote' => $quote,
            'streamItems' => $quote->activities
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
        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
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

        return Inertia::render('quotes/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'opportunities' => $opportunities,
            'products' => $products,
            'shippingProviderTypes' => $shippingProviderTypes,
            'taxes' => $taxes,
            'users' => $users
        ]);
    }

    public function edit($id)
    {
        $quote = Quote::with([
            'opportunity',
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
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $contacts = Contact::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();
        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
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

        return Inertia::render('quotes/edit', [
            'quote' => $quote,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'opportunities' => $opportunities,
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
            'opportunity_id' => 'required|exists:opportunities,id',
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
            'valid_until' => 'nullable|date|after:today',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired',
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

        $quote = Quote::create($validated);

        // Sync products with quantity accumulation
        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $lineTotal = $product['quantity'] * $product['unit_price'];
                $discountAmount = $this->calculateDiscountAmount($lineTotal, $product['discount_type'] ?? null, $product['discount_value'] ?? 0);

                if (isset($syncData[$productId])) {
                    $syncData[$productId]['quantity'] += $product['quantity'];
                    $syncData[$productId]['total_price'] = $syncData[$productId]['quantity'] * $product['unit_price'];
                    $syncData[$productId]['discount_amount'] = $this->calculateDiscountAmount($syncData[$productId]['total_price'], $product['discount_type'] ?? null, $product['discount_value'] ?? 0);
                } else {
                    $syncData[$productId] = [
                        'quantity' => $product['quantity'],
                        'unit_price' => $product['unit_price'],
                        'total_price' => $lineTotal,
                        'discount_type' => $product['discount_type'] ?? null,
                        'discount_value' => $product['discount_value'] ?? 0,
                        'discount_amount' => $discountAmount,
                    ];
                }
            }
            $quote->products()->sync($syncData);
        }

        // Calculate and update totals
        $quote->calculateTotals();

        // Fire QuoteCreated event for sending email and notification
        if ($quote && !IsDemo()) {
            event(new \App\Events\QuoteCreated($quote));
        }

        // Check for errors and combine them
        $emailError = session()->pull('email_error');
        $twilioError = session()->pull('twilio_error');

        $errors = [];
        if ($emailError) {
            $errors[] = __('Email send failed: ') . $emailError;
        }
        if ($twilioError) {
            $errors[] = __('SMS send failed: ') . $twilioError;
        }

        if (!empty($errors)) {
            $message = __('Quote created successfully, but ') . implode(', ', $errors);
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Quote created successfully.'));
    }

    public function update(Request $request, $quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'opportunity_id' => 'required|exists:opportunities,id',
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
            'valid_until' => 'nullable|date|after:today',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired',
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

        $quote->fill($validated);
        // Check if status is changing and fire event if email notification is enabled
        if (isEmailTemplateEnabled('Quote Status Changed', createdBy()) && $quote->isDirty('status') && !IsDemo()) {
            $oldStatus = $quote->getOriginal('status');
            $newStatus = $quote->status;

            event(new \App\Events\QuoteStatusChanged($quote, $oldStatus, $newStatus));
        }
        $quote->update($validated);

        // Sync products with quantity accumulation
        if (!empty($products)) {
            $syncData = [];
            foreach ($products as $product) {
                $productId = $product['product_id'];
                $lineTotal = $product['quantity'] * $product['unit_price'];
                $discountAmount = $this->calculateDiscountAmount($lineTotal, $product['discount_type'] ?? null, $product['discount_value'] ?? 0);

                if (isset($syncData[$productId])) {
                    $syncData[$productId]['quantity'] += $product['quantity'];
                    $syncData[$productId]['total_price'] = $syncData[$productId]['quantity'] * $product['unit_price'];
                    $syncData[$productId]['discount_amount'] = $this->calculateDiscountAmount($syncData[$productId]['total_price'], $product['discount_type'] ?? null, $product['discount_value'] ?? 0);
                } else {
                    $syncData[$productId] = [
                        'quantity' => $product['quantity'],
                        'unit_price' => $product['unit_price'],
                        'total_price' => $lineTotal,
                        'discount_type' => $product['discount_type'] ?? null,
                        'discount_value' => $product['discount_value'] ?? 0,
                        'discount_amount' => $discountAmount,
                    ];
                }
            }
            $quote->products()->sync($syncData);
        } else {
            $quote->products()->detach();
        }

        // Calculate and update totals
        $quote->calculateTotals();

        return redirect()->back()->with('success', __('Quote updated successfully.'));
    }

    public function destroy($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $quote->products()->detach();
        $quote->delete();

        return redirect()->back()->with('success', __('Quote deleted successfully.'));
    }

    public function toggleStatus($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $oldStatus = $quote->status;
        $newStatus = $quote->status === 'draft' ? 'sent' : 'draft';

        $quote->update(['status' => $newStatus]);

        // Fire QuoteStatusChanged event if email notification is enabled
        if (isEmailTemplateEnabled('Quote Status Changed', createdBy()) && !IsDemo()) {
            event(new \App\Events\QuoteStatusChanged($quote, $oldStatus, $newStatus));
        }

        return redirect()->back()->with('success', __('Quote status updated successfully.'));
    }

    public function assignUser(Request $request, $quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $quote->update(['assigned_to' => $validated['assigned_to']]);

        return redirect()->back()->with('success', __('User assigned to quote successfully.'));
    }

    public function addOpportunity(Request $request, $quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $validated = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id'
        ]);

        $opportunity = Opportunity::where('id', $validated['opportunity_id'])
            ->where('created_by', createdBy())
            ->with(['contact', 'products', 'account'])
            ->first();

        if (!$opportunity) {
            return redirect()->back()->with('error', __('Opportunity not found.'));
        }

        // Update quote with opportunity data
        $quote->update([
            'opportunity_id' => $opportunity->id,
            'billing_contact_id' => $opportunity->contact_id,
            'account_id' => $opportunity->account_id,
        ]);

        // Add opportunity products to quote
        if ($opportunity->products && $opportunity->products->count() > 0) {
            $syncData = [];

            foreach ($opportunity->products as $product) {
                $productId = $product->id;
                $quantity = $product->pivot->quantity ?? 1;
                $unitPrice = $product->pivot->unit_price ?? $product->price ?? 0;
                $lineTotal = $quantity * $unitPrice;

                if (isset($syncData[$productId])) {
                    $syncData[$productId]['quantity'] += $quantity;
                    $syncData[$productId]['total_price'] = $syncData[$productId]['quantity'] * $unitPrice;
                } else {
                    $syncData[$productId] = [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                        'discount_type' => null,
                        'discount_value' => 0,
                        'discount_amount' => 0,
                    ];
                }
            }

            $quote->products()->sync($syncData);

            // Calculate and update totals
            $quote->calculateTotals();
        }

        return redirect()->back()->with('success', __('Opportunity and its products added to quote successfully.'));
    }

    public function deleteActivities($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        \App\Models\QuoteActivity::where('quote_id', $quote->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($quoteId, $activityId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $activity = \App\Models\QuoteActivity::where('id', $activityId)
            ->where('quote_id', $quote->id)
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Activity not found.'));
        }

        $activity->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function getOpportunityDetails($opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->with(['account', 'contact', 'products'])
            ->first();

        if (!$opportunity) {
            return response()->json(['error' => 'Opportunity not found'], 404);
        }

        return response()->json([
            'account_id' => $opportunity->account_id,
            'billing_contact_id' => $opportunity->contact_id,
            'shipping_contact_id' => $opportunity->contact_id,
            'products' => $opportunity->products->map(function ($product) {
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

    public function fileExport()
    {
        if (!auth()->user()->can('export-quotes')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'quote_' . date('Y-m-d_H-i-s');
        return Excel::download(new QuoteExport(), $name . '.xlsx');
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

    public function publicView($quoteId)
    {
        try {
            $decryptedId = decrypt($quoteId);

            $quote = Quote::where('id', $decryptedId)
                ->with([
                    'account',
                    'contact',
                    'billingContact',
                    'shippingContact',
                    'shippingProviderType',
                    'products.tax',
                    'creator',
                    'assignedUser',
                    'activities.user'
                ])
                ->first();

            if (!$quote) {
                abort(404, __('Quote not found'));
            }

            $settings = settings($quote->created_by);
            $templateId = $settings['quoteTemplate'] ?? 'template1';
            $color = $settings['quoteColor'] ?? 'ffffff';
            $qrEnabled = ($settings['quoteQrEnabled'] ?? 'off') === 'on';
            $themeColor = $settings['themeColor'] ?? 'blue';
            $customColor = $settings['customColor'] ?? null;

            if (!empty($settings['quoteLogoId'])) {
                $media = Media::find($settings['quoteLogoId']);
                $settings['quoteLogo'] = $media ? $media->getUrl() : null;
            }

            return Inertia::render('quotes/public', [
                'quote' => $quote,
                'templateId' => $templateId,
                'color' => $color,
                'qrEnabled' => $qrEnabled,
                'settings' => $settings,
                'themeColor' => $themeColor,
                'customColor' => $customColor,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt quote ID', ['encrypted' => $quoteId, 'error' => $e->getMessage()]);
            abort(404, __('Invalid quote link'));
        }
    }
}
