import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, DollarSign, Package, User, Building, FileText, Truck } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTranslation } from 'react-i18next';

export default function ReturnOrderShow() {
  const { t } = useTranslation();
  const { returnOrder } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Return Orders'), href: route('return-orders.index') },
    { title: returnOrder.return_number }
  ];

  const getStatusBadge = (status: string) => {
    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
        status === 'processed' || status === 'received' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
        status === 'approved' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
        status === 'shipped' ? 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20' :
        status === 'cancelled' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
        'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
      }`}>
        {t(status?.charAt(0).toUpperCase() + status?.slice(1)) || t('Pending')}
      </span>
    );
  };

  const getReasonLabel = (reason: string) => {
    const reasonLabels = {
      defective: t('Defective'),
      wrong_item: t('Wrong Item'),
      damaged: t('Damaged'),
      not_needed: t('Not Needed'),
      other: t('Other')
    };
    return reasonLabels[reason as keyof typeof reasonLabels] || reason;
  };

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return t('-');
    return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
  };

  return (
    <PageTemplate
      title={returnOrder.return_number}
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
              <h1 className="text-lg font-bold">{returnOrder.name}</h1>
              <p className="text-base text-gray-600 mt-2 leading-relaxed max-w-3xl">{returnOrder.description || t('No description provided')}</p>
            </div>
            <div className="text-right ml-6">
              {getStatusBadge(returnOrder.status)}
              <p className="text-sm font-medium text-gray-700 mt-2 font-mono">{returnOrder.return_number}</p>
            </div>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="border-l-4 border-l-green-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Total Amount')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-green-600 leading-none">{formatCurrency(returnOrder.total_amount)}</h3>
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
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Return Reason')}</p>
                  <h3 className="mt-2 text-lg font-bold text-blue-600 leading-tight">{getReasonLabel(returnOrder.reason)}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-4">
                  <FileText className="h-5 w-5 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-purple-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-purple-600 leading-none">{returnOrder.products?.length || 0}</h3>
                </div>
                <div className="rounded-full bg-purple-100 p-4">
                  <Package className="h-5 w-5 text-purple-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-orange-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Return Date')}</p>
                  <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{window.appSettings?.formatDateTime(returnOrder.return_date, false) || new Date(returnOrder.return_date).toLocaleDateString()}</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-4">
                  <Calendar className="h-5 w-5 text-orange-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Return Order Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Return Order Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Return Number')}</label>
                  <p className="text-sm mt-1">{returnOrder.return_number}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">{getStatusBadge(returnOrder.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Return Reason')}</label>
                  <p className="text-sm mt-1">{getReasonLabel(returnOrder.reason)}</p>
                </div>
                {returnOrder.reason_description && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Reason Description')}</label>
                    <p className="text-sm mt-1">{returnOrder.reason_description}</p>
                  </div>
                )}
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                  <p className="text-sm mt-1">{returnOrder.creator?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{returnOrder.assigned_user?.name || t('Unassigned')}</p>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Tracking Number')}</label>
                  <p className="text-sm mt-1">{returnOrder.tracking_number || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Subtotal')}</label>
                  <p className="text-sm mt-1">{formatCurrency(returnOrder.subtotal)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Tax Amount')}</label>
                  <p className="text-sm mt-1">{formatCurrency(returnOrder.tax_amount)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Return Date')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(returnOrder.return_date, false) || new Date(returnOrder.return_date).toLocaleDateString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(returnOrder.created_at, false) || new Date(returnOrder.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(returnOrder.updated_at, false) || new Date(returnOrder.updated_at).toLocaleDateString()}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Related Data */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Building className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Related Data')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {returnOrder.sales_order && (
                <div className="p-6 bg-purple-50 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Sales Order')}</p>
                  <Link
                    href={route('sales-orders.show', returnOrder.sales_order.id)}
                    className="text-base font-medium text-purple-700 hover:text-purple-900 hover:underline transition-colors"
                  >
                    {returnOrder.sales_order.order_number} - {returnOrder.sales_order.name}
                  </Link>
                </div>
              )}

              {returnOrder.account && (
                <div className="p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Account')}</p>
                  <Link
                    href={route('accounts.show', returnOrder.account.id)}
                    className="text-base font-medium text-green-700 hover:text-green-900 hover:underline transition-colors"
                  >
                    {returnOrder.account.name}
                  </Link>
                </div>
              )}

              {returnOrder.contact && (
                <div className="p-6 bg-blue-50 rounded-xl border border-blue-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Contact')}</p>
                  <Link
                    href={route('contacts.show', returnOrder.contact.id)}
                    className="text-base font-medium text-blue-700 hover:text-blue-900 hover:underline transition-colors"
                  >
                    {returnOrder.contact.name}
                  </Link>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Shipping Information */}
        {(returnOrder.shipping_provider || returnOrder.tracking_number) && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <Truck className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Shipping Information')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {returnOrder.shipping_provider && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Shipping Provider')}</label>
                    <p className="text-sm mt-1">{returnOrder.shipping_provider}</p>
                  </div>
                )}
                {returnOrder.tracking_number && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Tracking Number')}</label>
                    <p className="text-sm mt-1">{returnOrder.tracking_number}</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Products */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Package className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Products')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            {returnOrder.products && returnOrder.products.length > 0 ? (
              <div className="overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-100">
                      <TableHead className="text-base font-bold text-gray-900 py-4 px-6 w-1/3">{t('Product')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Quantity')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Unit Price')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4 w-1/6">{t('Total')}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {returnOrder.products.map((product: any, index: number) => (
                      <TableRow key={index} className="border-b hover:bg-gray-50">
                        <TableCell className="font-semibold text-base text-gray-900 py-4 px-6">{product.name}</TableCell>
                        <TableCell className="text-right text-base font-medium py-4 px-4">{product.pivot.quantity}</TableCell>
                        <TableCell className="text-right text-base font-semibold py-4 px-4">{formatCurrency(product.pivot.unit_price)}</TableCell>
                        <TableCell className="text-right font-bold text-base py-4 px-4">
                          <span className="text-green-600 font-semibold">{formatCurrency(product.pivot.total_price)}</span>
                        </TableCell>
                      </TableRow>
                    ))}
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={3} className="text-right font-semibold text-base py-3 px-4">
                        {t('Subtotal')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(returnOrder.subtotal)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={3} className="text-right font-semibold text-base py-3 px-4">
                        {t('Total Tax')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(returnOrder.tax_amount)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-green-50 border-t-2">
                      <TableCell colSpan={3} className="text-right font-bold text-lg py-4 px-4">
                        {t('Grand Total')}:
                      </TableCell>
                      <TableCell className="text-right py-4 px-4">
                        <span className="text-green-600 font-bold text-xl">{formatCurrency(returnOrder.total_amount)}</span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            ) : (
              <div className="text-center py-16 text-gray-500">
                <Package className="h-16 w-16 mx-auto mb-6 text-gray-300" />
                <p className="text-lg font-medium">{t('No products added to this return order')}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Notes */}
        {returnOrder.notes && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Notes')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <p className="text-sm">{returnOrder.notes}</p>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}
