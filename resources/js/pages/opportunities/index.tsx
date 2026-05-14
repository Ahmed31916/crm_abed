import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Building2, User, Download, FileDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { useInitials } from '@/hooks/use-initials';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';


export default function Opportunities() {
    const { t } = useTranslation();
    const { auth, opportunities, accounts = [], contacts = [], products = [], opportunityStages = [], opportunitySources = [], users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';
    const getInitials = useInitials();

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedStage, setSelectedStage] = useState(pageFilters.opportunity_stage_id || 'all');
    const [selectedSource, setSelectedSource] = useState(pageFilters.opportunity_source_id || 'all');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [selectedAssignee, setSelectedAssignee] = useState(pageFilters.assigned_to || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [activeView, setActiveView] = useState(
        ['list', 'grid', 'kanban'].includes(pageFilters.view) ? pageFilters.view : 'kanban'
    );
    const [kanbanData, setKanbanData] = useState<any>(null);
    const [kanbanDataRef, setKanbanDataRef] = useState<any>(null);
    const [isLoadingKanban, setIsLoadingKanban] = useState(false);
    const [prefilledOpportunityStage, setPrefilledOpportunityStage] = useState<string>('');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedAccount !== 'all' || selectedStage !== 'all' || selectedSource !== 'all' || selectedStatus !== 'all' || selectedAssignee !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedAccount !== 'all' ? 1 : 0) + (selectedStage !== 'all' ? 1 : 0) + (selectedSource !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('opportunities.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            opportunity_stage_id: selectedStage !== 'all' ? selectedStage : undefined,
            opportunity_source_id: selectedSource !== 'all' ? selectedSource : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';
        router.get(route('opportunities.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            opportunity_stage_id: selectedStage !== 'all' ? selectedStage : undefined,
            opportunity_source_id: selectedSource !== 'all' ? selectedSource : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('opportunities.show', item.id));
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

    const handleAddOpportunity = (stageId: string) => {
        setCurrentItem(null);
        setFormMode('create');
        setPrefilledOpportunityStage(stageId);
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        // Calculate total amount from products if products are selected
        if (formData.products && formData.products.length > 0) {
            const totalAmount = formData.products.reduce((total: number, product: any) => {
                const quantity = parseFloat(product.quantity) || 0;
                const unitPrice = parseFloat(product.unit_price) || 0;
                return total + (quantity * unitPrice);
            }, 0);
            formData.amount = totalAmount;
        }

        if (formMode === 'create') {
            toast.loading(t('Creating opportunity...'));

            router.post(route('opportunities.store'), formData, {
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
            if (!hasPermission(permissions, 'edit-opportunities')) {
                toast.error(t('Permission denied.'));
                return;
            }

            toast.loading(t('Updating opportunity...'));

            router.put(route('opportunities.update', currentItem.id), formData, {
                onSuccess: (page) => {
                    setIsFormModalOpen(false);
                    toast.dismiss();
                    if (page.props.flash.success) {
                        toast.success(t(page.props.flash.success));
                    } else if (page.props.flash.error) {
                        toast.error(t(page.props.flash.error));
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
        toast.loading(t('Deleting opportunity...'));

        router.delete(route('opportunities.destroy', currentItem.id), {
            onSuccess: (page) => {
                setIsDeleteModalOpen(false);
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
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

    const handleToggleStatus = (opportunity: any) => {
        if (!hasPermission(permissions, 'toggle-status-opportunities')) {
            toast.error(t('Permission denied.'));
            return;
        }

        const newStatus = opportunity.status === 'active' ? 'inactive' : 'active';
        toast.loading(t('{{action}} opportunity...', { action: newStatus === 'active' ? t('Activating') : t('Deactivating') }));

        router.put(route('opportunities.toggle-status', opportunity.id), {}, {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
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
                    toast.error(t('Failed to update: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedAccount('all');
        setSelectedStage('all');
        setSelectedSource('all');
        setSelectedStatus('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('opportunities.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    const loadKanbanData = () => {
        if (activeView !== 'kanban') return;

        setIsLoadingKanban(true);

        // Use existing opportunities data to structure kanban
        const opportunitiesData = opportunities?.data || [];
        const structuredData = {};

        opportunityStages.forEach(stage => {
            structuredData[stage.id] = {
                status: stage,
                items: opportunitiesData.filter(opportunity => {
                    const matchesStage = opportunity.opportunity_stage?.id === stage.id;
                    const matchesSearch = !searchTerm ||
                        opportunity.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        opportunity.description?.toLowerCase().includes(searchTerm.toLowerCase());
                    const matchesAccount = selectedAccount === 'all' || opportunity.account_id?.toString() === selectedAccount;
                    const matchesSource = selectedSource === 'all' || opportunity.opportunity_source_id?.toString() === selectedSource;
                    const matchesStatus = selectedStatus === 'all' || opportunity.status === selectedStatus;

                    return matchesStage && matchesSearch && matchesAccount && matchesSource && matchesStatus;
                })
            };
        });

        setKanbanData(structuredData);
        setKanbanDataRef(structuredData);
        setIsLoadingKanban(false);
    };

    useEffect(() => {
        if (activeView === 'kanban') {
            loadKanbanData();
        }
    }, [activeView, opportunities, searchTerm, selectedAccount, selectedSource, selectedStatus]);

    // Define page actions
    const pageActions = [];

    // Add export button
    if (hasPermission(permissions, 'export-opportunities')) {
        pageActions.push({
            label: t('Export'),
            icon: <FileDown className="h-4 w-4 mr-2" />,
            variant: 'outline',
            onClick: () => window.location.href = route('opportunity.export')
        });
    }

    // Add the "Add Opportunity" button if user has permission
    if (hasPermission(permissions, 'create-opportunities')) {
        pageActions.push({
            label: t('Add Opportunity'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Opportunity Management') },
        { title: t('Opportunities') }
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
                            <div className="text-sm text-muted-foreground">{row.account?.name || t('No account')}</div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'close_date',
            label: t('Close Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'opportunity_stage',
            label: t('Stage'),
            render: (value: any) => value ? (
                <div className="flex items-center gap-2">
                    <div
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: value.color }}
                    ></div>
                    <span>{value.name}</span>
                </div>
            ) : '-'
        },
        {
            key: 'opportunity_source',
            label: t('Source'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'assigned_user',
            label: t('Assigned To'),
            render: (value: any) => value?.name || t('Unassigned')
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value: string) => {
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
                        ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                        }`}>
                        {value === 'active' ? t('Active') : t('Inactive')}
                    </span>
                );
            }
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
            requiredPermission: 'toggle-status-opportunities'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-opportunities'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-opportunities'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-opportunities'
        }
    ];

    return (
        <PageTemplate
            title={t("Opportunities")}
            url="/opportunities"
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
                        {
                            name: 'opportunity_stage_id',
                            label: t('Stage'),
                            type: 'select',
                            value: selectedStage,
                            onChange: setSelectedStage,
                            options: [
                                { value: 'all', label: t('All Stages') },
                                ...opportunityStages.map((stage: any) => ({
                                    value: stage.id.toString(),
                                    label: stage.name
                                }))
                            ]
                        },
                        {
                            name: 'opportunity_source_id',
                            label: t('Source'),
                            type: 'select',
                            value: selectedSource,
                            onChange: setSelectedSource,
                            options: [
                                { value: 'all', label: t('All Sources') },
                                ...opportunitySources.map((source: any) => ({
                                    value: source.id.toString(),
                                    label: source.name
                                }))
                            ]
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: [
                                { value: 'all', label: t('All Status') },
                                { value: 'active', label: t('Active') },
                                { value: 'inactive', label: t('Inactive') }
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
                    {...(activeView !== 'kanban' && {
                        currentPerPage: pageFilters.per_page?.toString() || "10",
                        onPerPageChange: (value) => {
                            const params = {
                                page: 1,
                                view: activeView,
                                search: searchTerm || undefined,
                                account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                                opportunity_stage_id: selectedStage !== 'all' ? selectedStage : undefined,
                                opportunity_source_id: selectedSource !== 'all' ? selectedSource : undefined,
                                status: selectedStatus !== 'all' ? selectedStatus : undefined,
                                assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                            };
                            if (parseInt(value) !== 10) {
                                params.per_page = parseInt(value);
                            }
                            router.get(route('opportunities.index'), params, { preserveState: true, preserveScroll: true });
                        }
                    })}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('opportunities.index'), {
                            view: view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            opportunity_stage_id: selectedStage !== 'all' ? selectedStage : undefined,
                            opportunity_source_id: selectedSource !== 'all' ? selectedSource : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
                        data={opportunities?.data || []}
                        from={opportunities?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-opportunities',
                            create: 'create-opportunities',
                            edit: 'edit-opportunities',
                            delete: 'delete-opportunities'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={opportunities?.from || 1}
                        to={opportunities?.to || opportunities?.data?.length || 0}
                        total={opportunities?.total || opportunities?.data?.length || 0}
                        links={opportunities?.links}
                        entityName={t("opportunities")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : activeView === 'kanban' ? (
                <>
                    {/* Kanban Board */}
                    <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                        <div className="bg-gray-50 p-4 rounded-lg overflow-hidden">
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
                            {isLoadingKanban ? (
                                <div className="flex items-center justify-center h-full">
                                    <div className="text-center">
                                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                                        <p className="text-gray-500 dark:text-gray-400">{t('Loading kanban board...')}</p>
                                    </div>
                                </div>
                            ) : kanbanData ? (
                                <div className="flex gap-4 overflow-x-auto pb-4 kanban-scroll" style={{ height: 'calc(100vh - 280px)' }}>
                                    {opportunityStages.map((stage) => {
                                        const stageOpportunities = Object.values(kanbanData).find((column: any) => column.status?.id === stage.id)?.items || [];
                                        return (
                                            <div
                                                key={stage.id}
                                                className="flex-shrink-0"
                                                style={{ minWidth: '300px', width: '300px' }}
                                                onDrop={(e) => {
                                                    e.preventDefault();
                                                    e.currentTarget.classList.remove('bg-blue-50');
                                                    const opportunityId = e.dataTransfer.getData('opportunityId');
                                                    if (opportunityId) {
                                                        // Check permission before updating
                                                        if (!hasPermission(permissions, 'edit-opportunities')) {
                                                            toast.error(t('Permission denied.'));
                                                            return;
                                                        }

                                                        toast.loading(t('Updating opportunity stage...'));

                                                        // Find the opportunity to get current data
                                                        const currentOpportunity = Object.values(kanbanData)
                                                            .flatMap((column: any) => column.items)
                                                            .find((opportunity: any) => opportunity.id.toString() === opportunityId);

                                                        if (currentOpportunity) {
                                                            router.put(route('opportunities.update-status', opportunityId), {
                                                                opportunity_stage_id: stage.id
                                                            }, {
                                                                preserveState: true,
                                                                preserveScroll: true,
                                                                onSuccess: (page) => {
                                                                    toast.dismiss();
                                                                    if (page.props.flash?.success) {
                                                                        toast.success(t(page.props.flash.success));
                                                                    } else if (page.props.flash.error) {
                                                                        toast.error(t(page.props.flash.error));
                                                                    }
                                                                    router.reload({ only: ['opportunities'] });
                                                                },
                                                                onError: () => {
                                                                    toast.dismiss();
                                                                    toast.error(t('Failed to update opportunity stage'));
                                                                    loadKanbanData();
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
                                                                <div className="w-3 h-3 rounded-full" style={{ backgroundColor: stage.color }}></div>
                                                                <h3 className="font-semibold text-sm text-gray-700">{stage.name}</h3>
                                                            </div>
                                                            <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">
                                                                {stageOpportunities.length}
                                                            </span>
                                                        </div>
                                                        {hasPermission(permissions, 'create-opportunities') && (
                                                            <button
                                                                onClick={() => handleAddOpportunity(stage.id.toString())}
                                                                className="w-full text-xs text-gray-600 hover:text-blue-600 hover:bg-blue-50 py-2 px-3 rounded-md border border-dashed border-gray-300 hover:border-blue-300 transition-all duration-200 flex items-center justify-center gap-1 cursor-pointer"
                                                            >
                                                                <Plus className="h-3 w-3" />
                                                                {t('Add Opportunity')}
                                                            </button>
                                                        )}
                                                    </div>
                                                    <div className="p-2 space-y-2 overflow-y-auto flex-1" style={{ maxHeight: 'calc(100vh - 350px)' }}>
                                                        {stageOpportunities.map((opportunity) => (
                                                            <div
                                                                key={opportunity.id}
                                                                draggable={hasPermission(permissions, 'edit-opportunities')}
                                                                onDragStart={(e) => {
                                                                    if (!hasPermission(permissions, 'edit-opportunities')) {
                                                                        e.preventDefault();
                                                                        return;
                                                                    }
                                                                    e.dataTransfer.setData('opportunityId', opportunity.id.toString());
                                                                    e.currentTarget.classList.add('opacity-50', 'scale-95');
                                                                }}
                                                                onDragEnd={(e) => {
                                                                    e.currentTarget.classList.remove('opacity-50', 'scale-95');
                                                                }}
                                                                className={`transition-all duration-200 ${hasPermission(permissions, 'edit-opportunities') ? 'cursor-move' : 'cursor-default'}`}
                                                            >
                                                                <Card className="hover:shadow-md transition-all duration-200 border-l-4 hover:scale-105" style={{ borderLeftColor: stage.color }}>
                                                                    <div className="p-3">
                                                                        <div className="space-y-2">
                                                                            <div className="flex items-start justify-between">
                                                                                <h4
                                                                                    className="font-medium text-sm line-clamp-2 hover:text-blue-600 transition-colors cursor-pointer flex-1"
                                                                                    onClick={() => handleAction('view', opportunity)}
                                                                                >
                                                                                    {opportunity.name}
                                                                                </h4>
                                                                                <DropdownMenu>
                                                                                    <DropdownMenuTrigger asChild>
                                                                                        <Button variant="ghost" size="sm" className="h-6 w-6 p-0 text-gray-400 hover:text-gray-600">
                                                                                            <MoreHorizontal className="h-4 w-4" />
                                                                                        </Button>
                                                                                    </DropdownMenuTrigger>
                                                                                    <DropdownMenuContent align="end" className="w-40">
                                                                                        <DropdownMenuItem onClick={() => handleAction('view', opportunity)}>
                                                                                            <Eye className="h-4 w-4 mr-2" />
                                                                                            {t('View')}
                                                                                        </DropdownMenuItem>
                                                                                        {hasPermission(permissions, 'edit-opportunities') && (
                                                                                            <DropdownMenuItem onClick={() => handleAction('edit', opportunity)}>
                                                                                                <Edit className="h-4 w-4 mr-2" />
                                                                                                {t('Edit')}
                                                                                            </DropdownMenuItem>
                                                                                        )}
                                                                                        {hasPermission(permissions, 'delete-opportunities') && (
                                                                                            <>
                                                                                                <DropdownMenuSeparator />
                                                                                                <DropdownMenuItem onClick={() => handleAction('delete', opportunity)} className="text-red-600">
                                                                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                                                                    {t('Delete')}
                                                                                                </DropdownMenuItem>
                                                                                            </>
                                                                                        )}
                                                                                    </DropdownMenuContent>
                                                                                </DropdownMenu>
                                                                            </div>

                                                                            <div className="text-xs text-gray-600">
                                                                                {opportunity.account?.name && <div>{opportunity.account.name}</div>}
                                                                                {opportunity.contact?.name && <div>{opportunity.contact.name}</div>}
                                                                            </div>

                                                                            <div className="flex items-center justify-between">
                                                                                <div className="text-xs text-gray-500">
                                                                                    {opportunity.amount ? (window.appSettings?.formatCurrency(parseFloat(opportunity.amount)) || `$${parseFloat(opportunity.amount).toFixed(2)}`) : t('No amount')}
                                                                                </div>
                                                                                {opportunity.assigned_user && (
                                                                                    <Avatar className="h-5 w-5 rounded-full">
                                                                                        <AvatarImage src={opportunity.assigned_user.avatar} />
                                                                                        <AvatarFallback>{getInitials(opportunity.assigned_user.name)}</AvatarFallback>
                                                                                    </Avatar>
                                                                                    // <div className="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white text-xs">
                                                                                    //     {getInitials(opportunity.assigned_user.name)}
                                                                                    // </div>
                                                                                )}
                                                                            </div>

                                                                            <div className="flex justify-between items-center text-xs text-gray-500">
                                                                                <div className="flex items-center gap-2">
                                                                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${opportunity.status === 'active'
                                                                                        ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                                                                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                                                                        }`}>
                                                                                        {opportunity.status === 'active' ? t('Active') : t('Inactive')}
                                                                                    </span>
                                                                                </div>
                                                                                <span>
                                                                                    {opportunity.close_date
                                                                                        ? (window.appSettings?.formatDateTime(opportunity.close_date, false)
                                                                                            || new Date(opportunity.close_date).toLocaleDateString())
                                                                                        : (window.appSettings?.formatDateTime(opportunity.created_at, false)
                                                                                            || new Date(opportunity.created_at).toLocaleDateString())
                                                                                    }
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </Card>
                                                            </div>
                                                        ))}
                                                        {stageOpportunities.length === 0 && (
                                                            <div className="text-center py-8 text-gray-400">
                                                                <Building2 className="h-8 w-8 mx-auto mb-2" />
                                                                <p className="text-sm">{t('No opportunities')}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="flex items-center justify-center h-full">
                                    <p className="text-gray-500 dark:text-gray-400">{t('No data available')}</p>
                                </div>
                            )}
                        </div>
                    </div>


                </>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {opportunities?.data?.map((opportunity: any) => (
                            <Card key={opportunity.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-start space-x-4">
                                            <div className="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold">
                                                {getInitials(opportunity.name)}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{opportunity.name}</h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{opportunity.account?.name || t('No account')}</p>
                                                <div className="flex items-center">
                                                    <div className={`h-2 w-2 rounded-full mr-2 ${opportunity.status === 'active' ? 'bg-green-500' : 'bg-gray-400'
                                                        }`}></div>
                                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {opportunity.status === 'active' ? t('Active') : t('Inactive')}
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
                                                {hasPermission(permissions, 'view-opportunities') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', opportunity)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Opportunity")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'toggle-status-opportunities') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', opportunity)}>
                                                        <span>{opportunity.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-opportunities') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', opportunity)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-opportunities') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', opportunity)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Opportunity info */}
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Amount')}: {opportunity.amount ? (window.appSettings?.formatCurrency(parseFloat(opportunity.amount)) || `$${parseFloat(opportunity.amount).toFixed(2)}`) : t('-')}
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Close Date')}: {opportunity.close_date || opportunity.created_at
                                                    ? (window.appSettings?.formatDateTime(opportunity.close_date || opportunity.created_at, false)
                                                        || new Date(opportunity.close_date || opportunity.created_at).toLocaleDateString())
                                                    : t('-')}
                                            </span>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            {opportunity.opportunity_stage && (
                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset " style={{
                                                    backgroundColor: `${opportunity.opportunity_stage.color}20`,
                                                    color: opportunity.opportunity_stage.color,
                                                    borderColor: `${opportunity.opportunity_stage.color}40`
                                                }}>
                                                    {opportunity.opportunity_stage.name}
                                                </span>
                                            )}
                                            {opportunity.opportunity_source && (
                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-purple-50 text-purple-700 ring-green-600/20">
                                                    {opportunity.opportunity_source.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(opportunity.created_at, false) || new Date(opportunity.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-opportunities') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', opportunity)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-opportunities') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', opportunity)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'delete-opportunities') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('delete', opportunity)}
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
                            from={opportunities?.from || 1}
                            to={opportunities?.to || opportunities?.data?.length || 0}
                            total={opportunities?.total || opportunities?.data?.length || 0}
                            links={opportunities?.links}
                            entityName={t("opportunities")}
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
                    productOptions: products,
                    fields: [
                        { name: 'name', label: t('Opportunity Name'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        { name: 'close_date', label: t('Close Date'), type: 'date' },
                        {
                            name: formMode === 'view' ? 'account_name' : 'account_id',
                            label: t('Account'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : accounts.map((account: any) => ({
                                value: account.id,
                                label: account.name
                            })),
                            emptyNote: accounts.length === 0 ? {
                                link: route('accounts.index'),
                                linkText: t('Accounts')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'contact_name' : 'contact_id',
                            label: t('Contact'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : [
                                ...contacts.map((contact: any) => ({
                                    value: contact.id,
                                    label: contact.name
                                }))
                            ],
                            emptyNote: contacts.length === 0 ? {
                                link: route('contacts.index'),
                                linkText: t('Contacts')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'opportunity_stage_name' : 'opportunity_stage_id',
                            label: t('Stage'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : opportunityStages.map((stage: any) => ({
                                value: stage.id,
                                label: stage.name
                            })),
                            defaultValue: prefilledOpportunityStage || undefined,
                            hidden: formMode === 'create' && prefilledOpportunityStage,
                            emptyNote: opportunityStages.length === 0 ? {
                                link: route('opportunity-stages.index'),
                                linkText: t('Opportunity Stages')
                            } : undefined
                        },
                        {
                            name: formMode === 'view' ? 'opportunity_source_name' : 'opportunity_source_id',
                            label: t('Source'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            options: formMode === 'view' ? [] : opportunitySources.map((source: any) => ({
                                value: source.id,
                                label: source.name
                            })),
                            emptyNote: opportunitySources.length === 0 ? {
                                link: route('opportunity-sources.index'),
                                linkText: t('Opportunity Sources')
                            } : undefined
                        },
                        {
                            name: 'products',
                            label: t('Products'),
                            type: 'array',
                            required: true,
                            productOptions: products,
                            renderFooter: (arrayValue: any[]) => {
                                let subtotal = 0;
                                let totalTax = 0;

                                arrayValue.forEach((item: any) => {
                                    const quantity = parseFloat(item.quantity) || 0;
                                    const unitPrice = parseFloat(item.unit_price) || 0;
                                    const lineTotal = quantity * unitPrice;
                                    subtotal += lineTotal;

                                    const product = products?.find((p: any) => p.id == item.product_id);
                                    if (product?.tax) {
                                        totalTax += (lineTotal * product.tax.rate) / 100;
                                    }
                                });

                                return (
                                    <>
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
                            renderSummary: (arrayValue: any[]) => {
                                let subtotal = 0;
                                let totalTax = 0;

                                arrayValue.forEach((item: any) => {
                                    const quantity = parseFloat(item.quantity) || 0;
                                    const unitPrice = parseFloat(item.unit_price) || 0;
                                    const lineTotal = quantity * unitPrice;
                                    subtotal += lineTotal;

                                    const product = products?.find((p: any) => p.id == item.product_id);
                                    if (product?.tax) {
                                        totalTax += (lineTotal * product.tax.rate) / 100;
                                    }
                                });

                                return (
                                    <div className="space-y-2">
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
                                    options: products.map((product: any) => ({
                                        value: product.id,
                                        label: `${product.name} - ${window.appSettings?.formatCurrency(parseFloat(product.price || 0)) || `$${parseFloat(product.price || 0).toFixed(2)}`}${product.tax ? ` (Tax: ${product.tax.name} - ${product.tax.rate}%)` : ''}`
                                    })),
                                    emptyNote: products.length === 0 ? {
                                        link: route('products.index'),
                                        linkText: t('Products')
                                    } : undefined
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
                                    name: 'tax_amount',
                                    label: t('Tax Amount'),
                                    type: 'calculated',
                                    calculate: (item: any) => {
                                        const product = products?.find((p: any) => p.id == item.product_id);
                                        const quantity = parseFloat(item.quantity) || 0;
                                        const unitPrice = parseFloat(item.unit_price) || 0;
                                        const lineTotal = quantity * unitPrice;
                                        const taxAmount = product?.tax ? (lineTotal * product.tax.rate) / 100 : 0;
                                        return window.appSettings?.formatCurrency(taxAmount) || `$${taxAmount.toFixed(2)}`;
                                    }
                                },
                                {
                                    name: 'line_total',
                                    label: t('Line Total'),
                                    type: 'calculated',
                                    calculate: (item: any) => {
                                        const quantity = parseFloat(item.quantity) || 0;
                                        const unitPrice = parseFloat(item.unit_price) || 0;
                                        const lineTotal = quantity * unitPrice;
                                        return window.appSettings?.formatCurrency(lineTotal) || `$${lineTotal.toFixed(2)}`;
                                    }
                                }
                            ]
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
                    account_name: currentItem.account?.name || t('-'),
                    contact_name: currentItem.contact?.name || t('-'),
                    opportunity_stage_name: currentItem.opportunity_stage?.name || t('-'),
                    opportunity_source_name: currentItem.opportunity_source?.name || t('-'),
                    products: currentItem.products?.map((product: any) => ({
                        product_id: product.id,
                        quantity: parseInt(product.pivot.quantity),
                        unit_price: parseFloat(product.pivot.unit_price)
                    })) || []
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Opportunity')
                        : formMode === 'edit'
                            ? t('Edit Opportunity')
                            : t('View Opportunity')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('opportunity')}
            />
        </PageTemplate>
    );
}
