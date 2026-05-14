<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
    public function index(Request $request)
    {
        $query = Tag::with('creator')
            ->visibleTo(createdBy());

        // البحث
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // فلتر حسب المالك
        if ($request->filled('owner') && $request->owner !== 'all') {
            if ($request->owner === 'mine') {
                $query->where('created_by', createdBy());
            } elseif ($request->owner === 'superadmin') {
                $query->where('created_by', getSuperAdminCompanyId());
            }
        }

        // الترتيب
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $allowedSorts = ['id', 'name', 'created_at'];
        $allowedDirections = ['asc', 'desc'];

        if (!in_array($sortField, $allowedSorts)) $sortField = 'name';
        if (!in_array($sortDirection, $allowedDirections)) $sortDirection = 'asc';

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->input('per_page', 25);
        $tags = $query->paginate($perPage);

        // إضافة عدد المنتجات لكل تاج
        $tags->each(function ($tag) {
            $tag->product_count = $tag->products()->count();
        });

        return Inertia::render('tags/index', [
            'tags' => $tags,
            'filters' => $request->all(['search', 'owner', 'sort_field', 'sort_direction', 'per_page']),
            'superAdminId' => getSuperAdminCompanyId(),
            'currentCompanyId' => createdBy(),
        ]);
    }

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'color'       => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            // تحقق إن الاسم مش موجود مسبقاً لنفس المستخدم
            $exists = Tag::where('name', $validated['name'])
                ->where('created_by', createdBy())
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['name' => __('You already have a tag with this name.')]);
            }

            Tag::create([
                'name'        => $validated['name'],
                'color'       => $validated['color'] ?? '#6B7280',
                'description' => $validated['description'] ?? null,
                'created_by'  => createdBy(),
            ]);

            return redirect()->route('tags.index')
                ->with('success', __('Tag created successfully.'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', __('Failed to create tag: :error', ['error' => $e->getMessage()]));
        }
    }

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
    public function update(Request $request, $id)
    {
        $tag = Tag::visibleTo(createdBy())->findOrFail($id);

        if (!$tag->canEdit()) {
            return redirect()->back()
                ->with('error', __('You cannot edit this tag.'));
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'color'       => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            // تحقق إن الاسم مش موجود عند نفس المستخدم (باستثناء التاج الحالي)
            $exists = Tag::where('name', $validated['name'])
                ->where('created_by', $tag->created_by)
                ->where('id', '!=', $tag->id)
                ->exists();

            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['name' => __('A tag with this name already exists.')]);
            }

            $tag->update([
                'name'        => $validated['name'],
                'color'       => $validated['color'] ?? $tag->color,
                'description' => $validated['description'],
            ]);

            return redirect()->route('tags.index')
                ->with('success', __('Tag updated successfully.'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', __('Failed to update tag: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * حذف تاج
     */
    public function destroy($id)
    {
        $tag = Tag::visibleTo(createdBy())->findOrFail($id);

        if (!$tag->canEdit()) {
            return redirect()->back()
                ->with('error', __('You cannot delete this tag. Only the owner can delete.'));
        }

        try {
            // حذف الـ pivot records أولاً
            \DB::table('product_tags')->where('tag_id', $tag->id)->delete();

            $tag->delete();

            return redirect()->route('tags.index')
                ->with('success', __('Tag deleted successfully.'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to delete tag: :error', ['error' => $e->getMessage()]));
        }
    }

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
}
