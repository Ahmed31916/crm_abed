import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { CrudTable } from '@/components/CrudTable';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';

export default function ContactMessagesIndex() {
    const { t } = useTranslation();
    const { contactMessages, filters: pageFilters = {} } = usePage().props as any;

    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [isViewModalOpen, setIsViewModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentMessage, setCurrentMessage] = useState<any>(null);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('contact-messages.index'), {
            page: 1,
            search: searchTerm || undefined,
            sort_field: pageFilters.sort_field || undefined,
            sort_direction: pageFilters.sort_direction || undefined,
            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
        router.get(route('contact-messages.index'), {
            page: 1,
            search: searchTerm || undefined,
            sort_field: field,
            sort_direction: direction,
            ...(parseInt(pageFilters.per_page) !== 10 && pageFilters.per_page && { per_page: pageFilters.per_page })
        }, { preserveState: true, preserveScroll: true });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        router.get(route('contact-messages.index'), { page: 1 }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        setCurrentMessage(item);
        if (action === 'view') setIsViewModalOpen(true);
        if (action === 'delete') setIsDeleteModalOpen(true);
    };

    const handleDeleteConfirm = () => {
        router.delete(route('contact-messages.destroy', currentMessage.id), {
            onSuccess: () => {
                toast.success(t('Contact message deleted successfully'));
                setIsDeleteModalOpen(false);
            },
            onError: () => {
                toast.error(t('Failed to delete contact message'));
                setIsDeleteModalOpen(false);
            }
        });
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Landing Page'), href: route('landing-page') },
        { title: t('Contact Inquiries') }
    ];

    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true
        },
        {
            key: 'email',
            label: t('Email'),
            sortable: true
        },
        {
            key: 'created_at',
            label: t('Date'),
            sortable: true,
            render: (value: string) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    const actions = [
        { label: t('View'), icon: 'Eye', action: 'view', className: 'text-blue-500' },
        { label: t('Delete'), icon: 'Trash2', action: 'delete', className: 'text-red-500' }
    ];

    return (
        <PageTemplate
            title={t('Contact Inquiries')}
            url="/contact-messages"
            breadcrumbs={breadcrumbs}
            noPadding
        >
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[]}
                    showFilters={false}
                    setShowFilters={() => {}}
                    hasActiveFilters={() => searchTerm !== ''}
                    activeFilterCount={() => searchTerm ? 1 : 0}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={pageFilters.per_page?.toString() || '10'}
                    onPerPageChange={(value) => {
                        router.get(route('contact-messages.index'), {
                            page: 1,
                            search: searchTerm || undefined,
                            sort_field: pageFilters.sort_field || undefined,
                            sort_direction: pageFilters.sort_direction || undefined,
                            ...(parseInt(value) !== 10 && { per_page: parseInt(value) })
                        }, { preserveState: true, preserveScroll: true });
                    }}
                    showViewToggle={false}
                    activeView="list"
                    onViewChange={() => {}}
                />
            </div>

            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={contactMessages?.data || []}
                    from={contactMessages?.from || 1}
                    onAction={handleAction}
                    onSort={handleSort}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    permissions={[]}
                    entityPermissions={{ view: '', create: '', edit: '', delete: '' }}
                />

                <Pagination
                    from={contactMessages?.from || 0}
                    to={contactMessages?.to || 0}
                    total={contactMessages?.total || 0}
                    links={contactMessages?.links}
                    entityName={t('messages')}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            {/* View Modal */}
            <Dialog open={isViewModalOpen} onOpenChange={setIsViewModalOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{t('Contact Inquiry Details')}</DialogTitle>
                    </DialogHeader>
                    {currentMessage && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Name')}</label>
                                    <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{currentMessage.name}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Email')}</label>
                                    <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{currentMessage.email}</p>
                                </div>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Subject')}</label>
                                <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{currentMessage.subject}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Message')}</label>
                                <p className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{currentMessage.message}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Date Time')}</label>
                                <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {window.appSettings?.formatDateTime(currentMessage.created_at, false) || '-'}
                                </p>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentMessage?.subject || ''}
                entityName="contact message"
            />
        </PageTemplate>
    );
}
