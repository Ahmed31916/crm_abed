<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\OpportunityStage;
use App\Models\OpportunitySource;
use App\Exports\OpportunityExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::query()
            ->with(['account', 'contact', 'opportunityStage', 'opportunitySource', 'assignedUser', 'products'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle filters
        if ($request->has('account_id') && !empty($request->account_id) && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('opportunity_stage_id') && !empty($request->opportunity_stage_id) && $request->opportunity_stage_id !== 'all') {
            $query->where('opportunity_stage_id', $request->opportunity_stage_id);
        }

        if ($request->has('opportunity_source_id') && !empty($request->opportunity_source_id) && $request->opportunity_source_id !== 'all') {
            $query->where('opportunity_source_id', $request->opportunity_source_id);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'name', 'amount', 'close_date', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        if ($request->view === 'kanban' || empty($request->view)) {
            $opportunities = collect(['data' => $query->get()]);
        } else {
            $perPage = $request->get('per_page', 10);
            $opportunities = $query->paginate((int)$perPage);
        }

        // Get data for dropdowns - filter by assigned_to for non-company users
        $accounts = Account::where('created_by', createdBy())->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })->get(['id', 'name']);
        $contacts = Contact::where('created_by', createdBy())->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })->get(['id', 'name', 'account_id']);
        $products = Product::where('created_by', createdBy())->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })->with('tax')->get(['id', 'name', 'price', 'tax_id']);
        $opportunityStages = OpportunityStage::where('created_by', createdBy())->where('status', 'active')
            ->get(['id', 'name', 'color']);
        $opportunitySources = OpportunitySource::where('created_by', createdBy())->where('status', 'active')
            ->get(['id', 'name']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('opportunities/index', [
            'opportunities' => $opportunities,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'products' => $products,
            'opportunityStages' => $opportunityStages,
            'opportunitySources' => $opportunitySources,
            'users' => $users,
            'filters' => $request->all(['view', 'search', 'account_id', 'opportunity_stage_id', 'opportunity_source_id', 'status', 'assigned_to', 'sort_field', 'sort_direction', 'per_page', 'page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'close_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'opportunity_stage_id' => 'required|exists:opportunity_stages,id',
            'opportunity_source_id' => 'required|exists:opportunity_sources,id',
            'status' => 'nullable|in:active,inactive',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $products = $validated['products'] ?? [];
        unset($validated['products']);

        $opportunity = Opportunity::create($validated);

        // Attach products
        if (!empty($products)) {
            foreach ($products as $product) {
                $opportunity->products()->attach($product['product_id'], [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'total_price' => $product['quantity'] * $product['unit_price'],
                ]);
            }

            // Calculate and update totals
            $opportunity->load('products.tax');
            $opportunity->calculateTotals();
        }

        if ($opportunity && !IsDemo()) {
            event(new \App\Events\OpportunityCreated($opportunity));
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
            $message = __('opportunity created successfully, but ') . implode(', ', $errors);
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Opportunity created successfully.'));
    }

    public function update(Request $request, $opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunity) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'amount' => 'nullable|numeric|min:0',
                    'close_date' => 'nullable|date',
                    'notes' => 'nullable|string',
                    'account_id' => 'required|exists:accounts,id',
                    'contact_id' => 'required|exists:contacts,id',
                    'opportunity_stage_id' => 'required|exists:opportunity_stages,id',
                    'opportunity_source_id' => 'required|exists:opportunity_sources,id',
                    'status' => 'nullable|in:active,inactive',
                    'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
                    'products' => 'required|array|min:1',
                    'products.*.product_id' => 'required|exists:products,id',
                    'products.*.quantity' => 'required|integer|min:1',
                    'products.*.unit_price' => 'required|numeric|min:0',
                ]);

                // Auto-assign to current user if staff user
                if (auth()->user()->type !== 'company') {
                    $validated['assigned_to'] = auth()->id();
                }

                $products = $validated['products'] ?? [];
                unset($validated['products']);

                // Remove amount from validated data if products exist to prevent auto-calculation override
                if ($opportunity->products()->count() > 0) {
                    unset($validated['amount']);
                }

                $opportunity->fill($validated);

                if (isEmailTemplateEnabled('Opportunity Status Changed', createdBy()) && $opportunity && $opportunity->assigned_to && $opportunity->isDirty('opportunity_stage_id') && !IsDemo()) {
                    $old = $opportunity->getOriginal('opportunity_stage_id');
                    $new = $opportunity->opportunity_stage_id;

                    $oldStageName = \App\Models\OpportunityStage::find($old)?->name ?? 'N/A';
                    $newStageName = \App\Models\OpportunityStage::find($new)?->name ?? 'N/A';
                    event(new \App\Events\OpportunityStageChanged($opportunity, $oldStageName, $newStageName));
                }

                $opportunity->update($validated);

                // Only sync products if products data is provided
                if (isset($request->products)) {
                    $opportunity->products()->detach();
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $opportunity->products()->attach($product['product_id'], [
                                'quantity' => $product['quantity'],
                                'unit_price' => $product['unit_price'],
                                'total_price' => $product['quantity'] * $product['unit_price'],
                            ]);
                        }

                        // Calculate and update totals
                        $opportunity->load('products.tax');
                        $opportunity->calculateTotals();
                    } else {
                        // If no products, set amount to 0
                        $opportunity->updateQuietly(['amount' => 0]);
                    }
                }

                return redirect()->back()->with('success', __('Opportunity updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update opportunity.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity not found.'));
        }
    }

    public function destroy($opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunity) {
            try {
                $opportunity->products()->detach();
                $opportunity->delete();
                return redirect()->back()->with('success', __('Opportunity deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete opportunity.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity not found.'));
        }
    }

    public function show($opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->with(['account', 'contact', 'opportunityStage', 'opportunitySource', 'assignedUser', 'products.tax', 'quotes', 'activities.user', 'comments.user'])
            ->first();

        if (!$opportunity) {
            return redirect()->route('opportunities.index')->with('error', __('Opportunity not found.'));
        }

        // Get related meetings
        $parentMeetings = \App\Models\Meeting::where('created_by', createdBy())
            ->where('parent_module', 'opportunity')
            ->where('parent_id', $opportunityId)
            ->with(['creator', 'assignedUser'])
            ->get();

        // Get related calls
        $parentCalls = \App\Models\Call::where('created_by', createdBy())
            ->where('parent_module', 'opportunity')
            ->where('parent_id', $opportunityId)
            ->with(['creator', 'assignedUser'])
            ->get()
            ->map(function ($call) {
                $call->type = 'call';
                return $call;
            });

        $meetings = $parentMeetings->merge($parentCalls)->sortByDesc('start_date')->values();

        return Inertia::render('opportunities/show', [
            'opportunity' => $opportunity,
            'streamItems' => $opportunity->activities()->orderBy('created_at', 'asc')->get(),
            'meetings' => $meetings
        ]);
    }

    public function deleteActivities($id)
    {
        $opportunity = Opportunity::where('id', $id)
            ->where('created_by', createdBy())
            ->firstOrFail();

        \App\Models\OpportunityActivity::where('opportunity_id', $opportunity->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($opportunityId, $activityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        \App\Models\OpportunityActivity::where('id', $activityId)
            ->where('opportunity_id', $opportunity->id)
            ->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function toggleStatus($opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunity) {
            try {
                $opportunity->status = $opportunity->status === 'active' ? 'inactive' : 'active';
                $opportunity->save();

                return redirect()->back()->with('success', __('Opportunity status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update opportunity status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity not found.'));
        }
    }

    public function updateStatus(Request $request, $opportunityId)
    {
        $validated = $request->validate([
            'opportunity_stage_id' => 'required|exists:opportunity_stages,id',
        ]);

        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $opportunity->fill($validated);

        if (isEmailTemplateEnabled('Opportunity Status Changed', createdBy()) && $opportunity && $opportunity->assigned_to && $opportunity->isDirty('opportunity_stage_id') && !IsDemo()) {
            $old = $opportunity->getOriginal('opportunity_stage_id');
            $new = $opportunity->opportunity_stage_id;

            $oldStageName = \App\Models\OpportunityStage::find($old)?->name ?? 'N/A';
            $newStageName = \App\Models\OpportunityStage::find($new)?->name ?? 'N/A';
            event(new \App\Events\OpportunityStageChanged($opportunity, $oldStageName, $newStageName));
        }
        $opportunity->update([
            'opportunity_stage_id' => $validated['opportunity_stage_id']
        ]);

        return redirect()->back()->with('success', __('Opportunity status updated successfully.'));
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-opportunities')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'opportunity_' . date('Y-m-d i:h:s');
        return Excel::download(new OpportunityExport(), $name . '.xlsx');
    }
}
