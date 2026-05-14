import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Calendar, Star, AlertTriangle, List } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function AnnouncementDashboard() {
    const { t } = useTranslation();
    const { auth, announcements = [] } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const [activeTab, setActiveTab] = useState('all');

    const filteredAnnouncements = announcements.filter((announcement: any) => {
        if (activeTab === 'all') return true;
        if (activeTab === 'featured') return announcement.is_featured;
        if (activeTab === 'expired') {
            return announcement.end_date && new Date(announcement.end_date) < new Date();
        }
        if (activeTab === 'upcoming') {
            return announcement.start_date && new Date(announcement.start_date) > new Date();
        }
        return true;
    });

    const formatDate = (startDate: string, endDate: string) => {
        const start = window.appSettings?.formatDateTime(startDate, false);
        const end = window.appSettings?.formatDateTime(endDate, false);
        return `${start} - ${end}`;
    };

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
            title={t("Announcement Dashboard")}
            actions={[
                ...(hasPermission(permissions, 'manage-announcements') ? [{
                    label: t('List View'),
                    icon: <List className="h-4 w-4 mr-2" />,
                    onClick: () => router.get(route('announcements.index'))
                }] : [])
            ]}
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('Announcements'), href: route('announcements.index') },
                { title: t('Dashboard') }
            ]}
        >
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList>
                            <TabsTrigger value="all">{t('All Announcements')}</TabsTrigger>
                            <TabsTrigger value="featured">{t('Featured')}</TabsTrigger>
                            <TabsTrigger value="upcoming">{t('Upcoming')}</TabsTrigger>
                            <TabsTrigger value="expired">{t('Expired')}</TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>

                <div className="space-y-4">
                    {filteredAnnouncements.length === 0 ? (
                        <div className="text-center py-12 bg-white dark:bg-gray-900 rounded-lg shadow">
                            <p className="text-gray-500">{t('No announcements found')}</p>
                        </div>
                    ) : (
                        filteredAnnouncements.map((announcement: any) => {
                            return (
                                <div
                                    key={announcement.id}
                                    className={`${announcement.is_featured ? 'bg-yellow-50' : announcement.status=='expired' ? 'bg-gray-100' : 'bg-white'}  dark:bg-gray-900 rounded-lg shadow p-6 border-l-4 border-primary`}
                                >
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex-1">
                                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                                {announcement.title}
                                            </h3>
                                            <p className="text-xs text-gray-500 mb-2">
                                                {announcement.category?.name || t('Uncategorized')}
                                            </p>
                                        </div>
                                        <div className="flex items-end gap-2">
                                            {getStatusBadge(announcement.status)}
                                            {announcement.is_featured && (
                                                <Badge className="bg-yellow-100 text-yellow-800 flex items-center gap-1  ring-1 ring-inset ring-yellow-600/20">
                                                    <Star className="h-3 w-3" />
                                                    {t('Featured')}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    {(announcement.start_date || announcement.end_date) && (
                                        <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-3">
                                            <Calendar className="h-4 w-4" />
                                            <span>
                                                {window.appSettings?.formatDateTime(announcement.start_date, false)}
                                                {announcement.end_date && ' - ' + window.appSettings?.formatDateTime(announcement.end_date, false)}
                                            </span>
                                        </div>
                                    )}
                                    <div className='text-sm line-clamp-2 md:line-clamp-3 cursor-pointer overflow-hidden mb-4' dangerouslySetInnerHTML={{ __html: announcement.content || t('No content') }} />

                                    {hasPermission(permissions, 'view-announcements') && <Button
                                        variant="default"
                                        size="sm"
                                        onClick={() => router.get(route('announcements.show', announcement.id))}
                                    >
                                        {t('Read More')}
                                    </Button>}
                                </div>
                            )
                        })
                    )}
                </div>
            </div>
        </PageTemplate>
    );
}
