import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, CheckCircle, Clock, Download, FileDown } from 'lucide-react';
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

export default function ProjectTasks() {
    const { t } = useTranslation();
    const { auth, tasks, projects = [], users = [], parentTasks = [], taskStatuses = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || 'all');
    const [selectedProject, setSelectedProject] = useState(pageFilters.project_id || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [activeView, setActiveView] = useState(
        ['list', 'grid'].includes(pageFilters.view) ? pageFilters.view : 'list'
    );

    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedPriority !== 'all' || selectedProject !== 'all' || selectedAssignee !== 'all';
    };

    const activeFilterCount = () => {
        return (selectedStatus !== 'all' ? 1 : 0) + (selectedPriority !== 'all' ? 1 : 0) + (selectedProject !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('project-tasks.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            project_id: selectedProject !== 'all' ? selectedProject : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('project-tasks.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: pageFilters.page || 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
            project_id: selectedProject !== 'all' ? selectedProject : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('project-tasks.show', item.id));
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
            toast.loading(t('Creating task...'));

            router.post(route('project-tasks.store'), formData, {
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
                        toast.error(t('Failed to create: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating task...'));

            router.put(route("project-tasks.update", currentItem.id), formData, {
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
                        toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting task...'));

        router.delete(route('project-tasks.destroy', currentItem.id), {
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
                    toast.error(t('Failed to delete: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleToggleStatus = (task: any) => {
        const newStatus = task.status === 'to_do' ? 'done' : 'to_do';
        toast.loading(t('{{action}} task...', { action: newStatus === 'done' ? t('Completing') : t('Reopening') }));

        router.put(route('project-tasks.toggle-status', task.id), {}, {
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
                    toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedPriority('all');
        setSelectedProject('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('project-tasks.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-project-tasks')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => (CrudFormModal as any).handleExport?.()
        });
    }

    if (hasPermission(permissions, 'create-project-tasks')) {
        pageActions.push({
            label: t('Add Task'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Project Management'), href: route('project-tasks.index') },
        { title: t('Project Tasks') }
    ];

    const columns = [
        {
            key: 'title',
            label: t('Title'),
            sortable: true
        },
        {
            key: 'project',
            label: t('Project'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'parent',
            label: t('Parent Task'),
            render: (value: any) => value?.title || t('-')
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
                        {t(value.charAt(0).toUpperCase() + value.slice(1))}
                    </span>
                );
            }
        },
        {
            key: 'task_status',
            label: t('Status'),
            render: (value: any, item: any) => {
                const taskStatus = taskStatuses.find((ts: any) => ts.id === item.task_status_id);
                if (taskStatus) {
                    return (
                        <div className="flex items-center gap-2">
                            <div
                                className="w-3 h-3 rounded-full"
                                style={{ backgroundColor: taskStatus.color }}
                            ></div>
                            <span className="text-sm font-medium">{taskStatus.name}</span>
                        </div>
                    );
                }
                return <span className="text-sm text-gray-500">{t('No Status')}</span>;
            }
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        },
        {
            key: 'due_date',
            label: t('Due Date'),
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'CheckCircle',
            action: 'toggle-status',
            className: 'text-green-500',
            requiredPermission: 'toggle-status-project-tasks'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-project-tasks'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-project-tasks'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-project-tasks'
        }
    ];

    return (
        <PageTemplate
            title={t("Project Tasks")}
            //   title={t("Project Task Management")}
            url="/project-tasks"
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
                            options: [
                                { value: 'all', label: t('All Status') },
                                ...taskStatuses.map((status: any) => ({
                                    value: status.id.toString(),
                                    label: status.name
                                }))
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
                            name: 'project_id',
                            label: t('Project'),
                            type: 'select',
                            value: selectedProject,
                            onChange: setSelectedProject,
                            options: [
                                { value: 'all', label: t('All Projects') },
                                ...projects.map((project: any) => ({
                                    value: project.id.toString(),
                                    label: project.name
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
                        router.get(route('project-tasks.index'), {
                            view: activeView,
                            page: 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            project_id: selectedProject !== 'all' ? selectedProject : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: parseInt(value),
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('project-tasks.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
                            project_id: selectedProject !== 'all' ? selectedProject : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: pageFilters.per_page || 10,
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {activeView === 'list' ? (
                <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                    <CrudTable
                        columns={columns}
                        actions={actions}
                        data={tasks?.data || []}
                        from={tasks?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-project-tasks',
                            create: 'create-project-tasks',
                            edit: 'edit-project-tasks',
                            delete: 'delete-project-tasks'
                        }}
                    />

                    <Pagination
                        from={tasks?.from || 0}
                        to={tasks?.to || 0}
                        total={tasks?.total || 0}
                        links={tasks?.links}
                        entityName={t("tasks")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {tasks?.data?.map((task: any) => (
                            <Card key={task.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-start space-x-4">
                                            <div className={`h-16 w-16 rounded-full flex items-center justify-center text-lg font-bold`}
                                                style={{ backgroundColor: taskStatuses.find((ts: any) => ts.id === task.task_status_id)?.color || '#6B7280' }}>
                                                {task.status === 'done' ? <CheckCircle className="h-8 w-8" /> : <Clock className="h-8 w-8" />}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{task.title}</h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{task.project?.name || t('No project')}</p>
                                                <div className="flex items-center">
                                                    <div className="h-2 w-2 rounded-full mr-2"
                                                        style={{ backgroundColor: taskStatuses.find((ts: any) => ts.id === task.task_status_id)?.color || '#6B7280' }}></div>
                                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {taskStatuses.find((ts: any) => ts.id === task.task_status_id)?.name || t('No Status')}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                                                {hasPermission(permissions, 'view-project-tasks') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', task)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Task")}</span>
                                                    </DropdownMenuItem>
                                                )}

                                                {hasPermission(permissions, 'toggle-status-project-tasks') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', task)}>
                                                        <CheckCircle className="h-4 w-4 mr-2" />
                                                        <span>{task.status === 'done' ? t("Reopen") : t("Complete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-project-tasks') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', task)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-project-tasks') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', task)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Progress')}: {task.progress}%
                                            </span>
                                            <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${task.progress}%` }}></div>
                                            </div>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Due')}: {task.due_date ? (window.appSettings?.formatDateTime(task.due_date, false) || new Date(task.due_date).toLocaleDateString()) : t('No due date')}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${task.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                    task.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-orange-600/20' :
                                                        task.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                                            'bg-gray-50 text-gray-700 ring-gray-600/20'
                                                }`}>
                                                {t(task.priority.charAt(0).toUpperCase() + task.priority.slice(1))}
                                            </span>
                                            {task.assigned_user && (
                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-purple-50 text-purple-700 ring-purple-600/20">
                                                    {task.assigned_user.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(task.created_at, false) || new Date(task.created_at).toLocaleDateString()}
                                    </div>

                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-project-tasks') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', task)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-project-tasks') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', task)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>

                    <div className="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                        <Pagination
                            from={tasks?.from || 0}
                            to={tasks?.to || 0}
                            total={tasks?.total || 0}
                            links={tasks?.links}
                            entityName={t("tasks")}
                            onPageChange={(url) => router.get(url)}
                        />
                    </div>
                </div>
            )}

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    ...(hasPermission(permissions, 'export-project-tasks') && { exportRoute: 'project-task.export' }),
                    fields: [
                        { name: 'title', label: t('Task Title'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        {
                            name: formMode === 'view' ? 'project_name' : 'project_id',
                            label: t('Project'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('projects.index'), linkText: t('Projects') },
                            options: formMode === 'view' ? [] : projects.map((project: any) => ({
                                value: project.id,
                                label: project.name
                            }))
                        },
                        {
                            name: formMode === 'view' ? 'parent_name' : 'parent_id',
                            label: t('Parent Task'),
                            type: formMode === 'view' ? 'text' : 'select',
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : parentTasks.map((task: any) => ({
                                value: task.id,
                                label: task.title
                            }))
                        },
                        { name: 'start_date', label: t('Start Date'), type: 'date' },
                        { name: 'due_date', label: t('Due Date'), type: 'date' },
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
                            name: 'task_status_id',
                            label: t('Status'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('task-statuses.index'), linkText: t('Task Statuses') },
                            options: taskStatuses.map((status: any) => ({
                                value: status.id,
                                label: status.name
                            })),
                            defaultValue: taskStatuses.find((s: any) => s.name === 'To Do')?.id || taskStatuses[0]?.id
                        },
                        { name: 'estimated_hours', label: t('Estimated Hours'), type: 'number', step: '0.5' },
                        { name: 'actual_hours', label: t('Actual Hours'), type: 'number', step: '0.5' },
                        { name: 'progress', label: t('Progress (%)'), type: 'number', min: '0', max: '100' },
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
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                    project_name: currentItem.project?.name || t('No Project'),
                    parent_name: currentItem.parent?.title || t('No Parent Task')
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Task')
                        : formMode === 'edit'
                            ? t('Edit Task')
                            : t('View Task')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.title || ''}
                entityName={t('task')}
            />
        </PageTemplate>
    );
}
