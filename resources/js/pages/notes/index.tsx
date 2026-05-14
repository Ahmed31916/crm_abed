import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Eye, Edit, Trash2, Share2, Users, MoreHorizontal, NotebookPen, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { hasPermission } from '@/utils/authorization';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function Notes() {
    const { t } = useTranslation();
    const { auth, myNotes, sharedNotes, totalPersonalNotes = 0, totalSharedNotes = 0, users = [], filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedCreator, setSelectedCreator] = useState(pageFilters.created_by || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
    const [activeView, setActiveView] = useState(pageFilters.view || 'grid');

    const applyFilters = (e?: React.FormEvent) => {
        e?.preventDefault();
        router.get(route('notes.index'), {
            view: activeView,
            search: searchTerm || undefined,
            created_by: selectedCreator !== 'all' ? selectedCreator : undefined,
            page: 1,
            per_page: pageFilters.per_page ?? 10
        }, { preserveState: true, preserveScroll: true });
    };

    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedCreator !== 'all';
    };

    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedCreator !== 'all' ? 1 : 0);
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedCreator('all');
        setShowFilters(false);
        router.get(route('notes.index'), {
            view: activeView,
            page: 1
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentItem(item);
        if (action === 'edit') {
            setFormMode('edit');
            setIsFormModalOpen(true);
        } else if (action === 'delete') {
            setIsDeleteModalOpen(true);
        } else if (action === 'view') {
            setFormMode('view');
            setIsFormModalOpen(true);
        }
    };

    const handleFormSubmit = (formData: any) => {
        const routeName = formMode === 'create' ? 'notes.store' : 'notes.update';
        const method = formMode === 'create' ? 'post' : 'put';

        router[method](route(routeName, formMode === 'edit' ? currentItem.id : undefined), formData, {
            onSuccess: () => {
                setIsFormModalOpen(false);
                toast.success(t(formMode === 'create' ? 'Note created successfully.' : 'Note updated successfully.'));
            },
            onError: () => toast.error(t('Failed to save note.'))
        });
    };

    const handleDeleteConfirm = () => {
        router.delete(route('notes.destroy', currentItem.id), {
            onSuccess: () => {
                setIsDeleteModalOpen(false);
                toast.success(t('Note deleted successfully.'));
            },
            onError: () => toast.error(t('Failed to delete note.'))
        });
    };

    const columns = [
        { key: 'title', label: t('Title'), sortable: true },
        { key: 'creator', label: t('Created By'), render: (value: any) => value?.name || '-' },
        {
            key: 'shared_users',
            label: t('Shared With'),
            render: (value: any[]) => value?.length ? `${value.length} users` : t('Not shared')
        },
        { key: 'created_at', label: t('Created At'), sortable: true, render: (value: string) => new Date(value).toLocaleDateString() }
    ];

    const actions = [
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'view-notes'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-notes',
            condition: (item: any) => item.created_by === auth.user.id
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-notes',
            condition: (item: any) => item.created_by === auth.user.id
        }
    ];

    const myNotesData = myNotes?.data || [];
    const sharedNotesData = sharedNotes?.data || [];

    return (
        <PageTemplate
            title={t("Notes")}
            actions={hasPermission(permissions, 'create-notes') ? [{
                label: t('Add Note'),
                variant:'default',
                icon: <Plus className="h-4 w-4 mr-2" />,
                onClick: () => { setCurrentItem(null); setFormMode('create'); setIsFormModalOpen(true); }
            }] : []}
            noPadding
            breadcrumbs={[{ title: t('Dashboard'), href: route('dashboard') }, { title: t('Notes') }]}
        >


            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <Card className="p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-500">{t('Total Notes')}</p>
                            <p className="text-2xl font-bold">{totalPersonalNotes + totalSharedNotes}</p>
                        </div>
                        <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <NotebookPen className="h-6 w-6 text-blue-600" />
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-500">{t('Personal Notes')}</p>
                            <p className="text-2xl font-bold">{totalPersonalNotes}</p>
                        </div>
                        <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                            <Users className="h-6 w-6 text-green-600" />
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm text-gray-500">{t('Shared Notes')}</p>
                            <p className="text-2xl font-bold">{totalSharedNotes}</p>
                        </div>
                        <div className="h-12 w-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <Share2 className="h-6 w-6 text-purple-600" />
                        </div>
                    </div>
                </Card>
            </div>
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={applyFilters}
                    filters={[
                        {
                            name: 'created_by',
                            label: t('Created By'),
                            type: 'select' as const,
                            value: selectedCreator,
                            onChange: setSelectedCreator,
                            options: [
                                { value: 'all', label: t('All Users') },
                                ...users.map((user: any) => ({
                                    value: user.id.toString(),
                                    label: user.name
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
                    {...(activeView !== 'kanban' && {
                        currentPerPage: pageFilters.per_page?.toString() || "10",
                        onPerPageChange: (value) => {
                            const params = {
                                view: activeView,
                                page: 1,
                                search: searchTerm || undefined,
                                created_by: selectedCreator !== 'all' ? selectedCreator : undefined
                            };
                            if (parseInt(value) !== 10) {
                                params.per_page = parseInt(value);
                            }
                            router.get(route('notes.index'), params, { preserveState: true, preserveScroll: true });
                        }
                    })}
                    showViewToggle={true}
                    activeView={activeView}
                    onViewChange={(view) => {
                        setActiveView(view);
                        router.get(route('notes.index'), {
                            view: view,
                            search: searchTerm || undefined,
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    viewOptions={[
                        { value: 'grid', label: t('Grid'), icon: 'Grid3X3' },
                        { value: 'kanban', label: t('Kanban'), icon: 'Columns' }
                    ]}
                />
            </div>

            {activeView === 'kanban' ? (
                <div className="h-[calc(100vh-380px)] md:h-[calc(100vh-320px)]">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
                        <div className="bg-gray-100 dark:bg-gray-900 rounded-lg p-3 md:p-4 flex flex-col h-full overflow-hidden">
                            <h3 className="font-semibold mb-3 md:mb-4 flex items-center gap-2 flex-shrink-0 text-sm md:text-base">
                                <Users className="h-4 w-4 md:h-5 md:w-5" />
                                {t('Personal Notes')} ({myNotesData.length})
                            </h3>
                            <div className="space-y-2 overflow-y-auto flex-1 pr-1 md:pr-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                                {myNotesData.map((note: any) => (
                                    <Card key={note.id} className="p-3 md:p-4 hover:shadow-md bg-white">
                                        <div className="flex items-start justify-between mb-2">
                                            <h4 className="font-medium cursor-pointer flex-1 truncate text-sm md:text-base">{note.title}</h4>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm" className="h-6 w-6 p-0 flex-shrink-0">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    {hasPermission(permissions, 'view-notes') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', note)}>
                                                        <Eye className="h-4 w-4 mr-2" />{t('View')}
                                                    </DropdownMenuItem>
                                                    )}
                                                    {hasPermission(permissions, 'edit-notes') && note.created_by === auth.user.id && (
                                                        <DropdownMenuItem onClick={() => handleAction('edit', note)}>
                                                            <Edit className="h-4 w-4 mr-2" />{t('Edit')}
                                                        </DropdownMenuItem>
                                                    )}
                                                    {hasPermission(permissions, 'delete-notes') && note.created_by === auth.user.id && (
                                                        <>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem onClick={() => handleAction('delete', note)} className="text-red-600">
                                                                <Trash2 className="h-4 w-4 mr-2" />{t('Delete')}
                                                            </DropdownMenuItem>
                                                        </>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <div className="text-sm line-clamp-2 md:line-clamp-3 cursor-pointer h-[40px] md:h-[60px] overflow-hidden" dangerouslySetInnerHTML={{ __html: note.content || t('No content') }}/>
                                    </Card>
                                ))}
                                {myNotesData.length === 0 && (
                                    <p className="text-center text-gray-400 py-6 md:py-8 text-sm md:text-base">{t('No notes yet')}</p>
                                )}
                            </div>
                        </div>

                        <div className="bg-gray-100 dark:bg-gray-900 rounded-lg p-3 md:p-4 flex flex-col h-full overflow-hidden">
                            <h3 className="font-semibold mb-3 md:mb-4 flex items-center gap-2 flex-shrink-0 text-sm md:text-base">
                                <Share2 className="h-4 w-4 md:h-5 md:w-5" />
                                {t('Shared Notes')} ({sharedNotesData.length})
                            </h3>
                            <div className="space-y-2 overflow-y-auto flex-1 pr-1 md:pr-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                                {sharedNotesData.map((note: any) => (
                                    <Card key={note.id} className="p-3 md:p-4 cursor-pointer hover:shadow-md bg-white">
                                        <div className="flex items-start justify-between mb-2">
                                            <h4 className="font-medium cursor-pointer flex-1 truncate text-sm md:text-base">{note.title}</h4>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm" className="h-6 w-6 p-0 flex-shrink-0">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    {hasPermission(permissions, 'view-notes') && (
                                                    <DropdownMenuItem onClick={() => handleAction('view', note)}>
                                                        <Eye className="h-4 w-4 mr-2" />{t('View')}
                                                    </DropdownMenuItem>
                                                    )}
                                                    {hasPermission(permissions, 'edit-notes') && note.created_by === auth.user.id && (
                                                        <DropdownMenuItem onClick={() => handleAction('edit', note)}>
                                                            <Edit className="h-4 w-4 mr-2" />{t('Edit')}
                                                        </DropdownMenuItem>
                                                    )}
                                                    {hasPermission(permissions, 'delete-notes') && note.created_by === auth.user.id && (
                                                        <>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem onClick={() => handleAction('delete', note)} className="text-red-600">
                                                                <Trash2 className="h-4 w-4 mr-2" />{t('Delete')}
                                                            </DropdownMenuItem>
                                                        </>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <div className="text-sm line-clamp-2 md:line-clamp-3 h-[40px] md:h-[60px] overflow-hidden" dangerouslySetInnerHTML={{ __html: note.content || t('No content') }} />
                                        <div className="flex items-center gap-2 mt-2 text-xs text-gray-400">
                                            <Users className="h-3 w-3" />
                                            <span>{t('By')} {note.creator?.name}</span>
                                        </div>
                                    </Card>
                                ))}
                                {sharedNotesData.length === 0 && (
                                    <p className="text-center text-gray-400 py-6 md:py-8 text-sm md:text-base">{t('No shared notes')}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div>
                    <div className="mb-6">
                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            {t('Personal Notes')} <span className="text-gray-500">({myNotesData.length})</span>
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                            {myNotesData.map((note: any) => (
                                <Card key={note.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div className="p-4">
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="flex items-center gap-2 flex-1 min-w-0">
                                                <h3 className="font-semibold text-gray-900 truncate">{note.title}</h3>
                                            </div>
                                            <User className="h-4 w-4 text-primary flex-shrink-0 ml-2" />
                                        </div>
                                        <p className="text-xs text-gray-500 mb-3">
                                            {t('By')} {note.creator?.name} • {window.appSettings?.formatDateTime(note.created_at,false) || '-'}
                                        </p>
                                        <div className="text-sm text-gray-600 mb-4 line-clamp-2 h-[40px] overflow-hidden" dangerouslySetInnerHTML={{ __html: note.content || t('No content') }} />
                                        <div className="flex justify-end gap-1 border-t pt-3">
                                            {hasPermission(permissions, 'view-notes') && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="icon" onClick={() => handleAction('view', note)} className="h-8 w-8 text-blue-500 hover:text-blue-700">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>{t('View')}</TooltipContent>
                                            </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'edit-notes') && note.created_by === auth.user.id && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="icon" onClick={() => handleAction('edit', note)} className="h-8 w-8 text-amber-500 hover:text-amber-700">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Edit')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'delete-notes') && note.created_by === auth.user.id && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="icon" onClick={() => handleAction('delete', note)} className="h-8 w-8 text-red-500 hover:text-red-700">
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Delete')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                        </div>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </div>

                    <div>
                        <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Share2 className="h-5 w-5" />
                            {t('Shared Notes')} <span className="text-gray-500">({sharedNotesData.length})</span>
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                            {sharedNotesData.map((note: any) => (
                                <Card key={note.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                    <div className="p-4">
                                        <div className="flex items-start justify-between mb-3">
                                            <div className="flex items-center gap-2 flex-1 min-w-0">
                                                <h3 className="font-semibold text-gray-900 truncate">{note.title}</h3>
                                            </div>
                                            <Share2 className="h-4 w-4 text-primary flex-shrink-0 ml-2" />
                                        </div>
                                        <p className="text-xs text-gray-500 mb-3">
                                            {t('By')} {note.creator?.name} • {new Date(note.created_at).toLocaleDateString()}
                                        </p>
                                        <div className="text-sm text-gray-600 mb-4 line-clamp-2 h-[40px] overflow-hidden" dangerouslySetInnerHTML={{ __html: note.content || t('No content') }} />
                                        <div className="flex justify-end gap-1 border-t pt-3">
                                            {hasPermission(permissions, 'view-notes') && (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="icon" onClick={() => handleAction('view', note)} className="h-8 w-8 text-blue-500 hover:text-blue-700">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>{t('View')}</TooltipContent>
                                            </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'edit-notes') && note.created_by === auth.user.id && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="icon" onClick={() => handleAction('edit', note)} className="h-8 w-8 text-amber-500 hover:text-amber-700">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Edit')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                            {hasPermission(permissions, 'delete-notes') && note.created_by === auth.user.id && (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button variant="ghost" size="icon" onClick={() => handleAction('delete', note)} className="h-8 w-8 text-red-500 hover:text-red-700">
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Delete')}</TooltipContent>
                                                </Tooltip>
                                            )}
                                        </div>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </div>

                    <div className="mt-6 bg-white rounded-lg shadow overflow-hidden">
                        <Pagination
                            from={myNotes?.from || 1}
                            to={myNotes?.to || 0}
                            total={myNotes?.total || 0}
                            links={myNotes?.links}
                            entityName={t('notes')}
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
                    fields: [
                        { name: 'title', label: t('Title'), type: 'text', required: true },
                        {
                            name: 'content',
                            label: t('Content'),
                            type: 'rich-textbox',
                            required: true,
                            colSpan: 12,
                        },
                        {
                            name: formMode === 'view' ? 'shared_users_names' : 'shared_users',
                            label: t('Share With'),
                            type: formMode === 'view' ? 'text' : 'multi-select',
                            options: formMode === 'view' ? [] : users.filter((u: any) => u.id !== auth.user.id).map((u: any) => ({ value: u.id, label: u.name })),
                            row: 2,
                            readOnly: formMode === 'view'
                        }
                    ],
                    modalSize: '2xl'
                }}
                initialData={currentItem ? {
                    ...currentItem,
                    shared_users: currentItem.shared_users?.map((u: any) => u.id) || [],
                    shared_users_names: currentItem.shared_users?.map((u: any) => u.name).join(', ') || t('-')
                } : null}
                title={formMode === 'create' ? t('Add Note') : formMode === 'edit' ? t('Edit Note') : t('View Note')}
                mode={formMode}
            />

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.title || ''}
                entityName={t('note')}
            />
        </PageTemplate>
    );
}
