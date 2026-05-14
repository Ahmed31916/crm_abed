import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Download, Lock, Unlock, FileDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function Cases() {
    const { t } = useTranslation();
    const { auth, cases, accounts, contacts, users, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || 'all');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedCaseType, setSelectedCaseType] = useState(pageFilters.case_type || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [activeView, setActiveView] = useState(
        ['list', 'grid'].includes(pageFilters.view) ? pageFilters.view : 'list'
    );

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedAccount !== 'all' || selectedPriority !== 'all' || selectedStatus !== 'all' || selectedCaseType !== 'all' || selectedAssignee !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedAccount !== 'all' ? 1 : 0) + (selectedPriority !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedCaseType !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('cases.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            case_type: selectedCaseType !== 'all' ? selectedCaseType : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('cases.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: pageFilters.page || 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            case_type: selectedCaseType !== 'all' ? selectedCaseType : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('cases.show', item.id));
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
            toast.loading(t('Creating case...'));

            router.post(route('cases.store'), formData, {
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
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(t('Failed to create case: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating case...'));

            router.put(route('cases.update', currentItem.id), formData, {
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
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(t('Failed to update case: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting case...'));

        router.delete(route('cases.destroy', currentItem.id), {
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
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(t('Failed to delete case: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleToggleStatus = (caseItem: any) => {
        const newStatus = caseItem.status === 'new' ? 'closed' : 'new';
        toast.loading(`${newStatus === 'new' ? t('Opening') : t('Closing')} case...`);

        router.put(route('cases.toggle-status', caseItem.id), {}, {
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
                    toast.error(t('Failed to update case status: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedAccount('all');
        setSelectedPriority('all');
        setSelectedStatus('all');
        setSelectedCaseType('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('cases.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    if (hasPermission(permissions, 'export-cases')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => window.location.href = route('case.export')
        });
    }

    // Add the "Add Case" button if user has permission
    if (hasPermission(permissions, 'create-cases')) {
        pageActions.push({
            label: t('Add Case'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Cases') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'subject',
            label: t('Subject'),
            sortable: true,
            render: (value: any, row: any) => {
                return (
                    <div className="font-medium">{row.subject}</div>
                );
            }
        },
        {
            key: 'account',
            label: t('Account'),
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'priority',
            label: t('Priority'),
            render: (value: string) => {
                const colors = {
                    low: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    medium: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    high: 'bg-orange-50 text-orange-700 ring-orange-600/20',
                    urgent: 'bg-red-50 text-red-700 ring-red-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[value as keyof typeof colors]}`}>
                        {value.charAt(0).toUpperCase() + value.slice(1)}
                    </span>
                );
            }
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const colors = {
                    new: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    pending: 'bg-orange-50 text-orange-700 ring-orange-600/20',
                    resolved: 'bg-green-50 text-green-700 ring-green-600/20',
                    closed: 'bg-gray-50 text-gray-700 ring-gray-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[value as keyof typeof colors]}`}>
                        {value.replace('_', ' ').charAt(0).toUpperCase() + value.replace('_', ' ').slice(1)}
                    </span>
                );
            }
        },
        {
            key: 'case_type',
            label: t('Type'),
            render: (value: string) => value.replace('_', ' ').charAt(0).toUpperCase() + value.replace('_', ' ').slice(1)
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

    // Define table actions
    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-cases'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-cases'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-cases'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-cases'
        }
    ];

    // Prepare filter options
    const accountOptions = [
        { value: 'all', label: t('All Accounts') },
        ...(accounts || []).map((account: any) => ({
            value: account.id.toString(),
            label: account.name
        }))
    ];

    const priorityOptions = [
        { value: 'all', label: t('All Priorities') },
        { value: 'low', label: t('Low') },
        { value: 'medium', label: t('Medium') },
        { value: 'high', label: t('High') },
        { value: 'urgent', label: t('Urgent') }
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'new', label: t('New') },
        { value: 'in_progress', label: t('In Progress') },
        { value: 'pending', label: t('Pending') },
        { value: 'resolved', label: t('Resolved') },
        { value: 'closed', label: t('Closed') }
    ];

    const caseTypeOptions = [
        { value: 'all', label: t('All Types') },
        { value: 'support', label: t('Support') },
        { value: 'bug', label: t('Bug') },
        { value: 'feature_request', label: t('Feature Request') },
        { value: 'complaint', label: t('Complaint') },
        { value: 'inquiry', label: t('Inquiry') }
    ];

    return (
        <PageTemplate
            title={t("Cases")}
            url="/cases"
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
                            name: 'account_id',
                            label: t('Account'),
                            type: 'select',
                            value: selectedAccount,
                            onChange: setSelectedAccount,
                            options: accountOptions
                        },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            value: selectedPriority,
                            onChange: setSelectedPriority,
                            options: priorityOptions
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
                            name: 'case_type',
                            label: t('Type'),
                            type: 'select',
                            value: selectedCaseType,
                            onChange: setSelectedCaseType,
                            options: caseTypeOptions
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
                        router.get(route('cases.index'), {
                            view: activeView,
                            page: 1,
                            search: searchTerm || undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            case_type: selectedCaseType !== 'all' ? selectedCaseType : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: parseInt(value),
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('cases.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            case_type: selectedCaseType !== 'all' ? selectedCaseType : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: pageFilters.per_page || 10,
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
                        data={cases?.data || []}
                        from={cases?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-cases',
                            create: 'create-cases',
                            edit: 'edit-cases',
                            delete: 'delete-cases'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={cases?.from || 0}
                        to={cases?.to || 0}
                        total={cases?.total || 0}
                        links={cases?.links}
                        entityName={t("cases")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {cases?.data?.map((caseItem: any) => (
                            <Card key={caseItem.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1 min-w-0">
                                            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{caseItem.subject}</h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">#{caseItem.id}</p>
                                            <div className="flex items-center mb-2">
                                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${caseItem.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                    caseItem.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-orange-600/20' :
                                                        caseItem.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                                            'bg-gray-50 text-gray-700 ring-gray-600/20'
                                                    }`}>
                                                    {caseItem.priority.charAt(0).toUpperCase() + caseItem.priority.slice(1)}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Actions dropdown */}
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                                                {hasPermission(permissions, 'view-cases') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', caseItem)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Case")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'toggle-status-cases') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', caseItem)}>
                                                        {caseItem.status === 'new' ?
                                                            <Lock className="h-4 w-4 mr-2" /> :
                                                            <Unlock className="h-4 w-4 mr-2" />
                                                        }
                                                        <span>{caseItem.status === 'new' ? t("Close") : t("Reopen")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-cases') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', caseItem)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-cases') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', caseItem)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Case info */}
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {t('Account')}: <span className="text-primary">{caseItem.account?.name || '-'}</span>
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Contact')}: {caseItem.contact?.name || '-'}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${caseItem.status === 'new' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                                caseItem.status === 'in_progress' ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20' :
                                                    caseItem.status === 'pending' ? 'bg-orange-50 text-orange-700 ring-orange-600/20' :
                                                        caseItem.status === 'resolved' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                                                            'bg-gray-50 text-gray-700 ring-gray-600/20'
                                                }`}>
                                                {caseItem.status.replace('_', ' ').charAt(0).toUpperCase() + caseItem.status.replace('_', ' ').slice(1)}
                                            </span>
                                            <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-purple-50 text-purple-700 ring-purple-600/20">
                                                {caseItem.case_type.replace('_', ' ').charAt(0).toUpperCase() + caseItem.case_type.replace('_', ' ').slice(1)}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(caseItem.created_at, false) || new Date(caseItem.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-cases') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', caseItem)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-cases') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', caseItem)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'delete-cases') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('delete', caseItem)}
                                                className="flex-1 h-9 text-sm text-gray-700 border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Trash2 className="h-4 w-4 mr-2" />
                                                {t("Delete")}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>

                    {/* Pagination for grid view */}
                    <div className="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                        <Pagination
                            from={cases?.from || 0}
                            to={cases?.to || 0}
                            total={cases?.total || 0}
                            links={cases?.links}
                            entityName={t("cases")}
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
                    fields: [
                        { name: 'subject', label: t('Subject'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        {
                            name: 'account_id',
                            label: t('Account'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('accounts.index'), linkText: t('Accounts') },
                            options: accounts ? accounts.map((account: any) => ({
                                value: account.id.toString(),
                                label: account.name
                            })) : []
                        },
                        {
                            name: 'contact_id',
                            label: t('Contact'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('contacts.index'), linkText: t('Contacts') },
                            options: [
                                ...contacts ? contacts.map((contact: any) => ({
                                    value: contact.id.toString(),
                                    label: `${contact.name} (${contact.account?.name || 'No Account'})`
                                })) : []
                            ]
                        },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'low', label: t('Low') },
                                { value: 'medium', label: t('Medium') },
                                { value: 'high', label: t('High') },
                                { value: 'urgent', label: t('Urgent') }
                            ],
                            defaultValue: 'medium'
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'new', label: t('New') },
                                { value: 'in_progress', label: t('In Progress') },
                                { value: 'pending', label: t('Pending') },
                                { value: 'resolved', label: t('Resolved') },
                                { value: 'closed', label: t('Closed') }
                            ],
                            defaultValue: 'new'
                        },
                        {
                            name: 'case_type',
                            label: t('Case Type'),
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'support', label: t('Support') },
                                { value: 'bug', label: t('Bug') },
                                { value: 'feature_request', label: t('Feature Request') },
                                { value: 'complaint', label: t('Complaint') },
                                { value: 'inquiry', label: t('Inquiry') }
                            ],
                            defaultValue: 'support'
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
                            readOnly: formMode === 'view',
                            hidden: !isCompany || (formMode === 'create' && auth?.user?.type === 'staff')
                        }] : [])
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned')
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Case')
                        : formMode === 'edit'
                            ? t('Edit Case')
                            : t('View Case')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.subject || ''}
                entityName={t('case')}
            />
        </PageTemplate>
    );
}
