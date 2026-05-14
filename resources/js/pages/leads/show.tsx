import { PageTemplate } from '@/components/page-template';
import { usePage, Link, router } from '@inertiajs/react';
import { ArrowLeft, User, Building, MapPin, FileText, Phone, Mail, Globe, DollarSign, Users, Calendar, Target, Briefcase, UserCheck, MessageCircle, EyeOff, Trash2, Send, Edit, Check, X } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { formatRelativeTime, getDisplayUrl } from '@/utils/helper';

export default function LeadShow() {
  const { t } = useTranslation();
  const { lead, streamItems, auth, relatedAccounts, relatedContacts, meetings } = usePage().props as any;
  const comments = lead.comments || [];
  const isCompany = auth?.user?.type === 'company';
  const [showStream, setShowStream] = useState(true);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDeleteAllModalOpen, setIsDeleteAllModalOpen] = useState(false);
  const [currentActivity, setCurrentActivity] = useState<any>(null);
  const [newComment, setNewComment] = useState('');
  const [editingComment, setEditingComment] = useState<number | null>(null);
  const [editCommentText, setEditCommentText] = useState('');

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Leads'), href: route('leads.index') },
    { title: lead.name }
  ];

  const getStatusBadge = (status: string) => {
    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
        status === 'active'
          ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
          : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
      }`}>
        {status === 'active' ? t('Active') : t('Inactive')}
      </span>
    );
  };

  const formatCurrency = (amount: number) => window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;





  return (
    <PageTemplate
      title={lead.name}
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
              <h1 className="text-lg font-bold">{lead.name}</h1>
              <p className="text-sm mt-2">{lead.company || t('No company provided')}</p>
            </div>
            <div className="text-right">
              {getStatusBadge(lead.status)}
              {lead.is_converted && (
                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 ml-2">
                  {t('Converted')}
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="border-l-4 border-l-green-500">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1">
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Lead Value')}</p>
                  <h3 className="text-xl font-bold text-green-600 truncate">{formatCurrency(lead.value)}</h3>
                </div>
                <div className="rounded-full bg-green-100 p-3 ml-3">
                  <DollarSign className="h-4 w-4 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-blue-500">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Status')}</p>
                  <h3 className="text-lg font-bold text-blue-600 truncate leading-tight">{lead.lead_status?.name || t('-')}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-3 ml-3">
                  <User className="h-4 w-4 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-orange-500">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Source')}</p>
                  <h3 className="text-lg font-bold text-orange-600 truncate leading-tight">{lead.lead_source?.name || t('-')}</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-3 ml-3">
                  <Building className="h-4 w-4 text-orange-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-purple-500">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Created')}</p>
                  <h3 className="text-lg font-bold text-purple-600 truncate leading-tight">{window.appSettings?.formatDateTime(lead.created_at, false) || new Date(lead.created_at).toLocaleDateString()}</h3>
                </div>
                <div className="rounded-full bg-purple-100 p-3 ml-3">
                  <Calendar className="h-4 w-4 text-purple-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Lead Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Lead Summary')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Lead Name')}</label>
                  <p className="text-sm mt-1">{lead.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">{getStatusBadge(lead.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Lead Status')}</label>
                  <p className="text-sm mt-1">{lead.lead_status?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Source')}</label>
                  <p className="text-sm mt-1">{lead.lead_source?.name || t('-')}</p>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{lead.assigned_user?.name || t('Unassigned')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                  <p className="text-sm mt-1">{lead.creator?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(lead.created_at, false) || new Date(lead.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(lead.updated_at, false) || new Date(lead.updated_at).toLocaleDateString()}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Contact & Address Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <User className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Contact & Address Information')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Name')}</label>
                  <p className="text-sm mt-1">{lead.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Email')}</label>
                  <div className="flex items-center mt-1">
                    <Mail className="h-4 w-4 text-muted-foreground mr-2" />
                    <p className="text-sm">{lead.email || t('-')}</p>
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Phone')}</label>
                  <div className="flex items-center mt-1">
                    <Phone className="h-4 w-4 text-muted-foreground mr-2" />
                    <p className="text-sm">{lead.phone || t('-')}</p>
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Company')}</label>
                  <div className="flex items-center mt-1">
                    <Building className="h-4 w-4 text-muted-foreground mr-2" />
                    <p className="text-sm">{lead.company || t('-')}</p>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Website')}</label>
                  <div className="flex items-center mt-1">
                    <Globe className="h-4 w-4 text-muted-foreground mr-2" />
                    {lead.website ? (
                      <a
                        href={lead.website}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-sm text-blue-600 hover:text-blue-800 hover:underline"
                      >
                        {lead.website}
                      </a>
                    ) : (
                      <p className="text-sm">{t('-')}</p>
                    )}
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Position')}</label>
                  <p className="text-sm mt-1">{lead.position || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Address')}</label>
                  <div className="flex items-start mt-1">
                    <MapPin className="h-4 w-4 text-muted-foreground mr-2 mt-0.5" />
                    <p className="text-sm whitespace-pre-line">{lead.address || t('-')}</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Additional Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <FileText className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Additional Information')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Industry')}</label>
                <p className="text-sm mt-1">{lead.account_industry?.name || t('-')}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Lead Value')}</label>
                <p className="text-sm mt-1">{formatCurrency(lead.value)}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Campaign')}</label>
                <p className="text-sm mt-1">{lead.campaign?.name || t('-')}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Account Name')}</label>
                <p className="text-sm mt-1">{lead.account_name || t('-')}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Conversion Status')}</label>
                <div className="mt-1">
                  {lead.is_converted ? (
                    <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">
                      {t('Yes')}
                    </span>
                  ) : (
                    <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20">
                      {t('No')}
                    </span>
                  )}
                </div>
              </div>
            </div>
            {lead.notes && (
              <div className="mt-6">
                <label className="text-sm font-medium text-muted-foreground">{t('Notes')}</label>
                <div className="mt-2 p-4 bg-gray-50 rounded-lg border">
                  <p className="text-sm whitespace-pre-line">{lead.notes}</p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Related Campaign */}
        {lead.campaign && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Related Campaign')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                <div>
                  <p className="text-base font-medium text-gray-700">{lead.campaign.name}</p>
                  <p className="text-sm text-gray-600">{lead.campaign.campaign_type?.name || t('Campaign')}</p>
                  <p className="text-sm text-gray-500">{t('Budget')}: {window.appSettings?.formatCurrency(Number(lead.campaign.budget || 0)) || `$${Number(lead.campaign.budget || 0).toFixed(2)}`}</p>
                </div>
                <Link href={route('campaigns.show', lead.campaign.id)}>
                  <Button variant="outline" size="sm" className="bg-white">
                    {t('View Campaign')}
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Related Accounts */}
        {relatedAccounts?.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Related Accounts')} ({relatedAccounts.length})</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="space-y-4">
                {relatedAccounts.map((account: any) => (
                  <div key={account.id} className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div>
                      <p className="text-base font-medium text-gray-700">{account.name}</p>
                      <p className="text-sm text-gray-600">{account.account_type?.name || t('Account')}</p>
                      <p className="text-sm text-gray-500">{account.email || t('No email')}</p>
                    </div>
                    <Link href={route('accounts.show', account.id)}>
                      <Button variant="outline" size="sm" className="bg-white">
                        {t('View Account')}
                      </Button>
                    </Link>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Related Contacts */}
        {relatedContacts?.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Related Contacts')} ({relatedContacts.length})</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="space-y-4">
                {relatedContacts.map((contact: any) => (
                  <div key={contact.id} className="flex items-center justify-between p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div>
                      <p className="text-base font-medium text-gray-700">{contact.name}</p>
                      <p className="text-sm text-gray-600">{contact.account?.name || t('No account')}</p>
                      <p className="text-sm text-gray-500">{contact.email || t('No email')}</p>
                    </div>
                    <Link href={route('contacts.show', contact.id)}>
                      <Button variant="outline" size="sm" className="bg-white">
                        {t('View Contact')}
                      </Button>
                    </Link>
                  </div>
                ))}
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
                        <Link href={route('calls.show', call.id)}>
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

        {/* Activity Stream - Full Width */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center text-lg font-semibold">
                <Calendar className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Activity Stream')}
              </CardTitle>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setShowStream(!showStream)}
                >
                  <EyeOff className="h-4 w-4 mr-2" />
                  {showStream ? t('Hide') : t('Show')}
                </Button>
                {streamItems && streamItems.length > 0 && isCompany && (
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => setIsDeleteAllModalOpen(true)}
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    {t('Delete Stream')}
                  </Button>
                )}
              </div>
            </div>
          </CardHeader>
          {showStream && (
            <CardContent className="p-0">
              {/* Add Comment Form */}
              <div className="sticky top-0 bg-white z-10 p-6 pb-6 border-b mb-4">
                <div className="flex gap-3">
                  <div className="flex-shrink-0 w-8 h-8 rounded-full overflow-hidden border-2 border-gray-200">
                    <img
                      src={auth?.user?.avatar || getDisplayUrl('/images/avatar/avatar.png')}
                      alt={auth?.user?.name || 'User'}
                      className="w-full h-full object-cover"
                      onError={(e) => {
                        const target = e.target as HTMLImageElement;
                        target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(auth?.user?.name || 'User')}&background=e5e7eb&color=374151&size=32`;
                      }}
                    />
                  </div>
                  <div className="flex-1">
                    <form onSubmit={(e) => {
                      e.preventDefault();
                      if (newComment.trim()) {
                        router.post(route('leads.comments.store', lead.id), {
                          comment: newComment
                        }, {
                          preserveScroll: true,
                          onSuccess: () => setNewComment('')
                        });
                      }
                    }}>
                      <div className="flex gap-2">
                        <Input
                          placeholder={t('Add a comment...')}
                          value={newComment}
                          onChange={(e) => setNewComment(e.target.value)}
                          className="flex-1"
                        />
                        <Button
                          type="submit"
                          size="sm"
                          disabled={!newComment.trim()}
                        >
                          <Send className="h-4 w-4" />
                        </Button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div className="p-6 pt-0 max-h-96 overflow-y-auto">
              {streamItems && streamItems.length > 0 ? (
                <div className="space-y-2">
                {streamItems.map((activity: any, index: number) => {
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

                  const getActivityColor = (type: string) => {
                    switch (type) {
                      case 'created': return 'border-green-200 bg-green-50';
                      case 'updated': return 'border-blue-200 bg-blue-50';
                      case 'assigned': return 'border-purple-200 bg-purple-50';
                      case 'converted': return 'border-orange-200 bg-orange-50';
                      case 'comment': return 'border-indigo-200 bg-indigo-50';
                      default: return 'border-gray-200 bg-gray-50';
                    }
                  };

                  return (
                    <div key={activity.id || index} className="flex gap-4">
                      <div className="flex flex-col items-center">
                        <div className="flex-shrink-0 w-8 h-8 rounded-full overflow-hidden border-2 border-gray-200">
                          <img
                            src={activity.user?.avatar || getDisplayUrl('/images/avatar/avatar.png')}
                            alt={activity.user?.name || 'User'}
                            className="w-full h-full object-cover"
                            onError={(e) => {
                              const target = e.target as HTMLImageElement;
                              target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(activity.user?.name || 'User')}&background=e5e7eb&color=374151&size=32`;
                            }}
                          />
                        </div>
                        {index < streamItems.length - 1 && <div className="w-px h-8 bg-gray-200 mt-2" />}
                      </div>
                      <div className="flex-1 min-w-0 pb-2">
                        <div className="flex items-center justify-between mb-1">
                          <span className="text-xs text-gray-400">
                            {activity.user?.name || t('System')}
                          </span>
                          <span className="text-xs text-gray-500 font-medium">
                            {formatRelativeTime(activity.created_at)}
                          </span>
                        </div>
                        <div className="bg-white border rounded-lg p-3 shadow-sm">
                          <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium text-gray-900" dangerouslySetInnerHTML={{
                              __html: activity.title.replace(
                                new RegExp(`^(${activity.user?.name || t('System')})`, 'g'),
                                '<span class="font-bold text-base">$1</span>'
                              )
                            }} />
                            <div className="flex items-center gap-2">
                              <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                {activity.activity_type.charAt(0).toUpperCase() + activity.activity_type.slice(1)}
                              </span>
                              {isCompany && (
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  className="h-6 w-6 p-0 text-red-500 hover:text-red-700"
                                  onClick={() => {
                                    setCurrentActivity(activity);
                                    setIsDeleteModalOpen(true);
                                  }}
                                >
                                  <Trash2 className="h-3 w-3" />
                                </Button>
                              )}
                            </div>
                          </div>
                          {activity.description && (
                            <div className="mb-2">
                              {activity.field_changed === 'status' && (activity.description === 'Active' || activity.description === 'Inactive') ? (
                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                                  activity.description === 'Active'
                                    ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                    : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                }`}>
                                  {activity.description}
                                </span>
                              ) : activity.field_changed === 'lead_status_id' ? (
                                <p className="text-sm text-gray-600" dangerouslySetInnerHTML={{
                                  __html: activity.description
                                }} />
                              ) : activity.activity_type === 'comment' ? (
                                <div>
                                  {editingComment === activity.id ? (
                                    <div className="flex gap-2">
                                      <Input
                                        value={editCommentText}
                                        onChange={(e) => setEditCommentText(e.target.value)}
                                        className="flex-1"
                                      />
                                      <Button
                                        size="sm"
                                        onClick={() => {
                                          router.put(route('leads.comments.update-activity', { lead: lead.id, activity: activity.id }), {
                                            comment: editCommentText
                                          }, { preserveScroll: true });
                                          setEditingComment(null);
                                        }}
                                      >
                                        <Check className="h-4 w-4" />
                                      </Button>
                                      <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => setEditingComment(null)}
                                      >
                                        <X className="h-4 w-4" />
                                      </Button>
                                    </div>
                                  ) : (
                                    <div className="flex items-start justify-between">
                                      <p className="text-sm text-gray-600 flex-1">{activity.description}</p>
                                      {activity.user_id === auth?.user?.id && (
                                        <Button
                                          size="sm"
                                          variant="ghost"
                                          className="h-6 w-6 p-0 text-gray-400 hover:text-gray-600"
                                          onClick={() => {
                                            setEditingComment(activity.id);
                                            setEditCommentText(activity.description);
                                          }}
                                        >
                                          <Edit className="h-3 w-3" />
                                        </Button>
                                      )}
                                    </div>
                                  )}
                                </div>
                              ) : activity.field_changed === 'name' || activity.field_changed === 'assigned_to' || activity.description.includes('into') ? (
                                <p className="text-sm text-gray-600" dangerouslySetInnerHTML={{
                                  __html: activity.description
                                }} />
                              ) : (
                                <p className="text-sm text-gray-600">{activity.description}</p>
                              )}
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500">
                  <Calendar className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                  <p className="text-sm">{t('No activities found')}</p>
                </div>
              )}
              </div>
            </CardContent>
          )}
        </Card>

      </div>

      {/* Delete Activity Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={() => {
          router.delete(route('leads.delete-activity', { lead: lead.id, activity: currentActivity.id }), {
            preserveScroll: true
          });
          setIsDeleteModalOpen(false);
        }}
        itemName={t('this activity')}
        entityName={t('activity')}
      />

      {/* Delete All Activities Modal */}
      <CrudDeleteModal
        isOpen={isDeleteAllModalOpen}
        onClose={() => setIsDeleteAllModalOpen(false)}
        onConfirm={() => {
          router.delete(route('leads.delete-activities', lead.id), {
            preserveScroll: true
          });
          setIsDeleteAllModalOpen(false);
        }}
        itemName={t('all activities for {{name}}', { name: lead.name })}
        entityName={t('activities')}
      />
    </PageTemplate>
  );
}
