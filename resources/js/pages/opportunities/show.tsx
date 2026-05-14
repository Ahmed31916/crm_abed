import { PageTemplate } from '@/components/page-template';
import { usePage, Link, router } from '@inertiajs/react';
import { ArrowLeft, Target, DollarSign, Calendar, User, Building, Package, FileText, EyeOff, Trash2, Send, Edit, Check, X, MessageCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { formatRelativeTime, getDisplayUrl } from '@/utils/helper';

export default function OpportunityShow() {
  const { t } = useTranslation();
  const { opportunity, streamItems, auth, meetings } = usePage().props as any;
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
    { title: t('Opportunities'), href: route('opportunities.index') },
    { title: opportunity.name }
  ];

  const getStatusBadge = (status: string) => {
    const statusColors = {
      active: 'bg-green-50 text-green-700 ring-green-600/20',
      inactive: 'bg-red-50 text-red-700 ring-red-600/10'
    };

    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.active}`}>
        {status?.charAt(0).toUpperCase() + status?.slice(1) || 'Active'}
      </span>
    );
  };

  const formatCurrency = (amount: number) => window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;

  const formatDate = (dateString: string) => {
    if (!dateString) return t('-');
    return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
  };

  const calculateProductTotals = () => {
    let subtotal = 0;
    let totalTax = 0;

    opportunity.products?.forEach((product: any) => {
      const lineTotal = Number(product.pivot?.total_price || 0);
      subtotal += lineTotal;

      if (product.tax && lineTotal > 0) {
        totalTax += (lineTotal * Number(product.tax.rate || 0)) / 100;
      }
    });

    return { subtotal, totalTax, grandTotal: subtotal + totalTax };
  };

  const { subtotal, totalTax, grandTotal } = calculateProductTotals();

  return (
    <PageTemplate
      title={opportunity.name}
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
              <h1 className="text-lg font-bold text-gray-900 leading-tight">{opportunity.name}</h1>
              <p className="text-sm text-gray-600 mt-2">{opportunity.description || t('No description provided')}</p>
            </div>
            <div className="text-right ml-6">
              {getStatusBadge(opportunity.status)}
            </div>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="border-l-4 border-l-green-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Amount')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-green-600 leading-none">{formatCurrency(opportunity.amount)}</h3>
                </div>
                <div className="rounded-full bg-green-100 p-4">
                  <DollarSign className="h-5 w-5 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-blue-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-blue-600 leading-none">{opportunity.products?.length || 0}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-4">
                  <Package className="h-5 w-5 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-orange-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Close Date')}</p>
                  <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{formatDate(opportunity.close_date)}</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-4">
                  <Calendar className="h-5 w-5 text-orange-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-purple-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Stage')}</p>
                  <div className="mt-2">
                    {opportunity.opportunity_stage ? (
                      <div className="flex items-center gap-2">
                        <div
                          className="w-3 h-3 rounded-full"
                          style={{ backgroundColor: opportunity.opportunity_stage.color }}
                        ></div>
                        <span className="text-lg font-bold text-purple-600">{opportunity.opportunity_stage.name}</span>
                      </div>
                    ) : (
                      <span className="text-lg font-bold text-purple-600">{t('-')}</span>
                    )}
                  </div>
                </div>
                <div className="rounded-full bg-purple-100 p-4">
                  <FileText className="h-5 w-5 text-purple-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Opportunity Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b px-8 py-6">
            <CardTitle className="text-lg font-semibold">{t('Opportunity Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-8">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div className="space-y-8">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Amount')}</label>
                  <p className="text-sm mt-1">{formatCurrency(opportunity.amount)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Stage')}</label>
                  <div className="mt-1">
                    {opportunity.opportunity_stage ? (
                      <div className="flex items-center gap-2 mt-1">
                        <div
                          className="w-3 h-3 rounded-full"
                          style={{ backgroundColor: opportunity.opportunity_stage.color }}
                        ></div>
                        <span>{opportunity.opportunity_stage.name}</span>
                      </div>
                    ) : (
                      <span className="text-sm">{t('-')}</span>
                    )}
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Source')}</label>
                  <p className="text-sm mt-1">{opportunity.opportunity_source?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{opportunity.assigned_user?.name || t('Unassigned')}</p>
                </div>
              </div>
              <div className="space-y-8">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Close Date')}</label>
                  <p className="text-sm mt-1">{formatDate(opportunity.close_date)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                  <p className="text-sm mt-1">{formatDate(opportunity.created_at)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                  <p className="text-sm mt-1">{formatDate(opportunity.updated_at)}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Related Records */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b px-8 py-6">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Building className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Related Records')}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6 p-8">
            {opportunity.account && (
              <div className="flex items-center justify-between p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                <div>
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Account')}</p>
                  <Link
                    href={route('accounts.show', opportunity.account.id)}
                    className="text-sm font-medium text-green-700 hover:text-green-900 hover:underline transition-colors"
                  >
                    {opportunity.account.name}
                  </Link>
                </div>
                <Link href={route('accounts.show', opportunity.account.id)}>
                  <Button variant="outline" size="sm" className="bg-white">
                    {t('View')}
                  </Button>
                </Link>
              </div>
            )}

            {opportunity.contact && (
              <div className="flex items-center justify-between p-6 bg-purple-50 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                <div>
                  <p className="text-sm font-medium text-muted-foreground mb-2">{t('Contact')}</p>
                  <Link
                    href={route('contacts.show', opportunity.contact.id)}
                    className="text-sm font-medium text-purple-700 hover:text-purple-900 hover:underline transition-colors"
                  >
                    {opportunity.contact.name}
                  </Link>
                </div>
                <Link href={route('contacts.show', opportunity.contact.id)}>
                  <Button variant="outline" size="sm" className="bg-white">
                    {t('View')}
                  </Button>
                </Link>
              </div>
            )}


          </CardContent>
        </Card>

        {/* Activities */}
        {meetings?.length > 0 && (
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

        {/* Products */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b px-8 py-6">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Package className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Products')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            {opportunity.products && opportunity.products.length > 0 ? (
              <div className="overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-100">
                      <TableHead className="text-base font-bold text-gray-900 py-4 px-6 w-1/3">{t('Product')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Quantity')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Unit Price')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Tax')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4 w-1/6">{t('Total')}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {opportunity.products.map((product: any, index: number) => (
                      <TableRow key={index} className="border-b hover:bg-gray-50">
                        <TableCell className="font-semibold text-base text-gray-900 py-4 px-6">{product.name}</TableCell>
                        <TableCell className="text-right text-base font-medium py-4 px-4">{product.pivot.quantity}</TableCell>
                        <TableCell className="text-right text-base font-semibold py-4 px-4">{formatCurrency(product.pivot.unit_price)}</TableCell>
                        <TableCell className="text-right text-base py-4 px-4">
                          {product.tax ? (
                            <div className="text-base">
                              <div className="font-semibold text-gray-700">{product.tax.name}</div>
                              <div className="text-gray-600 font-medium">({product.tax.rate}%)</div>
                            </div>
                          ) : (
                            <span className="text-gray-500 font-medium">{t('No Tax')}</span>
                          )}
                        </TableCell>
                        <TableCell className="text-right font-semibold text-base py-4 px-4">
                          <span className="text-green-600">{formatCurrency(product.pivot.total_price)}</span>
                        </TableCell>
                      </TableRow>
                    ))}
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={4} className="text-right font-semibold text-base py-3 px-4">
                        {t('Subtotal')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(subtotal)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={4} className="text-right font-semibold text-base py-3 px-4">
                        {t('Total Tax')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(totalTax)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-green-50 border-t-2">
                      <TableCell colSpan={4} className="text-right font-bold text-lg py-4 px-4">
                        {t('Grand Total')}:
                      </TableCell>
                      <TableCell className="text-right py-4 px-4">
                        <span className="text-green-600 font-bold text-xl">{formatCurrency(grandTotal)}</span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            ) : (
              <div className="text-center py-16 text-gray-500">
                <Package className="h-16 w-16 mx-auto mb-6 text-gray-300" />
                <p className="text-lg font-medium">{t('No products added to this opportunity')}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Related Quotes */}
        {opportunity.quotes?.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b px-8 py-6">
              <CardTitle className="text-lg font-semibold">{t('Related Quotes')} ({opportunity.quotes.length})</CardTitle>
            </CardHeader>
            <CardContent className="p-8">
              <div className="space-y-6">
                {opportunity.quotes.map((quote: any) => (
                  <div key={quote.id} className="flex items-center justify-between p-6 bg-blue-50 rounded-xl border border-blue-200 hover:shadow-md transition-shadow">
                    <div>
                      <p className="text-base font-bold text-gray-900">{quote.quote_number}</p>
                      <p className="text-base text-gray-700 mt-1">{quote.name}</p>
                    </div>
                    <Link href={route('quotes.show', quote.id)}>
                      <Button variant="outline" size="sm" className="bg-white">
                        {t('View')}
                      </Button>
                    </Link>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Activity Stream */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b px-8 py-6">
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
                        router.post(route('opportunities.comments.store', opportunity.id), {
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
                              {activity.activity_type === 'comment' ? (
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
                                          router.put(route('opportunities.comments.update-activity', { opportunity: opportunity.id, activity: activity.id }), {
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
                              ) : activity.description.includes('into') ? (
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

        {/* Notes */}
        {opportunity.notes && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b px-8 py-6">
              <CardTitle className="text-lg font-semibold">{t('Notes')}</CardTitle>
            </CardHeader>
            <CardContent className="p-8">
              <p className="text-base whitespace-pre-wrap text-gray-700 leading-relaxed">{opportunity.notes}</p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Delete Activity Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={() => {
          router.delete(route('opportunities.delete-activity', { opportunity: opportunity.id, activity: currentActivity.id }), {
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
          router.delete(route('opportunities.delete-activities', opportunity.id), {
            preserveScroll: true
          });
          setIsDeleteAllModalOpen(false);
        }}
        itemName={t('all activities for {{name}}', { name: opportunity.name })}
        entityName={t('activities')}
      />
    </PageTemplate>
  );
}
