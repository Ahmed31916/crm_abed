import { useState } from 'react';
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

export default function PurchaseOrders() {
    const { t } = useTranslation();
    const { auth, purchaseOrders, accounts, contacts, salesOrders, products, users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedSalesOrder, setSelectedSalesOrder] = useState(pageFilters.sales_order_id || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedAccount !== 'all' || selectedSalesOrder !== 'all' || selectedAssignee !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAccount !== 'all' ? 1 : 0) + (selectedSalesOrder !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('purchase-orders.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            sales_order_id: selectedSalesOrder !== 'all' ? selectedSalesOrder : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('purchase-orders.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            sales_order_id: selectedSalesOrder !== 'all' ? selectedSalesOrder : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('purchase-orders.show', item.id));
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
        // Remove manual total calculations - let backend handle this
        // The backend calculateTotals() method will compute correct values

        if (formMode === 'create') {
            toast.loading(t('Creating purchase order...'));

            router.post(route('purchase-orders.store'), formData, {
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
            toast.loading(t('Updating purchase order...'));

            router.put(route('purchase-orders.update', currentItem.id), formData, {
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
        toast.loading(t('Deleting purchase order...'));

        router.delete(route('purchase-orders.destroy', currentItem.id), {
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

    const handleToggleStatus = (purchaseOrder: any) => {
        toast.loading(t('Updating purchase order status...'));

        router.put(route('purchase-orders.toggle-status', purchaseOrder.id), {}, {
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
        setSelectedSalesOrder('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('purchase-orders.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    if (hasPermission(permissions, 'export-purchase-orders')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => window.location.href = route('purchase-order.export')
        });
    }

    if (hasPermission(permissions, 'create-purchase-orders')) {
        pageActions.push({
            label: t('Add Purchase Order'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Purchase Orders') }
    ];

    const columns = [
        {
            key: 'order_number',
            label: t('Order Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('purchase-orders.show', item.id)}
                    className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 transition-colors duration-200 border border-blue-200 hover:border-blue-300"
                >
                    {value}
                </Link>
            )
        },
        {
            key: 'name',
            label: t('Name'),
            sortable: true
        },
        {
            key: 'sales_order',
            label: t('Sales Order'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'order_date',
            label: t('Order Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    draft: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    sent: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    confirmed: 'bg-green-50 text-green-700 ring-green-600/20',
                    received: 'bg-purple-50 text-purple-700 ring-purple-600/20',
                    cancelled: 'bg-red-50 text-red-700 ring-red-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors] || statusColors.draft}`}>
                        {t(value.charAt(0).toUpperCase() + value.slice(1))}
                    </span>
                );
            }
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        },
        {
            key: 'created_at',
            label: t('Created At'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-purchase-orders'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-purchase-orders'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-purchase-orders'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-purchase-orders'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'draft', label: t('Draft') },
        { value: 'sent', label: t('Sent') },
        { value: 'confirmed', label: t('Confirmed') },
        { value: 'received', label: t('Received') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    return (
        <PageTemplate
            title={t("Purchase Orders")}
            url="/purchase-orders"
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
                            options: [
                                { value: 'all', label: t('All Accounts') },
                                ...accounts?.map((acc: any) => ({ value: acc.id.toString(), label: acc.name })) || []
                            ]
                        },
                        {
                            name: 'sales_order_id',
                            label: t('Sales Order'),
                            type: 'select',
                            value: selectedSalesOrder,
                            onChange: setSelectedSalesOrder,
                            options: [
                                { value: 'all', label: t('All Sales Orders') },
                                ...salesOrders?.map((so: any) => ({ value: so.id.toString(), label: so.name })) || []
                            ]
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
                        router.get(route('purchase-orders.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            sales_order_id: selectedSalesOrder !== 'all' ? selectedSalesOrder : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={purchaseOrders?.data || []}
                    from={purchaseOrders?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-purchase-orders',
                        create: 'create-purchase-orders',
                        edit: 'edit-purchase-orders',
                        delete: 'delete-purchase-orders'
                    }}
                />

                <Pagination
                    from={purchaseOrders?.from || 0}
                    to={purchaseOrders?.to || 0}
                    total={purchaseOrders?.total || 0}
                    links={purchaseOrders?.links}
                    entityName={t("purchase orders")}
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
                            name: formMode === 'view' ? 'sales_order_name' : 'sales_order_id',
                            label: t('Sales Order'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('sales-orders.index'), linkText: t('Sales Orders') },
                            options: formMode === 'view' ? [] : [
                                ...salesOrders?.map((so: any) => ({ value: so.id, label: so.name })) || []
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
                                ...accounts?.map((acc: any) => ({ value: acc.id, label: acc.name })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'billing_contact_name' : 'billing_contact_id',
                            label: t('Billing Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('contacts.index'), linkText: t('Contacts') },
                            options: formMode === 'view' ? [] : [
                                ...contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'shipping_contact_name' : 'shipping_contact_id',
                            label: t('Shipping Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('contacts.index'), linkText: t('Contacts') },
                            options: formMode === 'view' ? [] : [
                                ...contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || []
                            ]
                        },
                        { name: 'order_date', label: t('Order Date'), type: 'date', required: true, defaultValue: new Date().toISOString().split('T')[0] },
                        { name: 'expected_delivery_date', label: t('Expected Delivery Date'), type: 'date' },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'draft', label: t('Draft') },
                                { value: 'sent', label: t('Sent') },
                                { value: 'confirmed', label: t('Confirmed') },
                                { value: 'received', label: t('Received') },
                                { value: 'cancelled', label: t('Cancelled') }
                            ],
                            defaultValue: 'draft'
                        },
                        {
                            name: 'products_header',
                            type: 'custom',
                            colSpan: 2,
                            render: () => (
                                <div className="col-span-2 border-t pt-4 mt-4">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Products')} <span className="text-sm text-red-500">*</span></h3>
                                </div>
                            )
                        },
                        {
                            name: 'products',
                            label: '',
                            type: 'array',
                            colSpan: 2,
                            productOptions: products,
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
                            fields: [
                                {
                                    name: 'product_id',
                                    label: t('Product'),
                                    type: 'select',
                                    required: true,
                                    emptyNote: { link: route('products.index'), linkText: t('Products') },
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
                                    name: 'discount_amount',
                                    label: t('Discount Amount'),
                                    type: 'calculated',
                                    calculate: (item: any) => {
                                        try {
                                            const quantity = parseFloat(item.quantity) || 0;
                                            const unitPrice = parseFloat(item.unit_price) || 0;
                                            const lineTotal = quantity * unitPrice;
                                            const discountType = item.discount_type;
                                            const discountValue = parseFloat(item.discount_value) || 0;

                                            if (!discountType || discountType === 'none' || !discountValue) return window.appSettings?.formatCurrency(0) || '$0.00';

                                            let discountAmount = 0;
                                            if (discountType === 'percentage') {
                                                discountAmount = (lineTotal * discountValue) / 100;
                                            } else if (discountType === 'fixed') {
                                                discountAmount = Math.min(discountValue, lineTotal);
                                            }

                                            return window.appSettings?.formatCurrency(discountAmount) || `$${discountAmount.toFixed(2)}`;
                                        } catch (error) {
                                            return window.appSettings?.formatCurrency(0) || '$0.00';
                                        }
                                    }
                                },
                                {
                                    name: 'tax_amount',
                                    label: t('Tax Amount'),
                                    type: 'calculated',
                                    calculate: (item: any) => {
                                        try {
                                            const product = products?.find((p: any) => p.id == item.product_id);
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

                                            const discountedTotal = lineTotal - discountAmount;
                                            const taxAmount = product?.tax ? (discountedTotal * product.tax.rate) / 100 : 0;
                                            return window.appSettings?.formatCurrency(taxAmount) || `$${taxAmount.toFixed(2)}`;
                                        } catch (error) {
                                            return window.appSettings?.formatCurrency(0) || '$0.00';
                                        }
                                    }
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
                    sales_order_name: currentItem.sales_order?.name || t('-'),
                    account_name: currentItem.account?.name || t('-'),
                    billing_contact_name: currentItem.billing_contact?.name || t('-'),
                    shipping_contact_name: currentItem.shipping_contact?.name || t('-'),
                    products: currentItem.products?.map((product: any) => ({
                        product_id: product.id,
                        quantity: product.pivot.quantity,
                        unit_price: product.pivot.unit_price,
                        discount_type: product.pivot.discount_type || 'none',
                        discount_value: product.pivot.discount_value || 0
                    })) || []
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Purchase Order')
                        : formMode === 'edit'
                            ? t('Edit Purchase Order')
                            : t('View Purchase Order')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('purchase order')}
            />
        </PageTemplate>
    );
}
