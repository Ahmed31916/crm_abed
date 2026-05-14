import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { Phone, Clock, Users, Building2, ArrowLeft } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getDisplayUrl } from '@/utils/helper';

export default function CallShow() {
    const { t } = useTranslation();
    const { call } = usePage().props as any;

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Calls'), href: route('calls.index') },
        { title: call.title }
    ];

    const formatDateTime = (date: string, time: string) => {
        const dateObj = new Date(`${date}T${time}`);
        return dateObj.toLocaleString();
    };

    return (
        <PageTemplate
            title={call.title}
            url={`/calls/${call.id}`}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => window.history.back()
                }
            ]}
        >
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Content */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Call Details */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold mb-4">{t('Call Details')}</h2>

                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <Clock className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div>
                                    <p className="font-medium">{t('Date & Time')}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {window.appSettings?.formatDateTime(call.start_date, false) || new Date(call.start_date).toLocaleDateString()} {window.appSettings?.formatTime(call.start_time) || call.start_time} - {window.appSettings?.formatDateTime(call.end_date, false) || new Date(call.end_date).toLocaleDateString()} {window.appSettings?.formatTime(call.end_time) || call.end_time}
                                    </p>
                                </div>
                            </div>

                            {call.description && (
                                <div>
                                    <p className="font-medium mb-2">{t('Description')}</p>
                                    <p className="text-sm text-muted-foreground whitespace-pre-wrap">{call.description}</p>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <span className="font-medium">{t('Status')}:</span>
                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${call.status === 'planned' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                        call.status === 'held' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                                            call.status === 'not_held' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                'bg-gray-50 text-gray-700 ring-gray-600/20'
                                    }`}>
                                    {call.status === 'planned' ? t('Planned') :
                                        call.status === 'held' ? t('Held') :
                                            call.status === 'not_held' ? t('Not Held') :
                                                call.status}
                                </span>
                            </div>
                        </div>
                    </Card>

                    {/* Attendees */}
                    {call.attendees && call.attendees.length > 0 && (
                        <Card className="p-6">
                            <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {t('Attendees')}
                            </h2>

                            <div className="space-y-3">
                                {call.attendees.map((attendee: any, index: number) => (
                                    <div key={index} className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        {attendee?.attendee?.avatar ?
                                            <Avatar className="h-8 w-8">
                                                <AvatarImage
                                                    src={attendee?.attendee?.avatar}
                                                    alt={attendee?.attendee?.name || 'Avatar'}
                                                    onError={(e) => {
                                                        // Fallback to default avatar on error
                                                        const target = e.target as HTMLImageElement;
                                                        target.src = getDisplayUrl('avatars/avatar.png');
                                                    }}
                                                />
                                                <AvatarFallback className="text-lg">
                                                    {attendee?.attendee?.name?.charAt(0)?.toUpperCase() || 'U'}
                                                </AvatarFallback>
                                            </Avatar> :
                                            <div className="h-8 w-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">
                                                {attendee.attendee?.name?.charAt(0) || '?'}
                                            </div>}
                                        <div>
                                            <p className="font-medium">{attendee.attendee?.name || t('Unknown')}</p>
                                            <p className="text-sm text-muted-foreground capitalize">
                                                {t(attendee.attendee_type)} {attendee.attendee?.email && `• ${attendee.attendee.email}`}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    )}
                </div>

                {/* Sidebar */}
                <div className="space-y-6">
                    {/* Call Info */}
                    <Card className="p-6">
                        <h3 className="font-semibold mb-4">{t('Call Information')}</h3>

                        <div className="space-y-3">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Created By')}</p>
                                <p className="text-sm">{call.creator?.name || t('Unknown')}</p>
                            </div>

                            {call.assigned_user && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</p>
                                    <p className="text-sm">{call.assigned_user.name}</p>
                                </div>
                            )}

                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Created At')}</p>
                                <p className="text-sm">{window.appSettings?.formatDateTime(call.created_at, false) || new Date(call.created_at).toLocaleDateString()}</p>
                            </div>

                            {call.updated_at !== call.created_at && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Last Updated')}</p>
                                    <p className="text-sm">{window.appSettings?.formatDateTime(call.created_at, false) || new Date(call.created_at).toLocaleDateString()}</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Related Record */}
                    {call.parent_module && call.parent_record && (
                        <Card className="p-6">
                            <h3 className="font-semibold mb-4 flex items-center gap-2">
                                <Building2 className="h-4 w-4" />
                                {t('Related To')}
                            </h3>

                            <div className="space-y-3">
                                <Badge variant="outline" className="capitalize">
                                    {call.parent_module}
                                </Badge>
                                <div>
                                    <p className="font-medium">{call.parent_record.name || call.parent_record.subject}</p>
                                    {call.parent_record.email && (
                                        <p className="text-sm text-muted-foreground">{call.parent_record.email}</p>
                                    )}
                                </div>
                                <a
                                    href={route(`${call.parent_module === 'opportunity' ? 'opportunities' : call.parent_module + 's'}.show`, call.parent_id)}
                                    className="inline-flex items-center text-sm text-primary hover:underline"
                                >
                                    {t('View')} {t(call.parent_module)}
                                </a>
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </PageTemplate>
    );
}
