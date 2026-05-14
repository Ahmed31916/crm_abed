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
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CheckCircle, XCircle } from 'lucide-react';


export default function Invoices() {
    const { t } = useTranslation();
    const { auth, invoices, accounts, contacts, salesOrders, quotes, opportunities, products, availableSalesOrders, users = [], filters: pageFilters = {}, publicUrlBase, encryptedInvoiceIds, pendingPayments = [] } = usePage().props as any;
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
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [currentPayment, setCurrentPayment] = useState<any>(null);
    const [rejectNotes, setRejectNotes] = useState('');
    const [showReminderHistoryModal, setShowReminderHistoryModal] = useState(false);
    const [reminderHistory, setReminderHistory] = useState<any[]>([]);



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
        router.get(route('invoices.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('invoices.index'), {
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
                router.get(route('invoices.show', item.id));
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
                handleCopyInvoiceLink(item);
                break;
            case 'send-reminder':
                handleSendReminder(item, 'email');
                break;
            case 'reminder-history':
                handleReminderHistory(item);
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        // Convert empty strings to null for foreign key fields
        const foreignKeyFields = ['sales_order_id', 'quote_id', 'opportunity_id', 'account_id', 'contact_id', 'assigned_to'];
        foreignKeyFields.forEach(field => {
            if (formData[field] === '' || formData[field] === 'null' || formData[field] === 'none') {
                formData[field] = null;
            }
        });

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
            toast.loading(t('Creating invoice...'));

            router.post(route('invoices.store'), formData, {
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
                        toast.error(`Failed to create invoice: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating invoice...'));

            router.put(route('invoices.update', currentItem.id), formData, {
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
                        toast.error(`Failed to update invoice: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting invoice...'));

        router.delete(route('invoices.destroy', currentItem.id), {
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
                    toast.error(`Failed to delete invoice: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleToggleStatus = (invoice: any) => {
        const newStatus = invoice.status === 'draft' ? 'sent' : 'draft';
        toast.loading(`${newStatus === 'sent' ? t('Sending') : t('Setting to draft')} invoice...`);

        router.put(route('invoices.toggle-status', invoice.id), {}, {
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
                    toast.error(`Failed to update status: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleCopyInvoiceLink = (invoice: any) => {
        const baseUrl = publicUrlBase?.endsWith('/') ? publicUrlBase.slice(0, -1) : publicUrlBase;
        const encryptedId = encryptedInvoiceIds[invoice.id];
        const invoiceUrl = `${baseUrl}/invoices/public/${encryptedId}`;
        navigator.clipboard.writeText(invoiceUrl).then(() => {
            toast.success(t('Invoice link copied to clipboard!'));
        }).catch(() => {
            toast.error(t('Failed to copy invoice link'));
        });
    };

    const handleSendReminder = (invoice: any, type: string) => {
        toast.loading(t('Sending payment reminder...'));

        router.post(route('invoices.send-reminder', invoice.id), {
            type
        }, {
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
                    toast.error(`Failed to send reminder: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleReminderHistory = async (invoice: any) => {
        try {
            const response = await fetch(route('invoices.reminder-history', invoice.id));
            const data = await response.json();
            setReminderHistory(data.reminders || []);
            setShowReminderHistoryModal(true);
        } catch (error) {
            toast.error(t('Failed to load reminder history'));
        }
    };

    const handleApprovePayment = async (payment: any) => {
        toast.loading(t('Approving payment...'));
        try {
            const response = await fetch(route('invoice-payments.approve', payment.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            const data = await response.json();
            toast.dismiss();
            if (data.success) {
                if (data.message) {
                    toast.success(t(data.message));
                } else {
                    toast.success(t('Payment approved successfully'));
                }
                router.reload({ only: ['pendingPayments', 'invoices'] });
            } else {
                if (data.message) {
                    toast.error(t(data.message));
                } else {
                    toast.error(t('Failed to approve payment'));
                }
            }
        } catch (error) {
            toast.dismiss();
            toast.error(t('Failed to approve payment'));
        }
    };

    const handleRejectPayment = (payment: any) => {
        setCurrentPayment(payment);
        setShowRejectModal(true);
    };

    const handleRejectConfirm = async () => {
        toast.loading(t('Rejecting payment...'));
        try {
            const response = await fetch(route('invoice-payments.reject', currentPayment.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ notes: rejectNotes })
            });
            const data = await response.json();
            toast.dismiss();
            if (data.success) {
                if (data.message) {
                    toast.success(t(data.message));
                } else {
                    toast.success(t('Payment rejected successfully'));
                }
                setShowRejectModal(false);
                setRejectNotes('');
                router.reload({ only: ['pendingPayments', 'invoices'] });
            } else {
                if (data.message) {
                    toast.error(t(data.message));
                } else {
                    toast.error(t('Failed to reject payment'));
                }
            }
        } catch (error) {
            toast.dismiss();
            toast.error(t('Failed to reject payment'));
        }
    };





    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedAccount('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('invoices.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-invoices')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    if (hasPermission(permissions, 'create-invoices')) {
        pageActions.push({
            label: t('Add Invoice'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Invoices') }
    ];

    const columns = [
        {
            key: 'invoice_number',
            label: t('Invoice Number'),
            sortable: true,
            render: (value: string, item: any) => (
                <Link
                    href={route('invoices.show', item.id)}
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
            label: t('Total Amount'),
            render: (value: any) => window.appSettings?.formatCurrency(Number(value || 0)) || `$${Number(value || 0).toFixed(2)}`
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const statusColors = {
                    draft: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    sent: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    paid: 'bg-green-50 text-green-700 ring-green-600/20',
                    partially_paid: 'bg-orange-50 text-orange-700 ring-orange-600/20',
                    overdue: 'bg-red-50 text-red-700 ring-red-600/20',
                    cancelled: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
                };

                const getStatusLabel = (status: string) => {
                    switch (status) {
                        case 'draft': return t('Draft');
                        case 'sent': return t('Sent');
                        case 'pending': return t('Pending');
                        case 'paid': return t('Paid');
                        case 'partially_paid': return t('Partially Paid');
                        case 'overdue': return t('Overdue');
                        case 'cancelled': return t('Cancelled');
                        default: return t('Draft');
                    }
                };

                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[value as keyof typeof statusColors] || statusColors.draft}`}>
                        {getStatusLabel(value)}
                    </span>
                );
            }
        },
        {
            key: 'due_date',
            label: t('Due Date'),
            sortable: true,
            render: (value: string, item: any) => {
                const formattedDate = window.appSettings?.formatDateTime(value, false) || '-';
                return (
                    <span className={item.status === 'overdue' ? 'text-red-600' : ''}>
                        {formattedDate}
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
            label: t('Send Reminder'),
            icon: 'Mail',
            action: 'send-reminder',
            className: 'text-purple-500',
            requiredPermission: 'send-reminder-invoices',
            condition: (item: any) => ['pending', 'overdue', 'partially_paid'].includes(item.status)
        },
        {
            label: t('Reminder History'),
            icon: 'History',
            action: 'reminder-history',
            className: 'text-indigo-500',
            requiredPermission: 'view-invoices'
        },
        {
            label: t('Copy Invoice Link'),
            icon: 'Copy',
            action: 'copy-link',
            className: 'text-purple-500',
            requiredPermission: 'view-invoices'
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-invoices'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-invoices'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-invoices'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-invoices'
        }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'draft', label: t('Draft') },
        { value: 'sent', label: t('Sent') },
        { value: 'pending', label: t('Pending') },
        { value: 'paid', label: t('Paid') },
        { value: 'partially_paid', label: t('Partially Paid') },
        { value: 'overdue', label: t('Overdue') },
        { value: 'cancelled', label: t('Cancelled') }
    ];

    return (
        <PageTemplate
            title={t("Invoices")}
            url="/invoices"
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
                        router.get(route('invoices.index'), {
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

            {/* Pending Invoice Payments Section */}
            {pendingPayments.length > 0 && (
                <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                    <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('Pending Invoice Payments')}</h3>
                    <div className="space-y-3">
                        {pendingPayments.map((payment: any) => (
                            <div key={payment.id} className="flex items-center justify-between p-3 border rounded-lg">
                                <div className="flex-1">
                                    <div className="flex items-center space-x-4">
                                        <div>
                                            <p className="font-medium text-gray-900">
                                                Invoice #{payment.invoice.invoice_number}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {payment.payment_method === 'bank' ? t('Bank Transfer') : payment.payment_method} -
                                                {window.appSettings?.formatCurrency(Number(payment.amount)) || `$${Number(payment.amount).toFixed(2)}`} ({payment.payment_type})
                                            </p>
                                        </div>
                                        <div className="text-sm text-gray-500">
                                            {t('Requested')}: {window.appSettings?.formatDateTime(payment.created_at, false) || new Date(payment.created_at).toLocaleDateString()}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="text-green-600 border-green-600 hover:bg-green-50"
                                        onClick={() => handleApprovePayment(payment)}
                                    >
                                        <CheckCircle className="h-4 w-4 mr-1" />
                                        {t('Approve')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="text-red-600 border-red-600 hover:bg-red-50"
                                        onClick={() => handleRejectPayment(payment)}
                                    >
                                        <XCircle className="h-4 w-4 mr-1" />
                                        {t('Reject')}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={invoices?.data || []}
                    from={invoices?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-invoices',
                        create: 'create-invoices',
                        edit: 'edit-invoices',
                        delete: 'delete-invoices'
                    }}
                />

                <Pagination
                    from={invoices?.from || 0}
                    to={invoices?.to || 0}
                    total={invoices?.total || 0}
                    links={invoices?.links}
                    entityName={t("invoices")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    ...(hasPermission(permissions, 'export-invoices') && { exportRoute: 'invoice.export' }),
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
                                ...salesOrders?.map((so: any) => ({ value: so.id.toString(), label: `${so.order_number} - ${so.name}` })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'quote_name' : 'quote_id',
                            label: t('Quote'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('quotes.index'), linkText: t('Quotes') },
                            options: formMode === 'view' ? [] : [
                                ...quotes?.map((quote: any) => ({ value: quote.id.toString(), label: `${quote.quote_number} - ${quote.name}` })) || []
                            ]
                        },
                        {
                            name: formMode === 'view' ? 'opportunity_name' : 'opportunity_id',
                            label: t('Opportunity'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('opportunities.index'), linkText: t('Opportunities') },
                            options: formMode === 'view' ? [] : [
                                ...opportunities?.map((opp: any) => ({ value: opp.id.toString(), label: opp.name })) || []
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
                                ...accounts?.map((account: any) => ({ value: account.id.toString(), label: account.name })) || []
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
                                ...contacts?.map((contact: any) => ({ value: contact.id.toString(), label: contact.name })) || []
                            ]
                        },
                        { name: 'invoice_date', label: t('Invoice Date'), type: 'date', required: true, defaultValue: new Date().toISOString().split('T')[0] },
                        { name: 'due_date', label: t('Due Date'), type: 'date', required: true },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'draft', label: t('Draft') },
                                { value: 'sent', label: t('Sent') },
                                { value: 'pending', label: t('Pending') },
                                { value: 'paid', label: t('Paid') },
                                { value: 'partially_paid', label: t('Partially Paid') },
                                { value: 'overdue', label: t('Overdue') },
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
                                    {(currentItem?.status == 'paid' || currentItem?.status == 'cancelled') && <span className='text-xs text-red-500'>{t('You cannot modify products when the order status is Cancelled or Paid.')}</span>}
                                </div>
                            )
                        },
                        {
                            name: 'products',
                            label: '',
                            type: 'array',
                            disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
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
                                            <td colSpan={4} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Total Discount')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right text-red-600">
                                                -{window.appSettings?.formatCurrency(totalDiscount) || `$${totalDiscount.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-50">
                                            <td colSpan={4} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Subtotal')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {window.appSettings?.formatCurrency(subtotal) || `$${subtotal.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-50">
                                            <td colSpan={4} className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {t('Total Tax')}:
                                            </td>
                                            <td className="border border-gray-200 px-3 py-2 text-sm text-right">
                                                {window.appSettings?.formatCurrency(totalTax) || `$${totalTax.toFixed(2)}`}
                                            </td>
                                        </tr>
                                        <tr className="bg-gray-100 font-bold">
                                            <td colSpan={4} className="border border-gray-200 px-3 py-2 text-sm text-right">
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
                                    disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
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
                                    disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
                                    min: '1',
                                    defaultValue: 1,
                                },
                                {
                                    name: 'unit_price',
                                    label: t('Unit Price'),
                                    type: 'number',
                                    required: true,
                                    disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
                                    step: '0.01',
                                    min: '0'
                                },
                                {
                                    name: 'discount_type',
                                    label: t('Discount Type'),
                                    type: 'select',
                                    disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
                                    options: [
                                        { value: 'percentage', label: t('Percentage (%)') },
                                        { value: 'fixed', label: t('Fixed Amount') }
                                    ]
                                },
                                {
                                    name: 'discount_value',
                                    label: t('Discount Value'),
                                    type: 'number',
                                    disabled:currentItem?.status == 'paid' || currentItem?.status == 'cancelled',
                                    step: '0.01',
                                    min: '0'
                                }
                            ]
                        },
                        {
                            name: 'billing_section',
                            type: 'custom',
                            colSpan: 2,
                            render: (field: any, formData: any, handleChange: any) => (
                                <div className="col-span-2 border-t pt-4 mt-4">
                                    <h3 className="text-lg font-semibold text-gray-800 mb-3">{t('Billing Information')}</h3>
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
                            )
                        },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            emptyNote: { link: route('users.index'), linkText: t('Users') },
                            options: formMode === 'view' ? [] : [
                                ...users.map((user: any) => ({ value: user.id.toString(), label: `${user.name} (${user.email})` }))
                            ],
                            readOnly: formMode === 'view'
                        }] : []),
                        { name: 'notes', label: t('Notes'), type: 'textarea', colSpan: 2 },
                        { name: 'terms', label: t('Terms'), type: 'textarea', colSpan: 2 }
                    ]
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                    sales_order_name: currentItem.sales_order?.name || t('-'),
                    quote_name: currentItem.quote?.name || t('-'),
                    opportunity_name: currentItem.opportunity?.name || t('-'),
                    account_name: currentItem.account?.name || t('-'),
                    contact_name: currentItem.contact?.name || t('-'),
                    // Ensure foreign key IDs are properly set as strings for select fields
                    sales_order_id: currentItem.sales_order_id ? currentItem.sales_order_id.toString() : 'none',
                    quote_id: currentItem.quote_id ? currentItem.quote_id.toString() : 'none',
                    opportunity_id: currentItem.opportunity_id ? currentItem.opportunity_id.toString() : 'none',
                    account_id: currentItem.account_id ? currentItem.account_id.toString() : 'none',
                    contact_id: currentItem.contact_id ? currentItem.contact_id.toString() : 'none',
                    assigned_to: currentItem.assigned_to ? currentItem.assigned_to.toString() : 'none',
                    invoice_date: formMode === 'view' && currentItem.invoice_date ?
                        new Date(currentItem.invoice_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.invoice_date,
                    due_date: formMode === 'view' && currentItem.due_date ?
                        new Date(currentItem.due_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        }) : currentItem.due_date,
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
                        ? t('Add Invoice')
                        : formMode === 'edit'
                            ? t('Edit Invoice')
                            : t('View Invoice')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('invoice')}
            />

            {/* Reject Payment Modal */}
            <Dialog open={showRejectModal} onOpenChange={setShowRejectModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Reject Payment')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <p className="text-sm text-gray-600">
                            {t('Are you sure you want to reject this payment for Invoice #{{invoiceNumber}}?', { invoiceNumber: currentPayment?.invoice?.invoice_number })}
                        </p>
                        <div>
                            <Label htmlFor="reject-notes">{t('Rejection Notes (Optional)')}</Label>
                            <Textarea
                                id="reject-notes"
                                value={rejectNotes}
                                onChange={(e) => setRejectNotes(e.target.value)}
                                placeholder={t('Enter reason for rejection...')}
                                className="mt-1"
                            />
                        </div>
                        <div className="flex justify-end space-x-2">
                            <Button variant="outline" onClick={() => setShowRejectModal(false)}>
                                {t('Cancel')}
                            </Button>
                            <Button variant="destructive" onClick={handleRejectConfirm}>
                                {t('Reject Payment')}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Reminder History Modal */}
            <Dialog open={showReminderHistoryModal} onOpenChange={setShowReminderHistoryModal}>
                <DialogContent className="max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                    <DialogHeader>
                        <DialogTitle>{t('Invoice Payment Reminder History')}</DialogTitle>
                    </DialogHeader>
                    <div className="mt-4 overflow-y-auto flex-1">
                        {reminderHistory.length === 0 ? (
                            <p className="text-sm text-gray-500 text-center py-4">{t('No reminders sent yet')}</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">{t('Sent At')}</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">{t('Type')}</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 tracking-wider">{t('Sent By')}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {reminderHistory.map((reminder: any, index: number) => (
                                            <tr key={index}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {window.appSettings?.formatDateTime(reminder.created_at, false) || new Date(reminder.created_at).toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-purple-50 text-purple-700 ring-purple-600/20">
                                                        {reminder.type}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{reminder.sent_by?.name || t('-')}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>

        </PageTemplate>
    );
}
