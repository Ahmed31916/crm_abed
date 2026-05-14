import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Download, FileDown } from 'lucide-react';
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
import { capitalize } from '@/utils/helper';

export default function Projects() {
    const { t } = useTranslation();
    const { auth, projects, accounts = [], users = [], planLimits, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || 'all');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
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
        return searchTerm !== '' || selectedStatus !== 'all' || selectedPriority !== 'all' || selectedAccount !== 'all' || selectedAssignee !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedStatus !== 'all' ? 1 : 0) + (selectedPriority !== 'all' ? 1 : 0) + (selectedAccount !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('projects.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('projects.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: pageFilters.page || 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('projects.show', item.id));
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
            toast.loading(t('Creating project...'));

            router.post(route('projects.store'), formData, {
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
                        toast.error(`Failed to create project: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating project...'));

            router.put(route("projects.update", currentItem.id), formData, {
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
                        toast.error(`Failed to update project: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting project...'));

        router.delete(route('projects.destroy', currentItem.id), {
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
                    toast.error(`Failed to delete project: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleToggleStatus = (project: any) => {
        const newStatus = project.status === 'active' ? 'inactive' : 'active';
        toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} project...`);

        router.put(route('projects.toggle-status', project.id), {}, {
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
                    toast.error(`Failed to update project status: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedPriority('all');
        setSelectedAccount('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('projects.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-projects')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    // Add the "Add Project" button if user has permission
    if (hasPermission(permissions, 'create-projects')) {
        const canCreate = !planLimits || planLimits.can_create;
        pageActions.push({
            label: planLimits && !canCreate ? t('Project Limit Reached ({{current}}/{{max}})', { current: planLimits.current_projects, max: planLimits.max_projects }) : t('Add Project'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: canCreate ? 'default' : 'outline',
            onClick: canCreate ? () => handleAddNew() : () => toast.error(t('Project limit exceeded. Your plan allows maximum {{max}} projects. Please upgrade your plan.', { max: planLimits.max_projects })),
            disabled: !canCreate
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Project Management'), href: route('projects.index') },
        { title: t('Projects') }
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
            render: (value: string) => value || '-'
        },
        {
            key: 'budget',
            label: t('Budget'),
            render: (value: number) => value ? (window.appSettings?.formatCurrency(value) || `$${value.toLocaleString()}`) : '-'
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
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[value as keyof typeof colors] || colors.medium}`}>
                        {t(capitalize(value))}
                    </span>
                );
            }
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const colors = {
                    active: 'bg-green-50 text-green-700 ring-green-600/20',
                    inactive: 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    completed: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    on_hold: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[value as keyof typeof colors] || colors.active}`}>
                        {t(value.replace('_', ' ').charAt(0).toUpperCase() + value.replace('_', ' ').slice(1))}
                    </span>
                );
            }
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || 'Unassigned'
        },
        {
            key: 'start_date',
            label: t('Start Date'),
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'end_date',
            label: t('End Date'),
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
            requiredPermission: 'toggle-status-projects'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-projects'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-projects'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-projects'
        }
    ];

    return (
        <PageTemplate
            title={t("Projects")}
            url="/projects"
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
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: [
                                { value: 'all', label: t('All Status') },
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') },
                                { value: 'completed', label: t('Completed') },
                                { value: 'on_hold', label: t('On Hold') }
                            ]
                        },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            value: selectedPriority,
                            onChange: setSelectedPriority,
                            options: [
                                { value: 'all', label: t('All Priorities') },
                                { value: 'low', label: t('Low') },
                                { value: 'medium', label: t('Medium') },
                                { value: 'high', label: t('High') },
                                { value: 'urgent', label: t('Urgent') }
                            ]
                        },
                        {
                            name: 'account_id',
                            label: t('Account'),
                            type: 'select',
                            value: selectedAccount,
                            onChange: setSelectedAccount,
                            options: [
                                { value: 'all', label: t('All Accounts') },
                                ...accounts.map((account: any) => ({
                                    value: account.id.toString(),
                                    label: account.name
                                }))
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
                        router.get(route('projects.index'), {
                            view: activeView,
                            page: 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: parseInt(value),
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('projects.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
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
                        data={projects?.data || []}
                        from={projects?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-projects',
                            create: 'create-projects',
                            edit: 'edit-projects',
                            delete: 'delete-projects'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={projects?.from || 0}
                        to={projects?.to || 0}
                        total={projects?.total || 0}
                        links={projects?.links}
                        entityName={t("projects")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {projects?.data?.map((project: any) => (
                            <Card key={project.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-start space-x-4">
                                            <div className="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold">
                                                {project.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{project.name}</h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{project.code || 'No code'}</p>
                                                <div className="flex items-center">
                                                    <div className={`h-2 w-2 rounded-full mr-2 ${project.status === 'active' ? 'bg-green-500' :
                                                        project.status === 'completed' ? 'bg-blue-500' :
                                                            project.status === 'on_hold' ? 'bg-yellow-500' : 'bg-gray-400'
                                                        }`}></div>
                                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {t(project.status.replace('_', ' ').charAt(0).toUpperCase() + project.status.replace('_', ' ').slice(1))}
                                                    </span>
                                                </div>
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
                                                {hasPermission(permissions, 'view-projects') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', project)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Project")}</span>
                                                    </DropdownMenuItem>
                                                )}

                                                {hasPermission(permissions, 'toggle-status-projects') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', project)}>
                                                        <span>{project.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-projects') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', project)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-projects') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', project)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Project info */}
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                Account: {project.account?.name || '-'}
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                Budget: {project.budget ? (window.appSettings?.formatCurrency(project.budget) || `$${project.budget.toLocaleString()}`) : '-'}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${project.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                project.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-orange-600/20' :
                                                    project.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                                        'bg-gray-50 text-gray-700 ring-gray-600/20'
                                                }`}>
                                                {t(project.priority.charAt(0).toUpperCase() + project.priority.slice(1))}
                                            </span>
                                            {project.assigned_user && (
                                                <span className="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-900/30 px-2 py-1 text-xs font-medium text-purple-700 dark:text-purple-300">
                                                    {project.assigned_user.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(project.created_at, false) || new Date(project.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-projects') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', project)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-projects') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', project)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'delete-projects') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('delete', project)}
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
                            from={projects?.from || 0}
                            to={projects?.to || 0}
                            total={projects?.total || 0}
                            links={projects?.links}
                            entityName={t("projects")}
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
                    ...(hasPermission(permissions, 'export-projects') && { exportRoute: 'project.export' }),
                    fields: [
                        { name: 'name', label: t('Project Name'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        {
                            name: formMode === 'view' ? 'account_name' : 'account_id',
                            label: t('Account'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('accounts.index'), linkText: t('Accounts') },
                            options: formMode === 'view' ? [] : [
                                ...accounts.map((account: any) => ({
                                    value: account.id,
                                    label: account.name
                                }))
                            ]
                        },
                        { name: 'start_date', label: t('Start Date'), type: 'date' },
                        { name: 'end_date', label: t('End Date'), type: 'date' },
                        { name: 'budget', label: t('Budget'), type: 'number', step: '0.01' },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
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
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') },
                                { value: 'completed', label: t('Completed') },
                                { value: 'on_hold', label: t('On Hold') }
                            ],
                            defaultValue: 'active'
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
                            hidden: !isCompany || (currentItem?.assigned_to && currentItem?.assigned_to === auth?.user?.id)
                        }] : [])
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || 'Unassigned',
                    account_name: currentItem.account?.name || 'No Account'
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Project')
                        : formMode === 'edit'
                            ? t('Edit Project')
                            : t('View Project')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName="project"
            />
        </PageTemplate>
    );
}
