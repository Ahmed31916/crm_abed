import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal } from 'lucide-react';
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

export default function Campaigns() {
    const { t } = useTranslation();
    const { auth, campaigns, campaignTypes, targetLists, users, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedCampaignType, setSelectedCampaignType] = useState(pageFilters.campaign_type_id || 'all');
    const [selectedTargetList, setSelectedTargetList] = useState(pageFilters.target_list_id || 'all');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
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
        return searchTerm !== '' || selectedCampaignType !== 'all' || selectedTargetList !== 'all' || selectedStatus !== 'all' || selectedAssignee !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedCampaignType !== 'all' ? 1 : 0) + (selectedTargetList !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('campaigns.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            campaign_type_id: selectedCampaignType !== 'all' ? selectedCampaignType : undefined,
            target_list_id: selectedTargetList !== 'all' ? selectedTargetList : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('campaigns.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: pageFilters.page || 1,
            search: searchTerm || undefined,
            campaign_type_id: selectedCampaignType !== 'all' ? selectedCampaignType : undefined,
            target_list_id: selectedTargetList !== 'all' ? selectedTargetList : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page: pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.visit(route('campaigns.show', item.id));
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
            toast.loading(t('Creating campaign...'));

            router.post(route('campaigns.store'), formData, {
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
                        toast.error(t('Failed to create campaign: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating campaign...'));

            router.put(route('campaigns.update', currentItem.id), formData, {
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
                        toast.error(t('Failed to update campaign: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting campaign...'));

        router.delete(route('campaigns.destroy', currentItem.id), {
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
                    toast.error(t('Failed to delete campaign: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleToggleStatus = (campaign: any) => {
        const newStatus = campaign.status === 'active' ? 'inactive' : 'active';
        toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} campaign...`);

        router.put(route('campaigns.toggle-status', campaign.id), {}, {
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
                    toast.error(t('Failed to update campaign status: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedCampaignType('all');
        setSelectedTargetList('all');
        setSelectedStatus('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('campaigns.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    // Add the "Add Campaign" button if user has permission
    if (hasPermission(permissions, 'create-campaigns')) {
        pageActions.push({
            label: t('Add Campaign'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Campaign Management'), href: route('campaigns.index') },
        { title: t('Campaigns') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true,
            render: (value: any, row: any) => {
                return (
                    <div>
                        <div className="font-medium">{row.name}</div>
                        <div className="text-sm text-muted-foreground">{row.campaign_type?.name}</div>
                    </div>
                );
            }
        },
        {
            key: 'start_date',
            label: t('Start Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'end_date',
            label: t('End Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'budget',
            label: t('Budget'),
            sortable: true,
            render: (value: any) => value ? (window.appSettings?.formatCurrency(parseFloat(value)) || `$${parseFloat(value).toFixed(2)}`) : '-'
        },
        {
            key: 'actual_cost',
            label: t('Actual Cost'),
            sortable: true,
            render: (value: any) => window.appSettings?.formatCurrency(parseFloat(value || 0)) || `$${parseFloat(value || 0).toFixed(2)}`
        },
        {
            key: 'target_list',
            label: t('Target List'),
            render: (value: any) => value?.name || '-'
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
            requiredPermission: 'toggle-status-campaigns'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-campaigns'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-campaigns'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-campaigns'
        }
    ];

    // Prepare filter options
    const campaignTypeOptions = [
        { value: 'all', label: t('All Campaign Types') },
        ...(campaignTypes || []).map((type: any) => ({
            value: type.id.toString(),
            label: type.name
        }))
    ];

    const targetListOptions = [
        { value: 'all', label: t('All Target Lists') },
        ...(targetLists || []).map((list: any) => ({
            value: list.id.toString(),
            label: list.name
        }))
    ];

    const statusOptions = [
        { value: 'all', label: t('All Statuses') },
        { value: 'active', label: t('Active') },
        { value: 'inactive', label: t('Inactive') }
    ];

    return (
        <PageTemplate
            title={t("Campaigns")}
            url="/campaigns"
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
                            name: 'campaign_type_id',
                            label: t('Campaign Type'),
                            type: 'select',
                            value: selectedCampaignType,
                            onChange: setSelectedCampaignType,
                            options: campaignTypeOptions
                        },
                        {
                            name: 'target_list_id',
                            label: t('Target List'),
                            type: 'select',
                            value: selectedTargetList,
                            onChange: setSelectedTargetList,
                            options: targetListOptions
                        },
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: statusOptions
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
                        router.get(route('campaigns.index'), {
                            view: activeView,
                            page: 1,
                            search: searchTerm || undefined,
                            campaign_type_id: selectedCampaignType !== 'all' ? selectedCampaignType : undefined,
                            target_list_id: selectedTargetList !== 'all' ? selectedTargetList : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page: parseInt(value),
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('campaigns.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            campaign_type_id: selectedCampaignType !== 'all' ? selectedCampaignType : undefined,
                            target_list_id: selectedTargetList !== 'all' ? selectedTargetList : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
                        data={campaigns?.data || []}
                        from={campaigns?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-campaigns',
                            create: 'create-campaigns',
                            edit: 'edit-campaigns',
                            delete: 'delete-campaigns'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={campaigns?.from || 0}
                        to={campaigns?.to || 0}
                        total={campaigns?.total || 0}
                        links={campaigns?.links}
                        entityName={t("campaigns")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {campaigns?.data?.map((campaign: any) => (
                            <Card key={campaign.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex-1 min-w-0">
                                            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{campaign.name}</h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{campaign.campaign_type?.name}</p>
                                            <div className="flex items-center">
                                                <div className={`h-2 w-2 rounded-full mr-2 ${campaign.status === 'active' ? 'bg-green-500' : 'bg-gray-400'
                                                    }`}></div>
                                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {campaign.status === 'active' ? t('Active') : t('Inactive')}
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
                                                {hasPermission(permissions, 'view-campaigns') && (
                                                    <DropdownMenuItem onClick={() => router.visit(route('campaigns.show', campaign.id))}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Campaign")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'toggle-status-campaigns') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', campaign)}>
                                                        <span>{campaign.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-campaigns') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', campaign)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-campaigns') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', campaign)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Campaign info */}
                                    <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Duration')}: {window.appSettings?.formatDateTime(campaign.start_date,false)} - {window.appSettings?.formatDateTime(campaign.end_date,false)}
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Budget')}: {campaign.budget ? (window.appSettings?.formatCurrency(parseFloat(campaign.budget)) || `$${parseFloat(campaign.budget).toFixed(2)}`) : '-'}
                                            </span>
                                        </div>
                                        <div className="mb-2">
                                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                                {t('Actual Cost')}: {window.appSettings?.formatCurrency(parseFloat(campaign.actual_cost || 0)) || `$${parseFloat(campaign.actual_cost || 0).toFixed(2)}`}
                                            </span>
                                        </div>
                                        {campaign.target_list && (
                                            <div className="flex flex-wrap gap-1">
                                                <span className="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                    {campaign.target_list.name}
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {t("Created:")} {window.appSettings?.formatDateTime(campaign.created_at, false) || new Date(campaign.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'edit-campaigns') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', campaign)}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Edit className="h-4 w-4 mr-2" />
                                                {t("Edit")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'view-campaigns') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(route('campaigns.show', campaign.id))}
                                                className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                                            >
                                                <Eye className="h-4 w-4 mr-2" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'delete-campaigns') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('delete', campaign)}
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
                            from={campaigns?.from || 0}
                            to={campaigns?.to || 0}
                            total={campaigns?.total || 0}
                            links={campaigns?.links}
                            entityName={t("campaigns")}
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
                        { name: 'name', label: t('Campaign Name'), type: 'text', required: true },
                        { name: 'description', label: t('Description'), type: 'textarea' },
                        { name: 'start_date', label: t('Start Date'), type: 'date', required: true },
                        { name: 'end_date', label: t('End Date'), type: 'date', required: true },
                        { name: 'budget', label: t('Budget'), type: 'number', step: '0.01', min: '0' },
                        { name: 'actual_cost', label: t('Actual Cost'), type: 'number', step: '0.01', min: '0', readOnly: formMode === 'create' },
                        { name: 'expected_response', label: t('Expected Response'), type: 'number', min: '0' },
                        { name: 'actual_response', label: t('Actual Response'), type: 'number', min: '0', readOnly: formMode === 'create' },
                        {
                            name: 'campaign_type_id',
                            label: t('Campaign Type'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('campaign-types.index'), linkText: t('Campaign Types') },
                            options: campaignTypes ? campaignTypes.map((type: any) => ({
                                value: type.id.toString(),
                                label: type.name
                            })) : []
                        },
                        {
                            name: 'target_list_id',
                            label: t('Target List'),
                            type: 'select',
                            required: true,
                            emptyNote: { link: route('target-lists.index'), linkText: t('Target Lists') },
                            options: [
                                ...targetLists ? targetLists.map((list: any) => ({
                                    value: list.id.toString(),
                                    label: list.name
                                })) : []
                            ]
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
                    assigned_user_name: currentItem.assigned_user?.name || t('Unassigned')
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Campaign')
                        : formMode === 'edit'
                            ? t('Edit Campaign')
                            : t('View Campaign')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('campaign')}
            />
        </PageTemplate>
    );
}
