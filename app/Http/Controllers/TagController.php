<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Product;
use FedaPay\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Str;

/**
 * TagController - إدارة التاجات
 *
 * كنترولر كامل لإدارة التاجات في لوحة التحكم
 * يستخدم Inertia.js مثل باقي كنترولرات المشروع
 *
 * الصلاحيات:
 * - السوبر أدمن يقدر يشوف ويعدل تاجاته + تاجات كل الشركات
 * - الشركة تشوف تاجاتها + تاجات السوبر أدمن
 * - الشركة تقدر تعدل تاجاتها فقط
 * - الـ staff يشوف تاجات شركته + تاجات السوبر أدمن
 */
class TagController extends Controller
{
    /**
     * عرض قائمة التاجات
     */
    // public function index(Request $request)
    // {
    //     $query = Tag::with('creator')
    //         ->visibleTo(createdBy());

    //     // البحث
    //     if ($request->filled('search')) {
    //         $query->search($request->search);
    //     }

    //     // فلتر حسب المالك
    //     if ($request->filled('owner') && $request->owner !== 'all') {
    //         if ($request->owner === 'mine') {
    //             $query->where('created_by', createdBy());
    //         } elseif ($request->owner === 'superadmin') {
    //             $query->where('created_by', getSuperAdminCompanyId());
    //         }
    //     }

    //     // الترتيب
    //     $sortField = $request->input('sort_field', 'name');
    //     $sortDirection = $request->input('sort_direction', 'asc');
    //     $allowedSorts = ['id', 'name', 'created_at'];
    //     $allowedDirections = ['asc', 'desc'];

    //     if (!in_array($sortField, $allowedSorts)) $sortField = 'name';
    //     if (!in_array($sortDirection, $allowedDirections)) $sortDirection = 'asc';

    //     $query->orderBy($sortField, $sortDirection);

    //     $perPage = $request->input('per_page', 25);
    //     $tags = $query->paginate($perPage);

    //     // إضافة عدد المنتجات لكل تاج
    //     $tags->each(function ($tag) {
    //         $tag->product_count = $tag->products()->count();
    //     });

    //     return Inertia::render('tags/index', [
    //         'tags' => $tags,
    //         'filters' => $request->all(['search', 'owner', 'sort_field', 'sort_direction', 'per_page']),
    //         'superAdminId' => getSuperAdminCompanyId(),
    //         'currentCompanyId' => createdBy(),
    //     ]);
    // }

    /**
     * عرض صفحة إنشاء تاج جديد
     */
    public function create()
    {
        return Inertia::render('tags/create');
    }

    /**
     * حفظ تاج جديد
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name'        => 'required|string|max:255',
    //         'color'       => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
    //         'description' => 'nullable|string|max:1000',
    //     ]);

    //     try {
    //         // تحقق إن الاسم مش موجود مسبقاً لنفس المستخدم
    //         $exists = Tag::where('name', $validated['name'])
    //             ->where('created_by', createdBy())
    //             ->exists();

    //         if ($exists) {
    //             return redirect()->back()
    //                 ->withInput()
    //                 ->withErrors(['name' => __('You already have a tag with this name.')]);
    //         }

    //         Tag::create([
    //             'name'        => $validated['name'],
    //             'color'       => $validated['color'] ?? '#6B7280',
    //             'description' => $validated['description'] ?? null,
    //             'created_by'  => createdBy(),
    //         ]);

    //         return redirect()->route('tags.index')
    //             ->with('success', __('Tag created successfully.'));

    //     } catch (\Exception $e) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', __('Failed to create tag: :error', ['error' => $e->getMessage()]));
    //     }
    // }

    /**
     * عرض تفاصيل تاج مع المنتجات المرتبطة
     */
    public function show($id)
    {
        $tag = Tag::with('creator')
            ->visibleTo(createdBy())
            ->findOrFail($id);

        $products = Product::whereHas('tags', function ($q) use ($tag) {
            $q->where('tags.id', $tag->id);
        })
            ->with(['category', 'brand', 'media'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->where('status', 'active')
            ->paginate(15);

        return Inertia::render('tags/show', [
            'tag' => $tag,
            'products' => $products,
            'canEdit' => $tag->canEdit(),
            'superAdminId' => getSuperAdminCompanyId(),
        ]);
    }

    /**
     * عرض صفحة تعديل تاج
     */
    public function edit($id)
    {
        $tag = Tag::visibleTo(createdBy())->findOrFail($id);

        // فقط صاحب التاج يقدر يعدله
        if (!$tag->canEdit()) {
            return redirect()->route('tags.index')
                ->with('error', __('You cannot edit this tag. Only the owner can edit.'));
        }

        return Inertia::render('tags/edit', [
            'tag' => $tag,
        ]);
    }

    /**
     * تحديث تاج
     */
    // public function update(Request $request, $id)
    // {
    //     $tag = Tag::visibleTo(createdBy())->findOrFail($id);

    //     if (!$tag->canEdit()) {
    //         return redirect()->back()
    //             ->with('error', __('You cannot edit this tag.'));
    //     }

    //     $validated = $request->validate([
    //         'name'        => 'required|string|max:255',
    //         'color'       => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
    //         'description' => 'nullable|string|max:1000',
    //     ]);

    //     try {
    //         // تحقق إن الاسم مش موجود عند نفس المستخدم (باستثناء التاج الحالي)
    //         $exists = Tag::where('name', $validated['name'])
    //             ->where('created_by', $tag->created_by)
    //             ->where('id', '!=', $tag->id)
    //             ->exists();

    //         if ($exists) {
    //             return redirect()->back()
    //                 ->withInput()
    //                 ->withErrors(['name' => __('A tag with this name already exists.')]);
    //         }

    //         $tag->update([
    //             'name'        => $validated['name'],
    //             'color'       => $validated['color'] ?? $tag->color,
    //             'description' => $validated['description'],
    //         ]);

    //         return redirect()->route('tags.index')
    //             ->with('success', __('Tag updated successfully.'));

    //     } catch (\Exception $e) {
    //         return redirect()->back()
    //             ->withInput()
    //             ->with('error', __('Failed to update tag: :error', ['error' => $e->getMessage()]));
    //     }
    // }

    /**
     * حذف تاج
     */
    // public function destroy($id)
    // {
    //     $tag = Tag::visibleTo(createdBy())->findOrFail($id);

    //     if (!$tag->canEdit()) {
    //         return redirect()->back()
    //             ->with('error', __('You cannot delete this tag. Only the owner can delete.'));
    //     }

    //     try {
    //         // حذف الـ pivot records أولاً
    //         \DB::table('product_tags')->where('tag_id', $tag->id)->delete();

    //         $tag->delete();

    //         return redirect()->route('tags.index')
    //             ->with('success', __('Tag deleted successfully.'));

    //     } catch (\Exception $e) {
    //         return redirect()->back()
    //             ->with('error', __('Failed to delete tag: :error', ['error' => $e->getMessage()]));
    //     }
    // }

    /**
     * ربط تاج بمنتج (AJAX)
     * POST /tags/{tag}/attach-product
     */
    public function attachProduct(Request $request, $tagId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $tag = Tag::visibleTo(createdBy())->findOrFail($tagId);

        if (!$tag->canEdit()) {
            return response()->json(['error' => __('You cannot modify this tag.')], 403);
        }

        $productId = $request->product_id;

        // تحقق إن المنتج مرئي للمستخدم
        $product = Product::whereIn('created_by', getVisibleCompanyIds())
            ->find($productId);

        if (!$product) {
            return response()->json(['error' => __('Product not found.')], 404);
        }

        $tag->attachToProduct($productId);

        return response()->json([
            'success' => true,
            'message' => __('Tag attached to product successfully.'),
        ]);
    }

    /**
     * فصل تاج عن منتج (AJAX)
     * POST /tags/{tag}/detach-product
     */
    public function detachProduct(Request $request, $tagId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $tag = Tag::visibleTo(createdBy())->findOrFail($tagId);

        if (!$tag->canEdit()) {
            return response()->json(['error' => __('You cannot modify this tag.')], 403);
        }

        $tag->detachFromProduct($request->product_id);

        return response()->json([
            'success' => true,
            'message' => __('Tag detached from product successfully.'),
        ]);
    }

    /**
     * مزامنة تاجات منتج (AJAX)
     * POST /tags/sync-product-tags
     *
     * يُستخدم من صفحة تعديل المنتج لربط/فصل عدة تاجات دفعة واحدة
     */
    public function syncProductTags(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'tag_ids'    => 'nullable|array',
            'tag_ids.*'  => 'exists:tags,id',
        ]);

        // تحقق إن المنتج مرئي للمستخدم
        $product = Product::whereIn('created_by', getVisibleCompanyIds())
            ->find($validated['product_id']);

        if (!$product) {
            return response()->json(['error' => __('Product not found.')], 404);
        }

        // تحقق إن كل التاجات مرئية للمستخدم
        if (!empty($validated['tag_ids'])) {
            $visibleCount = Tag::whereIn('id', $validated['tag_ids'])
                ->visibleTo(createdBy())
                ->count();

            if ($visibleCount !== count($validated['tag_ids'])) {
                return response()->json(['error' => __('One or more tags are not accessible.')], 403);
            }
        }

        Tag::syncProductTags(
            $validated['product_id'],
            $validated['tag_ids'] ?? [],
            createdBy()
        );

        return response()->json([
            'success' => true,
            'message' => __('Product tags synced successfully.'),
        ]);
    }

    /**
     * جلب التاجات كـ JSON (لـ select dropdown في صفحة المنتج)
     * GET /tags/list
     */
    public function list(Request $request)
    {
        $query = Tag::visibleTo(createdBy());

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $tags = $query->orderBy('name', 'asc')
            ->get(['id', 'name', 'color', 'slug']);

        return response()->json($tags);
    }

    /**
     * جلب تاجات منتج معين (لـ edit page)
     * GET /tags/product/{productId}
     */
    public function productTags($productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())
            ->find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $companyId = createdBy();

        // تاجات الشركة على هذا المنتج
        $companyTagIds = \DB::table('product_tags')
            ->where('product_id', $productId)
            ->where('created_by', $companyId)
            ->pluck('tag_id')
            ->toArray();

        // إذا ما عندش تاجات، رجع تاجات السوبر أدمن
        if (empty($companyTagIds)) {
            $superAdminId = getSuperAdminCompanyId();
            $companyTagIds = \DB::table('product_tags')
                ->where('product_id', $productId)
                ->where('created_by', $superAdminId)
                ->pluck('tag_id')
                ->toArray();
        }

        $selectedTags = Tag::whereIn('id', $companyTagIds)
            ->get(['id', 'name', 'color']);

        return response()->json([
            'selected_tags' => $selectedTags,
        ]);
    }


        /**
     * عرض قائمة الـ Tags مع pagination + filters.
     *
     * قواعد الـ visibility:
     *  - السوبر ادمن: يرى كل الـ Tags (خاصة به + كل شركات النظام).
     *  - الشركة: ترى الـ Tags الخاصة بها + الـ Tags الخاصة بالسوبر ادمن فقط (لا ترى tags الشركات الأخرى).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        $query = Tag::query()
            ->with(['company:id,name,email', 'creator:id,name'])
            ->withCount('products');

        // تصفية حسب الصلاحية
        if (!$isSuperAdmin) {
            $superAdminId = getSuperAdminCompanyId();
            $query->where(function ($q) use ($user, $superAdminId) {
                $q->where('company_id', $user->id)
                  ->orWhere('company_id', $superAdminId)
                  ->orWhereNull('company_id'); // سجلات قديمة
            });
        }

        // فلتر الحالة (active/inactive)
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فلتر الملكية (own / superadmin / all)
        if ($request->has('ownership') && $request->ownership !== 'all') {
            if ($request->ownership === 'own') {
                $query->where('company_id', $user->id);
            } elseif ($request->ownership === 'superadmin' && !$isSuperAdmin) {
                $superAdminId = getSuperAdminCompanyId();
                $query->where('company_id', $superAdminId);
            }
        }

        // البحث
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // الترتيب
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['id', 'name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = (int) $request->get('per_page', 10);
        $tags = $query->paginate($perPage);

        // إضافة can_delete لكل tag (لا يمكن حذف tag مرتبط بمنتجات)
        $tags->getCollection()->transform(function ($tag) use ($user, $isSuperAdmin) {
            $tag->can_delete = ($tag->products_count == 0)
                && ($isSuperAdmin || $tag->company_id == $user->id);
            $tag->can_edit = $isSuperAdmin || $tag->company_id == $user->id;
            return $tag;
        });

        return Inertia::render('tags/index', [
            'tags'    => $tags,
            'filters' => $request->only(['search', 'status', 'ownership', 'per_page', 'sort_field', 'sort_direction']),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * إنشاء tag جديد.
     * - السوبر ادمن: يمكنه إنشاء tags لنفسه أو لأي شركة (company_id في الـ request).
     * - الشركة: tag ينتمي لها (company_id = $user->id).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        $rules = [
            'name'  => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'status'=> 'nullable|in:active,inactive',
        ];

        // السوبر ادمن يمكنه تحديد company_id
        if ($isSuperAdmin) {
            $rules['company_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        // تحديد company_id
        if ($isSuperAdmin && isset($validated['company_id'])) {
            $companyId = $validated['company_id'];
        } elseif ($isSuperAdmin) {
            // سوبر ادمن بدون تحديد → ينسب له نفسه
            $companyId = $user->id;
        } else {
            // شركة عادية → ينسب لها
            $companyId = $user->id;
        }

        // منع الشركات من إنشاء tags للسوبر ادمن أو لشركات أخرى
        if (!$isSuperAdmin && $companyId != $user->id) {
            return redirect()->back()
                ->with('error', __('You are not allowed to create tags for other companies.'));
        }

        // توليد slug فريد
        $slug = Str::slug($validated['name']);
        $original = $slug;
        $counter = 1;
        while (Tag::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        $tag = Tag::create([
            'name'       => $validated['name'],
            'slug'       => $slug,
            'color'      => $validated['color'] ?? '#6B7280',
            'status'     => $validated['status'] ?? 'active',
            'company_id' => $companyId,
            'created_by' => $user->id,
        ]);

        // Log::info('Tag created', [
        //     'tag_id'      => $tag->id,
        //     'name'        => $tag->name,
        //     'company_id'  => $tag->company_id,
        //     'created_by'  => $user->id,
        // ]);

        return redirect()->route('tags.index')->with('success', __('Tag created successfully!'));
    }

    /**
     * تعديل tag موجود.
     * - يمكن للسوبر ادمن تعديل أي tag.
     * - يمكن للشركة تعديل tagsها فقط (وليس tags السوبر ادمن).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        $tag = Tag::findOrFail($id);

        // التحقق من الملكية
        if (!$isSuperAdmin && $tag->company_id != $user->id) {
            return redirect()->back()
                ->with('error', __('You are not allowed to edit this tag.'));
        }

        $rules = [
            'name'  => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'status'=> 'nullable|in:active,inactive',
        ];

        if ($isSuperAdmin) {
            $rules['company_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        // تحديث slug فقط إذا تغيّر الاسم
        if ($tag->name !== $validated['name']) {
            $slug = Str::slug($validated['name']);
            $original = $slug;
            $counter = 1;
            while (Tag::where('slug', $slug)->where('id', '!=', $tag->id)->exists()) {
                $slug = $original . '-' . $counter++;
            }
            $tag->slug = $slug;
        }

        $tag->name   = $validated['name'];
        $tag->color  = $validated['color'] ?? $tag->color ?? '#6B7280';
        $tag->status = $validated['status'] ?? $tag->status;

        if ($isSuperAdmin && isset($validated['company_id'])) {
            // لا نسمح بتغيير الملكية إذا كان tag مرتبطاً بمنتجات
            if ($tag->products()->count() > 0 && $validated['company_id'] != $tag->company_id) {
                return redirect()->back()
                    ->with('error', __('Cannot change ownership of a tag linked to products.'));
            }
            $tag->company_id = $validated['company_id'];
        }

        $tag->save();

        // Log::info('Tag updated', [
        //     'tag_id'      => $tag->id,
        //     'updated_by'  => $user->id,
        // ]);

        return redirect()->route('tags.index')->with('success', __('Tag updated successfully!'));
    }

    /**
     * حذف tag.
     * - يُمنع الحذف إذا كان tag مرتبطاً بمنتجات.
     * - يمكن للسوبر ادمن حذف أي tag.
     * - يمكن للشركة حذف tagsها فقط.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdmin($user);

        $tag = Tag::findOrFail($id);

        // التحقق من الملكية
        if (!$isSuperAdmin && $tag->company_id != $user->id) {
            return redirect()->back()
                ->with('error', __('You are not allowed to delete this tag.'));
        }

        // منع الحذف إذا كان مرتبطاً بمنتجات
        $productsCount = $tag->products()->count();
        if ($productsCount > 0) {
            return redirect()->back()
                ->with('error', __('Cannot delete tag :name because it is linked to :count product(s).', [
                    'name'  => $tag->name,
                    'count' => $productsCount,
                ]));
        }

        $tagName = $tag->name;
        $tag->delete();

        // Log::info('Tag deleted', [
        //     'tag_id'      => $tag->id,
        //     'name'        => $tagName,
        //     'deleted_by'  => $user->id,
        // ]);

        return redirect()->route('tags.index')->with('success', __('Tag deleted successfully!'));
    }

    /**
     * Helper: تحديد ما إذا كان المستخدم سوبر ادمن.
     * يفضّل أن تستخدم نفس منطق التطبيق الموجود (مثلاً $user->hasRole('superadmin'))
     */
    private function isSuperAdmin($user): bool
    {
        if (!$user) return false;

        // أسلوب 1: عبر type
        if (isset($user->type) && in_array($user->type, ['superadmin', 'super-admin', 'super_admin'], true)) {
            return true;
        }

        // أسلوب 2: عبر hasRole إن وُجد (spatie/laravel-permission)
        if (method_exists($user, 'hasRole')) {
            try {
                return $user->hasRole('superadmin')
                    || $user->hasRole('super-admin')
                    || $user->hasRole('super_admin');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return false;
    }
}
