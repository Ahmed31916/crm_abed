import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Package, Download, Upload, FileDown, FileUp, Lock, ArrowRightLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import ImportExcelModal from '@/components/ImportExcelModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

// import { ProductBarcode } from '@/components/Barcode';

export default function Products() {
    const { t } = useTranslation();
    const { auth, products, categories, brands, taxes, tags, users, companies, superAdminCompanyId, samplePath, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';
    const isSuperAdmin = auth?.user?.type === 'superadmin';

    // ⚡ Helper: هل المنتج من إنشاء السوبر ادمن؟ (يُستخدم لإخفاء زر Toggle Status للشركات)
    const isProductSuperAdmin = (product: any) =>
        Number(product?.created_by) === Number(superAdminCompanyId);

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(pageFilters.category || 'all');
    const [selectedBrand, setSelectedBrand] = useState(pageFilters.brand || 'all');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    // ⚡ NEW: Tags filter — يظهر للسوبر ادمن وللشركة
    const [selectedTag, setSelectedTag] = useState(pageFilters.tag || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isImportExcelModalOpen, setIsImportExcelModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

    // ═══════════════════════════════════════════════════════════════
    // NEW: Ownership & Company filters (Super Admin only)
    // ═══════════════════════════════════════════════════════════════
    // ownership: 'all' | 'super_admin' | 'company'
    //   - 'super_admin': products created by super admin only
    //   - 'company': products created by any company (not super admin)
    //   - 'all': no filter
    //
    // companyFilter: 'all' | <company_id>
    //   - Only shown when ownership !== 'super_admin'
    //   - Further narrows down to a specific company's products
    // ═══════════════════════════════════════════════════════════════
    const [selectedOwnership, setSelectedOwnership] = useState(pageFilters.ownership || 'all');
    const [selectedCompanyFilter, setSelectedCompanyFilter] = useState(pageFilters.company_id || 'all');

    const [activeView, setActiveView] = useState<'list' | 'grid'>(pageFilters.view || 'list');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== ''
            || selectedCategory !== 'all'
            || selectedBrand !== 'all'
            || selectedStatus !== 'all'
            || selectedAssignee !== 'all'
            || selectedTag !== 'all'
            || (isSuperAdmin && selectedOwnership !== 'all')
            || (isSuperAdmin && selectedCompanyFilter !== 'all');
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedCategory !== 'all' ? 1 : 0)
            + (selectedBrand !== 'all' ? 1 : 0)
            + (selectedStatus !== 'all' ? 1 : 0)
            + (selectedAssignee !== 'all' ? 1 : 0)
            + (selectedTag !== 'all' ? 1 : 0)
            + (isSuperAdmin && selectedOwnership !== 'all' ? 1 : 0)
            + (isSuperAdmin && selectedOwnership !== 'super_admin' && selectedCompanyFilter !== 'all' ? 1 : 0);
    };

    // ═══════════════════════════════════════════════════════════════
    // Helper: بناء object الفلاتر المشتركة لكل طلبات router.get
    // ═══════════════════════════════════════════════════════════════
    const buildFilterParams = (extra: Record<string, any> = {}) => ({
        search: searchTerm || undefined,
        category: selectedCategory !== 'all' ? selectedCategory : undefined,
        brand: selectedBrand !== 'all' ? selectedBrand : undefined,
        status: selectedStatus !== 'all' ? selectedStatus : undefined,
        assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
        // ⚡ NEW: tag filter
        tag: selectedTag !== 'all' ? selectedTag : undefined,
        // فلتر الملكية (فقط للسوبر ادمن)
        ownership: isSuperAdmin && selectedOwnership !== 'all' ? selectedOwnership : undefined,
        // فلتر الشركة (فقط للسوبر ادمن + لما لا يكون ownership = super_admin)
        company_id: isSuperAdmin && selectedOwnership !== 'super_admin' && selectedCompanyFilter !== 'all'
            ? selectedCompanyFilter
            : undefined,
        ...extra,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('products.index'), {
            view: activeView,
            page: 1,
            ...buildFilterParams(),
            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';
        router.get(route('products.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: 1,
            ...buildFilterParams(),
            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.visit(route('products.show', item.id));
                break;
            case 'edit':
                router.visit(route('products.edit', item.id));
                break;
            case 'delete':
                setIsDeleteModalOpen(true);
                break;
            case 'toggle-status':
                handleToggleStatus(item);
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        if (formMode === 'create') {
            toast.loading(t('Creating product...'));

            router.post(route('products.store'), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(t('Failed to create: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating product...'));

            router.put(route('products.update', currentItem.id), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting product...'));

        router.delete(route('products.destroy', currentItem.id), {
            onSuccess: (page) => {
                setIsDeleteModalOpen(false);
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(t('Failed to delete: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleToggleStatus = (product: any) => {
        const newStatus = product.status === 'active' ? 'inactive' : 'active';
        toast.loading(t('{{action}} product...', { action: newStatus === 'active' ? t('Activating') : t('Deactivating') }));

        router.put(route('products.toggle-status', product.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedCategory('all');
        setSelectedBrand('all');
        setSelectedStatus('all');
        setSelectedAssignee('all');
        setSelectedTag('all');
        setSelectedOwnership('all');
        setSelectedCompanyFilter('all');
        setShowFilters(false);

        router.get(route('products.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    // ═══════════════════════════════════════════════════════════════
    // Handler خاص بفلتر الملكية: عند اختيار "super_admin" نصفر فلتر الشركة
    // ═══════════════════════════════════════════════════════════════
    const handleOwnershipChange = (value: string) => {
        setSelectedOwnership(value);
        // لو اختار منتجات السوبر ادمن، نصفر فلتر الشركة لأنه ما بيفرش
        if (value === 'super_admin') {
            setSelectedCompanyFilter('all');
        }
    };

    // Define page actions
    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-products')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    // Add Import from Excel button - SUPER ADMIN ONLY
    if (isSuperAdmin) {
        pageActions.push({
            label: t('Import from Excel'),
            icon: <FileUp className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => setIsImportExcelModalOpen(true)
        });
    }

    // Add Compare button - COMPANY USERS ONLY
    // يقارن بيانات الشركة مع بيانات السوبر ادمن الأصلية
    if (isCompany) {
        pageActions.push({
            label: t('Compare My Data'),
            icon: <ArrowRightLeft className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => router.visit(route('products.merchant-comparison'))
        });
    }

    // Add the "Add Product" button if user has permission
    if (hasPermission(permissions, 'create-products')) {
        pageActions.push({
            label: t('Add Product'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => router.visit(route('products.create'))
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Products') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true,
            render: (value: any, row: any) => {
                const mainImage = row.media?.find((m: any) => m.collection_name === 'main');
                const imageUrl = mainImage?.original_url || row.display_image_url || row.main_image_url || row.image;

                // تحديد نوع المنتج للسوبر ادمن (سوبر ادمن vs شركة)
                const superAdminId = auth?.user?.id;
                const isSuperAdminProduct = row.created_by === superAdminId;

                return (
                    <div className="flex items-center gap-3">
                        <div className="h-12 w-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden p-1">
                            <img
                                src={imageUrl}
                                alt={row.name}
                                className="max-h-full max-w-full object-contain rounded-lg"
                                onError={(e) => {
                                    const target = e.currentTarget as HTMLImageElement;
                                    if (!target.src.startsWith('data:image/svg+xml')) {
                                        target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMDAgNjBMMTQwIDgwVjE0MEwxMDAgMTYwTDYwIDE0MFY4MEwxMDAgNjBaIiBzdHJva2U9IiM5Q0EzQUYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0iI0U1RTdFQiIvPgo8Y2lyY2xlIGN4PSI4NSIgY3k9Ijk1IiByPSI4IiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik03MCAxMzBMODUgMTE1TDEwMCAxMzBMMTMwIDEwMEwxMzAgMTMwSDcwWiIgZmlsbD0iIzlDQTNBRiIvPgo8L3N2Zz4=';
                                    } else {
                                        target.style.display = 'none';
                                        const icon = target.nextElementSibling as HTMLElement;
                                        if (icon) icon.style.display = 'flex';
                                    }
                                }}
                            />
                            <Package className="h-6 w-6 text-gray-400 hidden" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="font-semibold text-gray-900 dark:text-white">{row.name}</span>
                                {/* ═══════════════════════════════════════════════════════════
                                    Badge يُظهر نوع المنتج للسوبر ادمن فقط
                                    ═══════════════════════════════════════════════════════════ */}
                                {isSuperAdmin && (
                                    isSuperAdminProduct ? (
                                        <span className="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                            {t('Super Admin')}
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium bg-cyan-50 text-cyan-700 ring-1 ring-inset ring-cyan-600/20">
                                            {t('Company')}
                                        </span>
                                    )
                                )}
                            </div>
                            <div className="text-sm text-gray-500 dark:text-gray-400">SKU: {row.sku}</div>
                            {/* ═══════════════════════════════════════════════════════════
                                اسم الشركة المنشئة للمنتج (للسوبر ادمن فقط)
                                ═══════════════════════════════════════════════════════════ */}
                            {isSuperAdmin && row.creator && (
                                <div className="text-xs text-gray-400 dark:text-gray-500">
                                    {t('by')}: {row.creator.name}
                                </div>
                            )}
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'price',
            label: t('Price'),
            sortable: true,
            render: (value: any) => (
                <span className="font-semibold text-green-600">
                    {window.appSettings?.formatCurrency(parseFloat(value || 0)) || `$${parseFloat(value || 0).toFixed(2)}`}
                </span>
            )
        },
        {
            key: 'stock_quantity',
            label: t('Stock'),
            sortable: true,
            render: (value: any) => (
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value > 10 ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
                        value > 0 ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' :
                            'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                    }`}>
                    {value} {value === 1 ? t('unit') : t('units')}
                </span>
            )
        },
        {
            key: 'category',
            label: t('Category'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'brand',
            label: t('Brand'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
                        ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                        }`}>
                        {value === 'active' ? t('Active') : t('Inactive')}
                    </span>
                );
            }
        }
    ];

    // Define table actions
    // ⚡ FIX: Toggle Status مختفي لمنتجات السوبر ادمن عند صاحب الشركة
    //        - shouldShow: دالة تحدد إظهار الأكشن حسب المنتج الحالي
    //          (لو CrudTable لا يدعمها، يُنصح بإضافة دعم لها في المكوّن)
    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-products',
            shouldShow: (item: any) => !(isCompany && isProductSuperAdmin(item)),
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-products'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-products'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-products'
        }
    ];

    // Prepare filter options
    const categoryOptions = [
        { value: 'all', label: t('All Categories') },
        ...(categories || []).map((category: any) => ({
            value: category.id.toString(),
            label: category.name
        }))
    ];

    const brandOptions = [
        { value: 'all', label: t('All Brands') },
        ...(brands || []).map((brand: any) => ({
            value: brand.id.toString(),
            label: brand.name
        }))
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'active', label: t('Active') },
        { value: 'inactive', label: t('Inactive') }
    ];

    // ═══════════════════════════════════════════════════════════════
    // NEW: Ownership & Company filter options (Super Admin only)
    // ═══════════════════════════════════════════════════════════════
    const ownershipOptions = [
        { value: 'all', label: t('All Products') },
        { value: 'super_admin', label: t('Super Admin Products') },
        { value: 'company', label: t('Company Products') },
    ];

    const companyFilterOptions = [
        { value: 'all', label: t('All Companies') },
        ...(companies || []).map((company: any) => ({
            value: company.id.toString(),
            label: company.name
        }))
    ];

    return (
        <PageTemplate
            title={t("Products")}
            url="/products"
            actions={pageActions}
            breadcrumbs={breadcrumbs}
            noPadding
        >
            {/* Search and filters section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[
                        // ═══════════════════════════════════════════════════════════
                        // NEW: Ownership filter — Super Admin only
                        // ═══════════════════════════════════════════════════════════
                        ...(isSuperAdmin ? [{
                            name: 'ownership',
                            label: t('Ownership'),
                            type: 'select',
                            value: selectedOwnership,
                            onChange: handleOwnershipChange,
                            options: ownershipOptions
                        }] : []),
                        // ═══════════════════════════════════════════════════════════
                        // NEW: Company filter — Super Admin only
                        // يظهر فقط لما ownership != 'super_admin'
                        // ═══════════════════════════════════════════════════════════
                        ...(isSuperAdmin && selectedOwnership !== 'super_admin' ? [{
                            name: 'company_id',
                            label: t('Company'),
                            type: 'select',
                            value: selectedCompanyFilter,
                            onChange: setSelectedCompanyFilter,
                            options: companyFilterOptions
                        }] : []),
                        // ═══════════════════════════════════════════════════════════
                        // Existing filters
                        // ═══════════════════════════════════════════════════════════
                        {
                            name: 'category',
                            label: t('Category'),
                            type: 'select',
                            value: selectedCategory,
                            onChange: setSelectedCategory,
                            options: categoryOptions
                        },
                        {
                            name: 'brand',
                            label: t('Brand'),
                            type: 'select',
                            value: selectedBrand,
                            onChange: setSelectedBrand,
                            options: brandOptions
                        },
                        // ⚡ NEW: Tags filter — يظهر للسوبر ادمن وللشركة
                        {
                            name: 'tag',
                            label: t('Tag'),
                            type: 'select',
                            value: selectedTag,
                            onChange: setSelectedTag,
                            options: [
                                { value: 'all', label: t('All Tags') },
                                ...((tags || []).map((tag: any) => ({
                                    value: tag.id.toString(),
                                    label: tag.name
                                })))
                            ]
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: statusOptions
                        },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assigned To'),
                            type: 'select',
                            value: selectedAssignee,
                            onChange: setSelectedAssignee,
                            options: [
                                { value: 'all', label: t('All Users') },
                                ...users.map((user: any) => ({
                                    value: user.id.toString(),
                                    label: user.name
                                }))
                            ]
                        }] : [])
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={pageFilters.per_page?.toString() || "10"}
                    onPerPageChange={(value) => {
                        router.get(route('products.index'), {
                            view: activeView,
                            page: 1,
                            per_page: parseInt(value),
                            ...buildFilterParams(),
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('products.index'), {
                            view,
                            page: pageFilters.page || 1,
                            ...buildFilterParams(),
                            sort_field: pageFilters.sort_field || undefined,
                            sort_direction: pageFilters.sort_direction || undefined,
                            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Content section */}
            {activeView === 'list' ? (
                <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                    <CrudTable
                        columns={columns}
                        actions={actions}
                        data={products?.data || []}
                        from={products?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-products',
                            create: 'create-products',
                            edit: 'edit-products',
                            delete: 'delete-products'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={products?.from || 0}
                        to={products?.to || 0}
                        total={products?.total || 0}
                        links={products?.links}
                        entityName={t("products")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                        {products?.data?.map((product: any) => (
                            <Card key={product.id} className="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                                {/* Product Image */}
                                <div className="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 p-4">
                                    {(() => {
                                        const mainImage = product.media?.find((m: any) => m.collection_name === 'main');
                                        const imageUrl = mainImage?.original_url || product.display_image_url || product.main_image_url || product.image;

                                        return (
                                            <>
                                                <img
                                                    src={imageUrl}
                                                    alt={product.name}
                                                    className="w-full h-full object-contain rounded-lg group-hover:scale-105 transition-transform duration-300"
                                                    onError={(e) => {
                                                        const target = e.currentTarget as HTMLImageElement;
                                                        if (!target.src.startsWith('data:image/svg+xml')) {
                                                            target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMDAgNjBMMTQwIDgwVjE0MEwxMDAgMTYwTDYwIDE0MFY4MEwxMDAgNjBaIiBzdHJva2U9IiM5Q0EzQUYiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0iI0U1RTdFQiIvPgo8Y2lyY2xlIGN4PSI4NSIgY3k9Ijk1IiByPSI4IiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik03MCAxMzBMODUgMTE1TDEwMCAxMzBMMTMwIDEwMEwxMzAgMTMwSDcwWiIgZmlsbD0iIzlDQTNBRiIvPgo8L3N2Zz4=';
                                                        } else {
                                                            target.style.display = 'none';
                                                            const icon = target.nextElementSibling as HTMLElement;
                                                            if (icon) icon.style.display = 'flex';
                                                        }
                                                    }}
                                                />
                                                <div className="absolute inset-0 flex items-center justify-center hidden">
                                                    <Package className="h-16 w-16 text-gray-400" />
                                                </div>
                                            </>
                                        );
                                    })()}

                                    {/* Status Badge */}
                                    <div className="absolute top-3 left-3 flex flex-col gap-1">
                                        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${product.status === 'active'
                                                ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                                : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                            }`}>
                                            {product.status === 'active' ? t('Active') : t('Inactive')}
                                        </span>
                                        {/* ═══════════════════════════════════════════════════════════
                                            Badge نوع المنتج (للسوبر ادمن فقط) في الـ Grid View
                                            ═══════════════════════════════════════════════════════════ */}
                                        {isSuperAdmin && (
                                            (() => {
                                                const superAdminId = auth?.user?.id;
                                                const isSuperAdminProduct = product.created_by === superAdminId;
                                                return isSuperAdminProduct ? (
                                                    <span className="inline-flex items-center rounded-md px-2 py-1 text-[10px] font-medium bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                                        {t('Super Admin')}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center rounded-md px-2 py-1 text-[10px] font-medium bg-cyan-50 text-cyan-700 ring-1 ring-inset ring-cyan-600/20">
                                                        {t('Company')}
                                                    </span>
                                                );
                                            })()
                                        )}
                                    </div>

                                    {/* Actions dropdown */}
                                    <div className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="secondary" size="sm" className="h-8 w-8 p-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                                                {hasPermission(permissions, 'view-products') && (
                                                    <DropdownMenuItem onClick={() => router.visit(route('products.show', product.id))}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Product")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'edit-products') && (
                                                    <DropdownMenuItem onClick={() => router.visit(route('products.edit', product.id))}>
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {/* ⚡ FIX: إخفاء Toggle Status لمنتجات السوبر ادمن عند صاحب الشركة */}
                                                {hasPermission(permissions, 'toggle-status-products') && !(isCompany && isProductSuperAdmin(product)) && (
                                                    <DropdownMenuItem onClick={() => handleToggleStatus(product)}>
                                                        <Lock className="h-4 w-4 mr-2"/>
                                                        <span>{product.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'delete-products') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', product)} className="text-red-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                {/* Product Info */}
                                <div className="p-4 space-y-3">
                                    {/* Product Name & SKU */}
                                    <div>
                                        <h3 className="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 mb-1">{product.name}</h3>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 font-mono">{product.sku}</p>
                                        {/* اسم الشركة المنشئة (للسوبر ادمن فقط) */}
                                        {isSuperAdmin && product.creator && (
                                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                {t('by')}: {product.creator.name}
                                            </p>
                                        )}
                                    </div>

                                    {/* Price & Stock */}
                                    <div className="flex items-center justify-between">
                                        <div className="text-lg font-bold text-green-600 dark:text-green-400">
                                            {window.appSettings?.formatCurrency(parseFloat(product.price || 0)) || `$${parseFloat(product.price || 0).toFixed(2)}`}
                                        </div>
                                        <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${product.stock_quantity > 10 ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
                                                product.stock_quantity > 0 ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' :
                                                    'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                            }`}>
                                            {product.stock_quantity} {t('in stock')}
                                        </div>
                                    </div>

                                    {/* Category & Brand Tags */}
                                    <div className="flex flex-wrap gap-1">
                                        {product.category && (
                                            <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                                {product.category.name}
                                            </span>
                                        )}
                                        {product.brand && (
                                            <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20">
                                                {product.brand.name}
                                            </span>
                                        )}
                                    </div>

                                    {/* Action Button */}
                                    {hasPermission(permissions, 'view-products') && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => router.visit(route('products.show', product.id))}
                                            className="w-full mt-3 h-8 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        >
                                            <Eye className="h-3 w-3 mr-2" />
                                            {t("View Details")}
                                        </Button>
                                    )}
                                </div>
                            </Card>
                        ))}
                    </div>

                    {/* Pagination for grid view */}
                    <div className="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                        <Pagination
                            from={products?.from || 0}
                            to={products?.to || 0}
                            total={products?.total || 0}
                            links={products?.links}
                            entityName={t("products")}
                            onPageChange={(url) => router.get(url)}
                        />
                    </div>
                </div>
            )}

            {/* Form Modal */}
            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    ...(hasPermission(permissions, 'export-products') && { exportRoute: 'product.export' }),
                    fields: [
                        { name: 'name', label: t('Product Name'), type: 'text', required: true },
                        { name: 'sku', label: t('SKU'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        { name: 'price', label: t('Price'), type: 'number', required: true, step: '0.01', min: '0' },
                        { name: 'stock_quantity', label: t('Stock Quantity'), type: 'number', required: false, min: '0', defaultValue: '0' },
                        { name: 'image', label: t('Image URL'), type: 'text' },
                        {
                            name: 'category_id',
                            label: t('Category'),
                            type: 'select',
                            options: categories ? categories.map((category: any) => ({
                                value: category.id.toString(),
                                label: category.name
                            })) : []
                        },
                        {
                            name: 'brand_id',
                            label: t('Brand'),
                            type: 'select',
                            options: brands ? brands.map((brand: any) => ({
                                value: brand.id.toString(),
                                label: brand.name
                            })) : []
                        },
                        {
                            name: 'tax_id',
                            label: t('Tax'),
                            type: 'select',
                            options: taxes ? taxes.map((tax: any) => ({
                                value: tax.id.toString(),
                                label: `${tax.name} (${tax.rate}%)`
                            })) : []
                        },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            options: formMode === 'view' ? [] : [
                                { value: null, label: t('Select User') },
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ],
                            readOnly: formMode === 'view',
                            hidden: !isCompany || (formMode === 'create' && auth?.user?.type === 'staff')
                        }] : []),
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') }
                            ],
                            defaultValue: 'active'
                        }
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned')
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Product')
                        : formMode === 'edit'
                            ? t('Edit Product')
                            : t('View Product')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('product')}
            />

            {/* ═══════════════════════════════════════════════════════════════
                Import from Excel Modal - Super Admin Only
            ═══════════════════════════════════════════════════════════════ */}
            {isSuperAdmin && (
                <ImportExcelModal
                    open={isImportExcelModalOpen}
                    onClose={() => setIsImportExcelModalOpen(false)}
                >
                    <div className="rounded-md bg-blue-50 border border-blue-200 p-4 mb-4">
                        <div className="flex items-start gap-3">
                            <svg className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                            </svg>
                            <div className="flex-1">
                                <p className="text-sm text-blue-800 font-medium mb-1">
                                    {t('You can download the import template to match your data with the provided template.')}
                                </p>
                                <a
                                    href={route('product.download.template')}
                                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-900 underline"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                    </svg>
                                    {t('Download Import Template (Excel)')}
                                </a>
                            </div>
                        </div>
                    </div>
                </ImportExcelModal>
            )}

        </PageTemplate>
    );
}
