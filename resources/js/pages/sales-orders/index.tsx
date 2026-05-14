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
import { Button } from '@/components/ui/button';

import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export default function SalesOrders() {
    const { t } = useTranslation();
    const { auth, salesOrders, accounts, contacts, quotes, products, shippingProviderTypes, taxes, users = [], filters: pageFilters = {}, publicUrlBase, encryptedSalesOrderIds } = usePage().props as any;
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
        router.get(route('sales-orders.index'), {
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

        router.get(route('sales-orders.index'), {
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
                router.get(route('sales-orders.show', item.id));
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
            case 'copy-link':
                handleCopySalesOrderLink(item);
                break;

        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };



    const handleFormSubmit = (formData: any) => {
        // Calculate totals from products if products are selected
        if (formData.products && formData.products.length > 0) {
            let subtotal = 0;
            let totalTax = 0;

            formData.products.forEach((product: any) => {
                const quantity = parseFloat(product.quantity) || 0;
                const unitPrice = parseFloat(product.unit_price) || 0;
                const lineTotal = quantity * unitPrice;

                // Calculate discount
                const discountType = product.discount_type;
                const discountValue = parseFloat(product.discount_value) || 0;
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

                const productData = products?.find((p: any) => p.id == product.product_id);
                if (productData?.tax) {
                    totalTax += (discountedTotal * productData.tax.rate) / 100;
                }
            });

            formData.subtotal = subtotal;
            formData.tax_amount = totalTax;
            formData.total_amount = subtotal + totalTax;
        }

        if (formMode === 'create') {
            toast.loading(t('Creating sales order...'));

            router.post(route('sales-orders.store'), formData, {
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
            toast.loading(t('Updating sales order...'));

            router.put(route('sales-orders.update', currentItem.id), formData, {
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
        toast.loading(t('Deleting sales order...'));

        router.delete(route('sales-orders.destroy', currentItem.id), {
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

    const handleToggleStatus = (salesOrder: any) => {
        const newStatus = salesOrder.status === 'draft' ? 'confirmed' : 'draft';
        toast.loading(t('{{action}} sales order...', { action: newStatus === 'confirmed' ? t('Confirming') : t('Setting to draft') }));

        router.put(route('sales-orders.toggle-status', salesOrder.id), {}, {
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

    const handleCopySalesOrderLink = (salesOrder: any) => {
        const baseUrl = publicUrlBase?.endsWith('/') ? publicUrlBase.slice(0, -1) : publicUrlBase;
        const encryptedId = encryptedSalesOrderIds[salesOrder.id];
        const salesOrderUrl = `${baseUrl}/sales-orders/public/${encryptedId}`;
        navigator.clipboard.writeText(salesOrderUrl).then(() => {
            toast.success(t('Sales order link copied to clipboard!'));
        }).catch(() => {
            toast.error(t('Failed to copy sales order link'));
        });
    };



    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedAccount('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('sales-orders.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-sales-orders')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    if (hasPermission(permissions, 'create-sales-orders')) {
        pageActions.push({
            label: t('Add Sales Order'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Sales Orders') }
    ];

    const columns = [
        {
            key: 'order_number',
            label: t('Order Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('sales-orders.show', item.id)}
                    className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 transition-colors duration-200 border border-blue-200 hover:border-blue-300"
                >
                    {value}
                </Link>
            )
        },
        {
            key: 'name',
            label: t('Name'),
            sortable: true,
        },
        {
            key: 'order_date',
            label: t('Order Date'),
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
                    draft: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    confirmed: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    processing: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    shipped: 'bg-purple-50 text-purple-700 ring-purple-600/20',
                    delivered: 'bg-green-50 text-green-700 ring-green-600/20',
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
            label: t('Copy Sales Order Link'),
            icon: 'Copy',
            action: 'copy-link',
            className: 'text-purple-500'
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-sales-orders'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-sales-orders'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-sales-orders'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-sales-orders'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'draft', label: t('Draft') },
        { value: 'confirmed', label: t('Confirmed') },
        { value: 'processing', label: t('Processing') },
        { value: 'shipped', label: t('Shipped') },
        { value: 'delivered', label: t('Delivered') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    const accountOptions = [
        { value: 'all', label: t('All Accounts') },
        ...accounts.map((account: any) => ({ value: account.id.toString(), label: account.name }))
    ];

    const assigneeOptions = [
        { value: 'all', label: t('All Users') },
        { value: 'unassigned', label: t('Unassigned') },
        ...users.map((user: any) => ({ value: user.id.toString(), label: user.name }))
    ];

    return (
        <PageTemplate
            title={t("Sales Orders")}
            url="/sales-orders"
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
                            options: assigneeOptions
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
                        router.get(route('sales-orders.index'), {
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
                    data={salesOrders?.data || []}
                    from={salesOrders?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-sales-orders',
                        create: 'create-sales-orders',
                        edit: 'edit-sales-orders',
                        delete: 'delete-sales-orders'
                    }}
                />

                <Pagination
                    from={salesOrders?.from || 0}
                    to={salesOrders?.to || 0}
                    total={salesOrders?.total || 0}
                    links={salesOrders?.links}
                    entityName={t("sales orders")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    ...(hasPermission(permissions, 'export-sales-orders') && { exportRoute: 'sales-order.export' }),
                    productOptions: products,
                    modalSize: '5xl',
                    layout: 'grid',
                    columns: 2,
                    fields: [
                        { name: 'name', label: t('Name'), type: 'text', required: true, colSpan: 2 },
                        { name: 'description', label: t('Description'), type: 'textarea', colSpan: 2 },
                        {
                            name: formMode === 'view' ? 'quote_name' : 'quote_id',
                            label: t('Quote'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...quotes?.map((quote: any) => ({ value: quote.id, label: `${quote.quote_number} - ${quote.name}` })) || []
                            ],
                            placeholder: t('Select Quote'),
                            emptyNote: quotes?.length === 0 ? {
                                link: route('quotes.index'),
                                linkText: t('Quotes')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'account_name' : 'account_id',
                            label: t('Account'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...accounts?.map((account: any) => ({ value: account.id, label: account.name })) || []
                            ],
                            placeholder: t('Select Account'),
                            emptyNote: accounts?.length === 0 ? {
                                link: route('accounts.index'),
                                linkText: t('Accounts')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'billing_contact_name' : 'billing_contact_id',
                            label: t('Billing Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || []
                            ],
                            placeholder: t('Select Contact'),
                            emptyNote: contacts?.length === 0 ? {
                                link: route('contacts.index'),
                                linkText: t('Contacts')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'shipping_contact_name' : 'shipping_contact_id',
                            label: t('Shipping Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || []
                            ],
                            placeholder: t('Select Contact'),
                            emptyNote: contacts?.length === 0 ? {
                                link: route('contacts.index'),
                                linkText: t('Contacts')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'shipping_provider_name' : 'shipping_provider_type_id',
                            label: t('Shipping Provider'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...shippingProviderTypes?.map((spt: any) => ({ value: spt.id, label: spt.name })) || []
                            ],
                            placeholder: t('Select Provider'),
                            emptyNote: shippingProviderTypes?.length === 0 ? {
                                link: route('shipping-provider-types.index'),
                                linkText: t('Shipping Provider Types')
                            } : undefined
                        },
                        { name: 'order_date', label: t('Order Date'), type: 'date', required: true, defaultValue: new Date().toISOString().split('T')[0] },
                        { name: 'delivery_date', label: t('Delivery Date'), type: 'date' },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'draft', label: t('Draft') },
                                { value: 'confirmed', label: t('Confirmed') },
                                { value: 'processing', label: t('Processing') },
                                { value: 'shipped', label: t('Shipped') },
                                { value: 'delivered', label: t('Delivered') },
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
                        {
                            name: 'billing_shipping_section',
                            type: 'custom',
                            colSpan: 2,
                            render: (field: any, formData: any, handleChange: any) => (
                                <div className="col-span-2 border-t pt-4 mt-4">
                                    <div className="grid grid-cols-2 gap-6">
                                        <div>
                                            <div className="flex justify-between items-center mb-3">
                                                <h3 className="text-lg font-semibold text-gray-800">{t('Billing Information')}</h3>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        // Set shipping address first
                                                        setTimeout(() => {
                                                            const textareas = document.querySelectorAll('textarea');
                                                            const shippingTextarea = textareas[textareas.length - 1];
                                                            if (shippingTextarea && formData.billing_address) {
                                                                const nativeTextareaSetter = Object.getOwnPropertyDescriptor(window.HTMLTextAreaElement.prototype, 'value').set;
                                                                nativeTextareaSetter.call(shippingTextarea, formData.billing_address);
                                                                shippingTextarea.dispatchEvent(new Event('input', { bubbles: true }));
                                                                shippingTextarea.dispatchEvent(new Event('change', { bubbles: true }));
                                                            }
                                                        }, 10);

                                                        // Set shipping text inputs with delays
                                                        setTimeout(() => {
                                                            const inputs = document.querySelectorAll('input[type="text"]');
                                                            const textInputs = Array.from(inputs).filter(input => input.getAttribute('type') === 'text');
                                                            const shippingTextInputs = textInputs.slice(-4);
                                                            const billingValues = [formData.billing_city, formData.billing_state, formData.billing_country, formData.billing_postal_code];

                                                            shippingTextInputs.forEach((input, index) => {
                                                                setTimeout(() => {
                                                                    if (billingValues[index]) {
                                                                        const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                                                                        nativeInputValueSetter.call(input, billingValues[index]);
                                                                        input.dispatchEvent(new Event('input', { bubbles: true }));
                                                                        input.dispatchEvent(new Event('change', { bubbles: true }));
                                                                    }
                                                                }, index * 20);
                                                            });
                                                        }, 50);
                                                    }}
                                                    className="text-xs"
                                                >
                                                    {t('Copy')} →
                                                </Button>
                                            </div>
                                            <div className="space-y-3">
                                                <div>
                                                    <Label className="text-sm font-medium">{t('Billing Address')}</Label>
                                                    <Textarea
                                                        value={formData.billing_address || ''}
                                                        onChange={(e) => handleChange('billing_address', e.target.value)}
                                                        className="mt-1"
                                                    />
                                                </div>
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Billing City')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.billing_city || ''}
                                                            onChange={(e) => handleChange('billing_city', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Billing State')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.billing_state || ''}
                                                            onChange={(e) => handleChange('billing_state', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Billing Country')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.billing_country || ''}
                                                            onChange={(e) => handleChange('billing_country', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Billing Postal Code')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.billing_postal_code || ''}
                                                            onChange={(e) => handleChange('billing_postal_code', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Shipping Information')}</h3>
                                            <div className="space-y-3">
                                                <div>
                                                    <Label className="text-sm font-medium">{t('Shipping Address')}</Label>
                                                    <Textarea
                                                        value={formData.shipping_address || ''}
                                                        onChange={(e) => handleChange('shipping_address', e.target.value)}
                                                        className="mt-1"
                                                    />
                                                </div>
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Shipping City')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.shipping_city || ''}
                                                            onChange={(e) => handleChange('shipping_city', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Shipping State')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.shipping_state || ''}
                                                            onChange={(e) => handleChange('shipping_state', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Shipping Country')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.shipping_country || ''}
                                                            onChange={(e) => handleChange('shipping_country', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <Label className="text-sm font-medium">{t('Shipping Postal Code')}</Label>
                                                        <input
                                                            type="text"
                                                            value={formData.shipping_postal_code || ''}
                                                            onChange={(e) => handleChange('shipping_postal_code', e.target.value)}
                                                            className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )
                        },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            options: formMode === 'view' ? [] : [
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ],
                            placeholder: t('Select User'),
                            readOnly: formMode === 'view',
                            emptyNote: users?.length === 0 ? {
                                link: route('users.index'),
                                linkText: t('Users')
                            } : undefined
                        }] : [])
                    ]
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                    quote_name: currentItem.quote?.name || t('-'),
                    account_name: currentItem.account?.name || t('-'),
                    billing_contact_name: currentItem.billing_contact?.name || t('-'),
                    shipping_contact_name: currentItem.shipping_contact?.name || t('-'),
                    shipping_provider_name: currentItem.shipping_provider_type?.name || t('-'),
                    order_date: formMode === 'view' && currentItem.order_date ?
                        new Date(currentItem.order_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.order_date,
                    delivery_date: formMode === 'view' && currentItem.delivery_date ?
                        new Date(currentItem.delivery_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.delivery_date,
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
                        ? t('Add Sales Order')
                        : formMode === 'edit'
                            ? t('Edit Sales Order')
                            : t('View Sales Order')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('sales order')}
            />


        </PageTemplate>
    );
}
