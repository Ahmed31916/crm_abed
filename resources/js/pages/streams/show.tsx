import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Trash2, User, Calendar, FileText, MessageCircle, UserCheck, Target } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { Pagination } from '@/components/ui/pagination';
import { hasPermission } from '@/utils/authorization';
import { useState } from 'react';
import { capitalize, formatRelativeTime, getDisplayUrl } from '@/utils/helper';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { PageTemplate } from '@/components/page-template';

interface Stream {
    id: number;
    activity_type: string;
    title: string;
    description: string;
    user_id: number;
    created_at: string;
    user?: {
        name: string;
        email: string;
    };
}

interface StreamsShowProps {
    module: string;
    moduleTitle: string;
    streams: {
        data: Stream[];
        links: any[];
        meta: any;
        from: number;
        to: number;
        total: number;
    };
}

export default function Show({ module, moduleTitle, streams }: StreamsShowProps) {
    const { t } = useTranslation();
    const { auth, flash } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentActivity, setCurrentActivity] = useState<any>(null);

    React.useEffect(() => {
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const getActivityIcon = (type: string) => {
        switch (type) {
            case 'created': return <User className="h-4 w-4 text-green-600" />;
            case 'updated': return <FileText className="h-4 w-4 text-blue-600" />;
            case 'assigned': return <UserCheck className="h-4 w-4 text-purple-600" />;
            case 'converted': return <Target className="h-4 w-4 text-orange-600" />;
            case 'comment': return <MessageCircle className="h-4 w-4 text-indigo-600" />;
            default: return <FileText className="h-4 w-4 text-gray-600" />;
        }
    };

    const getActivityBadgeColor = (type: string) => {
        switch (type?.toLowerCase()) {
            case 'created': return 'bg-green-50 text-green-700 ring-green-600/20';
            case 'updated': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
            case 'deleted': return 'bg-red-50 text-red-700  ring-red-600/20';
            case 'assigned': return 'bg-purple-50 text-purple-700 ring-purple-600/20';
            case 'converted': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
            case 'comment': return 'bg-indigo-50 text-indigo-700 ring-indigo-600/20';
            default: return 'bg-gray-50 text-gray-700  ring-gray-600/20';
        }
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Streams'), href: route('stream.index') },
        { title: t(moduleTitle) }
    ];

    return (
        <PageTemplate
            title={t(moduleTitle)}
            // url={route('stream.show', module)}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => window.history.back()
                }
            ]}
            breadcrumbs={breadcrumbs}
        >
            <Head title={`${t(moduleTitle)} - ${t('Streams')}`} />

            {hasPermission(permissions, 'view-stream') && (
                <>
                    <Card className="shadow-sm">
                        <CardHeader className="bg-white border-b">
                            <CardTitle className="flex items-center text-lg font-semibold">
                                <Calendar className="h-5 w-5 mr-3 text-primary" />
                                {t('Activity Stream')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-6">
                            {streams.data.length === 0 ? (
                                <div className="text-center py-12 text-gray-500">
                                    <Calendar className="h-16 w-16 mx-auto mb-4 text-gray-300" />
                                    <p className="text-base font-medium">{t('No activities found')}</p>
                                    <p className="text-sm text-gray-400 mt-1">{t('Activity logs will appear here')}</p>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <div className="p-6 max-h-[55vh] overflow-y-auto">
                                        {streams.data.map((activity, index) => (
                                            <div key={activity.id} className="flex gap-4">
                                                <div className="flex flex-col items-center">
                                                    <div className="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden border-2 border-primary/20 shadow-sm">
                                                        <img
                                                            src={activity.user?.avatar || getDisplayUrl('/images/avatar/avatar.png')}
                                                            alt={activity.user?.name || 'User'}
                                                            className="w-full h-full object-cover"
                                                            onError={(e) => {
                                                                const target = e.target as HTMLImageElement;
                                                                target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(activity.user?.name || 'User')}&background=6366f1&color=fff&size=40`;
                                                            }}
                                                        />
                                                    </div>
                                                    {index < streams.data.length - 1 && <div className="w-0.5 flex-1 bg-gradient-to-b from-primary to-transparent mt-2" />}
                                                </div>
                                                <div className="flex-1 min-w-0 pb-4">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <span className="font-bold font-medium text-gray-1000">
                                                            {activity.user?.name || t('Company')}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {formatRelativeTime(activity.created_at)}
                                                        </span>
                                                    </div>
                                                    <div className="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <span className="text-sm text-gray-900 flex-1 break-words" dangerouslySetInnerHTML={{
                                                                __html: activity.title || `${activity.user?.name || t('Company')} performed an action`
                                                            }} />
                                                            <div className="flex items-center gap-2 flex-shrink-0">
                                                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getActivityBadgeColor(activity.activity_type)}`}>
                                                                    {capitalize(activity.activity_type || 'Activity')}
                                                                </span>
                                                                {hasPermission(permissions, 'delete-stream') && (
                                                                    <TooltipProvider>
                                                                        <Tooltip>
                                                                            <TooltipTrigger asChild>
                                                                                <Button
                                                                                    variant="ghost"
                                                                                    size="icon"
                                                                                    className="h-7 w-7 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                                                    onClick={() => {
                                                                                        setCurrentActivity(activity);
                                                                                        setIsDeleteModalOpen(true);
                                                                                    }}
                                                                                >
                                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                                </Button>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>{t('Delete')}</p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    </TooltipProvider>
                                                                )}
                                                            </div>
                                                        </div>
                                                        {activity.description && (() => {
                                                            const statusBadges: Record<string, string> = {
                                                                'Active': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Inactive': 'bg-red-50 text-red-700 ring-red-600/20',
                                                                'Overdue': 'bg-red-50 text-red-700 ring-red-600/20',
                                                                'Paid': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Partially_paid': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                                                'Cancelled': 'bg-red-50 text-red-700 ring-red-600/20',
                                                                'Received': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Rejected': 'bg-red-50 text-red-700 ring-red-600/20',
                                                                'Expired': 'bg-orange-50 text-orange-700 ring-orange-600/20',
                                                                'Sent': 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                                                'Accepted': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Delivered': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Shipped': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                                                'Confirmed': 'bg-green-50 text-green-700 ring-green-600/20',
                                                                'Processing': 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                                                'Draft': 'bg-gray-50 text-gray-700 ring-gray-600/20'
                                                            };
                                                            const badgeColor = statusBadges[activity.description];

                                                            return (
                                                                <div className="mt-3 pt-3 border-t border-primary">
                                                                    {badgeColor ? (
                                                                        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${badgeColor}`}>
                                                                            {activity.description.replace('_', ' ')}
                                                                        </span>
                                                                    ) : activity.field_changed === 'lead_status_id' || activity.description?.includes('into') ? (
                                                                        <p className="text-sm text-gray-600" dangerouslySetInnerHTML={{
                                                                            __html: activity.description
                                                                        }} />
                                                                    ) : (
                                                                        <p className="text-sm text-gray-600">{activity.description}</p>
                                                                    )}
                                                                </div>
                                                            );
                                                        })()}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {streams.data.length > 0 && (
                        <div className="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                            <Pagination
                                from={streams.from || 1}
                                to={streams.to || streams.data?.length || 0}
                                total={streams.total || streams.data?.length || 0}
                                links={streams.links}
                                entityName={t('activities')}
                                onPageChange={(url) => router.get(url)}
                            />
                        </div>
                    )}

                    <CrudDeleteModal
                        isOpen={isDeleteModalOpen}
                        onClose={() => setIsDeleteModalOpen(false)}
                        onConfirm={() => {
                            if (currentActivity) {
                                router.delete(route(`stream.delete-${module.replace(/_/g, '-')}`, currentActivity.id), {
                                    onSuccess: () => {
                                        toast.success(t('Activity deleted successfully'));
                                        setIsDeleteModalOpen(false);
                                    },
                                    onError: () => {
                                        toast.error(t('Failed to delete activity'));
                                    }
                                });
                            }
                        }}
                        itemName={t('this activity')}
                        entityName={t('activity')}
                    />
                </>
            )}
        </PageTemplate>
    );
}
