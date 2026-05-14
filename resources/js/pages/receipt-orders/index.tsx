import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router, Link } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, Download, FileDown } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Button } from '@/components/ui/button';


export default function ReceiptOrders() {
    const { t } = useTranslation();
    const { auth, receiptOrders, accounts, contacts, purchaseOrders, returnOrders, products, taxes, users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');


    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedAccount !== 'all' || selectedAssignee !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAccount !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('receipt-orders.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('receipt-orders.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('receipt-orders.show', item.id));
                break;
            case 'edit':
                setFormMode('edit');
                setIsFormModalOpen(true);
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
        // Validate that at least one of purchase_order_id or return_order_id is provided
        if (!formData.purchase_order_id && !formData.return_order_id) {
            toast.error(t('Either Purchase Order or Return Order is required.'));
            return;
        }

        // Remove manual total calculations - let backend handle this
        // The backend calculateTotals() method will compute correct values

        if (formMode === 'create') {
            toast.loading(t('Creating receipt order...'));

            router.post(route('receipt-orders.store'), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    toast.error(t('Failed to create: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating receipt order...'));

            router.put(route('receipt-orders.update', currentItem.id), formData, {
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
                    toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting receipt order...'));

        router.delete(route('receipt-orders.destroy', currentItem.id), {
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
                toast.error(t('Failed to delete: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
        });
    };

    const handleToggleStatus = (receiptOrder: any) => {
        const newStatus = receiptOrder.status === 'pending' ? 'received' : 'pending';
        toast.loading(t('{{action}} receipt order...', { action: newStatus === 'received' ? t('Marking as received') : t('Setting to pending') }));

        router.put(route('receipt-orders.toggle-status', receiptOrder.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
        });
    };



    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedAccount('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('receipt-orders.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    if (hasPermission(permissions, 'export-receipt-orders')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => window.location.href = route('receipt-order.export')
        });
    }

    if (hasPermission(permissions, 'create-receipt-orders')) {
        pageActions.push({
            label: t('Add Receipt Order'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Receipt Orders') }
    ];

    const columns = [
        {
            key: 'receipt_number',
            label: t('Receipt Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('receipt-orders.show', item.id)}
                    className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 transition-colors duration-200 border border-blue-200 hover:border-blue-300"
                >
                    {value}
                </Link>
            )
        },
        {
            key: 'account',
            label: t('Account'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'receipt_date',
            label: t('Receipt Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'total_amount',
            label: t('Total Amount'),
            render: (value: any) => window.appSettings?.formatCurrency(Number(value || 0)) || `$${Number(value || 0).toFixed(2)}`
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    received: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    partial: 'bg-orange-50 text-orange-700 ring-orange-600/20',
                    completed: 'bg-green-50 text-green-700 ring-green-600/20',
                    cancelled: 'bg-red-50 text-red-700 ring-red-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors] || statusColors.pending}`}>
                        {t(value.charAt(0).toUpperCase() + value.slice(1))}
                    </span>
                );
            }
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        }
    ];

    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-receipt-orders'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-receipt-orders'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-receipt-orders'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-receipt-orders'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'pending', label: t('Pending') },
        { value: 'received', label: t('Received') },
        { value: 'partial', label: t('Partial') },
        { value: 'completed', label: t('Completed') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    const accountOptions = [
        { value: 'all', label: t('All Accounts') },
        ...accounts.map((account: any) => ({ value: account.id.toString(), label: account.name }))
    ];

    return (
        <PageTemplate
            title={t("Receipt Orders")}
            url="/receipt-orders"
            actions={pageActions}
            breadcrumbs={breadcrumbs}
            noPadding
        >
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: statusOptions
                        },
                        {
                            name: 'account_id',
                            label: t('Account'),
                            type: 'select',
                            value: selectedAccount,
                            onChange: setSelectedAccount,
                            options: accountOptions
                        },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assigned To'),
                            type: 'select',
                            value: selectedAssignee,
                            onChange: setSelectedAssignee,
                            options: [
                                { value: 'all', label: t('All Users') },
                                { value: 'unassigned', label: t('Unassigned') },
                                ...users.map((user: any) => ({ value: user.id.toString(), label: user.name }))
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
                        router.get(route('receipt-orders.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={receiptOrders?.data || []}
                    from={receiptOrders?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-receipt-orders',
                        create: 'create-receipt-orders',
                        edit: 'edit-receipt-orders',
                        delete: 'delete-receipt-orders'
                    }}
                />

                <Pagination
                    from={receiptOrders?.from || 0}
                    to={receiptOrders?.to || 0}
                    total={receiptOrders?.total || 0}
                    links={receiptOrders?.links}
                    entityName={t("receipt orders")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    productOptions: products,
                    modalSize: '5xl',
                    layout: 'grid',
                    columns: 2,

                    fields: [
                        { name: 'name', label: t('Name'), type: 'text', required: true, colSpan: 2 },
                        { name: 'description', label: t('Description'), type: 'textarea', colSpan: 2 },
                        {
                            name: formMode === 'view' ? 'purchase_order_name' : 'purchase_order_id',
                            label: t('Purchase Order'),
                            type: formMode === 'view' ? 'text' : 'select',
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('purchase-orders.index'), linkText: t('Purchase Orders') },
                            options: formMode === 'view' ? [] : [
                                ...purchaseOrders?.map((po: any) => ({ value: po.id, label: po.name })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'account_name' : 'account_id',
                            label: t('Account'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('accounts.index'), linkText: t('Accounts') },
                            options: formMode === 'view' ? [] : [
                                ...accounts?.map((account: any) => ({ value: account.id, label: account.name })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'return_order_name' : 'return_order_id',
                            label: t('Return Order'),
                            type: formMode === 'view' ? 'text' : 'select',
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('return-orders.index'), linkText: t('Return Orders') },
                            options: formMode === 'view' ? [] : [
                                ...returnOrders?.map((ro: any) => ({ value: ro.id, label: ro.name })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'contact_name' : 'contact_id',
                            label: t('Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('contacts.index'), linkText: t('Contacts') },
                            options: formMode === 'view' ? [] : [
                                ...contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || []
                            ]
                        },
                        { name: 'receipt_date', label: t('Receipt Date'), type: 'date', required: true, defaultValue: new Date().toISOString().split('T')[0] },
                        { name: 'expected_date', label: t('Expected Date'), type: 'date' },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'pending', label: t('Pending') },
                                { value: 'received', label: t('Received') },
                                { value: 'partial', label: t('Partial') },
                                { value: 'completed', label: t('Completed') },
                                { value: 'cancelled', label: t('Cancelled') }
                            ],
                            defaultValue: 'pending'
                        },
                        {
                            name: 'products_header',
                            type: 'custom',
                            colSpan: 2,
                            render: () => (
                                <div className="col-span-2 border-t pt-4 mt-4">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Products')} <span className="text-red-500">*</span></h3>
                                </div>
                            )
                        },
                        {
                            name: 'products',
                            label: t('Products'),
                            type: 'array',
                            colSpan: 2,
                            productOptions: products,
                            renderSummary: (arrayValue: any[], formData: any) => {
                                let subtotal = 0;
                                let totalTax = 0;
                                let totalDiscount = 0;

                                try {
                                    arrayValue.forEach((item: any) => {
                                        const quantity = parseFloat(item.quantity) || 0;
                                        const unitPrice = parseFloat(item.unit_price) || 0;
                                        const lineTotal = quantity * unitPrice;

                                        // Calculate discount
                                        const discountType = item.discount_type;
                                        const discountValue = parseFloat(item.discount_value) || 0;
                                        let discountAmount = 0;
                                        if (discountType && discountType !== 'none') {
                                            if (discountType === 'percentage') {
                                                discountAmount = (lineTotal * discountValue) / 100;
                                            } else if (discountType === 'fixed') {
                                                discountAmount = Math.min(discountValue, lineTotal);
                                            }
                                        }

                                        const discountedTotal = lineTotal - discountAmount;
                                        subtotal += discountedTotal;
                                        totalDiscount += discountAmount;

                                        const product = products?.find((p: any) => p.id == item.product_id);
                                        if (product?.tax) {
                                            totalTax += (discountedTotal * product.tax.rate) / 100;
                                        }
                                    });
                                } catch (error) {
                                }

                                return (
                                    <div className="space-y-2">
                                        <div className="flex justify-between items-center">
                                            <span className="font-medium text-gray-700">{t('Total Discount')}:</span>
                                            <span className="font-medium text-red-600">-{window.appSettings?.formatCurrency(totalDiscount) || `$${totalDiscount.toFixed(2)}`}</span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="font-medium text-gray-700">{t('Subtotal')}:</span>
                                            <span className="font-medium">{window.appSettings?.formatCurrency(subtotal) || `$${subtotal.toFixed(2)}`}</span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="font-medium text-gray-700">{t('Total Tax')}:</span>
                                            <span className="font-medium">{window.appSettings?.formatCurrency(totalTax) || `$${totalTax.toFixed(2)}`}</span>
                                        </div>
                                        <div className="flex justify-between items-center border-t pt-2">
                                            <span className="font-bold text-gray-900">{t('Grand Total')}:</span>
                                            <span className="font-bold text-lg text-primary">{window.appSettings?.formatCurrency(subtotal + totalTax) || `$${(subtotal + totalTax).toFixed(2)}`}</span>
                                        </div>
                                    </div>
                                );
                            },
                            renderFooter: (arrayValue: any[], formData: any) => {
                                let subtotal = 0;
                                let totalTax = 0;
                                let totalDiscount = 0;

                                try {
                                    arrayValue.forEach((item: any) => {
                                        const quantity = parseFloat(item.quantity) || 0;
                                        const unitPrice = parseFloat(item.unit_price) || 0;
                                        const lineTotal = quantity * unitPrice;

                                        // Calculate discount
                                        const discountType = item.discount_type;
                                        const discountValue = parseFloat(item.discount_value) || 0;
                                        let discountAmount = 0;
                                        if (discountType && discountType !== 'none') {
                                            if (discountType === 'percentage') {
                                                discountAmount = (lineTotal * discountValue) / 100;
                                            } else if (discountType === 'fixed') {
                                                discountAmount = Math.min(discountValue, lineTotal);
                                            }
                                        }

                                        const discountedTotal = lineTotal - discountAmount;
                                        subtotal += discountedTotal;
                                        totalDiscount += discountAmount;

                                        const product = products?.find((p: any) => p.id == item.product_id);
                                        if (product?.tax) {
                                            totalTax += (discountedTotal * product.tax.rate) / 100;
                                        }
                                    });
                                } catch (error) {
                                }

                                return (
                                    <>
                                        <tr className="bg-gray-50">
                                            <td colSpan={6} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Total Discount')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right text-red-600">
                                                -{window.appSettings?.formatCurrency(totalDiscount) || `$${totalDiscount.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-50">
                                            <td colSpan={6} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Subtotal')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {window.appSettings?.formatCurrency(subtotal) || `$${subtotal.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-50">
                                            <td colSpan={6} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Total Tax')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {window.appSettings?.formatCurrency(totalTax) || `$${totalTax.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-100 font-bold">
                                            <td colSpan={6} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Grand Total')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {window.appSettings?.formatCurrency(subtotal + totalTax) || `$${(subtotal + totalTax).toFixed(2)}`}
                                            </td>
                                        </tr>
                                    </>
                                );
                            },
                            fields: [
                                {
                                    name: 'product_id',
                                    label: t('Product'),
                                    type: 'select',
                                    required: true,
                                    options: products?.map((product: any) => ({
                                        value: product.id,
                                        label: `${product.name} - ${window.appSettings?.formatCurrency(parseFloat(product.price || 0)) || `$${parseFloat(product.price || 0).toFixed(2)}`}${product.tax ? ` (Tax: ${product.tax.name} - ${product.tax.rate}%)` : ''}`
                                    })) || []
                                },
                                {
                                    name: 'quantity',
                                    label: t('Quantity'),
                                    type: 'number',
                                    required: true,
                                    min: '1',
                                    defaultValue: 1
                                },
                                {
                                    name: 'unit_price',
                                    label: t('Unit Price'),
                                    type: 'number',
                                    required: true,
                                    step: '0.01',
                                    min: '0'
                                },
                                {
                                    name: 'discount_type',
                                    label: t('Discount Type'),
                                    type: 'select',
                                    options: [
                                        { value: 'percentage', label: t('Percentage (%)') },
                                        { value: 'fixed', label: t('Fixed Amount') }
                                    ]
                                },
                                {
                                    name: 'discount_value',
                                    label: t('Discount Value'),
                                    type: 'number',
                                    step: '0.01',
                                    min: '0'
                                },
                                {
                                    name: 'line_total',
                                    label: t('Final Total'),
                                    type: 'calculated',
                                    calculate: (item: any) => {
                                        try {
                                            const quantity = parseFloat(item.quantity) || 0;
                                            const unitPrice = parseFloat(item.unit_price) || 0;
                                            const lineTotal = quantity * unitPrice;

                                            // Calculate discount
                                            const discountType = item.discount_type;
                                            const discountValue = parseFloat(item.discount_value) || 0;
                                            let discountAmount = 0;
                                            if (discountType === 'percentage') {
                                                discountAmount = (lineTotal * discountValue) / 100;
                                            } else if (discountType === 'fixed') {
                                                discountAmount = Math.min(discountValue, lineTotal);
                                            }

                                            const finalTotal = lineTotal - discountAmount;
                                            return window.appSettings?.formatCurrency(finalTotal) || `$${finalTotal.toFixed(2)}`;
                                        } catch (error) {
                                            return window.appSettings?.formatCurrency(0) || '$0.00';
                                        }
                                    }
                                }
                            ]
                        },
                        { name: 'notes', label: t('Notes'), type: 'textarea', colSpan: 2 },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            emptyNote: { link: route('users.index'), linkText: t('Users') },
                            options: formMode === 'view' ? [] : [
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ],
                            readOnly: formMode === 'view'
                        }] : [])
                    ]
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                    purchase_order_name: currentItem.purchase_order?.name || t('-'),
                    account_name: currentItem.account?.name || t('-'),
                    return_order_name: currentItem.return_order?.name || t('-'),
                    contact_name: currentItem.contact?.name || t('-'),
                    receipt_date: formMode === 'view' && currentItem.receipt_date ?
                        new Date(currentItem.receipt_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.receipt_date,
                    expected_date: formMode === 'view' && currentItem.expected_date ?
                        new Date(currentItem.expected_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.expected_date,
                    products: currentItem.products?.map((product: any) => ({
                        product_id: product.id,
                        quantity: parseInt(product.pivot?.quantity || 1),
                        unit_price: parseFloat(product.pivot?.unit_price || product.price || 0),
                        discount_type: product.pivot?.discount_type || 'none',
                        discount_value: parseFloat(product.pivot?.discount_value || 0)
                    })) || []
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Receipt Order')
                        : formMode === 'edit'
                            ? t('Edit Receipt Order')
                            : t('View Receipt Order')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('receipt order')}
            />


        </PageTemplate>
    );
}
