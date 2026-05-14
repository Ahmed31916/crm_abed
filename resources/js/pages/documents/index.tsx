import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, MoreHorizontal, Download, FileText, User, Calendar, Lock, Unlock } from 'lucide-react';
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

export default function Documents() {
    const { t } = useTranslation();
    const {
        auth,
        documents,
        users = [],
        accounts = [],
        folders = [],
        types = [],
        opportunities = [],
        filters: pageFilters = {}
    } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isCompany = auth?.user?.type === 'company';

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedAccount, setSelectedAccount] = useState(pageFilters.account_id || 'all');
    const [selectedFolder, setSelectedFolder] = useState(pageFilters.folder_id || 'all');
    const [selectedType, setSelectedType] = useState(pageFilters.type_id || 'all');
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
        return searchTerm !== '' || selectedAccount !== 'all' || selectedFolder !== 'all' || selectedType !== 'all' || selectedStatus !== 'all' || selectedAssignee !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedAccount !== 'all' ? 1 : 0) + (selectedFolder !== 'all' ? 1 : 0) + (selectedType !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedAssignee !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('documents.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            folder_id: selectedFolder !== 'all' ? selectedFolder : undefined,
            type_id: selectedType !== 'all' ? selectedType : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page : pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('documents.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: pageFilters.page || 1,
            search: searchTerm || undefined,
            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
            folder_id: selectedFolder !== 'all' ? selectedFolder : undefined,
            type_id: selectedType !== 'all' ? selectedType : undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
            per_page : pageFilters.per_page || 10,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                router.get(route('documents.show', item.id));
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
            case 'download':
                if (item.attachment_url) {
                    const link = document.createElement('a');
                    link.href = route('documents.download', item.id);
                    link.download = '';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    toast.error(t('No attachment available for download'));
                }
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        // Convert 'null' strings to actual null values
        ['account_id', 'folder_id', 'type_id', 'opportunity_id', 'assigned_to'].forEach(field => {
            if (formData[field] === 'null') {
                formData[field] = null;
            }
        });

        if (formMode === 'create') {
            toast.loading(t('Creating document...'));

            router.post(route('documents.store'), formData, {
                preserveState: false,
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
            toast.loading(t('Updating document...'));

            if (!currentItem?.id) {
                toast.dismiss();
                toast.error(t('Invalid document selected'));
                return;
            }
            router.put(route("documents.update", currentItem.id), formData, {
                preserveState: false,
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
        toast.loading(t('Deleting document...'));

        if (!currentItem?.id) {
            toast.dismiss();
            toast.error(t('Invalid document selected'));
            return;
        }
        router.delete(route('documents.destroy', currentItem.id), {
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

    const handleToggleStatus = (document: any) => {
        const newStatus = document.status === 'active' ? 'inactive' : 'active';
        toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} document...`);

        router.put(route('documents.toggle-status', document.id), {}, {
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
                    toast.error(t('Failed to update status: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedAccount('all');
        setSelectedFolder('all');
        setSelectedType('all');
        setSelectedStatus('all');
        setSelectedAssignee('all');
        setShowFilters(false);

        router.get(route('documents.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    // Add the "Add Document" button if user has permission
    if (hasPermission(permissions, 'create-documents')) {
        pageActions.push({
            label: t('Add Document'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Document Management'), href: route('documents.index') },
        { title: t('Documents') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true
        },
        {
            key: 'account',
            label: t('Account'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'folder',
            label: t('Folder'),
            render: (value: any) => value?.name || t('-')
        },
        {
            key: 'type',
            label: t('Type'),
            render: (value: any) => value?.type_name || t('-')
        },

        {
            key: 'publish_date',
            label: t('Publish Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'expiration_date',
            label: t('Expiration Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
        {
            key: 'attachment_name',
            label: t('Attachment'),
            render: (value: string, item: any) => {
                if (value && item.attachment_url) {
                    return (
                        <div className="flex items-center gap-1">
                            <Button
                                size="sm"
                                variant="ghost"
                                className="h-6 w-6 p-0 text-blue-600 hover:text-blue-800"
                                onClick={() => window.open(item.attachment_url, '_blank')}
                                title={t('View Attachment')}
                            >
                                <Eye className="h-3 w-3" />
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                className="h-6 w-6 p-0 text-green-600 hover:text-green-800"
                                onClick={() => handleAction('download', item)}
                                title={t('Download')}
                            >
                                <Download className="h-3 w-3" />
                            </Button>
                        </div>
                    );
                }
                return t('-');
            }
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
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || new Date(value).toLocaleDateString()
        }
    ];

    // Define table actions
    const actions = [
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-documents'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-documents'
        },

        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-documents'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-documents'
        }
    ];

    return (
        <PageTemplate
            title={t("Documents")}
            url="/documents"
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
                            options: [
                                { value: 'all', label: t('All Accounts') },
                                { value: 'null', label: t('No Account') },
                                ...accounts.map((account: any) => ({
                                    value: account.id.toString(),
                                    label: account.name
                                }))
                            ]
                        },
                        {
                            name: 'folder_id',
                            label: t('Folder'),
                            type: 'select',
                            value: selectedFolder,
                            onChange: setSelectedFolder,
                            options: [
                                { value: 'all', label: t('All Folders') },
                                { value: 'null', label: t('No Folder') },
                                ...folders.map((folder: any) => ({
                                    value: folder.id.toString(),
                                    label: folder.name
                                }))
                            ]
                        },
                        {
                            name: 'type_id',
                            label: t('Type'),
                            type: 'select',
                            value: selectedType,
                            onChange: setSelectedType,
                            options: [
                                { value: 'all', label: t('All Types') },
                                { value: 'null', label: t('No Type') },
                                ...types.map((type: any) => ({
                                    value: type.id.toString(),
                                    label: type.type_name
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
                        router.get(route('documents.index'), {
                            view: activeView,
                            page: 1,
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            folder_id: selectedFolder !== 'all' ? selectedFolder : undefined,
                            type_id: selectedType !== 'all' ? selectedType : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('documents.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            account_id: selectedAccount !== 'all' ? selectedAccount : undefined,
                            folder_id: selectedFolder !== 'all' ? selectedFolder : undefined,
                            type_id: selectedType !== 'all' ? selectedType : undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            assigned_to: selectedAssignee !== 'all' ? selectedAssignee : undefined,
                            per_page : pageFilters.per_page || 10,
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
                        data={documents?.data || []}
                        from={documents?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-documents',
                            create: 'create-documents',
                            edit: 'edit-documents',
                            delete: 'delete-documents'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={documents?.from || 0}
                        to={documents?.to || 0}
                        total={documents?.total || 0}
                        links={documents?.links}
                        entityName={t("documents")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {documents?.data?.map((document: any) => (
                            <Card key={document.id} className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div className="p-5">
                                    <div className="flex items-start justify-between mb-4">
                                        <div className="flex items-start space-x-4">
                                            <div className="h-12 w-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white flex items-center justify-center shadow-sm">
                                                <FileText className="h-6 w-6" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-1 line-clamp-2">{document.name}</h3>
                                                <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">{document.account?.name || t('No account')}</p>
                                                <div className="flex items-center">
                                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${document.status === 'active'
                                                        ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                                        }`}>
                                                        {document.status === 'active' ? t('Active') : t('Inactive')}
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
                                                {hasPermission(permissions, 'view-documents') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', document)}>
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        <span>{t("View Document")}</span>
                                                    </DropdownMenuItem>
                                                )}

                                                {hasPermission(permissions, 'view-documents') && document.attachment_url && (
                                                    <DropdownMenuItem onClick={() => handleAction('download', document)}>
                                                        <Download className="h-4 w-4 mr-2" />
                                                        <span>{t("Download")}</span>
                                                    </DropdownMenuItem>
                                                )}

                                                {hasPermission(permissions, 'toggle-status-documents') && (
                                                    <DropdownMenuItem onClick={() => handleAction('toggle-status', document)}>
                                                        {document.status === 'active' ?
                                                            <Lock className="h-4 w-4 mr-2" /> :
                                                            <Unlock className="h-4 w-4 mr-2" />
                                                        }
                                                        <span>{document.status === 'active' ? t("Deactivate") : t("Activate")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuSeparator />
                                                {hasPermission(permissions, 'edit-documents') && (
                                                    <DropdownMenuItem onClick={() => handleAction('edit', document)} className="text-amber-600">
                                                        <Edit className="h-4 w-4 mr-2" />
                                                        <span>{t("Edit")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                                {hasPermission(permissions, 'delete-documents') && (
                                                    <DropdownMenuItem onClick={() => handleAction('delete', document)} className="text-rose-600">
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        <span>{t("Delete")}</span>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>

                                    {/* Document info */}
                                    <div className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 mb-4 space-y-2">
                                        <div className="grid grid-cols-1 gap-2 text-xs">
                                            <div className="flex justify-between">
                                                <span className="text-gray-500 dark:text-gray-400 font-medium">{t('Folder')}:</span>
                                                <span className="text-gray-700 dark:text-gray-300 truncate ml-2">{document.folder?.name || t('-')}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500 dark:text-gray-400 font-medium">{t('Type')}:</span>
                                                <span className="text-gray-700 dark:text-gray-300 truncate ml-2">{document.type?.type_name || t('-')}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-500 dark:text-gray-400 font-medium">{t('Opportunity')}:</span>
                                                <span className="text-gray-700 dark:text-gray-300 truncate ml-2">{document.opportunity?.name || t('-')}</span>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            {document.attachment_name && (
                                                <div className="flex items-center gap-1 bg-white dark:bg-gray-700 rounded-md px-2 py-1 border border-gray-200 dark:border-gray-600">
                                                    <FileText className="h-3 w-3 text-blue-500" />
                                                    <span className="text-xs font-medium text-gray-700 dark:text-gray-300 truncate max-w-20">
                                                        {document.attachment_name}
                                                    </span>
                                                    <div className="flex items-center gap-0.5 ml-1">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-5 w-5 p-0 text-blue-500 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                                            onClick={() => window.open(document.attachment_url, '_blank')}
                                                            title={t('View Attachment')}
                                                        >
                                                            <Eye className="h-3 w-3" />
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-5 w-5 p-0 text-green-500 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/30"
                                                            onClick={() => handleAction('download', document)}
                                                            title={t('Download')}
                                                        >
                                                            <Download className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}
                                            {document.assigned_user && (
                                                <span className="inline-flex items-center rounded-md bg-purple-100 dark:bg-purple-900/30 px-2 py-1 text-xs font-medium text-purple-700 dark:text-purple-300">
                                                    <User className="h-3 w-3 mr-1" />
                                                    {document.assigned_user.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Created date */}
                                    <div className="text-xs text-gray-400 dark:text-gray-500 mb-4 flex items-center">
                                        <Calendar className="h-3 w-3 mr-1" />
                                        {window.appSettings?.formatDateTime(document.created_at, false) || new Date(document.created_at).toLocaleDateString()}
                                    </div>

                                    {/* Action buttons */}
                                    <div className="flex gap-2">
                                        {hasPermission(permissions, 'view-documents') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('view', document)}
                                                className="flex-1 h-8 text-xs font-medium border-gray-200 dark:border-gray-600 hover:bg-blue-50 hover:border-blue-300 dark:hover:bg-blue-900/20"
                                            >
                                                <Eye className="h-3.5 w-3.5 mr-1.5" />
                                                {t("View")}
                                            </Button>
                                        )}

                                        {hasPermission(permissions, 'edit-documents') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleAction('edit', document)}
                                                className="flex-1 h-8 text-xs font-medium border-gray-200 dark:border-gray-600 hover:bg-amber-50 hover:border-amber-300 dark:hover:bg-amber-900/20"
                                            >
                                                <Edit className="h-3.5 w-3.5 mr-1.5" />
                                                {t("Edit")}
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
                            from={documents?.from || 0}
                            to={documents?.to || 0}
                            total={documents?.total || 0}
                            links={documents?.links}
                            entityName={t("documents")}
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
                        { name: 'name', label: t('Document Name'), type: 'text', required: true },
                        {
                            name: formMode === 'view' ? 'account_name' : 'account_id',
                            label: t('Account'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('accounts.index'), linkText: t('Accounts') },
                            options: formMode === 'view' ? [] : accounts.map((account: any) => ({
                                value: account.id,
                                label: account.name
                            }))
                        },
                        {
                            name: formMode === 'view' ? 'folder_name' : 'folder_id',
                            label: t('Folder'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('document-folders.index'), linkText: t('Document Folders') },
                            options: formMode === 'view' ? [] : folders.map((folder: any) => ({
                                value: folder.id,
                                label: folder.name
                            }))
                        },
                        {
                            name: formMode === 'view' ? 'type_name' : 'type_id',
                            label: t('Type'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('document-types.index'), linkText: t('Document Types') },
                            options: formMode === 'view' ? [] : types.map((type: any) => ({
                                value: type.id,
                                label: type.type_name
                            }))
                        },
                        {
                            name: formMode === 'view' ? 'opportunity_name' : 'opportunity_id',
                            label: t('Opportunity'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            readOnly: formMode === 'view',
                            emptyNote: { link: route('opportunities.index'), linkText: t('Opportunities') },
                            options: formMode === 'view' ? [] : opportunities.map((opportunity: any) => ({
                                value: opportunity.id,
                                label: opportunity.name
                            }))
                        },
                        { name: 'publish_date', label: t('Publish Date'), type: 'date' },
                        { name: 'expiration_date', label: t('Expiration Date'), type: 'date' },
                        {
                            name: 'attachment',
                            label: t('Attachment'),
                            required:true,
                            type: 'media-picker',
                            placeholder: t('Select file...')
                        },
                        ...(isCompany ? [{
                            name: formMode === 'view' ? 'assigned_user_name' : 'assigned_to',
                            label: t('Assign To'),
                            type: formMode === 'view' ? 'text' : 'select',
                            required: true,
                            emptyNote: { link: route('users.index'), linkText: t('Users') },
                            options: formMode === 'view' ? [] : users.map((user: any) => ({ value: user.id, label: `${user.name} (${user.email})` })),
                            readOnly: formMode === 'view'
                        }] : []),
                        { name: 'description', label: t('Description'), type: 'textarea' },
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
                    account_name: currentItem.account?.name || t('No Account'),
                    folder_name: currentItem.folder?.name || t('No Folder'),
                    type_name: currentItem.type?.type_name || t('No Type'),
                    opportunity_name: currentItem.opportunity?.name || t('No Opportunity'),
                    attachment: currentItem.attachment_url || ''
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add Document')
                        : formMode === 'edit'
                            ? t('Edit Document')
                            : t('View Document')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName={t('document')}
            />
        </PageTemplate>
    );
}
