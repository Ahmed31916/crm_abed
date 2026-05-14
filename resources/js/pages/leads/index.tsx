import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Building2, User, Users, Download, Upload, FileUp, FileDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { ImportModal } from '@/components/ImportModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';



export default function Leads() {
    const { t } = useTranslation();
    const { auth, leads, leadStatuses = [], leadSources = [], accounts = [], campaigns = [], accountIndustries = [], accountTypes = [], users = [], samplePath, filters: pageFilters = {}, kanbanData: initialKanbanData } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';
    const getInitials = useInitials();

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedLeadStatus, setSelectedLeadStatus] = useState(pageFilters.lead_status_id || 'all');
    const [selectedLeadSource, setSelectedLeadSource] = useState(pageFilters.lead_source_id || 'all');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedConverted, setSelectedConverted] = useState(pageFilters.is_converted || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isConvertModalOpen, setIsConvertModalOpen] = useState(false);
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);
    const [convertType, setConvertType] = useState<'account' | 'contact'>('account');
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [activeView, setActiveView] = useState(
        ['list', 'grid', 'kanban'].includes(pageFilters.view) ? pageFilters.view : 'kanban'
    );
    const [kanbanData, setKanbanData] = useState<any>(null);
    const [kanbanDataRef, setKanbanDataRef] = useState<any>(null);
    const [isLoadingKanban, setIsLoadingKanban] = useState(false);

    const [prefilledLeadStatus, setPrefilledLeadStatus] = useState<string>('');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedLeadStatus !== 'all' || selectedLeadSource !== 'all' || selectedStatus !== 'all' || selectedConverted !== 'all' || selectedAssignee !== 'all';
    };

    const hasEmptyDropdowns = leadStatuses.length === 0 || leadSources.length === 0 || accountIndustries.length === 0 || campaigns.length === 0 || (isCompany && users.length === 0);

    // Count active filters
    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedLeadStatus !== 'all' ? 1 : 0) + (selectedLeadSource !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedConverted !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('leads.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            lead_status_id: selectedLeadStatus !== 'all' ? selectedLeadStatus : undefined,
            lead_source_id: selectedLeadSource !== 'all' ? selectedLeadSource : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            is_converted: selectedConverted !== 'all' ? selectedConverted : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        (CrudFormModal as any).handleExport?.();
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('leads.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            lead_status_id: selectedLeadStatus !== 'all' ? selectedLeadStatus : undefined,
            lead_source_id: selectedLeadSource !== 'all' ? selectedLeadSource : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            is_converted: selectedConverted !== 'all' ? selectedConverted : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('leads.show', item.id));
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
            case 'convert-to-account':
                setConvertType('account');
                setIsConvertModalOpen(true);
                break;
            case 'convert-to-contact':
                setConvertType('contact');
                setIsConvertModalOpen(true);
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleAddLead = (statusId: string) => {
        setCurrentItem(null);
        setFormMode('create');
        setPrefilledLeadStatus(statusId);
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        if (formMode === 'create') {
            toast.loading(t('Creating lead...'));

            router.post(route('leads.store'), formData, {
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

                    if (activeView === 'kanban') {
                        loadKanbanData();
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
            if (!hasPermission(permissions, 'edit-leads')) {
                toast.error(t('Permission denied.'));
                return;
            }

            toast.loading(t('Updating lead...'));

            router.put(route('leads.update', currentItem.id), formData, {
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
                    // Don't reload kanban data for edit operations as drag-drop handles it
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
        toast.loading(t('Deleting lead...'));

        router.delete(route('leads.destroy', currentItem.id), {
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
                if (activeView === 'kanban') {
                    loadKanbanData();
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

    const handleToggleStatus = (lead: any) => {
        if (!hasPermission(permissions, 'toggle-status-leads')) {
            toast.error(t('Permission denied.'));
            return;
        }

        const newStatus = lead.status === 'active' ? 'inactive' : 'active';
        toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} lead...`);

        router.put(route('leads.toggle-status', lead.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                } else if (page.props.flash.warning) {
                    toast.warning(t(page.props.flash.warning));
                }
                if (activeView === 'kanban') {
                    loadKanbanData();
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(t('Failed to update status: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleConvertSubmit = (formData: any) => {
        const route_name = convertType === 'account' ? 'leads.convert-to-account' : 'leads.convert-to-contact';
        toast.loading(t(`Converting lead to ${convertType}...`));

        router.put(route(route_name, currentItem.id), formData, {
            // router.post(route(route_name, currentItem.id), formData, {
            onSuccess: (page) => {
                setIsConvertModalOpen(false);
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                } else if (page.props.flash.warning) {
                    toast.warning(t(page.props.flash.warning));
                }
                if (activeView === 'kanban') {
                    loadKanbanData();
                }
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(t('Failed to convert: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedLeadStatus('all');
        setSelectedLeadSource('all');
        setSelectedStatus('all');
        setSelectedConverted('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('leads.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    const loadKanbanData = () => {
        if (activeView !== 'kanban' || leadStatuses.length === 0) return;

        setIsLoadingKanban(true);

        // Use existing leads data to structure kanban
        const leadsData = leads?.data || [];
        const structuredData = {};

        leadStatuses.forEach(status => {
            structuredData[status.id] = {
                status: status,
                items: leadsData.filter(lead => {
                    const matchesStatus = lead.lead_status?.id === status.id;
                    const matchesSearch = !searchTerm ||
                        lead.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        lead.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        lead.phone?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        lead.company?.toLowerCase().includes(searchTerm.toLowerCase());
                    const matchesSource = selectedLeadSource === 'all' || lead.lead_source_id?.toString() === selectedLeadSource;
                    const matchesActiveStatus = selectedStatus === 'all' || lead.status === selectedStatus;
                    const matchesConverted = selectedConverted === 'all' ||
                        (selectedConverted === '1' && lead.is_converted) ||
                        (selectedConverted === '0' && !lead.is_converted);

                    return matchesStatus && matchesSearch && matchesSource && matchesActiveStatus && matchesConverted;
                })
            };
        });

        setKanbanData(structuredData);
        setKanbanDataRef(structuredData);
        setIsLoadingKanban(false);
    };

    useEffect(() => {
        if (activeView === 'kanban' && leadStatuses.length > 0) {
            loadKanbanData();
        }
    }, [activeView, leads, searchTerm, selectedLeadSource, selectedStatus, selectedConverted, leadStatuses]);

    // Define page actions
    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-leads')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => handleExport()
        });
    }

    // Add import button
    if (hasPermission(permissions, 'import-leads')) {
        pageActions.push({
            label: t('Import'),
            icon: <FileUp className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => setIsImportModalOpen(true)
        });
    }

    // Add the "Add Lead" button if user has permission
    if (hasPermission(permissions, 'create-leads')) {
        pageActions.push({
            label: t('Add Lead'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew(),
            disabled: hasEmptyDropdowns
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Lead Management') },
        { title: t('Leads') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true,
            render: (value: any, row: any) => {
                return (
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-white">
                            {getInitials(row.name)}
                        </div>
                        <div>
                            <div className="font-medium">{row.name}</div>
                            <div className="text-sm text-muted-foreground">{row.email || t('No email')}</div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'value',
            label: t('Value'),
            sortable: true,
            render: (value: any) => value ? (window.appSettings?.formatCurrency(parseFloat(value)) || `$${parseFloat(value).toFixed(2)}`) : t('-')
        },
        {
            key: 'lead_status',
            label: t('Progress'),
            render: (value: any) => value ? (
                <div className="flex items-center gap-2">
                    <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: value.color }}
                    ></div>
                    <span>{value.name}</span>
                </div>
            ) : t('-')
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => (
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
                    ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                    : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                    }`}>
                    {value === 'active' ? t('Active') : t('Inactive')}
                </span>
            )
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        },
        {
            key: 'is_converted',
            label: t('Converted'),
            render: (value: boolean) => (
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value
                    ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                    : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                    }`}>
                    {value ? t('Yes') : t('No')}
                </span>
            )
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
            label: t('Convert to Account'),
            icon: 'Building2',
            action: 'convert-to-account',
            className: 'text-green-500',
            requiredPermission: 'convert-leads',
            condition: (item: any) => !item.is_converted
        },
        {
            label: t('Convert to Contact'),
            icon: 'Users',
            action: 'convert-to-contact',
            className: 'text-blue-500',
            requiredPermission: 'convert-leads',
            condition: (item: any) => !item.is_converted
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-leads'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-leads'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-leads'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-leads'
        }
    ];

    return (
        <PageTemplate
            title={t("Leads")}
            url="/leads"
            actions={pageActions}
            breadcrumbs={breadcrumbs}
            noPadding
            className={activeView === 'kanban' ? 'overflow-hidden' : ''}
        >
            {/* Search and filters section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[
                        {
                            name: 'lead_status_id',
                            label: t('Lead Status'),
                            type: 'select' as const,
                            value: selectedLeadStatus,
                            onChange: setSelectedLeadStatus,
                            options: [
                                { value: 'all', label: t('All Statuses') },
                                ...leadStatuses.map((status: any) => ({
                                    value: status.id.toString(),
                                    label: status.name
                                }))
                            ]
                        },
                        {
                            name: 'lead_source_id',
                            label: t('Lead Source'),
                            type: 'select' as const,
                            value: selectedLeadSource,
                            onChange: setSelectedLeadSource,
                            options: [
                                { value: 'all', label: t('All Sources') },
                                ...leadSources.map((source: any) => ({
                                    value: source.id.toString(),
                                    label: source.name
                                }))
                            ]
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select' as const,
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: [
                                { value: 'all', label: t('All Status') },
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') }
                            ]
                        },
                        {
                            name: 'is_converted',
                            label: t('Converted'),
                            type: 'select' as const,
                            value: selectedConverted,
                            onChange: setSelectedConverted,
                            options: [
                                { value: 'all', label: t('All') },
                                { value: '1', label: t('Converted') },
                                { value: '0', label: t('Not Converted') }
                            ]
                        },
                        ...(isCompany ? [{
                            name: 'assigned_to',
                            label: t('Assigned To'),
                            type: 'select' as const,
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
                    {...(activeView !== 'kanban' && {
                        currentPerPage: pageFilters.per_page?.toString() || "10",
                        onPerPageChange: (value) => {
                            const params = {
                                page: 1,
                                view: activeView,
                                search: searchTerm || undefined,
                                lead_status_id: selectedLeadStatus !== 'all' ? selectedLeadStatus : undefined,
                                lead_source_id: selectedLeadSource !== 'all' ? selectedLeadSource : undefined,
                                status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                is_converted: selectedConverted !== 'all' ? selectedConverted : undefined,
                                assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                            };
                            if (parseInt(value) !== 10) {
                                params.per_page = parseInt(value);
                            }
                            router.get(route('leads.index'), params, { preserveState: true, preserveScroll: true });
                        }
                    })}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('leads.index'), {
                            view: view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            lead_status_id: selectedLeadStatus !== 'all' ? selectedLeadStatus : undefined,
                            lead_source_id: selectedLeadSource !== 'all' ? selectedLeadSource : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            is_converted: selectedConverted !== 'all' ? selectedConverted : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: pageFilters.per_page ?? 10
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    viewOptions={[
                        { value: 'list', label: t('List View'), icon: 'List' },
                        { value: 'kanban', label: t('Kanban View'), icon: 'Columns' },
                        { value: 'grid', label: t('Grid View'), icon: 'Grid3X3' }
                    ]}
                />
            </div>

            {/* Content section */}
            {activeView === 'list' ? (
                <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                    <CrudTable
                        columns={columns}
                        actions={actions}
                        data={leads?.data || []}
                        from={leads?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-leads',
                            create: 'create-leads',
                            edit: 'edit-leads',
                            delete: 'delete-leads'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={leads?.from || 1}
                        to={leads?.to || leads?.data?.length || 0}
                        total={leads?.total || leads?.data?.length || 0}
                        links={leads?.links}
                        entityName={t("leads")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : activeView === 'kanban' ? (
                <>
                    {/* Kanban Board */}
                    <div className="w-full">
                        <style>{`
              .kanban-scroll {
                overflow-x: auto;
                overflow-y: hidden;
              }
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
              main {
                max-width: 100vw;
                overflow-x: hidden;
              }
              body {
                overflow-x: hidden !important;
              }
            `}</style>
                        <div className="bg-gray-50 p-4 rounded-lg overflow-hidden">
                            {isLoadingKanban ? (
                                <div className="flex items-center justify-center h-full">
                                    <div className="text-center">
                                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                                        <p className="text-gray-500 dark:text-gray-400">{t('Loading kanban board...')}</p>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex gap-4 overflow-x-auto pb-4" style={{ height: 'calc(100vh - 280px)', width: '100%' }}>
                                    {leadStatuses.map((status) => {
                                        const statusLeads = kanbanData?.[status.id]?.items || [];
                                        return (
                                            <div
                                                key={status.id}
                                                className="flex-shrink-0"
                                                style={{ minWidth: 'calc(20% - 16px)', width: 'calc(20% - 16px)' }}
                                                onDrop={(e) => {
                                                    e.preventDefault();
                                                    e.currentTarget.classList.remove('bg-blue-50');
                                                    const leadId = e.dataTransfer.getData('leadId');
                                                    if (leadId) {
                                                        // Check permission before updating
                                                        if (!hasPermission(permissions, 'edit-leads')) {
                                                            toast.error(t('Permission denied.'));
                                                            return;
                                                        }

                                                        toast.loading('Updating lead status...');

                                                        // Find the lead to get current data
                                                        const currentLead = Object.values(kanbanData)
                                                            .flatMap((column: any) => column.items)
                                                            .find((lead: any) => lead.id.toString() === leadId);

                                                        if (currentLead) {
                                                            router.put(route('leads.update', leadId), {
                                                                ...currentLead,
                                                                lead_status_id: status.id
                                                            }, {
                                                                onSuccess: (page) => {
                                                                    toast.dismiss();
                                                                    if (page.props.flash?.success) {
                                                                        toast.success(t(page.props.flash.success));
                                                                    } else if (page.props.flash.error) {
                                                                        toast.error(t(page.props.flash.error));
                                                                    }
                                                                    loadKanbanData();
                                                                },
                                                                onError: () => {
                                                                    toast.dismiss();
                                                                    toast.error(t('Failed to update lead status'));
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
                                                <div className="bg-gray-100 dark:bg-gray-900 rounded-lg h-full flex flex-col">
                                                    <div className="p-3 border-b border-gray-200">
                                                        <div className="flex items-center justify-between mb-2">
                                                            <div className="flex items-center gap-2">
                                                                <div className="w-3 h-3 rounded-full" style={{ backgroundColor: status.color }}></div>
                                                                <h3 className="font-semibold text-sm text-gray-700">{status.name}</h3>
                                                            </div>
                                                            <span className="text-xs text-gray-500 bg-gray-200 dark:bg-gray-900 px-2 py-1 rounded-full">
                                                                {statusLeads.length}
                                                            </span>
                                                        </div>
                                                        {hasPermission(permissions, 'create-leads') && (
                                                            <button
                                                                onClick={() => handleAddLead(status.id.toString())}
                                                                className="w-full text-xs text-gray-600 hover:text-blue-600 hover:bg-blue-50 py-2 px-3 rounded-md border border-dashed border-gray-300 hover:border-blue-300 transition-all duration-200 flex items-center justify-center gap-1 cursor-pointer"
                                                            >
                                                                <Plus className="h-3 w-3" />
                                                                {t('Add Lead')}
                                                            </button>
                                                        )}
                                                    </div>
                                                    <div className="p-2 space-y-2 overflow-y-auto flex-1" style={{ maxHeight: 'calc(100vh - 350px)' }}>
                                                        {statusLeads.map((lead) => (
                                                            <div
                                                                key={lead.id}
                                                                draggable={hasPermission(permissions, 'edit-leads')}
                                                                onDragStart={(e) => {
                                                                    if (!hasPermission(permissions, 'edit-leads')) {
                                                                        e.preventDefault();
                                                                        return;
                                                                    }
                                                                    e.dataTransfer.setData('leadId', lead.id.toString());
                                                                    e.currentTarget.classList.add('opacity-50', 'scale-95');
                                                                }}
                                                                onDragEnd={(e) => {
                                                                    e.currentTarget.classList.remove('opacity-50', 'scale-95');
                                                                }}
                                                                className={`transition-all duration-200 ${hasPermission(permissions, 'edit-leads') ? 'cursor-move' : 'cursor-default'}`}
                                                            >
                                                                <Card className="hover:shadow-md transition-all duration-200 border-l-4 hover:scale-105" style={{ borderLeftColor: status.color }}>
                                                                    <div className="p-3">
                                                                        <div className="space-y-2">
                                                                            <div className="flex items-start justify-between">
                                                                                <h4
                                                                                    className="font-medium text-sm line-clamp-2 hover:text-blue-600 transition-colors cursor-pointer flex-1"
                                                                                    onClick={() => handleAction('view', lead)}
                                                                                >
                                                                                    {lead.name}
                                                                                </h4>
                                                                                <DropdownMenu>
                                                                                    <DropdownMenuTrigger asChild>
                                                                                        <Button variant="ghost" size="sm" className="h-6 w-6 p-0 text-gray-400 hover:text-gray-600">
                                                                                            <MoreHorizontal className="h-4 w-4" />
                                                                                        </Button>
                                                                                    </DropdownMenuTrigger>
                                                                                    <DropdownMenuContent align="end" className="w-40">
                                                                                        <DropdownMenuItem onClick={() => handleAction('view', lead)}>
                                                                                            <Eye className="h-4 w-4 mr-2" />
                                                                                            {t('View')}
                                                                                        </DropdownMenuItem>
                                                                                        {hasPermission(permissions, 'edit-leads') && (
                                                                                            <DropdownMenuItem onClick={() => handleAction('edit', lead)}>
                                                                                                <Edit className="h-4 w-4 mr-2" />
                                                                                                {t('Edit')}
                                                                                            </DropdownMenuItem>
                                                                                        )}
                                                                                        {hasPermission(permissions, 'convert-leads') && !lead.is_converted && (
                                                                                            <>
                                                                                                <DropdownMenuSeparator />
                                                                                                <DropdownMenuItem onClick={() => handleAction('convert-to-account', lead)} className="text-green-600">
                                                                                                    <Building2 className="h-4 w-4 mr-2" />
                                                                                                    {t('Convert to Account')}
                                                                                                </DropdownMenuItem>
                                                                                                <DropdownMenuItem onClick={() => handleAction('convert-to-contact', lead)} className="text-blue-600">
                                                                                                    <Users className="h-4 w-4 mr-2" />
                                                                                                    {t('Convert to Contact')}
                                                                                                </DropdownMenuItem>
                                                                                            </>
                                                                                        )}
                                                                                        {hasPermission(permissions, 'delete-leads') && (
                                                                                            <>
                                                                                                <DropdownMenuSeparator />
                                                                                                <DropdownMenuItem onClick={() => handleAction('delete', lead)} className="text-red-600">
                                                                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                                                                    {t('Delete')}
                                                                                                </DropdownMenuItem>
                                                                                            </>
                                                                                        )}
                                                                                    </DropdownMenuContent>
                                                                                </DropdownMenu>
                                                                            </div>

                                                                            <div className="text-xs text-gray-600">
                                                                                {lead.email && <div>{lead.email}</div>}
                                                                                {lead.company && <div>{lead.company}</div>}
                                                                            </div>

                                                                            <div className="flex items-center justify-between">
                                                                                <div className="text-xs text-gray-500">
                                                                                    {lead.value ? (window.appSettings?.formatCurrency(parseFloat(lead.value)) || `$${parseFloat(lead.value).toFixed(2)}`) : t('No value')}
                                                                                </div>
                                                                                {lead.assigned_user && (
                                                                                    <Avatar className="h-5 w-5 rounded-full">
                                                                                        <AvatarImage src={lead.assigned_user.avatar} />
                                                                                        <AvatarFallback>{getInitials(lead.assigned_user.name)}</AvatarFallback>
                                                                                    </Avatar>
                                                                                )}
                                                                            </div>

                                                                            <div className="flex justify-between items-center text-xs text-gray-500">
                                                                                <div className="flex items-center gap-2">
                                                                                    {lead.is_converted && (
                                                                                        <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                                                                                            {t('Converted')}
                                                                                        </span>
                                                                                    )}
                                                                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${lead.status === 'active'
                                                                                        ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                                                                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                                                                        }`}>
                                                                                        {lead.status === 'active' ? t('Active') : t('Inactive')}
                                                                                    </span>
                                                                                </div>
                                                                                <span>{window.appSettings?.formatDateTime(lead.created_at, false) || new Date(lead.created_at).toLocaleDateString()}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </Card>
                                                            </div>
                                                        ))}
                                                        {statusLeads.length === 0 && (
                                                            <div className="text-center py-8 text-gray-400">
                                                                <User className="h-8 w-8 mx-auto mb-2" />
                                                                <p className="text-sm">{t('No leads')}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                </>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {leads?.data?.map((lead: any) => (
                            <Card key={lead.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-start space-x-4">
                                            <div className="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold">
                                                {getInitials(lead.name)}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{lead.name}</h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{lead.email || t('No email')}</p>
                                                <div className="flex items-center">
                                                    <div className={`h-2 w-2 rounded-full mr-2 ${lead.status === 'active' ? 'bg-green-500' : 'bg-gray-400'
                                                        }`}></div>
                                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {lead.status === 'active' ? t('Active') : t('Inactive')}
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
                                                {hasPermission(permissions, 'view-leads') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', lead)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Lead")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'toggle-status-leads') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', lead)}>
                                                        <span>{lead.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'convert-leads') && !lead.is_converted && (
                                                    <>
                                                        <DropdownMenuItem onClick={() => handleAction('convert-to-account', lead)} className="text-green-600">
                                                            <span>{t("Convert to Account")}</span>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => handleAction('convert-to-contact', lead)} className="text-blue-600">
                                                            <span>{t("Convert to Contact")}</span>
                                                        </DropdownMenuItem>
                                                    </>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-leads') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', lead)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-leads') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', lead)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Lead info */}
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Company')}: {lead.company || t('-')}
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Value')}: {lead.value ? (window.appSettings?.formatCurrency(parseFloat(lead.value)) || `$${parseFloat(lead.value).toFixed(2)}`) : t('-')}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            {lead.lead_status && (
                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset " style={{
                                                    backgroundColor: `${lead.lead_status.color}20`,
                                                    color: lead.lead_status.color,
                                                    borderColor: `${lead.lead_status.color}40`
                                                }}>
                                                    {lead.lead_status.name}
                                                </span>
                                            )}
                                            {lead.is_converted && (
                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-green-50 text-green-700 ring-green-600/20">
                                                    {t('Converted')}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(lead.created_at, false) || new Date(lead.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-leads') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', lead)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-leads') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', lead)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'delete-leads') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('delete', lead)}
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
                            from={leads?.from || 1}
                            to={leads?.to || leads?.data?.length || 0}
                            total={leads?.total || leads?.data?.length || 0}
                            links={leads?.links}
                            entityName={t("leads")}
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
                    ...(hasPermission(permissions, 'export-leads') && { exportRoute: 'lead.export' }),
                    fields: [
                        { name: 'name', label: t('Lead Name'), type: 'text', required: true },
                        { name: 'email', label: t('Email'), type: 'email', required: true },
                        { name: 'phone', label: t('Phone'), type: 'text' },
                        { name: 'company', label: t('Company'), type: 'text' },
                        { name: 'account_name', label: t('Account Name'), type: 'text' },
                        {
                            name: formMode === 'view' ? 'account_industry_name' : 'account_industry_id',
                            label: t('Account Industry'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...accountIndustries.map((industry: any) => ({ value: industry.id, label: industry.name }))
                            ],
                            emptyNote: accountIndustries.length === 0 ? {
                                link: route('account-industries.index'),
                                linkText: t('Account Industries')
                            } : undefined
                        },
                        { name: 'website', label: t('Website'), type: 'text' },
                        { name: 'position', label: t('Position'), type: 'text' },
                        { name: 'value', label: t('Lead Value'), type: 'number', step: '0.01', min: '0' },
                        {
                            name: formMode === 'view' ? 'lead_status_name' : 'lead_status_id',
                            label: t('Lead Status'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : leadStatuses.map((status: any) => ({
                                value: status.id,
                                label: status.name
                            })),
                            defaultValue: prefilledLeadStatus || undefined,
                            hidden: formMode === 'create' && prefilledLeadStatus,
                            emptyNote: leadStatuses.length === 0 ? {
                                link: route('lead-statuses.index'),
                                linkText: t('Lead Statuses')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'lead_source_name' : 'lead_source_id',
                            label: t('Lead Source'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : leadSources.map((source: any) => ({
                                value: source.id,
                                label: source.name
                            })),
                            emptyNote: leadSources.length === 0 ? {
                                link: route('lead-sources.index'),
                                linkText: t('Lead Sources')
                            } : undefined
                        },
                        { name: 'address', label: t('Address'), type: 'textarea' },
                        {
                            name: formMode === 'view' ? 'campaign_name' : 'campaign_id',
                            label: t('Campaign'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...campaigns.map((campaign: any) => ({ value: campaign.id, label: campaign.name }))
                            ],
                            emptyNote: campaigns.length === 0 ? {
                                link: route('campaigns.index'),
                                linkText: t('Campaigns')
                            } : undefined
                        },
                        { name: 'notes', label: t('Notes'), type: 'textarea' },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            options: formMode === 'view' ? [] : [
                                ...users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` }))
                            ],
                            readOnly: formMode === 'view',
                            hidden: !isCompany || currentItem?.assigned_to === auth?.user?.id,
                            emptyNote: users.length === 0 ? {
                                link: route('users.index'),
                                linkText: t('Users')
                            } : undefined
                        }] : []),
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            options: [
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') }
                            ],
                            defaultValue: 'active'
                        }
                    ],
                    modalSize: 'xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned'),
                    lead_status_name: currentItem.lead_status?.name || t('-'),
                    lead_source_name: currentItem.lead_source?.name || t('-'),

                    campaign_name: currentItem.campaign?.name || t('No Campaign'),
                    account_industry_name: currentItem.account_industry?.name || t('-')
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Lead')
                        : formMode === 'edit'
                            ? t('Edit Lead')
                            : t('View Lead')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('lead')}
            />

            {/* Convert Modal */}
            <CrudFormModal
                isOpen={isConvertModalOpen}
                onClose={() => setIsConvertModalOpen(false)}
                onSubmit={handleConvertSubmit}
                formConfig={{
                    fields: convertType === 'account' ? [
                        {
                            name: 'account_type_id',
                            label: t('Account Type'),
                            type: 'select',
                            required: true,
                            options: accountTypes.map((type: any) => ({
                                value: type.id,
                                label: type.name
                            }))
                        },
                        { name: 'website', label: t('Website'), type: 'text' },
                        { name: 'address', label: t('Address'), type: 'textarea' }
                    ] : [
                        {
                            name: 'account_id',
                            label: t('Account'),
                            type: 'select',
                            required: true,
                            options: accounts.map((account: any) => ({
                                value: account.id,
                                label: account.name
                            }))
                        },
                        { name: 'position', label: t('Position'), type: 'text' },
                        { name: 'address', label: t('Address'), type: 'textarea' }
                    ],
                    modalSize: 'lg'
                }}
                initialData={currentItem ? {
                    address: currentItem.address,
                    website: convertType === 'account' ? currentItem.website : undefined
                } : null}
                title={t(`Convert Lead to ${convertType === 'account' ? 'Account' : 'Contact'}`)}
                mode="create"
            />

            {/* Import Modal */}
            <ImportModal
                isOpen={isImportModalOpen}
                onClose={() => setIsImportModalOpen(false)}
                title={t('Import Leads from CSV/Excel')}
                importRoute="lead.import"
                parseRoute="lead.parse"
                samplePath={samplePath}
                importNotes={t('Ensure that the values entered for Lead Status, Lead Source, Account Industry, Campaign match the existing records in your system.')}
                databaseFields={[
                    { key: 'name', required: true },
                    { key: 'email', required: true },
                    { key: 'phone' },
                    { key: 'company' },
                    { key: 'account_name' },
                    { key: 'account_industry' },
                    { key: 'website' },
                    { key: 'position' },
                    { key: 'value' },
                    { key: 'lead_status', required: true },
                    { key: 'lead_source', required: true },
                    { key: 'address' },
                    { key: 'campaign' },
                    { key: 'notes' },
                    { key: 'status' }
                ]}
            />

        </PageTemplate>
    );
}
