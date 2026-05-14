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

export default function DeliveryOrders() {
    const { t } = useTranslation();
    const { auth, deliveryOrders, accounts, contacts, salesOrders, products, shippingProviderTypes, users = [], filters: pageFilters = {} } = usePage().props as any;
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
        router.get(route('delivery-orders.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            sales_order_id: selectedSalesOrder !== 'all' ? selectedSalesOrder : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('delivery-orders.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            sales_order_id: selectedSalesOrder !== 'all' ? selectedSalesOrder : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('delivery-orders.show', item.id));
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
        if (formMode === 'create') {
            toast.loading(t('Creating delivery order...'));

            router.post(route('delivery-orders.store'), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    toast.error(t('Failed to create delivery order: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating delivery order...'));

            router.put(route('delivery-orders.update', currentItem.id), formData, {
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
                    toast.error(t('Failed to update delivery order: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting delivery order...'));

        router.delete(route('delivery-orders.destroy', currentItem.id), {
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
                toast.error(t('Failed to delete delivery order: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
        });
    };

    const handleToggleStatus = (deliveryOrder: any) => {
        const statusMap = {
            'pending': 'in_transit',
            'in_transit': 'delivered',
            'delivered': 'pending',
            'cancelled': 'pending'
        };
        const newStatus = statusMap[deliveryOrder.status as keyof typeof statusMap] || 'pending';
        toast.loading(t('Setting delivery order to {{status}}...', { status: newStatus }));

        router.put(route('delivery-orders.toggle-status', deliveryOrder.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                toast.error(t('Failed to update delivery order status: {{errors}}', { errors: Object.values(errors).join(', ') }));
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

        router.get(route('delivery-orders.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    if (hasPermission(permissions, 'export-delivery-orders')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => window.location.href = route('delivery-order.export')
        });
    }

    if (hasPermission(permissions, 'create-delivery-orders')) {
        pageActions.push({
            label: t('Add Delivery Order'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Delivery Orders') }
    ];

    const columns = [
        {
            key: 'delivery_number',
            label: t('Delivery Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('delivery-orders.show', item.id)}
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
            render: (value: any) => value ? `${value.order_number} - ${value.name}` : '-'
        },
        {
            key: 'delivery_date',
            label: t('Delivery Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    in_transit: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    delivered: 'bg-green-50 text-green-700 ring-green-600/20',
                    cancelled: 'bg-red-50 text-red-700 ring-red-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors] || statusColors.pending}`}>
                        {t(value.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()))}
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
            requiredPermission: 'toggle-status-delivery-orders'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-delivery-orders'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-delivery-orders'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-delivery-orders'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'pending', label: t('Pending') },
        { value: 'in_transit', label: t('In Transit') },
        { value: 'delivered', label: t('Delivered') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    const accountOptions = [
        { value: 'all', label: t('All Accounts') },
        ...accounts.map((account: any) => ({ value: account.id.toString(), label: account.name }))
    ];

    return (
        <PageTemplate
            title={t("Delivery Orders")}
            url="/delivery-orders"
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
                        {
                            name: 'sales_order_id',
                            label: t('Sales Order'),
                            type: 'select',
                            value: selectedSalesOrder,
                            onChange: setSelectedSalesOrder,
                            options: [
                                { value: 'all', label: t('All Sales Orders') },
                                ...salesOrders?.map((so: any) => ({ value: so.id.toString(), label: `${so.order_number} - ${so.name}` })) || []
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
                        router.get(route('delivery-orders.index'), {
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
                    data={deliveryOrders?.data || []}
                    from={deliveryOrders?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-delivery-orders',
                        create: 'create-delivery-orders',
                        edit: 'edit-delivery-orders',
                        delete: 'delete-delivery-orders'
                    }}
                />

                <Pagination
                    from={deliveryOrders?.from || 0}
                    to={deliveryOrders?.to || 0}
                    total={deliveryOrders?.total || 0}
                    links={deliveryOrders?.links}
                    entityName={t("delivery orders")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    modalSize: '4xl',
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
                                ...salesOrders?.map((order: any) => ({ value: order.id, label: `${order.order_number} - ${order.name}` })) || []
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
                        {
                            name: formMode === 'view' ? 'shipping_provider_name' : 'shipping_provider_type_id',
                            label: t('Shipping Provider'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('shipping-provider-types.index'), linkText: t('Shipping Provider Types') },
                            options: formMode === 'view' ? [] : [
                                ...shippingProviderTypes?.map((spt: any) => ({ value: spt.id, label: spt.name })) || []
                            ]
                        },
                        { name: 'delivery_date', label: t('Delivery Date'), type: 'date', required: true, defaultValue: new Date().toISOString().split('T')[0] },
                        { name: 'expected_delivery_date', label: t('Expected Delivery Date'), type: 'date' },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'pending', label: t('Pending') },
                                { value: 'in_transit', label: t('In Transit') },
                                { value: 'delivered', label: t('Delivered') },
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
                                    <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Products')} <span className="text-sm text-red-500">*</span></h3>
                                </div>
                            )
                        },
                        {
                            name: 'products',
                            label: t('Products'),
                            type: 'array',
                            colSpan: 2,
                            fields: [
                                {
                                    name: 'product_id',
                                    label: t('Product'),
                                    type: 'select',
                                    required: true,
                                    options: products?.map((product: any) => ({
                                        value: product.id,
                                        label: product.name
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
                                    name: 'unit_weight',
                                    label: t('Unit Weight (kg)'),
                                    type: 'number',
                                    step: '0.01',
                                    min: '0'
                                }
                            ]
                        },
                        {
                            name: 'delivery_section',
                            type: 'custom',
                            colSpan: 2,
                            render: (field: any, formData: any, handleChange: any) => (
                                <div className="col-span-2 border-t pt-4 mt-4">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Delivery Information')}</h3>
                                    <div className="space-y-3">
                                        <div>
                                            <Label className="text-sm font-medium">{t('Delivery Address')}</Label>
                                            <Textarea
                                                value={formData.delivery_address || ''}
                                                onChange={(e) => handleChange('delivery_address', e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <Label className="text-sm font-medium">{t('Delivery City')}</Label>
                                                <input
                                                    type="text"
                                                    value={formData.delivery_city || ''}
                                                    onChange={(e) => handleChange('delivery_city', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-sm font-medium">{t('Delivery State')}</Label>
                                                <input
                                                    type="text"
                                                    value={formData.delivery_state || ''}
                                                    onChange={(e) => handleChange('delivery_state', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <Label className="text-sm font-medium">{t('Delivery Country')}</Label>
                                                <input
                                                    type="text"
                                                    value={formData.delivery_country || ''}
                                                    onChange={(e) => handleChange('delivery_country', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-sm font-medium">{t('Delivery Postal Code')}</Label>
                                                <input
                                                    type="text"
                                                    value={formData.delivery_postal_code || ''}
                                                    onChange={(e) => handleChange('delivery_postal_code', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <Label className="text-sm font-medium">{t('Delivery Notes')}</Label>
                                                <Textarea
                                                    value={formData.delivery_notes || ''}
                                                    onChange={(e) => handleChange('delivery_notes', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-sm font-medium">{t('Shipping Cost')}</Label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={formData.shipping_cost || ''}
                                                    onChange={(e) => handleChange('shipping_cost', e.target.value)}
                                                    className="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                                />
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
                    sales_order_name: currentItem.sales_order ? `${currentItem.sales_order.order_number} - ${currentItem.sales_order.name}` : t('-'),
                    account_name: currentItem.account?.name || t('-'),
                    contact_name: currentItem.contact?.name || t('-'),
                    shipping_provider_name: currentItem.shipping_provider_type?.name || t('-'),
                    delivery_date: formMode === 'view' && currentItem.delivery_date ?
                        new Date(currentItem.delivery_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.delivery_date,
                    expected_delivery_date: formMode === 'view' && currentItem.expected_delivery_date ?
                        new Date(currentItem.expected_delivery_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.expected_delivery_date,
                    products: currentItem.products?.map((product: any) => ({
                        product_id: product.id,
                        quantity: parseInt(product.pivot?.quantity || 1),
                        unit_weight: parseFloat(product.pivot?.unit_weight || 0)
                    })) || []
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Delivery Order')
                        : formMode === 'edit'
                            ? t('Edit Delivery Order')
                            : t('View Delivery Order')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('delivery order')}
            />


        </PageTemplate>
    );
}
