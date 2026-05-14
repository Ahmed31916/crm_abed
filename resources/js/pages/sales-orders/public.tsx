import React, { useState, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Printer, Copy, Check, Calendar, DollarSign, Package, User, Building, Truck } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { hasPermission } from '@/utils/authorization';

import Template1 from './templates/Template1';
import Template2 from './templates/Template2';
import Template3 from './templates/Template3';
import Template4 from './templates/Template4';
import Template5 from './templates/Template5';
import Template6 from './templates/Template6';
import Template7 from './templates/Template7';
import Template8 from './templates/Template8';
import Template9 from './templates/Template9';
import Template10 from './templates/Template10';
import IframePortal, { IframePortalHandles } from '@/components/IframePortal';
import { formatRelativeTime, getDisplayUrl } from '@/utils/helper';

const templateComponents = {
    template1: Template1,
    template2: Template2,
    template3: Template3,
    template4: Template4,
    template5: Template5,
    template6: Template6,
    template7: Template7,
    template8: Template8,
    template9: Template9,
    template10: Template10,
};

export default function PublicSalesOrder({ salesOrder, templateId = 'template1', color = 'ffffff', qrEnabled = false, settings = {}, themeColor = 'blue', customColor = null }: any) {
    const { t } = useTranslation();
    const { props } = usePage<any>();
    const globalSettings = props.globalSettings;
    const [copied, setCopied] = useState(false);

    const auth = props.auth || {};
    const permissions = auth?.permissions || [];

    useEffect(() => {
        if (props.flash?.success) {
            toast.success(t(props.flash.success));
        }
        if (props.flash?.error) {
            toast.error(t(props.flash.error));
        }
    }, [props.flash, t]);

    const themeColors = { blue: '#3b82f6', green: '#10b77f', purple: '#8b5cf6', orange: '#f97316', red: '#ef4444' };
    const currentThemeColor = themeColor === 'custom' ? customColor : themeColors[themeColor as keyof typeof themeColors] || '#3b82f6';
    const template = { primary: currentThemeColor, secondary: currentThemeColor };

    const formatCurrency = (amount: number) => window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;

    const formatDate = (dateString: string) => {
        if (!dateString) return t('-');
        return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
    };

    const calculateProductTotals = () => {
        let subtotal = 0;
        let totalTax = 0;
        let totalDiscount = 0;

        salesOrder.products?.forEach((product: any) => {
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

    const items = salesOrder.products.map((product: any) => {
        const lineTotal = product.pivot.quantity * product.pivot.unit_price;
        const discountAmount = product.pivot.discount_amount || 0;
        const afterDiscount = lineTotal - discountAmount;
        const taxAmount = product.tax ? (afterDiscount * product.tax.rate / 100) : 0;

        return {
            name: product.name,
            quantity: product.pivot.quantity,
            price: product.pivot.unit_price,
            tax: product.tax?.rate || 0,
            discount: discountAmount,
            itemTax: product.tax ? [{
                name: product.tax.name,
                rate: `${product.tax.rate}%`,
                price: formatCurrency(taxAmount)
            }] : []
        };
    });

    const taxesData = salesOrder.products.reduce((acc: any, product: any) => {
        if (product.tax) {
            const lineTotal = product.pivot.quantity * product.pivot.unit_price;
            const discountAmount = product.pivot.discount_amount || 0;
            const afterDiscount = lineTotal - discountAmount;
            const taxAmount = afterDiscount * product.tax.rate / 100;
            acc[product.tax.name] = (acc[product.tax.name] || 0) + taxAmount;
        }
        return acc;
    }, {});

    const salesOrderData = {
        ...salesOrder,
        sales_order_number: salesOrder.order_number,
        order_date: formatDate(salesOrder.order_date || new Date().toISOString()),
        delivery_date: formatDate(salesOrder.delivery_date || new Date().toISOString()),
        sub_total: subtotal,
        total_tax: totalTax,
        total_amount: grandTotal,
        totalQuantity: salesOrder.products.reduce((sum: number, p: any) => sum + p.pivot.quantity, 0),
        totalRate: salesOrder.products.reduce((sum: number, p: any) => sum + (p.pivot.quantity * p.pivot.unit_price), 0),
        totalTaxPrice: totalTax,
        totalDiscount: totalDiscount,
        total_discount: totalDiscount,
    };

    const TemplateComponent = templateComponents[templateId as keyof typeof templateComponents] || Template1;

    // const handlePrint = () => window.print();
    const iframeRef = useRef<IframePortalHandles>(null);

    const handlePrint = () => {
        iframeRef.current?.print(); // calls the print function inside IframePortal
    };

    const copySalesOrderLink = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
        }
    };

    const getStatusBadge = (status: string) => {
        const statusColors = {
            draft: 'bg-gray-50 text-gray-600 ring-gray-500/10',
            confirmed: 'bg-blue-50 text-blue-700 ring-blue-700/10',
            processing: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
            shipped: 'bg-purple-50 text-purple-700 ring-purple-700/10',
            delivered: 'bg-green-50 text-green-700 ring-green-600/20',
            cancelled: 'bg-red-50 text-red-700 ring-red-600/10'
        };

        return (
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.draft}`}>
                {t(status?.charAt(0).toUpperCase() + status?.slice(1)) || t('Draft')}
            </span>
        );
    };

    return (
        <>
            <Head title={t('Sales Order {{orderNumber}}', { orderNumber: salesOrder.order_number })}>
                {settings?.favicon && (
                    <link rel="icon" href={getDisplayUrl(settings.favicon, globalSettings)} />
                )}
            </Head>

            <div className="min-h-screen bg-gray-50 dark:from-gray-900 dark:to-gray-800 py-8 print:p-0 print:m-0 print:bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print-container">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 print:hidden">
                        <div className="mb-4 sm:mb-0">
                            <h1 className="text-2xl font-bold text-gray-900">{t('Sales Order Details')} - {salesOrder.order_number}</h1>
                            <p className="text-gray-600 mt-1">{t('View your sales order')}</p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {hasPermission(permissions, 'view-sales-orders') && (
                                <button
                                    onClick={copySalesOrderLink}
                                    className="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer"
                                >
                                    {copied ? <Check className="w-4 h-4 mr-2 text-green-600" /> : <Copy className="w-4 h-4 mr-2" />}
                                    {copied ? t('Copied!') : t('Copy Link')}
                                </button>
                            )}
                            <button
                                onClick={handlePrint}
                                className="inline-flex items-center px-4 py-2.5 text-white rounded-xl text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer"
                                style={{
                                    backgroundColor: currentThemeColor,
                                    ':hover': { filter: 'brightness(0.9)' }
                                }}
                            >
                                <Printer className="w-4 h-4 mr-2" />
                                {t('Print Sales Order')}
                            </button>
                        </div>
                    </div>

                    {/* Template for Print */}
                    <div className="hidden print:block">
                        <IframePortal ref={iframeRef}>
                            <TemplateComponent
                                salesOrder={salesOrderData}
                                items={items}
                                taxesData={taxesData}
                                settings={settings}
                                color={color}
                                qr_invoice={qrEnabled ? 'on' : 'off'}
                            />
                        </IframePortal>
                    </div>

                    <div className="mx-auto space-y-6 print:hidden">
                        {/* Header Section */}
                        <div className="bg-white rounded-lg shadow-sm border p-8 print:hidden">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <h1 className="text-lg font-bold text-gray-900 leading-tight">{salesOrder.name}</h1>
                                    <p className="text-base text-gray-600 mt-2 leading-relaxed max-w-3xl">{salesOrder.description || t('No description provided')}</p>
                                </div>
                                <div className="text-right ml-6">
                                    {getStatusBadge(salesOrder.status)}
                                    <p className="text-sm font-medium text-gray-700 mt-2 font-mono">{salesOrder.order_number}</p>
                                </div>
                            </div>
                        </div>

                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="border-l-4 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border" style={{ borderLeftColor: template.primary }}>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Total Amount')}</p>
                                            <h3 className="mt-2 text-2xl font-bold leading-none" style={{ color: template.primary }}>{formatCurrency(salesOrder.total_amount)}</h3>
                                        </div>
                                        <div className="rounded-full p-4" style={{ backgroundColor: `${template.primary}15` }}>
                                            <DollarSign className="h-5 w-5" style={{ color: template.primary }} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border" style={{ borderLeftColor: template.secondary }}>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                                            <h3 className="mt-2 text-2xl font-bold leading-none" style={{ color: template.secondary }}>{salesOrder.products?.length || 0}</h3>
                                        </div>
                                        <div className="rounded-full p-4" style={{ backgroundColor: `${template.secondary}15` }}>
                                            <Package className="h-5 w-5" style={{ color: template.secondary }} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 border-l-orange-500 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border">
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Order Date')}</p>
                                            <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{formatDate(salesOrder.order_date)}</h3>
                                        </div>
                                        <div className="rounded-full bg-orange-100 p-4">
                                            <Calendar className="h-5 w-5 text-orange-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 border-l-purple-500 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border">
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Delivery Date')}</p>
                                            <h3 className="mt-2 text-lg font-bold text-purple-600 leading-tight">{formatDate(salesOrder.delivery_date)}</h3>
                                        </div>
                                        <div className="rounded-full bg-purple-100 p-4">
                                            <Truck className="h-5 w-5 text-purple-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Sales Order Information */}
                        <div className="bg-white rounded-lg shadow-sm border">
                            <div className="bg-gray-50 border-b px-6 py-4">
                                <h3 className="text-lg font-semibold">{t('Sales Order Information')}</h3>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-6">
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Order Number')}</label>
                                            <p className="text-sm mt-1">{salesOrder.order_number}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                                            <div className="mt-1">{getStatusBadge(salesOrder.status)}</div>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                                            <p className="text-sm mt-1">{salesOrder.creator?.name || t('-')}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                                            <p className="text-sm mt-1">{salesOrder.assignedUser?.name || t('Unassigned')}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-6">
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Subtotal')}</label>
                                            <p className="text-sm mt-1">{formatCurrency(salesOrder.subtotal)}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Discount Amount')}</label>
                                            <p className="text-sm mt-1">-{formatCurrency(totalDiscount)}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                                            <p className="text-sm mt-1">{formatDate(salesOrder.created_at)}</p>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                                            <p className="text-sm mt-1">{formatDate(salesOrder.updated_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Related Data */}
                        {(salesOrder.account || salesOrder.quote || salesOrder.shipping_provider_type) && (
                            <div className="bg-white rounded-lg shadow-sm border">
                                <div className="bg-gray-50 border-b px-6 py-4">
                                    <h3 className="flex items-center text-lg font-semibold">
                                        <Building className="h-5 w-5 mr-3 text-muted-foreground" />
                                        {t('Related Data')}
                                    </h3>
                                </div>
                                <div className="p-8">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        {salesOrder.account && (
                                            <div className="p-6 bg-green-50 rounded-xl border border-green-200 hover:shadow-md transition-shadow">
                                                <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Account')}</p>
                                                <p className="text-base font-medium text-green-700">{salesOrder.account.name}</p>
                                            </div>
                                        )}

                                        {salesOrder.quote && (
                                            <div className="p-6 bg-purple-50 rounded-xl border border-purple-200 hover:shadow-md transition-shadow">
                                                <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Quote')}</p>
                                                <p className="text-base font-medium text-purple-700">{salesOrder.quote.name}</p>
                                            </div>
                                        )}

                                        {salesOrder.shipping_provider_type && (
                                            <div className="p-6 bg-orange-50 rounded-xl border border-orange-200 hover:shadow-md transition-shadow">
                                                <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">{t('Shipping Provider')}</p>
                                                <p className="text-base font-medium text-orange-700">{salesOrder.shipping_provider_type.name}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Customer & Delivery Info */}
                        <div className="bg-white rounded-lg shadow-sm border">
                            <div className="bg-gray-50 border-b px-6 py-4">
                                <h3 className="flex items-center text-lg font-semibold">
                                    <User className="h-5 w-5 mr-3 text-muted-foreground" />
                                    {t('Billing & Shipping Details')}
                                </h3>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Billing Details */}
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">{t('Billing Details')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">{t('Contact')}</label>
                                                <p className="text-sm mt-1">{salesOrder.billing_contact?.name || t('-')}</p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">{t('Address')}</label>
                                                <p className="text-sm mt-1">{salesOrder.billing_address || t('-')}</p>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('City')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.billing_city || t('-')}</p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('State')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.billing_state || t('-')}</p>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('Postal Code')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.billing_postal_code || t('-')}</p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('Country')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.billing_country || t('-')}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Shipping Details */}
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4 pb-2 border-b border-gray-200">{t('Shipping Details')}</h3>
                                        <div className="space-y-4">
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">{t('Contact')}</label>
                                                <p className="text-sm mt-1">{salesOrder.shipping_contact?.name || t('-')}</p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">{t('Address')}</label>
                                                <p className="text-sm mt-1">{salesOrder.shipping_address || t('-')}</p>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('City')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.shipping_city || t('-')}</p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('State')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.shipping_state || t('-')}</p>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('Postal Code')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.shipping_postal_code || t('-')}</p>
                                                </div>
                                                <div>
                                                    <label className="text-sm font-medium text-muted-foreground">{t('Country')}</label>
                                                    <p className="text-sm mt-1">{salesOrder.shipping_country || t('-')}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Products Table */}
                        <div className="bg-white rounded-lg shadow-sm border">
                            <div className="bg-gray-50 border-b px-6 py-4">
                                <h3 className="flex items-center text-lg font-semibold">
                                    <Package className="h-5 w-5 mr-3 text-muted-foreground" />
                                    {t('Products')}
                                </h3>
                            </div>
                            <div className="p-0">
                                {salesOrder.products && salesOrder.products.length > 0 ? (
                                    <div className="overflow-hidden">
                                        <table className="min-w-full">
                                            <thead>
                                                <tr style={{ backgroundColor: template.primary }}>
                                                    <th className="text-base font-bold text-white py-4 px-6 w-1/3 text-left">{t('Product')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Quantity')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Unit Price')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Discount')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Tax')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4 w-1/6">{t('Total')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {salesOrder.products.map((product: any, index: number) => {
                                                    const lineTotal = Number(product.pivot.total_price) || 0;
                                                    const discountAmount = Number(product.pivot.discount_amount) || 0;
                                                    const finalTotal = lineTotal - discountAmount;

                                                    return (
                                                        <tr key={index} className="border-b hover:bg-gray-50">
                                                            <td className="font-semibold text-base text-gray-900 py-4 px-6">{product.name}</td>
                                                            <td className="text-right text-base font-medium py-4 px-4">{product.pivot.quantity}</td>
                                                            <td className="text-right text-base font-semibold py-4 px-4">{formatCurrency(product.pivot.unit_price)}</td>
                                                            <td className="text-right py-4 px-4">
                                                                {product.pivot.discount_type && product.pivot.discount_type !== 'none' && product.pivot.discount_value > 0 ? (
                                                                    <div className="text-base">
                                                                        <div className="font-semibold text-gray-700">{product.pivot.discount_type === 'percentage' ? `${Number(product.pivot.discount_value)}%` : formatCurrency(Number(product.pivot.discount_value))}</div>
                                                                        <div className="text-red-600 font-bold">(-{formatCurrency(discountAmount)})</div>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-gray-500 font-medium">-</span>
                                                                )}
                                                            </td>
                                                            <td className="text-right text-base py-4 px-4">
                                                                {product.tax ? (
                                                                    <div className="text-base">
                                                                        <div className="font-semibold text-gray-700">{product.tax.name}</div>
                                                                        <div className="text-gray-600 font-medium">({product.tax.rate}%)</div>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-gray-500 font-medium">{t('No Tax')}</span>
                                                                )}
                                                            </td>
                                                            <td className="text-right font-bold text-base py-4 px-4">
                                                                {discountAmount > 0 ? (
                                                                    <div>
                                                                        <div className="line-through text-gray-400 text-sm font-medium">{formatCurrency(lineTotal)}</div>
                                                                        <div className="text-green-600 font-semibold">{formatCurrency(finalTotal)}</div>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-green-600 font-semibold">{formatCurrency(lineTotal)}</span>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                                <tr style={{ backgroundColor: `${template.primary}10` }}>
                                                    <td colSpan={5} className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {t('Total Discount')}:
                                                    </td>
                                                    <td className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        -{formatCurrency(totalDiscount)}
                                                    </td>
                                                </tr>
                                                <tr style={{ backgroundColor: `${template.primary}10` }}>
                                                    <td colSpan={5} className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {t('Subtotal')}:
                                                    </td>
                                                    <td className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {formatCurrency(subtotal)}
                                                    </td>
                                                </tr>
                                                <tr style={{ backgroundColor: `${template.primary}10` }}>
                                                    <td colSpan={5} className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {t('Total Tax')}:
                                                    </td>
                                                    <td className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {formatCurrency(totalTax)}
                                                    </td>
                                                </tr>
                                                <tr className="border-t-2" style={{ backgroundColor: `${template.primary}15`, borderTopColor: template.primary }}>
                                                    <td colSpan={5} className="text-right font-bold text-lg py-4 px-4" style={{ color: template.primary }}>
                                                        {t('Grand Total')}:
                                                    </td>
                                                    <td className="text-right py-4 px-4">
                                                        <span className="font-bold text-xl" style={{ color: template.primary }}>{formatCurrency(grandTotal)}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="text-center py-16 text-gray-500">
                                        <Package className="h-16 w-16 mx-auto mb-6 text-gray-300" />
                                        <p className="text-lg font-medium">{t('No products added to this sales order')}</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notes */}
                        {salesOrder.notes && (
                            <div className="bg-white rounded-lg shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-3">{t('Notes')}</h3>
                                <p className="text-gray-700 whitespace-pre-wrap">{salesOrder.notes}</p>
                            </div>
                        )}

                        {/* Activity Stream */}
                        {salesOrder.activities && salesOrder.activities.length > 0 && (
                            <div className="bg-white rounded-lg shadow-sm border">
                                <div className="bg-gray-50 border-b px-6 py-4">
                                    <h3 className="flex items-center text-lg font-semibold">
                                        <Calendar className="h-5 w-5 mr-3 text-muted-foreground" />
                                        {t('Activity Stream')}
                                    </h3>
                                </div>
                                <div className="p-6 max-h-96 overflow-y-auto">
                                    <div className="space-y-2">
                                        {salesOrder.activities.map((activity: any, index: number) => {

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
                                                        {index < salesOrder.activities.length - 1 && <div className="w-px h-8 bg-gray-200 mt-2" />}
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
                                                                <span className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20">
                                                                    {activity.activity_type.charAt(0).toUpperCase() + activity.activity_type.slice(1)}
                                                                </span>
                                                            </div>
                                                            {activity.description && (
                                                                <div className="mb-2">
                                                                    {activity.field_changed === 'status' || activity.field_changed === 'name' || activity.field_changed === 'assigned_to' || activity.description.includes('into') ? (
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
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
