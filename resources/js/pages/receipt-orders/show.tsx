import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft } from 'lucide-react';

export default function ShowReceiptOrder() {
  const { t } = useTranslation();
  const { receiptOrder } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Receipt Orders'), href: route('receipt-orders.index') },
    { title: receiptOrder.receipt_number }
  ];

  const getStatusColor = (status: string) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      received: 'bg-blue-100 text-blue-800',
      partial: 'bg-orange-100 text-orange-800',
      completed: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800'
    };
    return colors[status as keyof typeof colors] || colors.pending;
  };

  const formatDate = (date: string) => {
    return window.appSettings?.formatDateTime(date, false) || new Date(date).toLocaleDateString();
  };

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;
  };

  return (
    <PageTemplate
      title={`Receipt Order: ${receiptOrder.receipt_number}`}
      url={`/receipt-orders/${receiptOrder.id}`}
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
      <div className="space-y-6">
        {/* Header Information */}
        <Card>
          <CardHeader>
            <div className="flex justify-between items-start">
              <div>
                <CardTitle className="text-lg font-bold">{receiptOrder.name}</CardTitle>
                <p className="text-base text-gray-600 mt-2 leading-relaxed">{receiptOrder.description || receiptOrder.receipt_number}</p>
              </div>
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                receiptOrder.status === 'completed' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
                receiptOrder.status === 'received' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                receiptOrder.status === 'partial' ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20' :
                receiptOrder.status === 'cancelled' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
              }`}>
                {t(receiptOrder.status.charAt(0).toUpperCase() + receiptOrder.status.slice(1))}
              </span>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div>
                <h4 className="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">{t('Receipt Information')}</h4>
                <div className="space-y-4">
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Receipt Date')}</label>
                    <p className="text-sm mt-1">{formatDate(receiptOrder.receipt_date)}</p>
                  </div>
                  {receiptOrder.expected_date && (
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">{t('Expected Date')}</label>
                      <p className="text-sm mt-1">{formatDate(receiptOrder.expected_date)}</p>
                    </div>
                  )}
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Created')}</label>
                    <p className="text-sm mt-1">{formatDate(receiptOrder.created_at)}</p>
                  </div>
                </div>
              </div>

              <div>
                <h4 className="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">{t('Related Information')}</h4>
                <div className="space-y-4">
                  {receiptOrder.account && (
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">{t('Account')}</label>
                      <p className="text-sm mt-1">{receiptOrder.account.name}</p>
                    </div>
                  )}
                  {receiptOrder.purchase_order && (
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">{t('Purchase Order')}</label>
                      <Link
                        href={route('purchase-orders.show', receiptOrder.purchase_order.id)}
                        className="block text-sm text-blue-600 hover:text-blue-800 hover:underline mt-1 transition-colors"
                      >
                        {receiptOrder.purchase_order.name}
                      </Link>
                    </div>
                  )}
                  {receiptOrder.return_order && (
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">{t('Return Order')}</label>
                      <Link
                        href={route('return-orders.show', receiptOrder.return_order.id)}
                        className="block text-sm text-blue-600 hover:text-blue-800 hover:underline mt-1 transition-colors"
                      >
                        {receiptOrder.return_order.name}
                      </Link>
                    </div>
                  )}
                  {receiptOrder.contact && (
                    <div>
                      <label className="text-sm font-medium text-muted-foreground">{t('Contact')}</label>
                      <p className="text-sm mt-1">{receiptOrder.contact.name}</p>
                    </div>
                  )}
                </div>
              </div>

              <div>
                <h4 className="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">{t('Assignment')}</h4>
                <div className="space-y-4">
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                    <p className="text-sm mt-1">{receiptOrder.creator?.name || t('-')}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                    <p className="text-sm mt-1">{receiptOrder.assigned_user?.name || t('Unassigned')}</p>
                  </div>
                </div>
              </div>
            </div>

            {receiptOrder.description && (
              <div className="mt-6">
                <h4 className="text-lg font-semibold mb-2">{t('Description')}</h4>
                <p className="text-sm">{receiptOrder.description}</p>
              </div>
            )}

            {receiptOrder.notes && (
              <div className="mt-6">
                <h4 className="text-lg font-semibold mb-2">{t('Notes')}</h4>
                <p className="text-sm">{receiptOrder.notes}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Products */}
        {receiptOrder.products && receiptOrder.products.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold">{t('Products')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="w-full border-collapse border border-gray-200">
                  <thead>
                    <tr className="bg-gray-50">
                      <th className="border border-gray-200 px-4 py-2 text-left text-base font-bold text-gray-900">
                        {t('Product')}
                      </th>
                      <th className="border border-gray-200 px-4 py-2 text-right text-base font-bold text-gray-900">
                        {t('Quantity')}
                      </th>
                      <th className="border border-gray-200 px-4 py-2 text-right text-base font-bold text-gray-900">
                        {t('Unit Price')}
                      </th>
                      <th className="border border-gray-200 px-4 py-2 text-right text-base font-bold text-gray-900">
                        {t('Discount')}
                      </th>
                      <th className="border border-gray-200 px-4 py-2 text-right text-base font-bold text-gray-900">
                        {t('Tax')}
                      </th>
                      <th className="border border-gray-200 px-4 py-2 text-right text-base font-bold text-gray-900">
                        {t('Total')}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {receiptOrder.products.map((product: any, index: number) => {
                      const quantity = product.pivot?.quantity || 0;
                      const unitPrice = product.pivot?.unit_price || 0;
                      const lineTotal = quantity * unitPrice;
                      const discountAmount = product.pivot?.discount_amount || 0;
                      const finalLineTotal = lineTotal - discountAmount;
                      const taxAmount = product.tax ? (finalLineTotal * product.tax.rate) / 100 : 0;

                      return (
                        <tr key={index} className="hover:bg-gray-50">
                          <td className="border border-gray-200 px-4 py-2 text-base">
                            <div className="font-semibold text-gray-900">{product.name}</div>
                            {product.tax && (
                              <div className="text-sm text-gray-500 mt-1">
                                {t('Tax')}: {product.tax.name} ({product.tax.rate}%)
                              </div>
                            )}
                          </td>
                          <td className="border border-gray-200 px-4 py-2 text-base text-right font-medium">
                            {quantity}
                          </td>
                          <td className="border border-gray-200 px-4 py-2 text-base text-right font-semibold">
                            {formatCurrency(unitPrice)}
                          </td>
                          <td className="border border-gray-200 px-4 py-2 text-base text-right">
                            {discountAmount > 0 ? (
                              <span className="text-red-600 font-bold">-{formatCurrency(discountAmount)}</span>
                            ) : (
                              <span className="text-gray-500 font-medium">{t('No Discount')}</span>
                            )}
                          </td>
                          <td className="border border-gray-200 px-4 py-2 text-base text-right">
                            {product.tax ? (
                              <div>
                                <div className="font-semibold text-gray-700">{product.tax.name}</div>
                                <div className="text-gray-600 font-medium">({product.tax.rate}%)</div>
                              </div>
                            ) : (
                              <span className="text-gray-500 font-medium">{t('No Tax')}</span>
                            )}
                          </td>
                          <td className="border border-gray-200 px-4 py-2 text-base text-right font-bold">
                            {discountAmount > 0 ? (
                              <div>
                                <div className="line-through text-gray-400 text-sm font-medium">{formatCurrency(lineTotal)}</div>
                                <div className="text-green-600 font-semibold">{formatCurrency(finalLineTotal)}</div>
                              </div>
                            ) : (
                              <span className="text-green-600 font-semibold">{formatCurrency(lineTotal)}</span>
                            )}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>

              <Separator className="my-4" />

              {/* Totals */}
              <div className="flex justify-end">
                <div className="w-64 space-y-2">
                  <div className="flex justify-between text-base">
                    <span className="font-semibold text-gray-600">{t('Subtotal')}:</span>
                    <span className="font-semibold">{formatCurrency(receiptOrder.subtotal)}</span>
                  </div>
                  <div className="flex justify-between text-base">
                    <span className="font-semibold text-gray-600">{t('Total Tax')}:</span>
                    <span className="font-semibold">{formatCurrency(receiptOrder.tax_amount)}</span>
                  </div>
                  <Separator />
                  <div className="flex justify-between text-xl font-bold">
                    <span>{t('Grand Total')}:</span>
                    <span className="text-green-600">{formatCurrency(receiptOrder.total_amount)}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}
