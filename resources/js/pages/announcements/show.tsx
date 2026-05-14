import { Head, router, usePage } from '@inertiajs/react';
import { PageTemplate } from '@/components/page-template';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Trash2, ArrowLeft, Star, CalendarDays } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { hasPermission } from '@/utils/authorization';
import { useState } from 'react';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';

export default function AnnouncementShow() {
    const { t } = useTranslation();
    const { auth, announcement } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting announcement...'));

        router.delete(route('announcements.destroy', announcement.id), {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                }
                router.get(route('announcements.index'));
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

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Announcements'), href: route('announcements.index') },
        { title: announcement.title }
    ];

    const pageActions = [
        {
            label: t('Back'),
            icon: <ArrowLeft className="h-4 w-4" />,
            variant: 'outline',
            onClick: () => window.history.back()
        }
    ];

    if (hasPermission(permissions, 'delete-announcements')) {
        pageActions.push({
            label: t('Delete'),
            icon: <Trash2 className="h-4 w-4 mr-2" />,
            variant: 'destructive',
            onClick: () => setIsDeleteModalOpen(true)
        });
    }

    const getStatusBadge = (status: string) => {
        const statusColors = {
        expired: 'bg-gray-100 text-gray-800 ring-1 ring-inset ring-gray-600/20',
        active: 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20',
        inactive: 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-600/20'
        };

        return (
            <Badge className={statusColors[status as keyof typeof statusColors] || statusColors.inactive}>
                {t(status?.charAt(0).toUpperCase() + status?.slice(1)) || t('Inactive')}
            </Badge>
        );
    };

    return (
        <PageTemplate
            title={announcement.title}
            breadcrumbs={breadcrumbs}
            actions={pageActions}
        >
            <Head title={announcement.title} />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <CardTitle className="text-xl">{announcement.title}</CardTitle>
                            <div className="flex gap-2">
                                {announcement.category && (
                                    <Badge variant="outline" className="bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                        {announcement.category.name}
                                    </Badge>
                                )}
                                {getStatusBadge(announcement.status)}
                                {announcement.is_featured && (
                                    <Badge className="bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-purple-600/20 flex items-center gap-1">
                                        <Star className="h-3 w-3" />
                                        {t('Featured')}
                                    </Badge>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-6 text-sm text-muted-foreground mt-4">
                            {announcement.start_date && <div className='flex items-center gap-2'><CalendarDays className="h-4 w-4" /> <span className='font-bold'>{t('Start Date')}:</span> {window.appSettings?.formatDateTime(announcement.start_date, false) || '-'}</div>}
                            {announcement.end_date && <div className='flex items-center gap-2'><CalendarDays className="h-4 w-4" /> <span className='font-bold'>{t('End Date')}:</span> {window.appSettings?.formatDateTime(announcement.end_date, false) || '-'}</div>}
                        </div>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <div dangerouslySetInnerHTML={{ __html: announcement.content || t('No content') }} />
                    </CardHeader>
                </Card>
            </div>

            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={announcement.title || ''}
                entityName={t('announcement')}
            />
        </PageTemplate >
    );
}
