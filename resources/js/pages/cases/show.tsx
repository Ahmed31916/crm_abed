import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, User, Building, AlertTriangle, Clock, CheckCircle, FileText } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

export default function CaseShow() {
    const { t } = useTranslation();
    const { case: caseData, meetings } = usePage().props as any;

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Cases'), href: route('cases.index') },
        { title: caseData.subject }
    ];

    const getPriorityBadge = (priority: string) => {
        const colors = {
            low: 'bg-gray-50 text-gray-700 ring-gray-600/20',
            medium: 'bg-blue-50 text-blue-700 ring-blue-600/20',
            high: 'bg-orange-50 text-orange-700 ring-orange-600/20',
            urgent: 'bg-red-50 text-red-700 ring-red-600/20'
        };
        return (
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[priority as keyof typeof colors]}`}>
                {priority?.charAt(0).toUpperCase() + priority?.slice(1)}
            </span>
        );
    };

    const getStatusBadge = (status: string) => {
        const colors = {
            new: 'bg-blue-50 text-blue-700 ring-blue-600/20',
            in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
            pending: 'bg-orange-50 text-orange-700 ring-orange-600/20',
            resolved: 'bg-green-50 text-green-700 ring-green-600/20',
            closed: 'bg-gray-50 text-gray-700 ring-gray-600/20'
        };
        return (
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${colors[status as keyof typeof colors]}`}>
                {status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
            </span>
        );
    };

    const getCaseTypeLabel = (type: string) => {
        const labels = {
            support: t('Support'),
            bug: t('Bug Report'),
            feature_request: t('Feature Request'),
            complaint: t('Complaint'),
            inquiry: t('Inquiry')
        };
        return labels[type as keyof typeof labels] || type;
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
    };

    return (
        <PageTemplate
            title={caseData.subject}
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
            <div className="mx-auto space-y-6">
                {/* Header Section */}
                <div className="bg-white rounded-lg shadow-sm border p-8">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <h1 className="text-lg font-bold">{caseData.subject}</h1>
                            <p className="text-sm mt-2">{caseData.description || t('No description provided')}</p>
                        </div>
                        <div className="text-right ml-6">
                            <div className="flex items-center gap-3 mb-2">
                                {getPriorityBadge(caseData.priority)}
                                {getStatusBadge(caseData.status)}
                            </div>
                            <p className="text-sm font-medium text-gray-700 font-mono">#{caseData.id}</p>
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-l-blue-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Priority')}</p>
                                    <div className="mt-2">{getPriorityBadge(caseData.priority)}</div>
                                </div>
                                <div className="rounded-full bg-blue-100 p-4">
                                    <AlertTriangle className="h-5 w-5 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-green-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Status')}</p>
                                    <div className="mt-2">{getStatusBadge(caseData.status)}</div>
                                </div>
                                <div className="rounded-full bg-green-100 p-4">
                                    <CheckCircle className="h-5 w-5 text-green-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-purple-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Case Type')}</p>
                                    <h3 className="mt-2 text-lg font-bold text-purple-600 leading-tight">{getCaseTypeLabel(caseData.case_type)}</h3>
                                </div>
                                <div className="rounded-full bg-purple-100 p-4">
                                    <FileText className="h-5 w-5 text-purple-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-orange-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Created')}</p>
                                    <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{window.appSettings?.formatDateTime(caseData.created_at, false) || new Date(caseData.created_at).toLocaleDateString()}</h3>
                                </div>
                                <div className="rounded-full bg-orange-100 p-4">
                                    <Calendar className="h-5 w-5 text-orange-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Case Information */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b px-8 py-6">
                        <CardTitle className="text-lg font-semibold">{t('Case Information')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="space-y-8">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Case ID')}</label>
                                    <p className="text-sm font-mono bg-gray-100 px-3 py-2 rounded-lg mt-1">#{caseData.id}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                                    <div className="mt-2">{getStatusBadge(caseData.status)}</div>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Priority')}</label>
                                    <div className="mt-2">{getPriorityBadge(caseData.priority)}</div>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Case Type')}</label>
                                    <p className="text-sm mt-1">{getCaseTypeLabel(caseData.case_type)}</p>
                                </div>
                            </div>
                            <div className="space-y-8">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                                    <p className="text-sm mt-1">{caseData.assigned_user?.name || t('Unassigned')}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                                    <p className="text-sm mt-1">{window.appSettings?.formatDateTime(caseData.created_at, false) || new Date(caseData.created_at).toLocaleDateString()}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                                    <p className="text-sm mt-1">{window.appSettings?.formatDateTime(caseData.updated_at, false) || new Date(caseData.updated_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Related Data */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b px-8 py-6">
                        <CardTitle className="flex items-center text-lg font-semibold">
                            <Building className="h-5 w-5 mr-3 text-muted-foreground" />
                            {t('Related Records')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {caseData.account && (
                                <div className="p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                                    <p className="text-sm font-medium text-muted-foreground mb-2">{t('Account')}</p>
                                    <Link
                                        href={route('accounts.show', caseData.account.id)}
                                        className="text-sm font-medium text-green-700 hover:text-green-900 hover:underline transition-colors"
                                    >
                                        {caseData.account.name}
                                    </Link>
                                </div>
                            )}

                            {caseData.contact && (
                                <div className="p-6 bg-blue-50 rounded-xl border border-blue-200 hover:shadow-md transition-shadow">
                                    <p className="text-sm font-medium text-muted-foreground mb-2">{t('Contact')}</p>
                                    <Link
                                        href={route('contacts.show', caseData.contact.id)}
                                        className="text-sm font-medium text-blue-700 hover:text-blue-900 hover:underline transition-colors"
                                    >
                                        {caseData.contact.name}
                                    </Link>
                                </div>
                            )}
                        </div>


                    </CardContent>
                </Card>

                {/* Activities */}
                {meetings && meetings.length > 0 && (
                    <Card className="shadow-sm">
                        <CardHeader className="bg-gray-50 border-b px-8 py-6">
                            <CardTitle className="text-lg font-semibold">{t('Activities')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-8">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                {/* Meetings Section */}
                                <div>
                                    <div className="flex items-center mb-4">
                                        <svg className="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <h4 className="text-sm font-medium text-muted-foreground">{t('Meetings')} ({meetings.filter((m: any) => m.type !== 'call').length})</h4>
                                    </div>
                                    <div className="space-y-3">
                                        {meetings.filter((m: any) => m.type !== 'call').slice(0, 5).map((meeting: any) => (
                                            <div key={meeting.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
                                                <div className="flex items-start space-x-3">
                                                    <div className="flex-shrink-0 mt-1">
                                                        <svg className="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">{meeting.title}</p>
                                                        <p className="text-xs text-gray-500">{window.appSettings?.formatDateTime(meeting.start_date, false) || new Date(meeting.start_date).toLocaleDateString()}</p>
                                                        <p className="text-xs text-gray-500">{meeting.assigned_user?.name || t('Unassigned')}</p>
                                                    </div>
                                                </div>
                                                <Link href={route('meetings.show', meeting.id)}>
                                                    <Button variant="outline" size="sm">{t('View')}</Button>
                                                </Link>
                                            </div>
                                        ))}
                                        {meetings.filter((m: any) => m.type !== 'call').length > 5 && (
                                            <p className="text-sm text-gray-500 text-center">{t('+{{count}} more meetings', { count: meetings.filter((m: any) => m.type !== 'call').length - 5 })}</p>
                                        )}
                                        {meetings.filter((m: any) => m.type !== 'call').length === 0 && (
                                            <p className="text-sm text-gray-500 text-center py-4">{t('No meetings found')}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Calls Section */}
                                <div>
                                    <div className="flex items-center mb-4">
                                        <svg className="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <h4 className="text-sm font-medium text-muted-foreground">{t('Calls')} ({meetings.filter((m: any) => m.type === 'call').length})</h4>
                                    </div>
                                    <div className="space-y-3">
                                        {meetings.filter((m: any) => m.type === 'call').slice(0, 5).map((call: any) => (
                                            <div key={call.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
                                                <div className="flex items-start space-x-3">
                                                    <div className="flex-shrink-0 mt-1">
                                                        <svg className="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                        </svg>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">{call.title}</p>
                                                        <p className="text-xs text-gray-500">{window.appSettings?.formatDateTime(call.start_date, false) || new Date(call.start_date).toLocaleDateString()}</p>
                                                        <p className="text-xs text-gray-500">{call.assigned_user?.name || t('Unassigned')}</p>
                                                    </div>
                                                </div>
                                                <Link href={route('meetings.show', call.id)}>
                                                    <Button variant="outline" size="sm">{t('View')}</Button>
                                                </Link>
                                            </div>
                                        ))}
                                        {meetings.filter((m: any) => m.type === 'call').length > 5 && (
                                            <p className="text-sm text-gray-500 text-center">{t('+{{count}} more calls', { count: meetings.filter((m: any) => m.type === 'call').length - 5 })}</p>
                                        )}
                                        {meetings.filter((m: any) => m.type === 'call').length === 0 && (
                                            <p className="text-sm text-gray-500 text-center py-4">{t('No calls found')}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Description */}
                {caseData.description && (
                    <Card className="shadow-sm">
                        <CardHeader className="bg-gray-50 border-b px-8 py-6">
                            <CardTitle className="text-lg font-semibold">{t('Description')}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-8">
                            <p className="text-sm whitespace-pre-wrap">{caseData.description}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </PageTemplate>
    );
}
