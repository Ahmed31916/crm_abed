import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission, hasRole } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export default function LoginHistory() {
    const { t } = useTranslation();
    const { auth, loginHistory, filters: pageFilters = {}, globalSettings } = usePage().props as any;
    const permissions = auth?.permissions || [];

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [showFilters, setShowFilters] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isViewModalOpen, setIsViewModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== '';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (searchTerm !== '' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('login-history.index'), {
            page: 1,
            search: searchTerm || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('login-history.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                setIsViewModalOpen(true);
                break;
            case 'delete':
                setIsDeleteModalOpen(true);
                break;
        }
    };

    const handleDeleteConfirm = () => {
        if (!globalSettings?.is_demo) {
            toast.loading(t('Deleting login history...'));
        }

        router.delete(route('login-history.destroy', currentItem.id), {
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
                    toast.error(t('Failed to delete login history: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setShowFilters(false);

        router.get(route('login-history.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    let breadcrumbs = [];
    if (hasRole('superadmin', auth.roles)) {
        breadcrumbs = [
            { title: t('Dashboard'), href: route('dashboard') },
            { title: t('Companies'), href: route('companies.index') },
            { title: t('Login History') }
        ];
    } else {
        breadcrumbs = [
            { title: t('Dashboard'), href: route('dashboard') },
            { title: t('Staff'), href: route('users.index') },
            { title: t('Login History') }
        ];
    }

    // Define table columns
    const columns = [
        {
            key: 'user.name',
            label: t('User'),
            render: (_, row) => (
                <div>
                    <div className="font-medium">{row.user?.name || '-'}</div>
                    <div className="text-xs text-gray-500">{row.user?.email || ''}</div>
                </div>
            )
        },
        {
            key: 'user.type',
            label: t('User Type'),
            render: (_, row) => {
                const userType = row.user?.type || '-';
                return userType.charAt(0).toUpperCase() + userType.slice(1);
            }
        },
        {
            key: 'ip',
            label: t('IP Address'),
            sortable: true,
            render: (value) => value || '-'
        },
        {
            key: 'date',
            label: t('Login Date'),
            sortable: true,
            render: (value) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'details',
            label: t('Details'),
            render: (value) => {
                try {
                    return (
                        <div className="text-xs">
                            <div>{value.browser_name || '-'}</div>
                            <div className="text-gray-500">{value.os_name || '-'}</div>
                        </div>
                    );
                } catch {
                    return '-';
                }
            }
        }
    ];

    // Define table actions
    const actions = [
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'show-login-history'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-login-history'
        }
    ];

    return (
        <PageTemplate
            title={t("Login History")}
            url="/login-history"
            breadcrumbs={breadcrumbs}
            noPadding
        >
            {/* Search and filters section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={pageFilters.per_page?.toString() || "10"}
                    onPerPageChange={(value) => {
                        router.get(route('login-history.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Content section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={loginHistory?.data || []}
                    from={loginHistory?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        delete: 'delete-login-history'
                    }}
                />

                {/* Pagination section */}
                <Pagination
                    from={loginHistory?.from || 0}
                    to={loginHistory?.to || 0}
                    total={loginHistory?.total || 0}
                    links={loginHistory?.links}
                    entityName={t("login records")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={`${currentItem?.user?.name || ''} `}
                itemType={t('login history')}
            />

            {/* View Modal */}
            <Dialog open={isViewModalOpen} onOpenChange={setIsViewModalOpen}>
                <DialogContent className="max-w-xl max-h-[80vh] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <DialogHeader>
                        <DialogTitle>{t('Login Details')}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="flex justify-between py-2 border-b border-gray-100">
                            <span className="text-gray-600">{t('User')}</span>
                            <span className="font-medium">{currentItem?.user?.name || '-'}</span>
                        </div>
                        <div className="flex justify-between py-2 border-b border-gray-100">
                            <span className="text-gray-600">{t('Email')}</span>
                            <span className="font-medium">{currentItem?.user?.email || '-'}</span>
                        </div>
                        <div className="flex justify-between py-2 border-b border-gray-100">
                            <span className="text-gray-600">{t('User Type')}</span>
                            <span className="font-medium">{currentItem?.user?.type ? currentItem.user.type.charAt(0).toUpperCase() + currentItem.user.type.slice(1) : '-'}</span>
                        </div>
                        <div className="flex justify-between py-2 border-b border-gray-100">
                            <span className="text-gray-600">{t('IP Address')}</span>
                            <span className="font-medium">{currentItem?.ip || '-'}</span>
                        </div>
                        <div className="flex justify-between py-2 border-b border-gray-100">
                            <span className="text-gray-600">{t('Login Date')}</span>
                            <span className="font-medium">{window.appSettings?.formatDateTime(currentItem?.date, false) || '-'}</span>
                        </div>
                        {(() => {
                            try {
                                const details = JSON.parse(currentItem?.Details || '{}');
                                return Object.entries(details).map(([key, value]) => (
                                    <div key={key} className="flex justify-between py-2 border-b border-gray-100">
                                        <span className="text-gray-600 capitalize">{key.replace(/_/g, ' ')}</span>
                                        <span className="font-medium">{String(value) || '-'}</span>
                                    </div>
                                ));
                            } catch {
                                return null;
                            }
                        })()}
                    </div>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
