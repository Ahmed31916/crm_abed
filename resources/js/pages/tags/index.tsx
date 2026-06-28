// pages/tags/index.tsx
// v6.9 — صفحة إدارة الـ Tags
//
// الميزات:
//  - جدول يعرض: name, slug, color, status, company (مالك), products_count, created_at
//  - زر "Add Tag" يفتح مودال إضافة
//  - زر تعديل لكل tag (يفتح نفس المودال معبأأ)
//  - زر حذف لكل tag (يُعطَّل إذا كان مرتبطاً بمنتجات)
//  - فلترة حسب الملكية (own / superadmin / all) — للشركات العادية
//  - فلترة حسب الحالة (active / inactive / all)
//  - بحث + per page + pagination + sort
import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { CrudTable } from '@/components/CrudTable';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, AlertTriangle } from 'lucide-react';

// ألوان افتراضية مقترحة لـ Tags
const PRESET_COLORS = [
    '#6B7280', // gray
    '#3B82F6', // blue
    '#10B981', // green
    '#F59E0B', // amber
    '#EF4444', // red
    '#8B5CF6', // purple
    '#EC4899', // pink
    '#14B8A6', // teal
    '#F97316', // orange
    '#0EA5E9', // sky
];

interface TagRow {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    status: string;
    company_id: number | null;
    company?: { id: number; name: string; email: string } | null;
    creator?: { id: number; name: string } | null;
    products_count: number;
    can_delete: boolean;
    can_edit: boolean;
    created_at: string;
}

export default function TagsPage() {
    const { t } = useTranslation();
    const { tags, filters: pageFilters = {}, auth, isSuperAdmin, globalSettings } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const userIsSuperAdmin = isSuperAdmin === true;

    // ============================================================
    // State — فلاتر
    // ============================================================
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedOwnership, setSelectedOwnership] = useState(pageFilters.ownership || 'all');
    const [showFilters, setShowFilters] = useState(false);

    // ============================================================
    // State — مودال الإضافة/التعديل
    // ============================================================
    const [formOpen, setFormOpen] = useState(false);
    const [editingTag, setEditingTag] = useState<TagRow | null>(null);
    const [formSubmitting, setFormSubmitting] = useState(false);
    const [form, setForm] = useState({
        name: '',
        color: PRESET_COLORS[0],
        status: 'active',
        company_id: '',  // للسوبر ادمن فقط
    });

    // ============================================================
    // State — مودال التأكيد على الحذف
    // ============================================================
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingTag, setDeletingTag] = useState<TagRow | null>(null);
    const [deleteSubmitting, setDeleteSubmitting] = useState(false);

    // مزامنة state محلية عند تغيّر pageFilters
    useEffect(() => {
        setSearchTerm(pageFilters.search || '');
        setSelectedStatus(pageFilters.status || 'all');
        setSelectedOwnership(pageFilters.ownership || 'all');
    }, [pageFilters.search, pageFilters.status, pageFilters.ownership]);

    const currentPerPage = pageFilters.per_page
        ? parseInt(String(pageFilters.per_page), 10) || 10
        : 10;

    const hasActiveFilters = () => {
        return selectedStatus !== 'all' || searchTerm !== '' ||
            (!userIsSuperAdmin && selectedOwnership !== 'all');
    };

    const activeFilterCount = () => {
        let n = 0;
        if (selectedStatus !== 'all') n++;
        if (searchTerm !== '') n++;
        if (!userIsSuperAdmin && selectedOwnership !== 'all') n++;
        return n;
    };

    // ============================================================
    // Helpers للتنقّل مع الحفاظ على الفلاتر
    // ============================================================
    const navigateWithFilters = (overrides: Record<string, any> = {}) => {
        const params: Record<string, any> = {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            ownership: (!userIsSuperAdmin && selectedOwnership !== 'all') ? selectedOwnership : undefined,
            per_page: currentPerPage,
            sort_field: pageFilters.sort_field,
            sort_direction: pageFilters.sort_direction,
            ...overrides,
        };
        Object.keys(params).forEach(k => {
            if (params[k] === undefined || params[k] === null) delete params[k];
        });
        router.get(route('tags.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        navigateWithFilters({ page: 1 });
    };

    const applyFilters = () => navigateWithFilters({ page: 1 });

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
        navigateWithFilters({ sort_field: field, sort_direction: direction, page: 1 });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedOwnership('all');
        setShowFilters(false);
        router.get(route('tags.index'), { page: 1, per_page: currentPerPage }, {
            preserveState: true, preserveScroll: true,
        });
    };

    const handlePageChange = (url: string | null) => {
        if (!url) return;
        try {
            const urlObj = new URL(url, window.location.origin);
            const page = urlObj.searchParams.get('page') || 1;
            navigateWithFilters({ page });
        } catch {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    // ============================================================
    // فتح مودال الإضافة
    // ============================================================
    const openCreateModal = () => {
        setEditingTag(null);
        setForm({
            name: '',
            color: PRESET_COLORS[0],
            status: 'active',
            company_id: '',
        });
        setFormOpen(true);
    };

    // ============================================================
    // فتح مودال التعديل
    // ============================================================
    const openEditModal = (tag: TagRow) => {
        setEditingTag(tag);
        setForm({
            name: tag.name || '',
            color: tag.color || PRESET_COLORS[0],
            status: tag.status || 'active',
            company_id: tag.company_id ? String(tag.company_id) : '',
        });
        setFormOpen(true);
    };

    const closeFormModal = () => {
        setFormOpen(false);
        setEditingTag(null);
        setForm({ name: '', color: PRESET_COLORS[0], status: 'active', company_id: '' });
    };

    // ============================================================
    // إرسال النموذج (إنشاء أو تعديل)
    // ============================================================
    const submitForm = () => {
        if (!form.name.trim()) {
            toast.error(t('Tag name is required'));
            return;
        }

        setFormSubmitting(true);
        if (!globalSettings?.is_demo) toast.loading(editingTag ? t('Updating tag...') : t('Creating tag...'));

        const payload: any = {
            name: form.name.trim(),
            color: form.color,
            status: form.status,
        };
        if (userIsSuperAdmin && form.company_id) {
            payload.company_id = form.company_id;
        }

        const onComplete = () => {
            setFormSubmitting(false);
            if (!globalSettings?.is_demo) toast.dismiss();
        };

        if (editingTag) {
            router.put(route('tags.update', editingTag.id), payload, {
                onSuccess: (page) => {
                    onComplete();
                    if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                    else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                    closeFormModal();
                },
                onError: (errors) => {
                    onComplete();
                    if (typeof errors === 'string') toast.error(t(errors));
                    else toast.error(t('Failed to save tag: {{errors}}', { errors: Object.values(errors).join(', ') }));
                },
            });
        } else {
            router.post(route('tags.store'), payload, {
                onSuccess: (page) => {
                    onComplete();
                    if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                    else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                    closeFormModal();
                },
                onError: (errors) => {
                    onComplete();
                    if (typeof errors === 'string') toast.error(t(errors));
                    else toast.error(t('Failed to create tag: {{errors}}', { errors: Object.values(errors).join(', ') }));
                },
            });
        }
    };

    // ============================================================
    // حذف tag
    // ============================================================
    const openDeleteModal = (tag: TagRow) => {
        setDeletingTag(tag);
        setDeleteOpen(true);
    };

    const closeDeleteModal = () => {
        setDeleteOpen(false);
        setDeletingTag(null);
    };

    const confirmDelete = () => {
        if (!deletingTag) return;
        setDeleteSubmitting(true);
        if (!globalSettings?.is_demo) toast.loading(t('Deleting tag...'));

        router.delete(route('tags.destroy', deletingTag.id), {
            onSuccess: (page) => {
                setDeleteSubmitting(false);
                if (!globalSettings?.is_demo) toast.dismiss();
                if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                closeDeleteModal();
            },
            onError: (errors) => {
                setDeleteSubmitting(false);
                if (!globalSettings?.is_demo) toast.dismiss();
                if (typeof errors === 'string') toast.error(t(errors));
                else toast.error(t('Failed to delete tag: {{errors}}', { errors: Object.values(errors).join(', ') }));
            },
        });
    };

    // ============================================================
    // Actions handler — يُستدعى من CrudTable
    // ============================================================
    const handleAction = (action: string, item: any) => {
        if (action === 'edit') {
            openEditModal(item as TagRow);
        } else if (action === 'delete') {
            openDeleteModal(item as TagRow);
        }
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Tags') },
    ];

    // ============================================================
    // Table columns
    // ============================================================
    const columns = [
        {
            key: 'name',
            label: t('Tag Name'),
            sortable: true,
            render: (value: string, row: TagRow) => (
                <div className="flex items-center gap-3">
                    <span
                        className="inline-block h-4 w-4 rounded-full ring-1 ring-gray-300 dark:ring-gray-700"
                        style={{ backgroundColor: row.color || '#6B7280' }}
                        title={row.color || ''}
                    />
                    <div>
                        <div className="font-medium text-gray-900 dark:text-gray-100">{value || '-'}</div>
                        <div className="text-xs text-gray-500 font-mono">{row.slug}</div>
                    </div>
                </div>
            ),
        },
        {
            key: 'color',
            label: t('Color'),
            render: (value: string | null, row: TagRow) => (
                <div className="flex items-center gap-2">
                    <span
                        className="inline-block h-5 w-5 rounded ring-1 ring-gray-300 dark:ring-gray-700"
                        style={{ backgroundColor: value || '#6B7280' }}
                    />
                    <code className="text-xs text-gray-600 dark:text-gray-400">{value || '-'}</code>
                </div>
            ),
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const isActive = value === 'active';
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                        isActive
                            ? 'bg-green-50 text-green-700 ring-green-600/20'
                            : 'bg-gray-50 text-gray-700 ring-gray-600/20'
                    }`}>
                        {t(value || 'inactive')}
                    </span>
                );
            },
        },
        {
            key: 'company.name',
            label: t('Owner'),
            render: (_: any, row: TagRow) => {
                // إن لم يوجد للسوبر ادمن ولم يُمرَّر company، نظهر "System"
                const ownerName = row.company?.name
                    || (row.company_id === null ? t('System (legacy)') : '-');

                // تمييز الـ tags الخاصة بالسوبر ادمن إن كان المستخدم شركة
                const isSuperAdminTag = !userIsSuperAdmin &&
                    row.company_id !== null &&
                    auth?.user?.id !== row.company_id;

                return (
                    <div className="flex flex-col">
                        <span className="text-sm font-medium text-gray-900 dark:text-gray-100">{ownerName}</span>
                        {isSuperAdminTag && (
                            <span className="text-xs text-blue-600 dark:text-blue-400">
                                {t('System Tag')}
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'products_count',
            label: t('Linked Products'),
            render: (value: number) => (
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                    value > 0
                        ? 'bg-blue-50 text-blue-700 ring-blue-600/20'
                        : 'bg-gray-50 text-gray-700 ring-gray-600/20'
                }`}>
                    {value}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: t('Created At'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-',
        },
    ];

    // ============================================================
    // Actions — Edit + Delete (مع condition لتفعيل/تعطيل Delete)
    // ============================================================
    const actions = [
        {
            label: t('Edit'),
            icon: 'Pencil',
            action: 'edit',
            className: 'text-blue-500',
            requiredPermission: null,  // الـ controller يتحقق من الملكية
            // condition يستخدم can_edit المُمرَّرة من الـ controller
            condition: (row: TagRow) => row.can_edit !== false,
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: null,
            // لا يظهر إن كان مرتبطاً بمنتجات أو لا يملك المستخدم صلاحية الحذف
            condition: (row: TagRow) => row.can_delete === true,
        },
    ];

    // ============================================================
    // Status options for filter
    // ============================================================
    const statusOptions = [
        { value: 'all',      label: t('All Status') },
        { value: 'active',   label: t('Active') },
        { value: 'inactive', label: t('Inactive') },
    ];

    const ownershipOptions = [
        { value: 'all',        label: t('All Tags') },
        { value: 'own',        label: t('My Tags') },
        { value: 'superadmin', label: t('System Tags') },
    ];

    const filtersConfig: any[] = [
        {
            name: 'status',
            label: t('Status'),
            type: 'select',
            value: selectedStatus,
            onChange: setSelectedStatus,
            options: statusOptions,
        },
    ];

    // إضافة فلتر الملكية للشركات العادية فقط (للسوبر ادمن لا معنى له)
    if (!userIsSuperAdmin) {
        filtersConfig.push({
            name: 'ownership',
            label: t('Ownership'),
            type: 'select',
            value: selectedOwnership,
            onChange: setSelectedOwnership,
            options: ownershipOptions,
        });
    }

    return (
        <PageTemplate
            title={t('Tags')}
            url="/tags"
            breadcrumbs={breadcrumbs}
            noPadding
        >
            {/* Search + filters + Add button */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {t('Tags Management')}
                    </h2>
                    <Button onClick={openCreateModal} className="gap-2">
                        <Plus className="h-4 w-4" />
                        {t('Add Tag')}
                    </Button>
                </div>

                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={filtersConfig}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={String(currentPerPage)}
                    onPerPageChange={(value) => {
                        const newPerPage = parseInt(value, 10) || 10;
                        router.get(route('tags.index'), {
                            page: 1,
                            per_page: newPerPage,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            ownership: (!userIsSuperAdmin && selectedOwnership !== 'all') ? selectedOwnership : undefined,
                            sort_field: pageFilters.sort_field,
                            sort_direction: pageFilters.sort_direction,
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Table */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={tags?.data || []}
                    from={tags?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                />

                <Pagination
                    from={tags?.from || 0}
                    to={tags?.to || 0}
                    total={tags?.total || 0}
                    links={tags?.links}
                    entityName={t('tags')}
                    onPageChange={handlePageChange}
                />
            </div>

            {/* ============================================================ */}
            {/* مودال الإضافة/التعديل                                       */}
            {/* ============================================================ */}
            <Dialog open={formOpen} onOpenChange={(o) => o ? setFormOpen(true) : closeFormModal()}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>
                            {editingTag ? t('Edit Tag') : t('Add New Tag')}
                        </DialogTitle>
                        <DialogDescription>
                            {editingTag
                                ? t('Update the tag details below.')
                                : t('Create a new tag for your company.')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* الاسم */}
                        <div className="space-y-2">
                            <Label htmlFor="tag-name">{t('Tag Name')} <span className="text-red-500">*</span></Label>
                            <Input
                                id="tag-name"
                                value={form.name}
                                onChange={(e) => setForm({ ...form, name: e.target.value })}
                                placeholder={t('Enter tag name')}
                                autoFocus
                            />
                            <p className="text-xs text-gray-500">
                                {t('Slug will be auto-generated from name.')}
                            </p>
                        </div>

                        {/* اللون */}
                        <div className="space-y-2">
                            <Label>{t('Color')}</Label>
                            <div className="flex flex-wrap gap-2">
                                {PRESET_COLORS.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        onClick={() => setForm({ ...form, color })}
                                        className={`h-8 w-8 rounded-full ring-2 transition ${
                                            form.color === color
                                                ? 'ring-gray-900 dark:ring-white scale-110'
                                                : 'ring-transparent hover:ring-gray-300'
                                        }`}
                                        style={{ backgroundColor: color }}
                                        aria-label={color}
                                    />
                                ))}
                            </div>
                            <div className="flex items-center gap-2 mt-2">
                                <Input
                                    type="color"
                                    value={form.color}
                                    onChange={(e) => setForm({ ...form, color: e.target.value })}
                                    className="h-9 w-16 p-1 cursor-pointer"
                                />
                                <Input
                                    type="text"
                                    value={form.color}
                                    onChange={(e) => setForm({ ...form, color: e.target.value })}
                                    className="font-mono text-sm"
                                    placeholder="#6B7280"
                                />
                            </div>
                        </div>

                        {/* الحالة */}
                        <div className="space-y-2">
                            <Label htmlFor="tag-status">{t('Status')}</Label>
                            <Select
                                value={form.status}
                                onValueChange={(v) => setForm({ ...form, status: v })}
                            >
                                <SelectTrigger id="tag-status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">{t('Active')}</SelectItem>
                                    <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {/* الملكية — للسوبر ادمن فقط */}
                        {userIsSuperAdmin && (
                            <div className="space-y-2">
                                <Label htmlFor="tag-company">
                                    {t('Assign to Company')}
                                </Label>
                                <Select
                                    value={form.company_id}
                                    onValueChange={(v) => setForm({ ...form, company_id: v })}
                                >
                                    <SelectTrigger id="tag-company">
                                        <SelectValue placeholder={t('Leave empty for self (super admin)')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">
                                            {t('Myself (Super Admin)')}
                                        </SelectItem>
                                        {/* قائمة الشركات تُمرَّر من الـ controller إن لزم —
                                            حالياً يمكن للمستخدم كتابة ID يدوياً،
                                            أو يمكنك جلب القائمة عبر AJAX */}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-gray-500">
                                    {t('Leave empty to assign to yourself as super admin. Otherwise, enter a company ID to create this tag on behalf of that company.')}
                                </p>
                                {/* حقل إدخال يدوي للـ company_id — بديل أبسط */}
                                <Input
                                    type="number"
                                    value={form.company_id}
                                    onChange={(e) => setForm({ ...form, company_id: e.target.value })}
                                    placeholder={t('Company ID (optional)')}
                                    className="text-sm"
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={closeFormModal}
                            disabled={formSubmitting}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button
                            onClick={submitForm}
                            disabled={formSubmitting || !form.name.trim()}
                        >
                            {formSubmitting
                                ? t('Saving...')
                                : editingTag ? t('Save Changes') : t('Create Tag')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ============================================================ */}
            {/* مودال تأكيد الحذف                                          */}
            {/* ============================================================ */}
            <Dialog open={deleteOpen} onOpenChange={(o) => o ? setDeleteOpen(true) : closeDeleteModal()}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-red-500" />
                            {t('Delete Tag')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('Are you sure you want to delete this tag? This action cannot be undone.')}
                        </DialogDescription>
                    </DialogHeader>

                    {deletingTag && (
                        <div className="py-3 px-4 rounded-md bg-gray-50 dark:bg-gray-800 space-y-2">
                            <div className="flex items-center gap-3">
                                <span
                                    className="inline-block h-4 w-4 rounded-full"
                                    style={{ backgroundColor: deletingTag.color || '#6B7280' }}
                                />
                                <span className="font-medium text-gray-900 dark:text-gray-100">
                                    {deletingTag.name}
                                </span>
                            </div>
                            <div className="text-xs text-gray-500">
                                {t('Slug')}: <code className="font-mono">{deletingTag.slug}</code>
                            </div>
                            <div className="text-xs text-gray-500">
                                {t('Linked Products')}: <strong>{deletingTag.products_count}</strong>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={closeDeleteModal}
                            disabled={deleteSubmitting}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDelete}
                            disabled={deleteSubmitting}
                        >
                            {deleteSubmitting ? t('Deleting...') : t('Delete Tag')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
