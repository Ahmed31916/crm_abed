import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { Calendar, MapPin, Clock, Users, Building2, ArrowLeft } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getDisplayUrl } from '@/utils/helper';

export default function MeetingShow() {
    const { t } = useTranslation();
    const { meeting } = usePage().props as any;

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Meetings'), href: route('meetings.index') },
        { title: meeting.title }
    ];



    return (
        <PageTemplate
            title={meeting.title}
            url={`/meetings/${meeting.id}`}
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
                    {/* Meeting Details */}
                    <Card className="p-6">
                        <h2 className="text-lg font-semibold mb-4">{t('Meeting Details')}</h2>

                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <Calendar className="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div>
                                    <p className="font-medium">{t('Date & Time')}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {window.appSettings?.formatDateTime(meeting.start_date, false) || new Date(meeting.start_date).toLocaleDateString()} {window.appSettings?.formatTime(meeting.start_time) || meeting.start_time} - {window.appSettings?.formatDateTime(meeting.end_date, false) || new Date(meeting.end_date).toLocaleDateString()} {window.appSettings?.formatTime(meeting.end_time) || meeting.end_time}
                                    </p>
                                </div>
                            </div>

                            {meeting.location && (
                                <div className="flex items-start gap-3">
                                    <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
                                    <div>
                                        <p className="font-medium">{t('Location')}</p>
                                        <p className="text-sm text-muted-foreground">{meeting.location}</p>
                                    </div>
                                </div>
                            )}

                            {meeting.description && (
                                <div>
                                    <p className="font-medium mb-2">{t('Description')}</p>
                                    <p className="text-sm text-muted-foreground whitespace-pre-wrap">{meeting.description}</p>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <span className="font-medium">{t('Status')}:</span>
                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${meeting.status === 'planned' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                        meeting.status === 'held' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                                            meeting.status === 'not_held' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                'bg-gray-50 text-gray-700 ring-gray-600/20'
                                    }`}>
                                    {meeting.status === 'planned' ? t('Planned') :
                                        meeting.status === 'held' ? t('Held') :
                                            meeting.status === 'not_held' ? t('Not Held') :
                                                meeting.status}
                                </span>
                            </div>
                        </div>
                    </Card>

                    {/* Attendees */}
                    {meeting.attendees && meeting.attendees.length > 0 && (
                        <Card className="p-6">
                            <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {t('Attendees')}
                            </h2>

                            <div className="space-y-3">
                                {meeting.attendees.map((attendee: any, index: number) => (
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
                                                {attendee.attendee_type} {attendee.attendee?.email && `• ${attendee.attendee.email}`}
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
                    {/* Meeting Info */}
                    <Card className="p-6">
                        <h3 className="font-semibold mb-4">{t('Meeting Information')}</h3>
                        <div className="space-y-3">

                            {meeting.assigned_user && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</p>
                                    <p className="text-sm">{meeting.assigned_user.name}</p>
                                </div>
                            )}

                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Created At')}</p>
                                <p className="text-sm">{window.appSettings?.formatDateTime(meeting.created_at, false) || new Date(meeting.created_at).toLocaleDateString()}</p>
                            </div>

                            {meeting.updated_at !== meeting.created_at && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Last Updated')}</p>
                                    <p className="text-sm">{window.appSettings?.formatDateTime(meeting.updated_at, false) || new Date(meeting.updated_at).toLocaleDateString()}</p>
                                </div>
                            )}
                        </div>
                    </Card>

                    {/* Related Record */}
                    {meeting.parent_module && meeting.parent_record && (
                        <Card className="p-6">
                            <h3 className="font-semibold mb-4 flex items-center gap-2">
                                <Building2 className="h-4 w-4" />
                                {t('Related To')}
                            </h3>

                            <div className="space-y-3">
                                <Badge variant="outline" className="capitalize">
                                    {meeting.parent_module}
                                </Badge>
                                <div>
                                    <p className="font-medium">{meeting.parent_record.name || meeting.parent_record.subject}</p>
                                    {meeting.parent_record.email && (
                                        <p className="text-sm text-muted-foreground">{meeting.parent_record.email}</p>
                                    )}
                                </div>
                                <a
                                    href={route(`${meeting.parent_module === 'opportunity' ? 'opportunities' : meeting.parent_module + 's'}.show`, meeting.parent_id)}
                                    className="inline-flex items-center text-sm text-primary hover:underline"
                                >
                                    {t('View {{module}}', { module: meeting.parent_module })}
                                </a>
                            </div>
                        </Card>
                    )}
                </div>
            </div>
        </PageTemplate>
    );
}
