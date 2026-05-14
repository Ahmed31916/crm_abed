import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, Calendar } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { capitalize } from '@/utils/helper';

export default function Meetings() {
    const { t } = useTranslation();
    const { auth, meetings, users = [], filters: pageFilters = {}, settings = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';
    const isGoogleCalendarSynced = settings?.googleCalendarEnabled === '1';

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');


    const hasActiveFilters = () => searchTerm !== '' || selectedStatus !== 'all' || selectedAssignee !== 'all';
    const activeFilterCount = () => (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('meetings.index'), {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(route('meetings.index'), {
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);
        switch (action) {
            case 'view':
                router.get(route('meetings.show', item.id));
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
        // Process attendees data to ensure proper format
        if (formData.attendees && Array.isArray(formData.attendees)) {
            formData.attendees = formData.attendees.filter((attendee: any) =>
                attendee.type && attendee.id && attendee.id !== ''
            );
        }

        // Ensure parent_id and assigned_to are strings
        if (formData.parent_id) {
            formData.parent_id = String(formData.parent_id);
        }
        if (formData.assigned_to) {
            formData.assigned_to = String(formData.assigned_to);
        }

        if (formMode === 'create') {
            toast.loading(t('Creating meeting...'));
            router.post(route('meetings.store'), formData, {
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
            toast.loading(t('Updating meeting...'));
            router.put(route('meetings.update', currentItem.id), formData, {
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
        toast.loading(t('Deleting meeting...'));
        router.delete(route('meetings.destroy', currentItem.id), {
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

    const handleToggleStatus = (meeting: any) => {
        const newStatus = meeting.status === 'planned' ? 'held' : 'planned';
        toast.loading(t('{{action}} meeting...', { action: newStatus === 'held' ? t('Marking as held') : t('Marking as planned') }));
        router.put(route('meetings.toggle-status', meeting.id), {}, {
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

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedAssignee('all');
        setShowFilters(false);
        router.get(route('meetings.index'), {
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };



    const pageActions = [];
    if (hasPermission(permissions, 'create-meetings')) {
        pageActions.push({
            label: t('Add Meeting'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Meetings') }
    ];

    const columns = [
        {
            key: 'title',
            label: t('Title'),
            sortable: true,
            render: (value: string, row: any) => (
                <div>
                    <div className="font-medium">{value}</div>
                    <div className="text-sm text-muted-foreground">{row.location || t('No location')}</div>
                </div>
            )
        },
        {
            key: 'start_date',
            label: t('Date & Time'),
            sortable: true,
            render: (value: string, row: any) => (
                <div>
                    <div className="font-medium">{window.appSettings?.formatDateTime(value, false) || '-'}</div>
                    <div className="text-sm text-muted-foreground">
                        {window.appSettings?.formatTime(row.start_time) || row.start_time} - {window.appSettings?.formatTime(row.end_time) || row.end_time}
                    </div>
                </div>
            )
        },
        {
            key: 'parent_module',
            label: t('Related To'),
            render: (value: string, row: any) => value ? (
                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-blue-50 text-blue-700 ring-blue-600/20">
                    {capitalize(value)}
                </span>
            ) : t('-')
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                const getStatusColor = (status: string) => {
                    switch (status) {
                        case 'planned': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
                        case 'held': return 'bg-green-50 text-green-700 ring-green-600/20';
                        case 'not_held': return 'bg-red-50 text-red-700 ring-red-600/20';
                        default: return 'bg-gray-50 text-gray-700 ring-gray-600/20';
                    }
                };
                const getStatusLabel = (status: string) => {
                    switch (status) {
                        case 'planned': return t('Planned');
                        case 'held': return t('Held');
                        case 'not_held': return t('Not Held');
                        default: return status;
                    }
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
                        {getStatusLabel(value)}
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
            requiredPermission: 'toggle-status-meetings'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-meetings'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-meetings'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-meetings'
        }
    ];

    return (
        <PageTemplate
            title={t("Meetings")}
            url="/meetings"
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
                                { value: 'planned', label: t('Planned') },
                                { value: 'held', label: t('Held') },
                                { value: 'not_held', label: t('Not Held') }
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
                        router.get(route('meetings.index'), {
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={meetings?.data || []}
                    from={meetings?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-meetings',
                        create: 'create-meetings',
                        edit: 'edit-meetings',
                        delete: 'delete-meetings'
                    }}
                />

                <Pagination
                    from={meetings?.from || 0}
                    to={meetings?.to || 0}
                    total={meetings?.total || 0}
                    links={meetings?.links}
                    entityName={t("meetings")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: [
                        { name: 'title', label: t('Meeting Title'), type: 'text' as const, required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' as const },
                        { name: 'location', label: t('Location'), type: 'text' as const },
                        { name: 'start_date', label: t('Start Date'), type: 'date' as const, required: true },
                        { name: 'end_date', label: t('End Date'), type: 'date' as const, required: true },
                        { name: 'start_time', label: t('Start Time'), type: 'time' as const, required: true },
                        { name: 'end_time', label: t('End Time'), type: 'time' as const, required: true },
                        {
                            name: 'parent_module',
                            label: t('Related To'),
                            type: 'select' as const,
                            required: true,
                            options: [
                                { value: 'lead', label: t('Lead') },
                                { value: 'account', label: t('Account') },
                                { value: 'contact', label: t('Contact') },
                                { value: 'opportunity', label: t('Opportunity') },
                                { value: 'case', label: t('Case') },
                                { value: 'project', label: t('Project') }
                            ]
                        },
                        {
                            name: 'parent_id',
                            label: t('Select Record'),
                            type: 'select' as const,
                            required: true,
                            options: [],
                            placeholder: t('Select Record'),
                            emptyNote: (formData: any) => {
                                const parentModule = formData.parent_module;
                                if (!parentModule || parentModule === 'none') return null;
                                const routes: Record<string, string> = {
                                    lead: route('leads.index'),
                                    account: route('accounts.index'),
                                    contact: route('contacts.index'),
                                    opportunity: route('opportunities.index'),
                                    case: route('cases.index'),
                                    project: route('projects.index')
                                };
                                const labels: Record<string, string> = {
                                    lead: t('Leads'),
                                    account: t('Accounts'),
                                    contact: t('Contacts'),
                                    opportunity: t('Opportunities'),
                                    case: t('Cases'),
                                    project: t('Projects')
                                };
                                return { link: routes[parentModule], linkText: labels[parentModule] };
                            },
                            conditional: (mode: string, formData: any) => {
                                const parentModule = formData.parent_module;
                                return parentModule && parentModule !== 'none';
                            }
                        },
                        {
                            name: 'attendees',
                            label: t('Attendees'),
                            type: 'array' as const,
                            required: true,
                            fields: [
                                {
                                    name: 'type',
                                    label: t('Type'),
                                    type: 'select' as const,
                                    required: true,
                                    options: [
                                        { value: 'user', label: t('User') },
                                        { value: 'contact', label: t('Contact') },
                                        { value: 'lead', label: t('Lead') }
                                    ]
                                },
                                {
                                    name: 'id',
                                    label: t('Select Person'),
                                    type: 'select' as const,
                                    required: true,
                                    options: [],
                                    emptyNote: (formData: any, arrayIndex?: number) => {
                                        if (arrayIndex === undefined) return null;
                                        const attendees = formData.attendees || [];
                                        const attendeeType = attendees[arrayIndex]?.type;
                                        if (!attendeeType) return null;
                                        const routes: Record<string, string> = {
                                            user: route('users.index'),
                                            contact: route('contacts.index'),
                                            lead: route('leads.index')
                                        };
                                        const labels: Record<string, string> = {
                                            user: t('Users'),
                                            contact: t('Contacts'),
                                            lead: t('Leads')
                                        };
                                        return { link: routes[attendeeType], linkText: labels[attendeeType] };
                                    }
                                }
                            ]
                        },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assign To'),
                            type: 'select' as const,
                            required: true,
                            emptyNote: { link: route('users.index'), linkText: t('Users') },
                            options: [
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ]
                        }] : []),
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select' as const,
                            options: [
                                { value: 'planned', label: t('Planned') },
                                { value: 'held', label: t('Held') },
                                { value: 'not_held', label: t('Not Held') }
                            ],
                            defaultValue: 'planned'
                        },
                        ...(isGoogleCalendarSynced ? [{
                            name: 'sync_with_google_calendar',
                            label: t('Sync with Google Calendar'),
                            type: 'switch' as const,
                            defaultValue: false,
                            conditional: (mode: string) => mode === 'create'
                        }] : [])
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    attendees: currentItem.attendees?.map((attendee: any) => ({
                        type: attendee.attendee_type,
                        id: attendee.attendee_id
                    })) || []
                } : {}}
                title={
                    formMode === 'create'
                        ? t('Add Meeting')
                        : formMode === 'edit'
                            ? t('Edit Meeting')
                            : t('View Meeting')
                }
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.title || ''}
                entityName={t('meeting')}
            />
        </PageTemplate>
    );
}
