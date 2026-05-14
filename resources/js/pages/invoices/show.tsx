import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, DollarSign, Package, User, Building, FileText, Plus, EyeOff, Trash2, Send, Edit, Check, X } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatRelativeTime, getDisplayUrl } from '@/utils/helper';

export default function InvoiceShow() {
  const { t } = useTranslation();
  const { invoice, streamItems, pendingPayments, invoiceReminders, availableSalesOrders, auth } = usePage().props as any;
  const isCompany = auth?.user?.type === 'company';
  const [isAssignSalesOrderModalOpen, setIsAssignSalesOrderModalOpen] = useState(false);
  const [selectedSalesOrderId, setSelectedSalesOrderId] = useState('empty');
  const [showStream, setShowStream] = useState(true);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isDeleteAllModalOpen, setIsDeleteAllModalOpen] = useState(false);
  const [currentActivity, setCurrentActivity] = useState<any>(null);
  const [newComment, setNewComment] = useState('');
  const [editingComment, setEditingComment] = useState<number | null>(null);
  const [editCommentText, setEditCommentText] = useState('');

  // Calculate paid amount from completed payments
  const paidAmount = invoice.payments?.reduce((total: number, payment: any) => {
    const amount = parseFloat(payment.amount) || 0;
    return payment.status === 'completed' ? total + amount : total;
  }, 0) || 0;

  // Calculate due amount
  const dueAmount = Math.max(0, (parseFloat(invoice.total_amount) || 0) - paidAmount);

  const handleAssignSalesOrder = (formData?: any) => {
    const salesOrderId = formData?.sales_order_id || selectedSalesOrderId;
    if (!salesOrderId || salesOrderId === 'empty') {
      toast.error(t('Please select a sales order'));
      return;
    }

    toast.loading(t('Assigning sales order...'));

    router.put(route('invoices.add-sales-order', invoice.id), {
      sales_order_id: salesOrderId
    }, {
      onSuccess: (page) => {
        setIsAssignSalesOrderModalOpen(false);
        setSelectedSalesOrderId('empty');
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to assign sales order: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Invoices'), href: route('invoices.index') },
    { title: invoice.invoice_number }
  ];

  const getStatusBadge = (status: string) => {
    const statusColors = {
      draft: 'bg-gray-50 text-gray-600 ring-gray-500/10',
      sent: 'bg-blue-50 text-blue-700 ring-blue-700/10',
      paid: 'bg-green-50 text-green-700 ring-green-600/20',
      partially_paid: 'bg-orange-50 text-orange-800 ring-orange-600/20',
      overdue: 'bg-red-50 text-red-700 ring-red-600/10',
      cancelled: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20'
    };

    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.draft}`}>
        {status === 'partially_paid' ? t('Partially Paid') : (status?.charAt(0).toUpperCase() + status?.slice(1) || t('Draft'))}
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
    let totalDiscount = 0;

    invoice.products?.forEach((product: any) => {
      const lineTotal = Number(product.pivot.total_price) || 0;
      const discountAmount = Number(product.pivot.discount_amount) || 0;
      const finalLineTotal = lineTotal - discountAmount;

      subtotal += finalLineTotal;
      totalDiscount += discountAmount;

      if (product.tax) {
        totalTax += (finalLineTotal * Number(product.tax.rate)) / 100;
      }
    });

    return { subtotal, totalTax, totalDiscount, grandTotal: subtotal + totalTax };
  };

  const { subtotal, totalTax, totalDiscount, grandTotal } = calculateProductTotals();

  return (
    <PageTemplate
      title={invoice.invoice_number}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => window.history.back()
        },
        ...(!invoice.sales_order ? [{
          label: t('Assign Sales Order'),
          icon: <Plus className="h-4 w-4 mr-2" />,
          variant: 'default',
          onClick: () => setIsAssignSalesOrderModalOpen(true)
        }] : [])
      ]}
    >
      <div className="mx-auto space-y-6">
        {/* Header Section */}
        <div className="bg-white rounded-lg shadow-sm border p-8">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h1 className="text-lg font-bold">{invoice.name}</h1>
              <p className="text-base text-gray-600 mt-2 leading-relaxed max-w-3xl">{invoice.description || t('No description provided')}</p>
            </div>
            <div className="text-right ml-6">
              {getStatusBadge(invoice.status)}
              <p className="text-sm font-medium text-gray-700 mt-2 font-mono">{invoice.invoice_number}</p>
            </div>
          </div>
        </div>

        {/* Payment Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="border-l-4 border-l-green-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Total Amount')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-green-600 leading-none">{formatCurrency(invoice.total_amount)}</h3>
                </div>
                <div className="rounded-full bg-green-100 p-4">
                  <DollarSign className="h-5 w-5 text-green-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-blue-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Paid Amount')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-blue-600 leading-none">{formatCurrency(paidAmount)}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-4">
                  <DollarSign className="h-5 w-5 text-blue-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-red-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Due Amount')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-red-600 leading-none">{formatCurrency(dueAmount)}</h3>
                </div>
                <div className="rounded-full bg-red-100 p-4">
                  <DollarSign className="h-5 w-5 text-red-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Invoice Details Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="border-l-4 border-l-purple-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                  <h3 className="mt-2 text-2xl font-bold text-purple-600 leading-none">{invoice.products?.length || 0}</h3>
                </div>
                <div className="rounded-full bg-purple-100 p-4">
                  <Package className="h-5 w-5 text-purple-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-orange-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Invoice Date')}</p>
                  <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{window.appSettings?.formatDateTime(invoice.invoice_date, false) || new Date(invoice.invoice_date).toLocaleDateString()}</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-4">
                  <Calendar className="h-5 w-5 text-orange-600" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-l-4 border-l-amber-500 hover:shadow-lg transition-shadow">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Due Date')}</p>
                  <h3 className="mt-2 text-lg font-bold text-amber-600 leading-tight">{window.appSettings?.formatDateTime(invoice.due_date, false) || new Date(invoice.due_date).toLocaleDateString()}</h3>
                </div>
                <div className="rounded-full bg-amber-100 p-4">
                  <FileText className="h-5 w-5 text-amber-600" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Invoice Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Invoice Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Invoice Number')}</label>
                  <p className="text-sm mt-1">{invoice.invoice_number}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">{getStatusBadge(invoice.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                  <p className="text-sm mt-1">{invoice.creator?.name || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                  <p className="text-sm mt-1">{invoice.assignedUser?.name || t('Unassigned')}</p>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Subtotal')}</label>
                  <p className="text-sm mt-1">{formatCurrency(invoice.subtotal)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Discount Amount')}</label>
                  <p className="text-sm mt-1">-{formatCurrency(totalDiscount)}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(invoice.created_at, false) || new Date(invoice.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                  <p className="text-sm mt-1">{window.appSettings?.formatDateTime(invoice.updated_at, false) || new Date(invoice.updated_at).toLocaleDateString()}</p>
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
              {invoice.account && (
                <div className="p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Account')}</p>
                  <Link
                    href={route('accounts.show', invoice.account.id)}
                    className="text-base font-medium text-green-700 hover:text-green-900 hover:underline transition-colors"
                  >
                    {invoice.account.name}
                  </Link>
                </div>
              )}

              {invoice.sales_order && (
                <div className="p-6 bg-purple-50 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Sales Order')}</p>
                  <Link
                    href={route('sales-orders.show', invoice.sales_order.id)}
                    className="text-base font-medium text-purple-700 hover:text-purple-900 hover:underline transition-colors"
                  >
                    {invoice.sales_order.name}
                  </Link>
                </div>
              )}

              {invoice.quote && (
                <div className="p-6 bg-orange-50 rounded-xl border border-orange-200 hover:shadow-md transition-shadow">
                  <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Quote')}</p>
                  <Link
                    href={route('quotes.show', invoice.quote.id)}
                    className="text-base font-medium text-orange-700 hover:text-orange-900 hover:underline transition-colors"
                  >
                    {invoice.quote.name}
                  </Link>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Billing Details */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="flex items-center text-lg font-semibold">
              <User className="h-5 w-5 mr-3 text-muted-foreground" />
              {t('Billing Details')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="space-y-4">
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Contact')}</label>
                {invoice.contact ? (
                  <Link
                    href={route('contacts.show', invoice.contact.id)}
                    className="block text-sm text-blue-600 hover:text-blue-800 hover:underline mt-1 transition-colors"
                  >
                    {invoice.contact.name}
                  </Link>
                ) : (
                  <p className="text-sm mt-1">{t('-')}</p>
                )}
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Address')}</label>
                <p className="text-sm mt-1">{invoice.billing_address || t('-')}</p>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('City')}</label>
                  <p className="text-sm mt-1">{invoice.billing_city || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('State')}</label>
                  <p className="text-sm mt-1">{invoice.billing_state || t('-')}</p>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Postal Code')}</label>
                  <p className="text-sm mt-1">{invoice.billing_postal_code || t('-')}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Country')}</label>
                  <p className="text-sm mt-1">{invoice.billing_country || t('-')}</p>
                </div>
              </div>
            </div>
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
            {invoice.products && invoice.products.length > 0 ? (
              <div className="overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-gray-100">
                      <TableHead className="text-base font-bold text-gray-900 py-4 px-6 w-1/3">{t('Product')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Quantity')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Unit Price')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Discount')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Tax')}</TableHead>
                      <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4 w-1/6">{t('Total')}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {invoice.products.map((product: any, index: number) => {
                      const lineTotal = Number(product.pivot.total_price) || 0;
                      const discountAmount = Number(product.pivot.discount_amount) || 0;
                      const finalTotal = lineTotal - discountAmount;

                      return (
                        <TableRow key={index} className="border-b hover:bg-gray-50">
                          <TableCell className="font-semibold text-base text-gray-900 py-4 px-6">{product.name}</TableCell>
                          <TableCell className="text-right text-base font-medium py-4 px-4">{product.pivot.quantity}</TableCell>
                          <TableCell className="text-right text-base font-semibold py-4 px-4">{formatCurrency(product.pivot.unit_price)}</TableCell>
                          <TableCell className="text-right py-4 px-4">
                            {product.pivot.discount_type && product.pivot.discount_type !== 'none' && product.pivot.discount_value > 0 ? (
                              <div className="text-base">
                                <div className="font-semibold text-gray-700">{product.pivot.discount_type === 'percentage' ? `${Number(product.pivot.discount_value)}%` : formatCurrency(Number(product.pivot.discount_value))}</div>
                                <div className="text-red-600 font-bold">(-{formatCurrency(discountAmount)})</div>
                              </div>
                            ) : (
                              <span className="text-gray-500 font-medium">{t('No Discount')}</span>
                            )}
                          </TableCell>
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
                          <TableCell className="text-right font-bold text-base py-4 px-4">
                            {discountAmount > 0 ? (
                              <div>
                                <div className="line-through text-gray-400 text-sm font-medium">{formatCurrency(lineTotal)}</div>
                                <div className="text-green-600 font-semibold">{formatCurrency(finalTotal)}</div>
                              </div>
                            ) : (
                              <span className="text-green-600 font-semibold">{formatCurrency(lineTotal)}</span>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                    <TableRow className="bg-gray-50 border-t-2">
                      <TableCell colSpan={5} className="text-right font-semibold text-base py-3 px-4">
                        {t('Total Discount')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base text-red-600 py-3 px-4">
                        -{formatCurrency(totalDiscount)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={5} className="text-right font-semibold text-base py-3 px-4">
                        {t('Subtotal')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(subtotal)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-gray-50">
                      <TableCell colSpan={5} className="text-right font-semibold text-base py-3 px-4">
                        {t('Total Tax')}:
                      </TableCell>
                      <TableCell className="text-right font-semibold text-base py-3 px-4">
                        {formatCurrency(totalTax)}
                      </TableCell>
                    </TableRow>
                    <TableRow className="bg-green-50 border-t-2">
                      <TableCell colSpan={5} className="text-right font-bold text-lg py-4 px-4">
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
                <p className="text-lg font-medium">{t('No products added to this invoice')}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Pending Payments */}
        {pendingPayments && pendingPayments.length > 0 && (
          <Card className="shadow-sm border-l-4 border-l-yellow-500">
            <CardHeader className="bg-yellow-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <DollarSign className="h-5 w-5 mr-3 text-yellow-600" />
                {t('Pending Payments')} ({pendingPayments.length})
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow className="bg-gray-100">
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-6">{t('Date')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Method')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Type')}</TableHead>
                    <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Amount')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Payment ID')}</TableHead>
                    <TableHead className="text-center text-base font-bold text-gray-900 py-4 px-4">{t('Actions')}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {pendingPayments.map((payment: any, index: number) => (
                    <TableRow key={index} className="border-b hover:bg-gray-50">
                      <TableCell className="py-4 px-6">{window.appSettings?.formatDateTime(payment.created_at, false) || new Date(payment.created_at).toLocaleDateString()}</TableCell>
                      <TableCell className="py-4 px-4 capitalize">{payment.payment_method}</TableCell>
                      <TableCell className="py-4 px-4 capitalize">{payment.payment_type}</TableCell>
                      <TableCell className="text-right py-4 px-4 font-semibold">{formatCurrency(payment.amount)}</TableCell>
                      <TableCell className="py-4 px-4 font-mono text-sm">{payment.payment_id}</TableCell>
                      <TableCell className="py-4 px-4">
                        <div className="flex justify-center gap-2">
                          <TooltipProvider>
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  className="text-green-600"
                                  onClick={() => {
                                    router.post(route('invoice.payments.approve', payment.payment_id), {}, {
                                      preserveScroll: true,
                                      onSuccess: (page) => {
                                        if (page.props.flash.success) {
                                          toast.success(t(page.props.flash.success));
                                        } else {
                                          toast.success(t('Payment approved successfully'));
                                        }
                                      },
                                      onError: (errors) => {
                                        if (typeof errors === 'string') {
                                          toast.error(errors);
                                        } else {
                                          toast.error(t('Failed to approve payment'));
                                        }
                                      }
                                    });
                                  }}
                                >
                                  <Check className="h-4 w-4" />
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>{t('Approve')}</p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                          <TooltipProvider>
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  className="text-red-600"
                                  onClick={() => {
                                    const reason = prompt(t('Reason for rejection (optional):'));
                                    router.post(route('invoice.payments.reject', payment.payment_id), {
                                      reason: reason || ''
                                    }, {
                                      preserveScroll: true,
                                      onSuccess: (page) => {
                                        if (page.props.flash.success) {
                                          toast.success(t(page.props.flash.success));
                                        } else {
                                          toast.success(t('Payment rejected successfully'));
                                        }
                                      },
                                      onError: (errors) => {
                                        if (typeof errors === 'string') {
                                          toast.error(errors);
                                        } else {
                                          toast.error(t('Failed to reject payment'));
                                        }
                                      }
                                    });
                                  }}
                                >
                                  <X className="h-4 w-4" />
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>
                                <p>{t('Reject')}</p>
                              </TooltipContent>
                            </Tooltip>
                          </TooltipProvider>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        )}

        {/* Payments */}
        {invoice.payments && invoice.payments.length > 0 && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="flex items-center text-lg font-semibold">
                <DollarSign className="h-5 w-5 mr-3 text-muted-foreground" />
                {t('Payment History')}
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow className="bg-gray-100">
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-6">{t('Date')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Method')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Type')}</TableHead>
                    <TableHead className="text-right text-base font-bold text-gray-900 py-4 px-4">{t('Amount')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Status')}</TableHead>
                    <TableHead className="text-base font-bold text-gray-900 py-4 px-4">{t('Payment ID')}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {invoice.payments.map((payment: any, index: number) => (
                    <TableRow key={index} className="border-b hover:bg-gray-50">
                      <TableCell className="py-4 px-6">{window.appSettings?.formatDateTime(payment.processed_at || payment.created_at, false) || new Date(payment.processed_at || payment.created_at).toLocaleDateString()}</TableCell>
                      <TableCell className="py-4 px-4 capitalize">{payment.payment_method}</TableCell>
                      <TableCell className="py-4 px-4 capitalize">{payment.payment_type}</TableCell>
                      <TableCell className="text-right py-4 px-4 font-semibold">{formatCurrency(payment.amount)}</TableCell>
                      <TableCell className="py-4 px-4">
                        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                          payment.status === 'completed' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                          payment.status === 'pending' ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' :
                          'bg-red-50 text-red-700 ring-red-600/10'
                        }`}>
                          {payment.status}
                        </span>
                      </TableCell>
                      <TableCell className="py-4 px-4 font-mono text-sm">{payment.payment_id || t('-')}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        )}

        {/* Notes and Terms */}
        {(invoice.notes || invoice.terms) && (
          <Card className="shadow-sm">
            <CardHeader className="bg-gray-50 border-b">
              <CardTitle className="text-lg font-semibold">{t('Additional Information')}</CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {invoice.notes && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Notes')}</label>
                    <p className="text-sm mt-1">{invoice.notes}</p>
                  </div>
                )}
                {invoice.terms && (
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Terms')}</label>
                    <p className="text-sm mt-1">{invoice.terms}</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Activity Stream */}
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
                        router.post(route('invoices.comments.store', invoice.id), {
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
                      case 'assigned': return <User className="h-4 w-4 text-purple-600" />;
                      default: return <FileText className="h-4 w-4 text-gray-600" />;
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
                                new RegExp(`^(${activity.user?.name || 'System'})`, 'g'),
                                '<span class="font-bold text-sm">$1</span>'
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
                              {activity.field_changed === 'status' || activity.field_changed === 'invoice_number' || activity.field_changed === 'assigned_to' || activity.description.includes('into') ? (
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
                                          router.put(route('invoices.comments.update-activity', { invoice: invoice.id, activity: activity.id }), {
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

        <CrudFormModal
          isOpen={isAssignSalesOrderModalOpen}
          onClose={() => {
            setIsAssignSalesOrderModalOpen(false);
            setSelectedSalesOrderId('empty');
          }}
          onSubmit={handleAssignSalesOrder}
          formConfig={{
            modalSize: 'md',
            layout: 'vertical',
            fields: [
              {
                name: 'sales_order_id',
                label: t('Select Sales Order'),
                type: 'select',
                required: true,
                options: [
                  { value: 'empty', label: t('Select Sales Order') },
                  ...availableSalesOrders?.map((so: any) => ({
                    value: so.id.toString(),
                    label: `${so.order_number} - ${so.name}`
                  })) || []
                ]
              }
            ]
          }}
          initialData={{ sales_order_id: selectedSalesOrderId || 'empty' }}
          title={t('Assign Sales Order to Invoice')}
          mode="create"
          onFieldChange={(field, value) => {
            if (field === 'sales_order_id') {
              setSelectedSalesOrderId(value);
            }
          }}
        />

        {/* Delete Activity Modal */}
        <CrudDeleteModal
          isOpen={isDeleteModalOpen}
          onClose={() => setIsDeleteModalOpen(false)}
          onConfirm={() => {
            router.delete(route('invoices.delete-activity', { invoice: invoice.id, activity: currentActivity.id }), {
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
            router.delete(route('invoices.delete-activities', invoice.id), {
              preserveScroll: true
            });
            setIsDeleteAllModalOpen(false);
          }}
          itemName={t('all activities for {{invoiceName}}', { invoiceName: invoice.name })}
          entityName={t('activities')}
        />
      </div>
    </PageTemplate>
  );
}
