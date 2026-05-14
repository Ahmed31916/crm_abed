// pages/coupons/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Copy, Plus } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { hasPermission } from '@/utils/authorization';
import { Tooltip, TooltipProvider, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip';

export default function CouponsPage() {
    const { t } = useTranslation();
    const { auth, coupons, filters: pageFilters = {}, globalSettings } = usePage().props as any;
    const permissions = auth?.permissions || [];

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedType, setSelectedType] = useState(pageFilters.type || '_empty_');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '_empty_');
    const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
    const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [copiedCode, setCopiedCode] = useState<string | null>(null);

    // Check if any filters are active
    const hasActiveFilters = () => {
        return selectedType !== '_empty_' || selectedStatus !== '_empty_' || dateFrom !== '' || dateTo !== '' || searchTerm !== '';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedType !== '_empty_' ? 1 : 0) +
            (selectedStatus !== '_empty_' ? 1 : 0) +
            (dateFrom !== '' ? 1 : 0) +
            (dateTo !== '' ? 1 : 0) +
            (searchTerm !== '' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('coupons.index'), {
            page: 1,
            search: searchTerm || undefined,
            type: selectedType !== '_empty_' ? selectedType : undefined,
            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('coupons.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            type: selectedType !== '_empty_' ? selectedType : undefined,
            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view-details':
                router.get(route('coupons.show', item.id));
                break;
            case 'edit':
                setFormMode('edit');
                setIsFormModalOpen(true);
                break;
            case 'delete':
                setIsDeleteModalOpen(true);
                break;
            default:
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const generateCouponCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 10; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    };

    const handleFormSubmit = (formData: any) => {
        // Set default values
        if (!formData.code_type) formData.code_type = 'manual';
        if (formData.status === undefined || formData.status === null) formData.status = true;

        // Ensure numeric fields are properly formatted
        if (formData.minimum_spend) formData.minimum_spend = parseFloat(formData.minimum_spend);
        if (formData.maximum_spend) formData.maximum_spend = parseFloat(formData.maximum_spend);
        if (formData.discount_amount) formData.discount_amount = parseFloat(formData.discount_amount);
        if (formData.use_limit_per_coupon) formData.use_limit_per_coupon = parseInt(formData.use_limit_per_coupon);
        if (formData.use_limit_per_user) formData.use_limit_per_user = parseInt(formData.use_limit_per_user);

        if (formMode === 'create') {
            if (!globalSettings?.is_demo) {
                toast.loading(t('Creating coupon...'));
            }

            router.post(route('coupons.store'), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    if (!globalSettings?.is_demo) {
                        toast.dismiss();
                    }
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    }
                },
                onError: (errors) => {
                    if (!globalSettings?.is_demo) {
                        toast.dismiss();
                    }
                    if (typeof errors === 'string') {
                        toast.error(t(errors));
                    } else {
                        toast.error(t('Failed to create coupon: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            if (!globalSettings?.is_demo) {
                toast.loading(t('Updating coupon...'));
            }

            router.put(route('coupons.update', currentItem.id), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    if (!globalSettings?.is_demo) {
                        toast.dismiss();
                    }
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    }
                },
                onError: (errors) => {
                    if (!globalSettings?.is_demo) {
                        toast.dismiss();
                    }
                    if (typeof errors === 'string') {
                        toast.error(t(errors));
                    } else {
                        toast.error(t('Failed to update coupon: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        if (!globalSettings?.is_demo) {
            toast.loading(t('Deleting coupon...'));
        }

        router.delete(route('coupons.destroy', currentItem.id), {
            onSuccess: (page) => {
                setIsDeleteModalOpen(false);
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(t('Failed to delete coupon: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedType('_empty_');
        setSelectedStatus('_empty_');
        setDateFrom('');
        setDateTo('');
        setShowFilters(false);

        router.get(route('coupons.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleToggleStatus = (coupon: any) => {
        if (!globalSettings?.is_demo) {
            toast.loading(t(coupon.status ? 'Deactivating' : 'Activating') + ' ' + t('coupon...'));
        }

        router.put(route('coupons.toggle-status', coupon.id), {}, {
            onSuccess: (page) => {
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(t('Failed to update coupon status: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleCopyCode = async (code: string) => {
        try {
            await navigator.clipboard.writeText(code);
            setCopiedCode(code);
            setTimeout(() => {
                setCopiedCode(null);
            }, 2000);
        } catch (err) {

        }
    };

    // Define page actions
    const pageActions = [];

    if (hasPermission(permissions, 'create-coupons')) {
        pageActions.push({
            label: t('Add Coupon'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Coupons') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true
        },
        {
            key: 'code',
            label: t('Code'),
            sortable: true,
            render: (value) => (
                <div className="flex flex-col items-start">
                    <button
                        onClick={() => handleCopyCode(value)}
                        className="px-3 py-1.5 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors font-mono text-sm cursor-pointer"
                    >
                        {value}
                    </button>
                    {copiedCode === value && (
                        <span className="text-xs text-red-600 dark:text-red-400 mt-1 font-medium ml-3">
                            {t('Copied!')}
                        </span>
                    )}
                </div>
            )
        },
        {
            key: 'type',
            label: t('Type'),
            sortable: true,
            render: (value) => value === 'percentage' ? t('Percentage') : t('Flat Amount')
        },
        {
            key: 'minimum_spend',
            label: t('Min Spend'),
            render: (value) => value ? (window.appSettings?.formatCurrency(value) || `$${parseFloat(value).toFixed(2)}`) : '-'
        },
        {
            key: 'maximum_spend',
            label: t('Max Spend'),
            render: (value) => value ? (window.appSettings?.formatCurrency(value) || `$${parseFloat(value).toFixed(2)}`) : '-'
        },
        {
            key: 'discount_amount',
            label: t('Discount'),
            render: (_, row) => {
                const amount = parseFloat(row.discount_amount);
                return row.type === 'percentage'
                    ? `${amount}%`
                    : (window.appSettings?.formatCurrency(amount) || `$${amount.toFixed(2)}`);
            }
        },
        {
            key: 'use_limit_per_coupon',
            label: t('Coupon Limit'),
            render: (value) => value || t('Unlimited')
        },
        {
            key: 'use_limit_per_user',
            label: t('User Limit'),
            render: (value) => value || t('Unlimited')
        },
        {
            key: 'expiry_date',
            label: t('Expiry Date'),
            sortable: true,
            render: (value) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'status',
            label: t('Status'),
            render: (_, row) => (
                <div className="flex items-center">
                    <Switch
                        checked={!!row.status}
                        onCheckedChange={() => handleToggleStatus(row)}
                    />
                </div>
            )
        }
    ];

    // Define table actions
    const actions = [
        {
            label: t('View Details'),
            icon: 'Eye',
            action: 'view-details',
            className: 'text-blue-500',
            requiredPermission: 'view-coupons'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'create-coupons'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-coupons'
        }
    ];

    // Prepare filter options
    const typeOptions = [
        { value: '_empty_', label: t('All Types') },
        { value: 'percentage', label: t('Percentage') },
        { value: 'flat', label: t('Flat Amount') }
    ];

    const statusOptions = [
        { value: '_empty_', label: t('All Status') },
        { value: '1', label: t('Active') },
        { value: '0', label: t('Inactive') }
    ];

    return (
        <PageTemplate
            title={t('Coupons')}
            url="/coupons"
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
                        {
                            name: 'type',
                            label: t('Type'),
                            type: 'select',
                            value: selectedType,
                            onChange: setSelectedType,
                            options: typeOptions
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: statusOptions
                        },
                        {
                            name: 'date_from',
                            label: t('Date From'),
                            type: 'date',
                            value: dateFrom,
                            onChange: setDateFrom
                        },
                        {
                            name: 'date_to',
                            label: t('Date To'),
                            type: 'date',
                            value: dateTo,
                            onChange: setDateTo
                        }
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={pageFilters.per_page?.toString() || "10"}
                    onPerPageChange={(value) => {
                        router.get(route('coupons.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            type: selectedType !== '_empty_' ? selectedType : undefined,
                            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
                            date_from: dateFrom || undefined,
                            date_to: dateTo || undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Content section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={coupons?.data || []}
                    from={coupons?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-coupons',
                        create: 'create-coupons',
                        edit: 'create-coupons',
                        delete: 'delete-coupons'
                    }}
                />

                {/* Pagination section */}
                <Pagination
                    from={coupons?.from || 0}
                    to={coupons?.to || 0}
                    total={coupons?.total || 0}
                    links={coupons?.links}
                    entityName={t("coupons")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            {/* Form Modal */}
            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: [
                        {
                            name: 'name',
                            label: t('Coupon Name'),
                            type: 'text',
                            required: true,
                            placeholder: t('Enter coupon name'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'type',
                            label: t('Discount Type'),
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'percentage', label: t('Percentage (%)') },
                                { value: 'flat', label: t('Fixed Amount ($)') }
                            ],
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'discount_amount',
                            label: t('Discount Value'),
                            type: 'number',
                            required: true,
                            min: 0,
                            step: 0.01,
                            placeholder: t('Enter value'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'use_limit_per_coupon',
                            label: t('Total Usage Limit'),
                            type: 'number',
                            min: 1,
                            placeholder: t('Leave empty for unlimited'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'code_type',
                            label: t('Code Generation'),
                            type: 'radio',
                            required: true,
                            options: [
                                { value: 'manual', label: t('Manual Entry') },
                                { value: 'auto', label: t('Auto Generate') }
                            ],
                            defaultValue: 'manual',
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'code',
                            label: t('Coupon Code'),
                            type: 'text',
                            required: true,
                            placeholder: t('Enter coupon code'),
                            width: 'calc(50% - 0.5rem)',
                            render: (field, formData, handleChange) => {
                                const isAutoGenerate = formData.code_type === 'auto';
                                return (
                                    <div className="space-y-2">
                                        {isAutoGenerate ? (
                                            <div className="flex gap-2">
                                                <Input
                                                    id={field.name}
                                                    name={field.name}
                                                    type="text"
                                                    placeholder={t('Click generate to create code')}
                                                    value={formData[field.name] || ''}
                                                    readOnly
                                                    className="flex-1"
                                                />
                                                <Button
                                                    type="button"
                                                    onClick={() => handleChange(field.name, generateCouponCode())}
                                                    variant="default"
                                                >
                                                    {t('Generate')}
                                                </Button>
                                            </div>
                                        ) : (
                                            <Input
                                                id={field.name}
                                                name={field.name}
                                                type="text"
                                                placeholder={field.placeholder}
                                                value={formData[field.name] || ''}
                                                onChange={(e) => handleChange(field.name, e.target.value)}
                                            />
                                        )}
                                    </div>
                                );
                            }
                        },
                        {
                            name: 'minimum_spend',
                            label: t('Minimum Spend'),
                            type: 'number',
                            min: 0,
                            step: 0.01,
                            placeholder: t('Optional'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'maximum_spend',
                            label: t('Maximum Spend'),
                            type: 'number',
                            min: 0,
                            step: 0.01,
                            placeholder: t('Optional'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'use_limit_per_user',
                            label: t('Usage Limit Per User'),
                            type: 'number',
                            min: 1,
                            placeholder: t('Leave empty for unlimited'),
                            width: 'calc(50% - 0.5rem)'
                        },
                        {
                            name: 'expiry_date',
                            label: t('Expiry Date'),
                            type: 'date',
                            width: 'calc(50% - 0.5rem)'
                        },
                    ],
                    modalSize: '4xl',
                    layout: 'flex'
                }}
                initialData={currentItem}
                title={
                    formMode === 'create'
                        ? t('Add Coupon')
                        : formMode === 'edit'
                            ? t('Edit Coupon')
                            : t('View Coupon')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName="coupon"
            />
        </PageTemplate>
    );
}
