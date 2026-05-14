import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router, Link } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, Download, FileDown } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';

import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function Quotes() {
    const { t } = useTranslation();
    const { auth, quotes, accounts, contacts, opportunities, products, shippingProviderTypes, taxes, users = [], filters: pageFilters = {}, publicUrlBase, encryptedQuoteIds } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedOpportunity, setSelectedOpportunity] = useState(pageFilters.opportunity_id || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');




    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedAccount !== 'all' || selectedOpportunity !== 'all' || selectedAssignee !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAccount !== 'all' ? 1 : 0) + (selectedOpportunity !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('quotes.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            opportunity_id: selectedOpportunity !== 'all' ? selectedOpportunity : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('quotes.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            opportunity_id: selectedOpportunity !== 'all' ? selectedOpportunity : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('quotes.show', item.id));
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
                handleCopyQuoteLink(item);
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
            formData.total_amount = subtotal + totalTax;
        }

        if (formMode === 'create') {
            toast.loading(t('Creating quote...'));

            router.post(route('quotes.store'), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    } else if (page.props.flash.warning) {
                        toast.warning(t(page.props.flash.warning));
                    }
                },
                onError: (errors) => {
                    toast.dismiss();
                    toast.error(t('Failed to create: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating quote...'));

            router.put(route('quotes.update', currentItem.id), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
                    } else if (page.props.flash.warning) {
                        toast.warning(t(page.props.flash.warning));
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
        toast.loading(t('Deleting quote...'));

        router.delete(route('quotes.destroy', currentItem.id), {
            onSuccess: (page) => {
                setIsDeleteModalOpen(false);
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                } else if (page.props.flash.warning) {
                    toast.warning(t(page.props.flash.warning));
                }
            },
            onError: (errors) => {
                toast.dismiss();
                toast.error(t('Failed to delete: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
        });
    };

    const handleCopyQuoteLink = (quote: any) => {
        const baseUrl = publicUrlBase?.endsWith('/') ? publicUrlBase.slice(0, -1) : publicUrlBase;
        const encryptedId = encryptedQuoteIds[quote.id];
        const quoteUrl = `${baseUrl}/quotes/public/${encryptedId}`;
        navigator.clipboard.writeText(quoteUrl).then(() => {
            toast.success(t('Quote link copied to clipboard!'));
        }).catch(() => {
            toast.error(t('Failed to copy quote link'));
        });
    };

    const handleToggleStatus = (quote: any) => {
        toast.loading(t('Updating quote status...'));

        router.put(route('quotes.toggle-status', quote.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.errors));
                } else if (page.props.flash.warning) {
                    toast.warning(t(page.props.flash.warning));
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
        setSelectedOpportunity('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('quotes.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-quotes')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    if (hasPermission(permissions, 'create-quotes')) {
        pageActions.push({
            label: t('Add Quote'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Quotes') }
    ];

    const columns = [
        {
            key: 'quote_number',
            label: t('Quote Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('quotes.show', item.id)}
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
            key: 'total_amount',
            label: t('Amount'),
            render: (value: any) => window.appSettings?.formatCurrency(Number(value || 0)) || `$${Number(value || 0).toFixed(2)}`
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    draft: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    sent: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    accepted: 'bg-green-50 text-green-700 ring-green-600/20',
                    rejected: 'bg-red-50 text-red-700 ring-red-600/20',
                    expired: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
                };

                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors] || statusColors.draft}`}>
                        {t(value?.charAt(0).toUpperCase() + value?.slice(1)) || t('Draft')}
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
            label: t('Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    const actions = [
        {
            label: t('Copy Quote Link'),
            icon: 'Copy',
            action: 'copy-link',
            className: 'text-purple-500',
            requiredPermission: 'view-quotes'
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-quotes'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-quotes'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-quotes'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-quotes'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'draft', label: t('Draft') },
        { value: 'sent', label: t('Sent') },
        { value: 'accepted', label: t('Accepted') },
        { value: 'rejected', label: t('Rejected') },
        { value: 'expired', label: t('Expired') }
    ];

    return (
        <PageTemplate
            title={t("Quotes")}
            url="/quotes"
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
                            name: 'opportunity_id',
                            label: t('Opportunity'),
                            type: 'select',
                            value: selectedOpportunity,
                            onChange: setSelectedOpportunity,
                            options: [
                                { value: 'all', label: t('All Opportunities') },
                                ...opportunities?.map((opp: any) => ({ value: opp.id.toString(), label: opp.name })) || []
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
                        router.get(route('quotes.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            opportunity_id: selectedOpportunity !== 'all' ? selectedOpportunity : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={quotes?.data || []}
                    from={quotes?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-quotes',
                        create: 'create-quotes',
                        edit: 'edit-quotes',
                        delete: 'delete-quotes'
                    }}
                />

                <Pagination
                    from={quotes?.from || 0}
                    to={quotes?.to || 0}
                    total={quotes?.total || 0}
                    links={quotes?.links}
                    entityName={t("quotes")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <div>
                <CrudFormModal
                    isOpen={isFormModalOpen}
                    onClose={() => setIsFormModalOpen(false)}
                    onSubmit={handleFormSubmit}
                    formConfig={{
                        ...(hasPermission(permissions, 'export-quotes') && { exportRoute: 'quote.export' }),
                        productOptions: products,
                        modalSize: '5xl',
                        layout: 'grid',
                        columns: 2,
                        fields: [
                            { name: 'name', label: t('Name'), type: 'text', required: true, colSpan: 2 },
                            { name: 'description', label: t('Description'), type: 'textarea', colSpan: 2 },
                            {
                                name: formMode === 'view' ? 'opportunity_name' : 'opportunity_id',
                                label: t('Opportunity'),
                                type: formMode === 'view' ? 'text' : 'select',
                                required: true,
                                readOnly: formMode === 'view',
                                options: formMode === 'view' ? [] : opportunities?.map((opp: any) => ({ value: opp.id, label: opp.name })) || [],
                                emptyNote: opportunities?.length === 0 ? {
                                    link: route('opportunities.index'),
                                    linkText: t('Opportunities')
                                } : undefined
                            },
                            {
                                name: formMode === 'view' ? 'account_name' : 'account_id',
                                label: t('Account'),
                                type: formMode === 'view' ? 'text' : 'select',
                                required: true,
                                readOnly: formMode === 'view',
                                options: formMode === 'view' ? [] : accounts?.map((acc: any) => ({ value: acc.id, label: acc.name })) || [],
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
                                options: formMode === 'view' ? [] : contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || [],
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
                                options: formMode === 'view' ? [] : contacts?.map((contact: any) => ({ value: contact.id, label: contact.name })) || [],
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
                                options: formMode === 'view' ? [] : shippingProviderTypes?.map((spt: any) => ({ value: spt.id, label: spt.name })) || [],
                                emptyNote: shippingProviderTypes?.length === 0 ? {
                                    link: route('shipping-provider-types.index'),
                                    linkText: t('Shipping Provider Types')
                                } : undefined
                            },


                            {
                                name: 'status',
                                label: t('Status'),
                                type: 'select',
                                options: [
                                    { value: 'draft', label: t('Draft') },
                                    { value: 'sent', label: t('Sent') },
                                    { value: 'accepted', label: t('Accepted') },
                                    { value: 'rejected', label: t('Rejected') },
                                    { value: 'expired', label: t('Expired') }
                                ],
                                defaultValue: 'draft'
                            },
                            { name: 'valid_until', label: t('Valid Until'), type: 'date' },
                            ...(isCompany ? [{
                                name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                                label: t('Assign To'),
                                type: formMode === 'view' ? 'text' : 'select',
                                required: true,
                                options: formMode === 'view' ? [] : [
                                    ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                                ],
                                readOnly: formMode === 'view',
                                emptyNote: users?.length === 0 ? {
                                    link: route('users.index'),
                                    linkText: t('Users')
                                } : undefined
                            }] : []),
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
                                                        {t('Copy →')}
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
                        ],
                    }}
                    initialData={currentItem ? {
                        ...currentItem,
                        assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                        opportunity_name: currentItem.opportunity?.name || t('-'),
                        account_name: currentItem.account?.name || t('-'),
                        billing_contact_name: currentItem.billing_contact?.name || t('-'),
                        shipping_contact_name: currentItem.shipping_contact?.name || t('-'),
                        shipping_provider_name: currentItem.shipping_provider_type?.name || t('-'),
                        valid_until: formMode === 'view' && currentItem.valid_until ?
                            new Date(currentItem.valid_until).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            }) : currentItem.valid_until,
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
                            ? t('Add Quote')
                            : formMode === 'edit'
                                ? t('Edit Quote')
                                : t('View Quote')
                    }
                    mode={formMode}
                />
            </div>

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('quote')}
            />


        </PageTemplate>
    );
}
