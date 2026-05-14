import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, User, Mail, Phone, MapPin, Building, Briefcase } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

export default function ContactShow() {
  const { t } = useTranslation();
  const { contact, meetings } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Contacts'), href: route('contacts.index') },
    { title: contact.name }
  ];

  const getStatusBadge = (status: string) => {
    const statusColors = {
      active: 'bg-green-50 text-green-700 ring-green-600/20',
      inactive: 'bg-red-50 text-red-700 ring-red-600/10'
    };

    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.active}`}>
        {status?.charAt(0).toUpperCase() + status?.slice(1) || t('Active')}
      </span>
    );
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
  };

  return (
    <PageTemplate
      title={contact.name}
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
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-gray-900">{contact.name}</h1>
              <p className="text-sm text-gray-600 mt-2">{contact.position || t('No position specified')}</p>
            </div>
            <div className="text-right">
              {getStatusBadge(contact.status)}
            </div>
          </div>
        </div>

        {/* Contact Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Contact Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div className="flex items-center space-x-3">
                  <Mail className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Email')}</label>
                    <p className="text-sm mt-1">{contact.email || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Phone className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Phone')}</label>
                    <p className="text-sm mt-1">{contact.phone || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Briefcase className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Position')}</label>
                    <p className="text-sm mt-1">{contact.position || t('-')}</p>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div className="flex items-center space-x-3">
                  <Building className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Account')}</label>
                    {contact.account ? (
                      <Link
                        href={route('accounts.show', contact.account.id)}
                        className="text-sm text-blue-600 hover:text-blue-800 hover:underline mt-1 block"
                      >
                        {contact.account.name}
                      </Link>
                    ) : (
                      <p className="text-sm mt-1">{t('-')}</p>
                    )}
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{contact.assigned_user?.name || t('Unassigned')}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Address Information */}
        {contact.address && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <MapPin className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Address')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <p className="text-sm">{contact.address}</p>
            </CardContent>
          </Card>
        )}

        {/* Related Records */}
        {(contact.quotes?.length > 0 || contact.cases?.length > 0) && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Related Records')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {contact.quotes?.length > 0 && (
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground mb-4">{t('Quotes')} ({contact.quotes.length})</h4>
                    <div className="space-y-3">
                      {contact.quotes.slice(0, 5).map((quote: any) => (
                        <div key={quote.id} className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                          <span className="text-sm font-medium text-blue-600">{quote.quote_number}</span>
                          <Link href={route('quotes.show', quote.id)}>
                            <Button variant="outline" size="sm" className="bg-white">{t('View')}</Button>
                          </Link>
                        </div>
                      ))}
                      {contact.quotes.length > 5 && (
                        <p className="text-sm text-muted-foreground">{t('+{{count}} more', { count: contact.quotes.length - 5 })}</p>
                      )}
                    </div>
                  </div>
                )}

                {contact.cases?.length > 0 && (
                  <div>
                    <h4 className="text-sm font-medium text-muted-foreground mb-4">{t('Cases')} ({contact.cases.length})</h4>
                    <div className="space-y-3">
                      {contact.cases.slice(0, 5).map((caseItem: any) => (
                        <div key={caseItem.id} className="flex items-center justify-between p-4 bg-orange-50 rounded-lg border border-orange-200">
                          <span className="text-sm font-medium text-orange-600">{caseItem.subject}</span>
                          <Link href={route('cases.index')}>
                            <Button variant="outline" size="sm" className="bg-white">{t('View')}</Button>
                          </Link>
                        </div>
                      ))}
                      {contact.cases.length > 5 && (
                        <p className="text-sm text-muted-foreground">{t('+{{count}} more', { count: contact.cases.length - 5 })}</p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Activities */}
        {meetings?.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Activities')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
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

        {/* Timestamps */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Record Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                <p className="text-sm mt-1">{formatDate(contact.created_at)}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                <p className="text-sm mt-1">{formatDate(contact.updated_at)}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}
