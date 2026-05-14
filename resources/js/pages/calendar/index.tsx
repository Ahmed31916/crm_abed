import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Phone, CheckSquare, User, Clock, ExternalLink, RefreshCw } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { hasPermission } from '@/utils/authorization';
import { toast } from '@/components/custom-toast';
import { capitalize } from '@/utils/helper';

export default function CalendarIndex() {
    const { t } = useTranslation();
    const { events, auth, settings = {}, globalSettings = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];
    const isDemo = globalSettings?.is_demo === '1' || globalSettings?.is_demo === true;
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [activeCalendar, setActiveCalendar] = useState<'local' | 'google'>('local');
    const [isSyncing, setIsSyncing] = useState(false);
    const [googleEvents, setGoogleEvents] = useState<any[]>([]);
    const isGoogleCalendarSynced = settings?.is_googlecalendar_sync === '1' || settings?.is_googlecalendar_sync === true;
    const isGoogleEnabled = settings?.googleCalendarEnabled === '1' || settings?.googleCalendarEnabled === true;
    const Timezone = settings?.defaultTimezone && settings?.defaultTimezone !== '';

    const handleCalendarChange = async (value: 'local' | 'google') => {
        setActiveCalendar(value);
        if (value === 'google') {
            setIsSyncing(true);
            try {
                const response = await fetch(route('google-calendar.sync'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
                const data = await response.json();
                if (data.success) {
                    setGoogleEvents(data.events || []);
                    // toast.success(t('Google Calendar synced successfully'));
                } else {
                    toast.error(data.message || t('Failed to sync Google Calendar'));
                    setActiveCalendar('local');
                }
            } catch (error) {
                toast.error(t('Failed to sync Google Calendar'));
                setActiveCalendar('local');
            } finally {
                setIsSyncing(false);
            }
        } else {
            setGoogleEvents([]);
        }
    };

    const displayEvents = activeCalendar === 'google' ? googleEvents : events;

    const handleEventClick = (info: any) => {
        info.jsEvent.preventDefault();
        const event = info.event;

        setSelectedEvent({
            title: event.title,
            start: event.extendedProps.start || event.startStr,
            end: event.extendedProps.end || event.endStr,
            type: event.extendedProps.type,
            ...event.extendedProps
        });
        setShowModal(true);
    };

    const getEventIcon = (type: string) => {
        switch (type) {
            case 'meeting': return <Calendar className="h-4 w-4" />;
            case 'call': return <Phone className="h-4 w-4" />;
            case 'task': return <CheckSquare className="h-4 w-4" />;
            default: return <Calendar className="h-4 w-4" />;
        }
    };

    const getEventColor = (type: string) => {
        switch (type) {
            case 'meeting': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
            case 'call': return 'bg-green-50 text-green-700 ring-green-600/20';
            case 'task': return 'bg-amber-50 text-amber-700 ring-amber-600/20';
            default: return 'bg-gray-50 text-gray-700 ring-gray-600/20';
        }
    };

    const getStatusClasses = (status: string, eventType: string) => {
        if (eventType === 'meeting') {
            // Meeting status colors
            switch (status?.toLowerCase()) {
                case 'planned':
                    return 'bg-blue-50 text-blue-700 ring-blue-600/20';
                case 'held':
                    return 'bg-green-50 text-green-700 ring-green-600/20';
                case 'not_held':
                    return 'bg-red-50 text-red-700 ring-red-600/20';
                default:
                    return 'bg-gray-50 text-gray-700 ring-gray-600/20';
            }
        } else if (eventType === 'call') {
            // Call status colors
            switch (status?.toLowerCase()) {
                case 'planned':
                    return 'bg-blue-50 text-blue-700 ring-blue-600/20';
                case 'held':
                    return 'bg-green-50 text-green-700 ring-green-600/20';
                case 'not_held':
                    return 'bg-red-50 text-red-700 ring-red-600/20';
                default:
                    return 'bg-gray-50 text-gray-700 ring-gray-600/20';
            }
        } else if (eventType === 'task') {
            // Task status colors
            switch (status?.toLowerCase()) {
                case 'to_do':
                    return 'bg-gray-50 text-gray-700 ring-gray-600/20';
                case 'in_progress':
                    return 'bg-blue-50 text-blue-700 ring-blue-600/20';
                case 'review':
                    return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
                case 'done':
                    return 'bg-green-50 text-green-700 ring-green-600/20';
                default:
                    return 'bg-gray-50 text-gray-700 ring-gray-600/20';
            }
        }
        return 'bg-gray-50 text-gray-700 ring-gray-600/20';
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Calendar') }
    ];

    const pageActions = [];
    if (isGoogleCalendarSynced && isGoogleEnabled) {
        pageActions.push({
            label: '',
            icon: (
                <Select value={activeCalendar} onValueChange={handleCalendarChange} disabled={isSyncing}>
                    <SelectTrigger className="w-40">
                        <SelectValue>
                            <div className="flex items-center gap-2">
                                {isSyncing ? (
                                    <RefreshCw className="h-4 w-4 animate-spin" />
                                ) : (
                                    <></>
                                )}
                                <span>{activeCalendar === 'local' ? t('Local Calendar') : t('Google Calendar')}</span>
                            </div>
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="local">
                            <div className="flex items-center gap-2">
                                <span>{t('Local Calendar')}</span>
                            </div>
                        </SelectItem>
                        <SelectItem value="google">
                            <div className="flex items-center gap-2">
                                <span>{t('Google Calendar')}</span>
                            </div>
                        </SelectItem>
                    </SelectContent>
                </Select>
            ),
            variant: 'ghost' as const,
            onClick: () => { },
            className: 'hover:bg-transparent'
        });
    }

    return (
        <PageTemplate
            title={t('Calendar')}
            breadcrumbs={breadcrumbs}
            actions={pageActions}
        >
            <Card className="p-4">
                <div className="mb-4 flex flex-wrap gap-4 justify-end">
                    <div className="flex flex-wrap gap-4">
                        <div className="flex items-center gap-2">
                            <div className="w-3 h-3 rounded" style={{ backgroundColor: '#3b82f6' }}></div>
                            <span className="text-sm">{t('Meetings')}</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-3 h-3 rounded" style={{ backgroundColor: '#10b77f' }}></div>
                            <span className="text-sm">{t('Calls')}</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-3 h-3 rounded" style={{ backgroundColor: '#f59e0b' }}></div>
                            <span className="text-sm">{t('Tasks')}</span>
                        </div>
                    </div>
                </div>
                <FullCalendar
                    plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                    initialView="dayGridMonth"
                    headerToolbar={{
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    }}
                    events={displayEvents}
                    eventClick={handleEventClick}
                    timeZone={Timezone ? Timezone : 'local'}
                    eventTimeFormat={{
                        hour: '2-digit',
                        minute: '2-digit',
                        meridiem: 'short',
                    }}
                    height="auto"
                    aspectRatio={1.8}
                    eventDisplay="block"
                    dayMaxEvents={1}
                    moreLinkClick="popover"
                    eventContent={(eventInfo) => {
                        const isNotHeld = eventInfo.event.extendedProps.status === 'not_held';
                        return (
                            <div className="p-1 overflow-hidden cursor-pointer hover:opacity-80">
                                <div className={`font-medium text-xs truncate ${isNotHeld ? 'line-through' : ''}`}>
                                    {eventInfo.event.title}
                                </div>
                                {eventInfo.view.type !== 'dayGridMonth' && eventInfo.event.extendedProps.parent_name && (
                                    <div className={`text-xs truncate ${isNotHeld ? 'line-through' : ''}`}>
                                        {eventInfo.event.extendedProps.parent_name}
                                    </div>
                                )}
                            </div>
                        );
                    }}
                />
            </Card>

            {/* Event Details Modal */}
            <Dialog open={showModal} onOpenChange={setShowModal}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            {selectedEvent && getEventIcon(selectedEvent.type)}
                            {selectedEvent?.title}
                        </DialogTitle>
                    </DialogHeader>

                    {selectedEvent && (
                        <div className="space-y-4">
                            <div className="flex items-center gap-2">
                                <span className={'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' + getEventColor(selectedEvent.type || 'event')}>
                                    {selectedEvent.type ? t(capitalize(selectedEvent.type)) : t('Event')}
                                </span>
                            </div>

                            <div className="space-y-3">
                                <div className="text-sm">
                                    <div className="flex items-start gap-2">
                                        <Clock className="h-4 w-4 text-gray-500 mt-0.5" />
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <strong className="text-gray-700">{t('Start')}:</strong>
                                                <span className="text-gray-600">
                                                    {selectedEvent.start ? (
                                                        <>
                                                            {window.appSettings?.formatDateTime(selectedEvent.start)}
                                                        </>
                                                    ) : '-'}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2 mt-1">
                                                <strong className="text-gray-700">{t('End')}:</strong>
                                                <span className="text-gray-600">
                                                    {selectedEvent.end ? (
                                                        <>
                                                            {window.appSettings?.formatDateTime(selectedEvent.end)}
                                                        </>
                                                    ) : '-'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {selectedEvent.status && (
                                    <div className="flex items-center gap-2 text-sm">
                                        <strong>{t('Status')}:</strong>
                                        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusClasses(selectedEvent.status, selectedEvent.type)}`}>
                                            {selectedEvent.type === 'meeting' || selectedEvent.type === 'call' ? (
                                                selectedEvent.status === 'planned' ? t('Planned') :
                                                    selectedEvent.status === 'held' ? t('Held') :
                                                        selectedEvent.status === 'not_held' ? t('Not Held') :
                                                            selectedEvent.status
                                            ) : selectedEvent.type === 'task' ? (
                                                selectedEvent.status === 'to_do' ? t('To Do') :
                                                    selectedEvent.status === 'in_progress' ? t('In Progress') :
                                                        selectedEvent.status === 'review' ? t('Review') :
                                                            selectedEvent.status === 'done' ? t('Done') :
                                                                selectedEvent.status
                                            ) : t(capitalize(selectedEvent.status))}
                                        </span>
                                    </div>
                                )}

                                {selectedEvent.description && (
                                    <div className="text-sm">
                                        <strong className="text-gray-700">{t('Description')}:</strong>
                                        <p className="text-gray-600 mt-1">{selectedEvent.description}</p>
                                    </div>
                                )}

                                {selectedEvent.location && (
                                    <div className="text-sm">
                                        <strong className="text-gray-700">{t('Location')}:</strong>
                                        <span className="text-gray-600 ml-2">{selectedEvent.location}</span>
                                    </div>
                                )}

                                {selectedEvent.parent_name && (
                                    <div className="text-sm">
                                        <strong className="text-gray-700">{t('Related to')}:</strong>
                                        <span className="text-gray-600 ml-2">{selectedEvent.parent_name}</span>
                                    </div>
                                )}
                            </div>

                            {(() => {
                                const eventType = selectedEvent.type;
                                const hasViewPermission =
                                    (eventType === 'meeting' && hasPermission(permissions, 'view-meetings')) ||
                                    (eventType === 'call' && hasPermission(permissions, 'view-calls')) ||
                                    (eventType === 'task' && hasPermission(permissions, 'view-project-tasks'));

                                return hasViewPermission && !isDemo ? (
                                    <div className="flex justify-end pt-4 border-t">
                                        <Button
                                            onClick={() => {
                                                if (eventType === 'meeting') {
                                                    router.get(route('meetings.show', selectedEvent.meeting_id));
                                                } else if (eventType === 'call') {
                                                    router.get(route('calls.show', selectedEvent.call_id));
                                                } else if (eventType === 'task') {
                                                    router.get(route('project-tasks.show', selectedEvent.task_id));
                                                }
                                                setShowModal(false);
                                            }}
                                            className="flex items-center gap-2"
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                            {t('View Details')}
                                        </Button>
                                    </div>
                                ) : null;
                            })()}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
