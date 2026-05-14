// pages/plans/plan-orders.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { CrudTable } from '@/components/CrudTable';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { capitalize, getDisplayUrl } from '@/utils/helper';

export default function PlanOrdersPage() {
    const { t } = useTranslation();
    const { planOrders, filters: pageFilters = {}, auth, globalSettings } = usePage().props as any;
    const permissions = auth?.permissions || [];

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '_empty_');
    const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
    const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
    const [showFilters, setShowFilters] = useState(false);
    const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);

    // Check if any filters are active
    const hasActiveFilters = () => {
        return selectedStatus !== '_empty_' || dateFrom !== '' || dateTo !== '' || searchTerm !== '';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedStatus !== '_empty_' ? 1 : 0) +
            (dateFrom !== '' ? 1 : 0) +
            (dateTo !== '' ? 1 : 0) +
            (searchTerm !== '' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('plan-orders.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('plan-orders.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        if (action === 'approve') {
            if (!globalSettings?.is_demo) {
                toast.loading(t('Approving plan order...'));
            }

            router.post(route('plan-orders.approve', item.id), {}, {
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
                        toast.error(t('Failed to approve plan order: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (action === 'reject') {
            setCurrentItem(item);
            setIsRejectModalOpen(true);
        }
    };

    const handleRejectConfirm = (notes: string) => {
        if (!globalSettings?.is_demo) {
            toast.loading(t('Rejecting plan order...'));
        }

        router.post(route('plan-orders.reject', currentItem.id), { notes }, {
            onSuccess: (page) => {
                setIsRejectModalOpen(false);
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
                    toast.error(t('Failed to reject plan order: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('_empty_');
        setDateFrom('');
        setDateTo('');
        setShowFilters(false);

        router.get(route('plan-orders.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Plans'), href: route('plans.index') },
        { title: t('Plan Orders') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'order_number',
            label: t('Order Number'),
            render: (value) => value || '-'
        },
        {
            key: 'user.name',
            label: t('Name'),
            render: (_, row) => {
                const avatarUrl = row.user?.avatar ? getDisplayUrl(row.user.avatar) : getDisplayUrl('avatars/avatar.png');
                return (
                    <div className="flex items-center gap-3">
                        <div>
                            <div className="font-medium">{row.user?.name || '-'}</div>
                            <div className="text-xs text-gray-500">{row.user?.email || ''}</div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'plan.name',
            label: t('Plan'),
            render: (_, row) => {
                const planName = row.plan?.name;
                if (!planName) return '-';
                return (
                    <span className={'inline-flex items-center rounded-md px-2 py-1 text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'}>
                        {capitalize(planName)}
                    </span>
                );
            }
        },
        {
            key: 'original_price',
            label: t('Original Price'),
            render: (value) => window.appSettings.formatCurrency(value) || '0'
        },
        {
            key: 'discount_amount',
            label: t('Discount'),
            render: (value) => value > 0 ? `-${window.appSettings.formatCurrency(value)}` : '-'
        },
        {
            key: 'final_price',
            label: t('Final Price'),
            sortable: true,
            render: (value) => window.appSettings.formatCurrency(value) || '0'
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value) => {
                const statusColors: Record<string, string> = {
                    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    approved: 'bg-green-50 text-green-700 ring-green-600/20',
                    rejected: 'bg-red-50 text-red-700 ring-red-600/20',
                    completed: 'bg-blue-50 text-blue-700 ring-blue-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset capitalize ${statusColors[value] || 'bg-gray-50 text-gray-700 ring-gray-600/20'}`}>
                        {t(value)}
                    </span>
                );
            }
        },
        {
            key: 'ordered_at',
            label: t('Order Date'),
            sortable: true,
            render: (value) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    // Define table actions - only visible to super admin
    const isSuperAdmin = auth?.user?.type === 'superadmin';
    const actions = isSuperAdmin ? [
        {
            label: t('Approve'),
            icon: 'Check',
            action: 'approve',
            className: 'text-green-500',
            requiredPermission: 'approve-plan-orders',
            condition: (row) => row.status === 'pending'
        },
        {
            label: t('Reject'),
            icon: 'X',
            action: 'reject',
            className: 'text-red-500',
            requiredPermission: 'reject-plan-orders',
            condition: (row) => row.status === 'pending'
        }
    ] : [];

    // Prepare status options for filter
    const statusOptions = [
        { value: '_empty_', label: t('All Status') },
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'rejected', label: t('Rejected') },
        { value: 'completed', label: t('Completed') }
    ];

    return (
        <PageTemplate
            title={t('Plan Orders')}
            url="/plan-orders"
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
                        router.get(route('plan-orders.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
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
                    data={planOrders?.data || []}
                    from={planOrders?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                />

                {/* Pagination section */}
                <Pagination
                    from={planOrders?.from || 0}
                    to={planOrders?.to || 0}
                    total={planOrders?.total || 0}
                    links={planOrders?.links}
                    entityName={t("plan orders")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            {/* Reject Modal */}
            <Dialog open={isRejectModalOpen} onOpenChange={setIsRejectModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Reject Plan Order')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        const formData = new FormData(e.currentTarget);
                        const notes = formData.get('notes') as string;
                        handleRejectConfirm(notes);
                    }}>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="notes">{t('Rejection Reason (Optional)')}</Label>
                                <Textarea
                                    id="notes"
                                    name="notes"
                                    placeholder={t('Enter rejection reason...')}
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => setIsRejectModalOpen(false)}>
                                {t('Cancel')}
                            </Button>
                            <Button type="submit" variant="destructive">
                                {t('Reject')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
