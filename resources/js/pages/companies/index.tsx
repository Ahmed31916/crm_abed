// pages/companies/index.tsx
import { useEffect, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Card } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Plus, Eye, Edit, Trash2, KeyRound, Lock, Unlock, ArrowUpRight, CreditCard, History, Info } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { UpgradePlanModal } from '@/components/UpgradePlanModal';
import { hasPermission } from '@/utils/authorization';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { capitalize, getDisplayUrl } from '@/utils/helper';

export default function Companies() {
    const { t } = useTranslation();
    const { auth, companies, plans, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const getInitials = useInitials();

    // State
    const [activeView, setActiveView] = useState(
        ['list', 'grid'].includes(pageFilters.view) ? pageFilters.view : 'list'
    );
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [startDate, setStartDate] = useState<Date | undefined>(pageFilters.start_date ? new Date(pageFilters.start_date) : undefined);
    const [endDate, setEndDate] = useState<Date | undefined>(pageFilters.end_date ? new Date(pageFilters.end_date) : undefined);
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
    const [showFilters, setShowFilters] = useState(false);

    // Modal state
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isResetPasswordModalOpen, setIsResetPasswordModalOpen] = useState(false);
    const [isUpgradePlanModalOpen, setIsUpgradePlanModalOpen] = useState(false);

    const [currentCompany, setCurrentCompany] = useState<any>(null);
    const [availablePlans, setAvailablePlans] = useState<any[]>([]);


    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return selectedStatus !== 'all' || searchTerm !== '' || startDate !== undefined || endDate !== undefined;
    };

    // Count active filters
    const activeFilterCount = () => {
        return (selectedStatus !== 'all' ? 1 : 0) +
            (searchTerm ? 1 : 0) +
            (startDate ? 1 : 0) +
            (endDate ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('companies.index'), {
            view: activeView,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            start_date: startDate ? startDate.toISOString().split('T')[0] : undefined,
            end_date: endDate ? endDate.toISOString().split('T')[0] : undefined,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        router.get(route('companies.index'), {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== 'all' ? selectedStatus : undefined,
            start_date: startDate ? startDate.toISOString().split('T')[0] : undefined,
            end_date: endDate ? endDate.toISOString().split('T')[0] : undefined,
            ...(pageFilters.per_page && pageFilters.per_page !== 10 && { per_page: pageFilters.per_page })
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, company: any) => {
        setCurrentCompany(company);

        switch (action) {
            case 'login-as':
                router.get(route("impersonate.start", company.id));
                break;
            case 'company-info':
                setFormMode('view');
                setIsFormModalOpen(true);
                break;
            case 'upgrade-plan':
                handleUpgradePlan(company);
                break;

            case 'reset-password':
                setIsResetPasswordModalOpen(true);
                break;
            case 'toggle-status':
                handleToggleStatus(company);
                break;
            case 'edit':
                setFormMode('edit');
                setIsFormModalOpen(true);
                break;
            case 'delete':
                setIsDeleteModalOpen(true);
                break;
            default:
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentCompany(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        if (formMode === 'create') {
            toast.loading(t('Creating company...'));

            router.post(route('companies.store'), formData, {
                forceFormData: true,
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
                        toast.error(`Failed to create company: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating company...'));

            router.put(route('companies.update', currentCompany.id), formData, {
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
                        toast.error(`Failed to update company: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting company...'));

        router.delete(route("companies.destroy", currentCompany.id), {
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
                    toast.error(`Failed to delete company: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleResetPasswordConfirm = (data: { password: string }) => {
        toast.loading(t('Resetting password...'));

        router.put(route('companies.reset-password', currentCompany.id), data, {
            onSuccess: (page) => {
                setIsResetPasswordModalOpen(false);
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
                    toast.error(`Failed to reset password: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleToggleStatus = (company: any) => {
        toast.loading(t('Updating status...'));

        router.put(route('companies.toggle-status', company.id), {}, {
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
                    toast.error(`Failed to update status: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSelectedStatus('all');
        setSearchTerm('');
        setStartDate(undefined);
        setEndDate(undefined);
        setShowFilters(false);

        router.get(route('companies.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    const handleUpgradePlan = (company: any) => {
        setCurrentCompany(company);

        // Fetch available plans
        toast.loading(t('Loading plans...'));
        fetch(route('companies.plans', company.id))
            .then(res => res.json())
            .then(data => {
                setAvailablePlans(data.plans);
                setIsUpgradePlanModalOpen(true);
                toast.dismiss();
            })
            .catch(err => {
                toast.dismiss();
                toast.error(t('Failed to load plans'));
            });
    };

    const handleUpgradePlanConfirm = (planId: number, duration: string) => {
        toast.loading(t('Upgrading plan...'));

        // Use Inertia router to handle the request
        router.put(route('companies.upgrade-plan', currentCompany.id), {
            plan_id: planId,
            duration: duration

        }, {
            onSuccess: (page) => {
                setIsUpgradePlanModalOpen(false);
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
                router.reload();
            },
            onError: (errors) => {
                toast.dismiss();
                if (typeof errors === 'string') {
                    toast.error(errors);
                } else {
                    toast.error(`Failed to upgrade plan: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };


    // Define page actions
    const pageActions = [];

    // Add User Logs button for superadmin
    if (auth?.user?.type === 'superadmin' && hasPermission(permissions, 'manage-login-history')) {
        pageActions.push({
            icon: <History className="h-4 w-4 mx-auto" />,
            variant: 'outline',
            onClick: () => router.visit(route('login-history.index')),
            tooltip: t('Login History')
        });
    }

    pageActions.push({
        label: t('Add Company'),
        icon: <Plus className="h-4 w-4 mr-2" />,
        variant: 'default',
        onClick: () => handleAddNew()
    });

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Companies') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true,
            render: (value: any, row: any) => (
                <div className="flex items-center gap-3">
                    <Avatar className="h-10 w-10">
                        <AvatarImage src={row.avatar} />
                        <AvatarFallback>{getInitials(row.name)}</AvatarFallback>
                    </Avatar>
                    <div>
                        <div className="font-medium">{row.name}</div>
                        <div className="text-sm text-muted-foreground">{row.email}</div>
                    </div>
                </div>
            )
        },
        {
            key: 'plan_name',
            label: t('Plan'),
            render: (value: string) => <span className={'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'}>
                {capitalize(value)}
            </span>
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
            label: t('Login as Company'),
            icon: 'ArrowUpRight',
            action: 'login-as',
            className: 'text-blue-500'
        },
        {
            label: t('Company Info'),
            icon: 'Info',
            action: 'company-info',
            className: 'text-blue-500'
        },
        {
            label: t('Upgrade Plan'),
            icon: 'CreditCard',
            action: 'upgrade-plan',
            className: 'text-amber-500'
        },
        {
            label: t('Reset Password'),
            icon: 'KeyRound',
            action: 'reset-password',
            className: 'text-blue-500'
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500'
        }
    ];

    return (
        <PageTemplate
            title={t("Companies")}
            url="/companies"
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
                                { value: 'inactive', label: t('Inactive') }
                            ]
                        },
                        {
                            name: 'start_date',
                            label: t('Start Date'),
                            type: 'date',
                            value: startDate,
                            onChange: (date) => setStartDate(date)
                        },
                        {
                            name: 'end_date',
                            label: t('End Date'),
                            type: 'date',
                            value: endDate,
                            onChange: (date) => setEndDate(date)
                        }
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={pageFilters.per_page?.toString() || "10"}
                    onPerPageChange={(value) => {
                        const params: any = {
                            view: activeView,
                            page: 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            start_date: startDate ? startDate.toISOString().split('T')[0] : undefined,
                            end_date: endDate ? endDate.toISOString().split('T')[0] : undefined,
                        };
                        if (parseInt(value) !== 10) {
                            params.per_page = parseInt(value);
                        }
                        router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('companies.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            status: selectedStatus !== 'all' ? selectedStatus : undefined,
                            start_date: startDate ? startDate.toISOString().split('T')[0] : undefined,
                            end_date: endDate ? endDate.toISOString().split('T')[0] : undefined,
                            sort_field: pageFilters.sort_field || undefined,
                            sort_direction: pageFilters.sort_direction || undefined,
                            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
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
                        data={companies?.data || []}
                        from={companies?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-companies',
                            create: 'create-companies',
                            edit: 'edit-companies',
                            delete: 'delete-companies'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={companies?.from || 0}
                        to={companies?.to || 0}
                        total={companies?.total || 0}
                        links={companies?.links}
                        entityName={t("companies")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {companies?.data?.map((company: any) => (
                            <Card key={company.id} className="group relative overflow-hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300">
                                {/* Status Badge */}
                                <div className="absolute top-4 right-4 z-10">
                                    <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${company.status === 'active'
                                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'
                                        : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                        }`}>
                                        {company.status === 'active' ? t('Active') : t('Inactive')}
                                    </div>
                                </div>

                                {/* Card Content */}
                                <div className="p-6">
                                    {/* Company Header */}
                                    <div className="flex items-start space-x-4 mb-6">
                                        <div className="relative">
                                            <Avatar className="h-14 w-14 rounded-full object-cover shadow-sm">
                                                <AvatarImage
                                                    src={company.avatar}
                                                    alt={company?.name || 'Avatar'}
                                                    onError={(e) => {
                                                        // Fallback to default avatar on error
                                                        const target = e.target as HTMLImageElement;
                                                        target.src = getDisplayUrl('avatars/avatar.png');
                                                    }}
                                                />
                                                <AvatarFallback className="text-lg">
                                                    {company.name?.charAt(0)?.toUpperCase() || 'U'}
                                                </AvatarFallback>
                                            </Avatar>
                                        </div>
                                        <div className="flex-1 min-w-0 max-w-80">
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1 mr-10">
                                                {company.name}
                                            </h3>
                                            <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
                                                {company.email}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Plan Information */}
                                    <div className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 mb-6">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center">
                                                <CreditCard className="h-4 w-4 text-primary mr-2" />
                                                <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {company.plan_name}
                                                </span>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleAction('upgrade-plan', company)}
                                                className="h-6 px-2 text-xs text-primary hover:text-primary hover:bg-primary/10"
                                            >
                                                {t("Upgrade")}
                                            </Button>
                                        </div>
                                        {company.plan_expiry_date && (
                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                {t("Expires")}: {window.appSettings?.formatDateTime(company.plan_expiry_date, false) || new Date(company.plan_expiry_date).toLocaleDateString()}
                                            </div>
                                        )}
                                    </div>

                                    {/* Quick Actions */}
                                    <div className="flex items-center justify-between">
                                        <div className="flex space-x-1">
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleAction('login-as', company)}
                                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                                    >
                                                        <ArrowUpRight className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>{t("Login as Company")}</TooltipContent>
                                            </Tooltip>

                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleAction('company-info', company)}
                                                        className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                                    >
                                                        <Info className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>{t("Company Info")}</TooltipContent>
                                            </Tooltip>

                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleAction('edit', company)}
                                                        className="h-8 w-8 p-0 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>{t("Edit")}</TooltipContent>
                                            </Tooltip>
                                        </div>

                                        {/* More Actions Dropdown */}
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="12" cy="5" r="1"></circle>
                                                        <circle cx="12" cy="19" r="1"></circle>
                                                    </svg>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                                                <DropdownMenuItem onClick={() => handleAction('reset-password', company)}>
                                                    <KeyRound className="h-4 w-4 mr-2" />
                                                    <span>{t("Reset Password")}</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => handleAction('toggle-status', company)}>
                                                    {company.status === 'active' ?
                                                        <Lock className="h-4 w-4 mr-2" /> :
                                                        <Unlock className="h-4 w-4 mr-2" />
                                                    }
                                                    <span>{company.status === 'active' ? t("Disable Login") : t("Enable Login")}</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={() => handleAction('delete', company)} className="text-red-600 focus:text-red-600">
                                                    <Trash2 className="h-4 w-4 mr-2" />
                                                    <span>{t("Delete")}</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                {/* Hover Effect Overlay - REMOVED */}
                            </Card>
                        ))}

                        {(!companies?.data || companies.data.length === 0) && (
                            <div className="col-span-full">
                                <div className="text-center py-12">
                                    <div className="mx-auto h-24 w-24 text-gray-300 dark:text-gray-600 mb-4">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" className="w-full h-full">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">{t("No companies found")}</h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-6">{t("Get started by creating your first company")}</p>
                                    <Button onClick={handleAddNew} className="inline-flex items-center">
                                        <Plus className="h-4 w-4 mr-2" />
                                        {t("Add Company")}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Pagination for grid view */}
                    <div className="mt-8">
                        <div className="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <Pagination
                                from={companies?.from || 0}
                                to={companies?.to || 0}
                                total={companies?.total || 0}
                                links={companies?.links}
                                entityName={t("companies")}
                                onPageChange={(url) => router.get(url)}
                            />
                        </div>
                    </div>
                </div>
            )}

            {/* Form Modal */}
            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={(data) => {
                    // If login_enabled is false, remove password field
                    if (data.login_enabled === false) {
                        delete data.password;
                    }
                    // Set status based on login_enabled
                    data.status = data.login_enabled ? 'active' : 'inactive';

                    // Remove login_enabled field as it's not needed in the backend
                    delete data.login_enabled;
                    handleFormSubmit(data);
                }}
                formConfig={{
                    fields: [
                        { name: 'name', label: t('Company Name'), type: 'text', required: true },
                        { name: 'email', label: t('Email'), type: 'email', required: true },
                        {
                            name: 'login_enabled',
                            label: t('Enable Login'),
                            placeholder: '', // Empty placeholder to prevent duplicate label
                            type: 'switch',
                            defaultValue: true,
                            conditional: (mode) => mode !== 'view' && mode !== 'edit'
                        },
                        {
                            name: 'password',
                            label: t('Password'),
                            type: 'password',
                            required: (mode) => mode === 'create',
                            conditional: (mode, data) => {
                                return mode !== 'view' && data?.login_enabled === true && mode !== 'edit';
                            }
                        }
                    ],
                    modalSize: 'lg'
                }}
                initialData={{
                    ...currentCompany,
                    login_enabled: currentCompany?.status === 'active'
                }}
                title={
                    formMode === 'create'
                        ? t('Add Company')
                        : formMode === 'edit'
                            ? t('Edit Company')
                            : t('View Company')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentCompany?.name || ''}
                entityName="company"
            />

            {/* Reset Password Modal */}
            <CrudFormModal
                isOpen={isResetPasswordModalOpen}
                onClose={() => setIsResetPasswordModalOpen(false)}
                onSubmit={handleResetPasswordConfirm}
                formConfig={{
                    fields: [
                        { name: 'password', label: t('New Password'), type: 'password', required: true }
                    ],
                    modalSize: 'sm'
                }}
                initialData={{}}
                title={`Reset Password for ${currentCompany?.name || 'Company'}`}
                mode="edit"
            />

            {/* Upgrade Plan Modal */}
            <UpgradePlanModal
                isOpen={isUpgradePlanModalOpen}
                onClose={() => setIsUpgradePlanModalOpen(false)}
                onConfirm={handleUpgradePlanConfirm}
                plans={availablePlans}
                currentPlanId={currentCompany?.plan_id}
                companyName={currentCompany?.name || ''}
            />


        </PageTemplate>
    );
}
