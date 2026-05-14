import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Card } from '@/components/ui/card';
import { Eye, Edit, Trash2, User, Calendar, Building2, MoreHorizontal } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { hasPermission } from '@/utils/authorization';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { capitalize, getDisplayUrl } from '@/utils/helper';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

export default function ProjectKanban() {
    const { t } = useTranslation();
    const { auth, project, kanbanData, statuses, users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [prefilledStatus, setPrefilledStatus] = useState<string>('');
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || 'all');
    const [showFilters, setShowFilters] = useState(!!(pageFilters.status || pageFilters.priority || pageFilters.search));
    const getInitials = useInitials();

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
        }
    };

    const handleAddTask = (status: string) => {
        setCurrentItem(null);
        setFormMode('create');
        setPrefilledStatus(status);
        setIsFormModalOpen(true);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('projects.kanban', project.id), {
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            priority: selectedPriority !== 'all' ? selectedPriority : undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedPriority('all');
        setShowFilters(false);
        router.get(route('projects.kanban', project.id), {}, { preserveState: true, preserveScroll: true });
    };

    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedStatus !== 'all' || selectedPriority !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedPriority !== 'all' ? 1 : 0);
    };

    const handleFormSubmit = (formData: any) => {
        if (formMode === 'create') {
            toast.loading(t('Creating task...'));

            const taskData = {
                ...formData,
                project_id: project.id,
                task_status_id: prefilledStatus ? parseInt(prefilledStatus) : (formData.task_status_id ? parseInt(formData.task_status_id) : null)
            };

            router.post(route('project-tasks.store'), taskData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    }
                    router.reload();
                },
                onError: (errors) => {
                    toast.dismiss();
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(`Failed to create task: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        } else if (formMode === 'edit') {
            if (!hasPermission(permissions, 'edit-project-tasks')) {
                toast.error(t('Permission denied.'));
                return;
            }

            toast.loading(t('Updating task...'));

            // Ensure task_status_id is properly formatted
            const updateData = {
                ...formData,
                task_status_id: formData.task_status_id ? parseInt(formData.task_status_id) : null
            };

            router.put(route('project-tasks.update', currentItem.id), updateData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    }
                    router.reload();
                },
                onError: (errors) => {
                    toast.dismiss();
                    if (typeof errors === 'string') {
                        toast.error(errors);
                    } else {
                        toast.error(`Failed to update task: ${Object.values(errors).join(', ')}`);
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
                }
                router.reload();
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(`Failed to delete task: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const pageActions = [
        {
            label: t('Back'),
            icon: <ArrowLeft className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => router.get(route('projects.show', project.id))
        }
    ];

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Projects'), href: route('projects.index') },
        { title: project.name, href: route('projects.show', project.id) },
        { title: t('Kanban View') }
    ];

    return (
        <PageTemplate
            title={`${project.name} - ${t('Kanban View')}`}
            url={`/projects/${project.id}/kanban`}
            actions={pageActions}
            breadcrumbs={breadcrumbs}
            noPadding
            className="overflow-hidden"
        >
            <style>{`
        .kanban-scroll::-webkit-scrollbar {
          height: 8px;
        }
        .kanban-scroll::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 4px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 4px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
        .column-scroll::-webkit-scrollbar {
          width: 6px;
        }
        .column-scroll::-webkit-scrollbar-track {
          background: #f8fafc;
          border-radius: 3px;
        }
        .column-scroll::-webkit-scrollbar-thumb {
          background: #e2e8f0;
          border-radius: 3px;
        }
        .column-scroll::-webkit-scrollbar-thumb:hover {
          background: #cbd5e1;
        }
      `}</style>

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
                                ...statuses.map((status: any) => ({ value: status.id, label: status.name }))
                            ]
                        },
                        {
                            name: 'priority',
                            label: t('Priority'),
                            type: 'select',
                            value: selectedPriority,
                            onChange: setSelectedPriority,
                            options: [
                                { value: 'all', label: t('All Priority') },
                                { value: 'low', label: t('Low') },
                                { value: 'medium', label: t('Medium') },
                                { value: 'high', label: t('High') },
                                { value: 'urgent', label: t('Urgent') }
                            ]
                        }
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    hidePerPage={true}
                    hideViewToggle={true}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <div className="bg-gray-50 p-4 rounded-lg overflow-hidden">
                    <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll" style={{ height: 'calc(100vh - 250px)', width: '100%' }}>
                        {statuses.map((status) => {
                            const statusTasks = Object.values(kanbanData).find((column: any) => column.status?.id === status.id)?.tasks || [];
                            return (
                                <div
                                    key={status.id}
                                    className="flex-shrink-0"
                                    style={{ minWidth: 'calc(20% - 16px)', width: 'calc(20% - 16px)' }}
                                    onDrop={(e) => {
                                        e.preventDefault();
                                        e.currentTarget.classList.remove('bg-blue-50');
                                        const taskId = e.dataTransfer.getData('taskId');
                                        if (taskId) {
                                            if (!hasPermission(permissions, 'edit-project-tasks')) {
                                                toast.error(t('Permission denied.'));
                                                return;
                                            }

                                            toast.loading('Updating task status...');

                                            const currentTask = Object.values(kanbanData)
                                                .flatMap((column: any) => column.tasks)
                                                .find((task: any) => task.id.toString() === taskId);

                                            if (currentTask) {
                                                router.put(route('project-tasks.update-status', taskId), {
                                                    task_status_id: status.id
                                                }, {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                    onSuccess: (page) => {
                                                        toast.dismiss();
                                                        if (page.props.flash?.success) {
                                                            toast.success(t(page.props.flash.success));
                                                        }
                                                        router.reload();
                                                    },
                                                    onError: () => {
                                                        toast.dismiss();
                                                        toast.error('Failed to update task status');
                                                    }
                                                });
                                            }
                                        }
                                    }}
                                    onDragOver={(e) => {
                                        e.preventDefault();
                                        e.currentTarget.classList.add('bg-blue-50');
                                    }}
                                    onDragLeave={(e) => {
                                        e.currentTarget.classList.remove('bg-blue-50');
                                    }}
                                >
                                    <div className="bg-gray-100 rounded-lg h-full flex flex-col">
                                        <div className="p-3 border-b border-gray-200">
                                            <div className="flex items-center justify-between mb-2">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-3 h-3 rounded-full" style={{ backgroundColor: status.color }}></div>
                                                    <h3 className="font-semibold text-sm text-gray-700">{status.name}</h3>
                                                </div>
                                                <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                                                    {statusTasks.length}
                                                </span>
                                            </div>
                                            {hasPermission(permissions, 'create-project-tasks') && (
                                                <button
                                                    onClick={() => handleAddTask(status.id)}
                                                    className="w-full text-xs text-gray-600 hover:text-blue-600 hover:bg-blue-50 py-2 px-3 rounded-md border border-dashed border-gray-300 hover:border-blue-300 transition-all duration-200 flex items-center justify-center gap-1 cursor-pointer"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                    {t('Add Task')}
                                                </button>
                                            )}
                                        </div>
                                        <div className="p-2 space-y-2 overflow-y-auto flex-1 column-scroll" style={{ maxHeight: 'calc(100vh - 320px)' }}>
                                            {statusTasks.map((task) => (
                                                <div
                                                    key={task.id}
                                                    draggable={hasPermission(permissions, 'edit-project-tasks')}
                                                    onDragStart={(e) => {
                                                        if (!hasPermission(permissions, 'edit-project-tasks')) {
                                                            e.preventDefault();
                                                            return;
                                                        }
                                                        e.dataTransfer.setData('taskId', task.id.toString());
                                                        e.currentTarget.classList.add('opacity-50', 'scale-95');
                                                    }}
                                                    onDragEnd={(e) => {
                                                        e.currentTarget.classList.remove('opacity-50', 'scale-95');
                                                    }}
                                                    className={`transition-all duration-200 ${hasPermission(permissions, 'edit-project-tasks') ? 'cursor-move' : 'cursor-default'
                                                        }`}
                                                >
                                                    <Card className="bg-white border-l-4 border-t border-r border-b border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 group" style={{ borderLeftColor: status.color }}>
                                                        <div className="p-3">
                                                            <div className="flex items-center justify-between mb-2">
                                                                <h4
                                                                    className="font-medium text-sm text-gray-900 cursor-pointer hover:text-blue-600 flex-1 pr-2"
                                                                    onClick={() => handleAction('view', task)}
                                                                >
                                                                    {task.title}
                                                                </h4>
                                                                <div className="opacity-0 group-hover:opacity-100 transition-opacity">
                                                                    <DropdownMenu>
                                                                        <DropdownMenuTrigger asChild>
                                                                            <button className="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded cursor-pointer">
                                                                                <MoreHorizontal className="h-4 w-4" />
                                                                            </button>
                                                                        </DropdownMenuTrigger>
                                                                        <DropdownMenuContent align="end" className="w-40">
                                                                            <DropdownMenuItem onClick={() => handleAction('view', task)}>
                                                                                <Eye className="h-4 w-4 mr-2" />
                                                                                {t('View')}
                                                                            </DropdownMenuItem>
                                                                            {hasPermission(permissions, 'edit-project-tasks') && (
                                                                                <DropdownMenuItem onClick={() => handleAction('edit', task)}>
                                                                                    <Edit className="h-4 w-4 mr-2" />
                                                                                    {t('Edit')}
                                                                                </DropdownMenuItem>
                                                                            )}
                                                                            {hasPermission(permissions, 'delete-project-tasks') && (
                                                                                <>
                                                                                    <DropdownMenuSeparator />
                                                                                    <DropdownMenuItem onClick={() => handleAction('delete', task)} className="text-red-600">
                                                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                                                        {t('Delete')}
                                                                                    </DropdownMenuItem>
                                                                                </>
                                                                            )}
                                                                        </DropdownMenuContent>
                                                                    </DropdownMenu>
                                                                </div>
                                                            </div>

                                                            {task.description && (
                                                                <p className="text-xs text-gray-500 mb-3 line-clamp-2">{task.description}</p>
                                                            )}

                                                            <div className="mb-3">
                                                                <div className="flex justify-between text-xs mb-1">
                                                                    <span className="text-gray-600">Progress</span>
                                                                    <span className="font-medium">{task.progress}%</span>
                                                                </div>
                                                                <div className="w-full bg-gray-200 rounded-full h-1.5">
                                                                    <div
                                                                        className={`h-1.5 rounded-full transition-all duration-300 ${task.progress >= 100 ? 'bg-green-500' :
                                                                                task.progress >= 75 ? 'bg-blue-500' :
                                                                                    task.progress >= 50 ? 'bg-yellow-500' : 'bg-orange-500'
                                                                            }`}
                                                                        style={{ width: `${task.progress}%` }}
                                                                    ></div>
                                                                </div>
                                                            </div>

                                                            <div className="flex items-center justify-between text-xs">
                                                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${task.priority === 'urgent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                                                                        task.priority === 'high' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                                                                            task.priority === 'medium' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                                                                                'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                                                                    }`}>
                                                                    {capitalize(task.priority)}
                                                                </span>
                                                                {task.assigned_user && (
                                                                    <div className="flex items-center gap-1">
                                                                        <Avatar className="w-5 h-5 rounded-full object-cover">
                                                                            <AvatarImage
                                                                                src={task.assigned_user?.avatar}
                                                                                alt={task.assigned_user?.name || 'Avatar'}
                                                                                onError={(e) => {
                                                                                    // Fallback to default avatar on error
                                                                                    const target = e.target as HTMLImageElement;
                                                                                    target.src = getDisplayUrl('avatars/avatar.png');
                                                                                }}
                                                                            />
                                                                            <AvatarFallback className="text-lg">
                                                                                {task.assigned_user?.name?.charAt(0)?.toUpperCase() || 'U'}
                                                                            </AvatarFallback>
                                                                        </Avatar>
                                                                        <span className="text-gray-600 max-w-16 truncate">{task.assigned_user.name}</span>
                                                                    </div>
                                                                )}
                                                            </div>

                                                            {task.due_date && (
                                                                <div className="flex items-center gap-1 mt-2 text-xs text-gray-500">
                                                                    <Calendar className="h-3 w-3" />
                                                                    <span>Due: {window.appSettings?.formatDateTime(task.due_date, false) || new Date(task.due_date).toLocaleDateString()}</span>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </Card>
                                                </div>
                                            ))}
                                            {statusTasks.length === 0 && (
                                                <div className="text-center py-8 text-gray-400">
                                                    <Building2 className="h-8 w-8 mx-auto mb-2" />
                                                    <p className="text-sm">{t('No tasks')}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: [
                        { name: 'title', label: t('Task Title'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
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
                            options: statuses.map((status: any) => ({ value: status.id, label: status.name })),
                            defaultValue: formMode === 'create' ? (prefilledStatus || statuses[0]?.id) : undefined,
                            hidden: formMode === 'create' && prefilledStatus
                        },
                        { name: 'estimated_hours', label: t('Estimated Hours'), type: 'number', step: '0.5' },
                        { name: 'progress', label: t('Progress (%)'), type: 'number', min: '0', max: '100', defaultValue: '0' },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assign To'),
                            type: 'select',
                            options: [
                                { value: null, label: t('Unassigned') },
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ]
                        }] : [])
                    ],
                    modalSize: 'lg'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_to: currentItem.assigned_user?.id || null,
                    task_status_id: currentItem.task_status_id
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
                entityName="task"
            />
        </PageTemplate>
    );
}
