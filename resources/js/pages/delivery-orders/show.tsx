import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Package, User, Building, Truck, FileText, MapPin, Weight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useTranslation } from 'react-i18next';

export default function DeliveryOrderShow() {
  const { t } = useTranslation();
  const { deliveryOrder } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Delivery Orders'), href: route('delivery-orders.index') },
    { title: deliveryOrder.delivery_number }
  ];

  const getStatusBadge = (status: string) => {
    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
        status === 'delivered' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
        status === 'in_transit' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
        status === 'cancelled' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
        'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
      }`}>
        {status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || t('Pending')}
      </span>
    );
  };

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;
  };



  const formatAddress = () => {
    const parts = [
      deliveryOrder.delivery_address,
      deliveryOrder.delivery_city,
      deliveryOrder.delivery_state,
      deliveryOrder.delivery_postal_code,
      deliveryOrder.delivery_country
    ].filter(Boolean);

    return parts.length > 0 ? parts.join(', ') : t('-');
  };

  return (
    <PageTemplate
      title={deliveryOrder.delivery_number}
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
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold">{deliveryOrder.name}</h1>
              <p className="text-sm text-gray-600 mt-2 leading-relaxed">{deliveryOrder.description || t('No description provided')}</p>
            </div>
            <div className="text-right">
              {getStatusBadge(deliveryOrder.status)}
              <p className="text-sm text-gray-500 mt-2">{deliveryOrder.delivery_number}</p>
            </div>
          </div>
        </div>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <Card className="border-l-4 border-l-green-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Shipping Cost')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-green-600 leading-none">{formatCurrency(deliveryOrder.shipping_cost)}</h3>
                </div>
                <div className="rounded-full bg-green-100 p-3">
                  <Truck className="h-5 w-5 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-blue-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-blue-600 leading-none">{deliveryOrder.products?.length || 0}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-3">
                  <Package className="h-5 w-5 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-orange-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Delivery Date')}</p>
                  <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{window.appSettings?.formatDateTime(deliveryOrder.delivery_date, false) || new Date(deliveryOrder.delivery_date).toLocaleDateString()}</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-3">
                  <Calendar className="h-5 w-5 text-orange-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-purple-500">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Total Weight')}</p>
                  <h3 className="mt-2 text-lg font-bold text-purple-600 leading-none">{deliveryOrder.total_weight} kg</h3>
                </div>
                <div className="rounded-full bg-purple-100 p-3">
                  <Weight className="h-5 w-5 text-purple-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Delivery Order Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Delivery Order Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Delivery Number')}</label>
                  <p className="text-sm mt-1">{deliveryOrder.delivery_number}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">{getStatusBadge(deliveryOrder.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                  <p className="text-sm mt-1">{deliveryOrder.creator?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{deliveryOrder.assigned_user?.name || t('Unassigned')}</p>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Total Weight')}</label>
                  <p className="text-sm mt-1">{deliveryOrder.total_weight} kg</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Shipping Cost')}</label>
                  <p className="text-sm mt-1">{formatCurrency(deliveryOrder.shipping_cost)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(deliveryOrder.created_at, false) || new Date(deliveryOrder.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(deliveryOrder.updated_at, false) || new Date(deliveryOrder.updated_at).toLocaleDateString()}</p>
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
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              {deliveryOrder.sales_order && (
                <div className="p-6 bg-blue-50 rounded-xl border border-blue-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Sales Order')}</p>
                  <Link
                    href={route('sales-orders.show', deliveryOrder.sales_order.id)}
                    className="text-base font-medium text-blue-600 hover:text-blue-800 hover:underline"
                  >
                    {deliveryOrder.sales_order.order_number} - {deliveryOrder.sales_order.name}
                  </Link>
                </div>
              )}

              {deliveryOrder.account && (
                <div className="p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Account')}</p>
                  <Link
                    href={route('accounts.show', deliveryOrder.account.id)}
                    className="text-base font-medium text-green-600 hover:text-green-800 hover:underline"
                  >
                    {deliveryOrder.account.name}
                  </Link>
                </div>
              )}

              {deliveryOrder.contact && (
                <div className="p-6 bg-purple-50 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Contact')}</p>
                  <Link
                    href={route('contacts.show', deliveryOrder.contact.id)}
                    className="text-base font-medium text-purple-600 hover:text-purple-800 hover:underline"
                  >
                    {deliveryOrder.contact.name}
                  </Link>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Delivery Address */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <MapPin className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Delivery Address')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <p className="text-sm">{formatAddress()}</p>
            {deliveryOrder.tracking_number && (
              <div className="mt-6 pt-6 border-t">
                <label className="text-sm font-medium text-muted-foreground">{t('Tracking Number')}</label>
                <p className="text-sm mt-1">{deliveryOrder.tracking_number}</p>
              </div>
            )}
            {deliveryOrder.delivery_notes && (
              <div className="mt-6 pt-6 border-t">
                <label className="text-sm font-medium text-muted-foreground">{t('Delivery Notes')}</label>
                <p className="text-sm mt-1">{deliveryOrder.delivery_notes}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Products */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <Package className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Products')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            {deliveryOrder.products && deliveryOrder.products.length > 0 ? (
              <div className="overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-100">
                      <TableHead className="text-base font-bold text-gray-900 py-4 px-6 w-1/3">{t('Product')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Quantity')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Unit Weight')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4 w-1/6">{t('Total Weight')}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {deliveryOrder.products.map((product: any, index: number) => (
                      <TableRow key={index} className="border-b hover:bg-gray-50">
                        <TableCell className="font-medium text-base text-gray-800 py-4 px-6">{product.name}</TableCell>
                        <TableCell className="text-right text-base font-medium text-gray-800 py-4 px-4">{product.pivot.quantity}</TableCell>
                        <TableCell className="text-right text-base font-medium text-gray-800 py-4 px-4">{product.pivot.unit_weight} kg</TableCell>
                        <TableCell className="text-right font-medium text-base py-4 px-4">
                          <span className="text-green-600 font-bold">{product.pivot.total_weight} kg</span>
                        </TableCell>
                      </TableRow>
                    ))}
                    <TableRow className="bg-green-50 border-t-2">
                      <TableCell colSpan={3} className="text-right font-bold text-lg py-4 px-4">
                        {t('Total Weight')}:
                      </TableCell>
                      <TableCell className="text-right py-4 px-4">
                        <span className="text-green-600 font-bold text-xl">{deliveryOrder.total_weight} kg</span>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            ) : (
              <div className="text-center py-12 text-gray-500">
                <Package className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                <p className="text-lg">{t('No products added to this delivery order')}</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}
