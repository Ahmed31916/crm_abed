import { PageTemplate } from '@/components/page-template';
import { usePage, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building, User, Mail, Phone, Globe, MapPin, Calendar, EyeOff, Trash2, FileText, UserCheck, Send, Edit, Check, X, MessageCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from 'react-i18next';
import { useState, useMemo } from 'react';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { capitalize, formatRelativeTime, getDisplayUrl } from '@/utils/helper';

export default function AccountShow() {
  const { t } = useTranslation();
  const { account, streamItems, auth, meetings } = usePage().props as any;
  const isCompany = auth?.user?.type === 'company';
  const [showStream, setShowStream] = useState(true);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDeleteAllModalOpen, setIsDeleteAllModalOpen] = useState(false);
  const [currentActivity, setCurrentActivity] = useState<any>(null);
  const [newComment, setNewComment] = useState('');
  const [editingComment, setEditingComment] = useState<number | null>(null);
  const [editCommentText, setEditCommentText] = useState('');

  const filteredMeetings = useMemo(() => meetings?.filter((m: any) => m.type !== 'call') || [], [meetings]);
  const filteredCalls = useMemo(() => meetings?.filter((m: any) => m.type === 'call') || [], [meetings]);

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Accounts'), href: route('accounts.index') },
    { title: account.name }
  ];

  const getStatusBadge = (status: string) => {
    const statusColors = {
      active: 'bg-green-50 text-green-700 ring-green-600/20',
      inactive: 'bg-red-50 text-red-700 ring-red-600/10'
    };

    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.active}`}>
        {capitalize(status) || 'Active'}
      </span>
    );
  };

  return (
    <PageTemplate
      title={account.name}
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
              <h1 className="text-lg font-bold">{account.name}</h1>
              <p className="text-sm text-muted-foreground mt-2">{account.account_type?.name || t('No type specified')}</p>
            </div>
            <div className="text-right">
              {getStatusBadge(account.status)}
            </div>
          </div>
        </div>

        {/* Account Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Account Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div className="flex items-center space-x-3">
                  <Mail className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Email')}</label>
                    <p className="text-sm mt-1">{account.email || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Phone className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Phone')}</label>
                    <p className="text-sm mt-1">{account.phone || t('-')}</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Globe className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Website')}</label>
                    {account.website ? (
                      <a href={account.website.startsWith('http') ? account.website : `https://${account.website}`}
                         target="_blank" rel="noopener noreferrer"
                         className="text-sm text-blue-600 hover:underline mt-1 block">
                        {account.website}
                      </a>
                    ) : (
                      <p className="text-sm mt-1">{t('-')}</p>
                    )}
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Account Type')}</label>
                  <p className="text-sm mt-1">{account.account_type?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Industry')}</label>
                  <div className="mt-1">
                    {account.account_industry ? (
                      <span
                        className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                        style={{
                          backgroundColor: `${account.account_industry.color}20`,
                          color: account.account_industry.color,
                          borderColor: `${account.account_industry.color}40`
                        }}
                      >
                        {account.account_industry.name}
                      </span>
                    ) : (
                      <span className="text-sm">{t('-')}</span>
                    )}
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{account.assigned_user?.name || t('Unassigned')}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Address Information */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <MapPin className="h-5 w-5 mr-3" />
                {t('Billing Address')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6 space-y-3">
              <p className="text-sm">{account.billing_address || t('-')}</p>
              <p className="text-sm">
                {[account.billing_city, account.billing_state, account.billing_postal_code]
                  .filter(Boolean).join(', ') || t('-')}
              </p>
              <p className="text-sm">{account.billing_country || t('-')}</p>
            </CardContent>
          </Card>

          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <MapPin className="h-5 w-5 mr-3" />
                {t('Shipping Address')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6 space-y-3">
              <p className="text-sm">{account.shipping_address || t('-')}</p>
              <p className="text-sm">
                {[account.shipping_city, account.shipping_state, account.shipping_postal_code]
                  .filter(Boolean).join(', ') || t('-')}
              </p>
              <p className="text-sm">{account.shipping_country || t('-')}</p>
            </CardContent>
          </Card>
        </div>

        {/* Related Records */}
        {(account.contacts?.length > 0 || account.quotes?.length > 0) && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Related Records')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {account.contacts?.length > 0 && (
                  <div>
                    <h4 className="text-sm font-semibold mb-4">{t('Contacts')} ({account.contacts.length})</h4>
                    <div className="space-y-3">
                      {account.contacts.slice(0, 5).map((contact: any) => (
                        <div key={contact.id} className="flex items-center justify-between p-4 bg-purple-50 rounded-lg border border-purple-200">
                          <span className="text-sm font-medium text-purple-600">{contact.name}</span>
                          <Link href={route('contacts.show', contact.id)}>
                            <Button variant="outline" size="sm" className="bg-white">{t('View')}</Button>
                          </Link>
                        </div>
                      ))}
                      {account.contacts.length > 5 && (
                        <p className="text-sm text-muted-foreground">{t('+{{count}} more', { count: account.contacts.length - 5 })}</p>
                      )}
                    </div>
                  </div>
                )}

                {account.quotes?.length > 0 && (
                  <div>
                    <h4 className="text-sm font-semibold mb-4">{t('Quotes')} ({account.quotes.length})</h4>
                    <div className="space-y-3">
                      {account.quotes.slice(0, 5).map((quote: any) => (
                        <div key={quote.id} className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                          <span className="text-sm font-medium text-blue-600">{quote.quote_number}</span>
                          <Link href={route('quotes.show', quote.id)}>
                            <Button variant="outline" size="sm" className="bg-white">{t('View')}</Button>
                          </Link>
                        </div>
                      ))}
                      {account.quotes.length > 5 && (
                        <p className="text-sm text-muted-foreground">{t('+{{count}} more', { count: account.quotes.length - 5 })}</p>
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
                    <h4 className="text-sm font-semibold">{t('Meetings')} ({filteredMeetings.length})</h4>
                  </div>
                  <div className="space-y-3">
                    {filteredMeetings.slice(0, 5).map((meeting: any) => (
                      <div key={meeting.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
                        <div className="flex items-start space-x-3">
                          <div className="flex-shrink-0 mt-1">
                            <svg className="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{meeting.title}</p>
                            <p className="text-xs text-gray-500">{new Date(meeting.start_date).toLocaleDateString()}</p>
                            <p className="text-xs text-gray-500">{meeting.assigned_user?.name || t('Unassigned')}</p>
                          </div>
                        </div>
                        <Link href={route('meetings.show', meeting.id)}>
                          <Button variant="outline" size="sm">{t('View')}</Button>
                        </Link>
                      </div>
                    ))}
                    {filteredMeetings.length > 5 && (
                      <p className="text-sm text-gray-500 text-center">{t('+{{count}} more meetings', { count: filteredMeetings.length - 5 })}</p>
                    )}
                    {filteredMeetings.length === 0 && (
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
                    <h4 className="text-sm font-semibold">{t('Calls')} ({filteredCalls.length})</h4>
                  </div>
                  <div className="space-y-3">
                    {filteredCalls.slice(0, 5).map((call: any) => (
                      <div key={call.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow">
                        <div className="flex items-start space-x-3">
                          <div className="flex-shrink-0 mt-1">
                            <svg className="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{call.title}</p>
                            <p className="text-xs text-gray-500">{new Date(call.start_date).toLocaleDateString()}</p>
                            <p className="text-xs text-gray-500">{call.assigned_user?.name || t('Unassigned')}</p>
                          </div>
                        </div>
                        <Link href={route('meetings.show', call.id)}>
                          <Button variant="outline" size="sm">{t('View')}</Button>
                        </Link>
                      </div>
                    ))}
                    {filteredCalls.length > 5 && (
                      <p className="text-sm text-gray-500 text-center">{t('+{{count}} more calls', { count: filteredCalls.length - 5 })}</p>
                    )}
                    {filteredCalls.length === 0 && (
                      <p className="text-sm text-gray-500 text-center py-4">{t('No calls found')}</p>
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Activity Stream */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center text-lg font-semibold">
                <Calendar className="h-5 w-5 mr-3" />
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
                        router.post(route('accounts.comments.store', account.id), {
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
                            <span className="text-sm font-medium text-gray-900">
                              {activity.title}
                            </span>
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
                              ) : activity.field_changed === 'account_type_id' ? (
                                <p className="text-sm text-gray-600">{activity.description}</p>
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
                                          router.put(route('accounts.comments.update-activity', { account: account.id, activity: activity.id }), {
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
                              ) : activity.field_changed === 'name' || activity.field_changed === 'assigned_to' || activity.description?.includes('into') ? (
                                <p className="text-sm text-gray-600" dangerouslySetInnerHTML={{
                                  __html: activity.description?.replace(/<span class="font-bold text-base">(.*?)<\/span>/g, '<strong>$1</strong>') || ''
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

        {/* Timestamps */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Record Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(account.created_at,false) || '-'}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(account.updated_at,false) || '-'}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Delete Activity Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={() => {
          if (currentActivity?.id) {
            router.delete(route('accounts.delete-activity', { account: account.id, activity: currentActivity.id }), {
              preserveScroll: true
            });
          }
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
          router.delete(route('accounts.delete-activities', account.id), {
            preserveScroll: true
          });
          setIsDeleteAllModalOpen(false);
        }}
        itemName={t('all activities for {{name}}', { name: account.name })}
        entityName={t('activities')}
      />
    </PageTemplate>
  );
}
