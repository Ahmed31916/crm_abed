// pages/users/index.tsx
import { useState, useEffect } from 'react';
import { usersConfig } from '@/config/crud/users';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Card } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Filter, Search, Plus, Eye, Edit, Trash2, KeyRound, Lock, Unlock, History } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';
import { getDisplayUrl } from '@/utils/helper';

export default function Users() {
    const { t } = useTranslation();
    const { auth, users, roles, planLimits, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const getInitials = useInitials();

    // State
    const [activeView, setActiveView] = useState(
        ['list', 'grid'].includes(pageFilters.view) ? pageFilters.view : 'list'
    );
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedRole, setSelectedRole] = useState(pageFilters.role || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isResetPasswordModalOpen, setIsResetPasswordModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return selectedRole !== 'all' || searchTerm !== '';
    };

    // Count active filters
    const activeFilterCount = () => {
        return selectedRole !== 'all' ? 1 : 0;
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        const params: any = { page: 1, view: activeView };

        if (searchTerm) {
            params.search = searchTerm;
        }

        if (selectedRole !== 'all') {
            params.role = selectedRole;
        }

        // Add per_page if it exists
        if (pageFilters.per_page) {
            params.per_page = pageFilters.per_page;
        }

        router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc';

        const params: any = {
            view: activeView,
            sort_field: field,
            sort_direction: direction,
            page: 1
        };

        // Add search and filters
        if (searchTerm) {
            params.search = searchTerm;
        }

        if (selectedRole !== 'all') {
            params.role = selectedRole;
        }

        // Add per_page if it exists
        if (pageFilters.per_page) {
            params.per_page = pageFilters.per_page;
        }

        router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);

        switch (action) {
            case 'view':
                setFormMode('view');
                setIsFormModalOpen(true);
                break;
            case 'edit':
                setFormMode('edit');
                setIsFormModalOpen(true);
                break;
            case 'delete':
                setIsDeleteModalOpen(true);
                break;
            case 'reset-password':
                setIsResetPasswordModalOpen(true);
                break;
            case 'toggle-status':
                handleToggleStatus(item);
                break;
            default:
                break;
        }
    };

    const handleAddNew = () => {
        setCurrentItem(null);
        setFormMode('create');
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (formData: any) => {
        // Keep roles as single string value, not array
        if (formData.roles && Array.isArray(formData.roles)) {
            formData.roles = formData.roles[0];
        }

        if (formMode === 'create') {
            toast.loading(t('Creating user...'));

            router.post(route('users.store'), formData, {
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
                        toast.error(`Failed to create user: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating user...'));

            router.put(route("users.update", currentItem.id), formData, {
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
                        toast.error(`Failed to update user: ${Object.values(errors).join(', ')}`);
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting user...'));

        router.delete(route("users.destroy", currentItem.id), {
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
                    toast.error(`Failed to delete user: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleResetPasswordConfirm = (data: { password: string, password_confirmation: string }) => {
        toast.loading(t('Resetting password...'));

        router.put(route('users.reset-password', currentItem.id), data, {
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

    const handleToggleStatus = (user: any) => {
        const newStatus = user.status === 'active' ? 'inactive' : 'active';
        toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} user...`);

        router.put(route('users.toggle-status', user.id), {}, {
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
                    toast.error(`Failed to update user status: ${Object.values(errors).join(', ')}`);
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSelectedRole('all');
        setSearchTerm('');
        setShowFilters(false);

        router.get(route('users.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    // Add User Logs button
    if (hasPermission(permissions, 'manage-login-history')) {
        pageActions.push({
            icon: <History className="h-4 w-4 mx-auto" />,
            variant: 'outline',
            onClick: () => router.visit(route('login-history.index')),
            tooltip: t('Login History')
        });
    }

    // Add the "Add New User" button if user has permission and within limits
    if (hasPermission(permissions, 'create-users')) {
        const canCreate = !planLimits || planLimits.can_create;
        pageActions.push({
            label: planLimits && !canCreate ? t('User Limit Reached ({{current}}/{{max}})', { current: planLimits.current_users, max: planLimits.max_users }) : t('Add User'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: canCreate ? 'default' : 'outline',
            onClick: canCreate ? () => handleAddNew() : () => toast.error(t('User limit exceeded. Your plan allows maximum {{max}} users. Please upgrade your plan.', { max: planLimits.max_users })),
            disabled: !canCreate
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Staff'), href: route('users.index') },
        { title: t('Users') }
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
                        <Avatar className="h-10 w-10">
                            <AvatarImage src={row.avatar} />
                            <AvatarFallback>{getInitials(row.name)}</AvatarFallback>
                        </Avatar>
                        <div>
                            <div className="font-medium">{row.name}</div>
                            <div className="text-sm text-muted-foreground">{row.email}</div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'roles',
            label: t('Roles'),
            render: (value: any) => {
                if (!value || !value.length) return <span className="text-muted-foreground">No roles assigned</span>;

                return value.map((role: any) => {
                    return <span key={role.id} className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mr-1">{role.label || role.name}</span>;
                });
            }
        },
        {
            key: 'created_at',
            label: t('Joined'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        },
    ];

    // Define table actions
    const actions = [
        {
            label: t('Reset Password'),
            icon: 'KeyRound',
            action: 'reset-password',
            className: 'text-blue-500',
            requiredPermission: 'reset-password-users'
        },
        {
            label: t('Toggle Status'),
            icon: 'Lock',
            action: 'toggle-status',
            className: 'text-amber-500',
            requiredPermission: 'toggle-status-users'
        },
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-users'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-users'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-users'
        }
    ];

    return (
        <PageTemplate
            title={t("Users")}
            url="/users"
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
                            name: 'role',
                            label: t('Role'),
                            type: 'select',
                            value: selectedRole,
                            onChange: setSelectedRole,
                            options: [
                                { value: 'all', label: t('All Roles') },
                                ...(roles || []).map((role: any) => ({
                                    value: role.id.toString(),
                                    label: role.label || role.name
                                }))
                            ]
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
                        const params: any = { page: 1, view: activeView, per_page: parseInt(value) };

                        if (searchTerm) {
                            params.search = searchTerm;
                        }

                        if (selectedRole !== 'all') {
                            params.role = selectedRole;
                        }

                        router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('users.index'), {
                            view,
                            page: pageFilters.page || 1,
                            search: searchTerm || undefined,
                            role: selectedRole !== 'all' ? selectedRole : undefined,
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
                        data={users?.data || []}
                        from={users?.from || 1}
                        onAction={handleAction}
                        sortField={pageFilters.sort_field}
                        sortDirection={pageFilters.sort_direction}
                        onSort={handleSort}
                        permissions={permissions}
                        entityPermissions={{
                            view: 'view-users',
                            create: 'create-users',
                            edit: 'edit-users',
                            delete: 'delete-users'
                        }}
                    />

                    {/* Pagination section */}
                    <Pagination
                        from={users?.from || 0}
                        to={users?.to || 0}
                        total={users?.total || 0}
                        links={users?.links}
                        entityName={t("users")}
                        onPageChange={(url) => router.get(url)}
                    />
                </div>
            ) : (
                <div>
                    {/* Grid View */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {users?.data?.map((user: any) => (
                            <Card key={user.id} className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
                                <div className="p-4">
                                    {/* Top: Avatar + Name + Email */}
                                    <div className="flex items-center gap-3">
                                        <div className="flex-shrink-0">
                                            {user.avatar ? (
                                                <Avatar className="h-12 w-12 rounded-lg object-cover">
                                                    <AvatarImage
                                                        src={user.avatar}
                                                        alt={user?.name || 'Avatar'}
                                                        onError={(e) => {
                                                            // Fallback to default avatar on error
                                                            const target = e.target as HTMLImageElement;
                                                            target.src = getDisplayUrl('avatars/avatar.png');
                                                        }}
                                                    />
                                                    <AvatarFallback className="text-lg">
                                                        {user.name?.charAt(0)?.toUpperCase() || 'U'}
                                                    </AvatarFallback>
                                                </Avatar>
                                            ) : null}
                                            <div className={`h-12 w-12 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-sm font-bold ${user.avatar ? 'hidden' : ''
                                                }`}>
                                                {getInitials(user.name)}
                                            </div>
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <h3 className="text-sm font-semibold text-gray-900 dark:text-white truncate">{user.name}</h3>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 truncate">{user.email}</p>
                                        </div>
                                    </div>

                                    {/* Divider */}
                                    <div className="border-t border-gray-100 dark:border-gray-700 my-3" />

                                    {/* Bottom: Actions + Role badge */}
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-1">
                                            {hasPermission(permissions, 'view-users') && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="sm" onClick={() => handleAction('view', user)}
                                                            className="h-8 w-8 p-0 text-blue-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('View')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'edit-users') && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="sm" onClick={() => handleAction('edit', user)}
                                                            className="h-8 w-8 p-0 text-amber-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Edit')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'reset-password-users') && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="sm" onClick={() => handleAction('reset-password', user)}
                                                            className="h-8 w-8 p-0 text-blue-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                            <KeyRound className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Reset Password')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'toggle-status-users') && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="sm" onClick={() => handleAction('toggle-status', user)}
                                                            className="h-8 w-8 p-0 text-amber-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                                            {user.status === 'active' ? <Lock className="h-4 w-4" /> : <Unlock className="h-4 w-4" />}
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{user.status === 'active' ? t('Disable User') : t('Enable User')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'delete-users') && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="sm" onClick={() => handleAction('delete', user)}
                                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Delete')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                        </div>

                                        {/* Role badge */}
                                        <div>
                                            {user.roles && user.roles.length > 0 ? (
                                                <span className="inline-flex items-center rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-white capitalize">
                                                    {user.roles[0].label || user.roles[0].name}
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    {t('No role')}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        ))}

                        {(!users?.data || users.data.length === 0) && (
                            <div className="col-span-full text-center py-16">
                                <div className="mx-auto h-20 w-20 text-gray-300 dark:text-gray-600 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" className="w-full h-full">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">{t('No users found')}</h3>
                                <p className="text-gray-500 dark:text-gray-400 mb-6">{t('Get started by creating your first user')}</p>
                                {hasPermission(permissions, 'create-users') && (
                                    <Button onClick={handleAddNew}><Plus className="h-4 w-4 mr-2" />{t('Add User')}</Button>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    <div className="mt-6">
                        <div className="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <Pagination
                                from={users?.from || 0}
                                to={users?.to || 0}
                                total={users?.total || 0}
                                links={users?.links}
                                entityName={t('users')}
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
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: [
                        { name: 'name', label: t('Name'), type: 'text', required: true },
                        { name: 'email', label: t('Email'), type: 'email', required: true },
                        {
                            name: 'password',
                            label: t('Password'),
                            type: 'password',
                            required: true,
                            conditional: (mode) => mode === 'create'
                        },
                        {
                            name: 'password_confirmation',
                            label: t('Confirm Password'),
                            type: 'password',
                            required: true,
                            conditional: (mode) => mode === 'create'
                        },
                        {
                            name: 'roles',
                            label: t('Role'),
                            type: 'select',
                            options: roles ? roles.map((role: any) => ({
                                value: role.id.toString(),
                                label: role.label || role.name
                            })) : [],
                            required: true,
                            emptyNote: !roles || roles.length === 0 ? {
                                link: route('roles.index'),
                                linkText: t('Roles')
                            } : undefined
                        },
                        // ===== License ID + Hardware ID (v4 addition) =====
                        // Optional text fields used by the desktop client /
                        // LicenseKeyService. Stored verbatim on the users
                        // table. Visible in both create and edit modes; in
                        // view mode CrudFormModal renders them read-only.
                        {
                            name: 'license_id',
                            label: t('License ID'),
                            type: 'text',
                            required: false,
                            placeholder: t('Paste the license ID (e.g. UUID)')
                        },
                        {
                            name: 'hardware_id',
                            label: t('Hardware ID'),
                            type: 'text',
                            required: false,
                            placeholder: t('Paste the hardware / machine ID')
                        }
                    ],
                    modalSize: 'lg'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    roles: currentItem.roles && currentItem.roles.length > 0 ? currentItem.roles[0].id.toString() : '',
                    // Pre-fill license_id / hardware_id from the existing
                    // user record so they show up in edit / view mode.
                    license_id: currentItem.license_id || '',
                    hardware_id: currentItem.hardware_id || ''
                } : null}
                title={
                    formMode === 'create'
                        ? t('Add User')
                        : formMode === 'edit'
                            ? t('Edit User')
                            : t('View User')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName="user"
            />

            {/* Reset Password Modal */}
            <CrudFormModal
                isOpen={isResetPasswordModalOpen}
                onClose={() => setIsResetPasswordModalOpen(false)}
                onSubmit={handleResetPasswordConfirm}
                formConfig={{
                    fields: [
                        { name: 'password', label: t('New Password'), type: 'password', required: true },
                        { name: 'password_confirmation', label: t('Confirm Password'), type: 'password', required: true }
                    ],
                    modalSize: 'sm'
                }}
                initialData={{}}
                title={`Reset Password for ${currentItem?.name || 'User'}`}
                mode="edit"
            />
        </PageTemplate>
    );
}
