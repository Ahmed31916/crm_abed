import React, { useState, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Calendar, MapPin, CreditCard, Printer, Package, DollarSign, User, Copy, Check, FileText } from 'lucide-react';
import { InvoicePaymentModal } from '@/components/invoice-payment-modal';
import { toast } from '@/components/custom-toast';
import { hasPermission } from '@/utils/authorization';
import { getDisplayUrl } from '@/utils/helper';

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

interface Invoice {
    id: number;
    invoice_number: string;
    name: string;
    description?: string;
    invoice_date: string;
    due_date: string;
    status: string;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    billing_address?: string;
    billing_city?: string;
    billing_state?: string;
    billing_postal_code?: string;
    billing_country?: string;
    notes?: string;
    terms?: string;
    payment_method?: string;
    account?: {
        id: number;
        name: string;
        email?: string;
        phone?: string;
    };
    contact?: {
        id: number;
        name: string;
        email?: string;
        phone?: string;
    };
    products: {
        id: number;
        name: string;
        pivot: {
            quantity: number;
            unit_price: number;
            total_price: number;
            discount_type?: string;
            discount_value?: number;
            discount_amount?: number;
        };
        tax?: {
            name: string;
            rate: number;
        };
    }[];
    payments?: {
        id: number;
        amount: number;
        payment_method: string;
        payment_type: string;
        status: string;
        processed_at?: string;
        created_at: string;
    }[];
}

const templates = {
    1: { primary: '#3b82f6', secondary: '#1d4ed8' },
    2: { primary: '#6b7280', secondary: '#374151' },
    3: { primary: '#059669', secondary: '#047857' },
    4: { primary: '#ea580c', secondary: '#c2410c' },
    5: { primary: '#7c3aed', secondary: '#5b21b6' },
    6: { primary: '#dc2626', secondary: '#991b1b' },
    7: { primary: '#0891b2', secondary: '#0e7490' },
    8: { primary: '#d97706', secondary: '#92400e' },
    9: { primary: '#db2777', secondary: '#be185d' }
};

interface Props {
    invoice: Invoice;
    templateId?: string;
    color?: string;
    qrEnabled?: boolean;
    settings?: any;
    themeColor?: string;
    customColor?: string;
}

export default function PublicInvoice({ invoice, templateId = 'template1', color = 'ffffff', qrEnabled = false, settings = {}, themeColor = 'blue', customColor = null }: Props) {
    const { t } = useTranslation();
    const { props } = usePage<any>();
    const globalSettings = props.globalSettings;
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [copied, setCopied] = useState(false);

    const auth = props.auth || {};
    const permissions = auth?.permissions || [];

    // Handle flash messages
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

    // Calculate paid amount from completed payments
    const paidAmount = invoice.payments?.reduce((total, payment) => {
        const amount = Number(payment.amount) || 0;
        return payment.status === 'completed' ? total + amount : total;
    }, 0) || 0;

    // Calculate due amount
    const dueAmount = Math.max(0, (Number(invoice.total_amount) || 0) - paidAmount);

    const [paymentAmount, setPaymentAmount] = useState(dueAmount);

    const formatCurrency = (amount: number) => {
        return window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;
    };

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

    const items = invoice.products.map((product: any) => {
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

    const taxesData = invoice.products.reduce((acc: any, product: any) => {
        if (product.tax) {
            const lineTotal = product.pivot.quantity * product.pivot.unit_price;
            const discountAmount = product.pivot.discount_amount || 0;
            const afterDiscount = lineTotal - discountAmount;
            const taxAmount = afterDiscount * product.tax.rate / 100;
            acc[product.tax.name] = (acc[product.tax.name] || 0) + taxAmount;
        }
        return acc;
    }, {});

    const invoiceData = {
        ...invoice,
        invoice_date: formatDate(invoice.invoice_date || new Date().toISOString()),
        due_date: formatDate(invoice.due_date || new Date().toISOString()),
        sub_total: subtotal,
        total_tax: totalTax,
        total_amount: grandTotal,
        totalQuantity: invoice.products.reduce((sum: number, p: any) => sum + p.pivot.quantity, 0),
        totalRate: invoice.products.reduce((sum: number, p: any) => sum + (p.pivot.quantity * p.pivot.unit_price), 0),
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

    const copyInvoiceLink = async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
        }
    };

    return (
        <>
            <Head title={t('Invoice {{invoiceNumber}}', { invoiceNumber: invoice.invoice_number })}>
                {settings?.favicon && (
                    <link rel="icon" href={getDisplayUrl(settings.favicon, props.globalSettings)} />
                )}
            </Head>

            <div className="min-h-screen bg-gray-50 dark:from-gray-900 dark:to-gray-800 py-8 print:p-0 print:m-0 print:bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print-container">
                    {/* Action Bar */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 print:hidden">
                        <div className="mb-4 sm:mb-0">
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('Invoice Details')}</h1>
                            <p className="text-gray-600 dark:text-gray-400 mt-1">{t('View and manage your invoice')}</p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {hasPermission(permissions, 'view-invoices') && (
                                <button
                                    onClick={copyInvoiceLink}
                                    className="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer"
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
                                {t('Print Invoice')}
                            </button>
                            {invoice.status !== 'paid' && (
                                <button
                                    onClick={() => setShowPaymentModal(true)}
                                    className="inline-flex items-center px-6 py-2.5 text-white rounded-xl text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 cursor-pointer"
                                    style={{
                                        background: `linear-gradient(135deg, ${currentThemeColor}, ${currentThemeColor}dd)`,
                                        ':hover': { filter: 'brightness(0.9)' }
                                    }}
                                >
                                    <CreditCard className="w-4 h-4 mr-2" />
                                    {invoice.status === 'partially_paid' ? t('Pay Remaining') : t('Pay Invoice')}
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Template for Print */}
                    <div className='hidden print:block'>
                        <IframePortal ref={iframeRef}>
                            <TemplateComponent
                                invoice={invoiceData}
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
                                    <h1 className="text-2xl font-bold text-gray-900 leading-tight">{invoice.name}</h1>
                                    <p className="text-base text-gray-600 mt-2 leading-relaxed max-w-3xl">{invoice.description || t('No description provided')}</p>
                                </div>
                                <div className="text-right ml-6">
                                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${invoice.status === 'paid' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                                            invoice.status === 'partially_paid' ? 'bg-orange-50 text-orange-700 ring-orange-600/20' :
                                                invoice.status === 'sent' ? 'bg-blue-50 text-blue-700 ring-blue-600/20' :
                                                    invoice.status === 'overdue' ? 'bg-red-50 text-red-700 ring-red-600/20' :
                                                        invoice.status === 'cancelled' ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20' :
                                                            'bg-gray-50 text-gray-700 ring-gray-600/20'
                                        }`}>
                                        {invoice.status === 'partially_paid' ? t('Partially Paid') :
                                            (invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1))}
                                    </span>
                                    <p className="text-sm font-medium text-gray-700 mt-2 font-mono">{invoice.invoice_number}</p>
                                </div>
                            </div>
                        </div>

                        {/* Payment Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="border-l-4 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border" style={{ borderLeftColor: template.primary }}>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Total Amount')}</p>
                                            <h3 className="mt-2 text-2xl font-bold leading-none" style={{ color: template.primary }}>{formatCurrency(invoice.total_amount)}</h3>
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
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Paid Amount')}</p>
                                            <h3 className="mt-2 text-2xl font-bold leading-none" style={{ color: template.secondary }}>{formatCurrency(paidAmount)}</h3>
                                        </div>
                                        <div className="rounded-full p-4" style={{ backgroundColor: `${template.secondary}15` }}>
                                            <DollarSign className="h-5 w-5" style={{ color: template.secondary }} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 border-l-red-500 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border">
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Due Amount')}</p>
                                            <h3 className="mt-2 text-2xl font-bold text-red-600 leading-none">{formatCurrency(dueAmount)}</h3>
                                        </div>
                                        <div className="rounded-full bg-red-100 p-4">
                                            <DollarSign className="h-5 w-5 text-red-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Invoice Details Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="border-l-4 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border" style={{ borderLeftColor: template.primary }}>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Products')}</p>
                                            <h3 className="mt-2 text-2xl font-bold leading-none" style={{ color: template.primary }}>{invoice.products?.length || 0}</h3>
                                        </div>
                                        <div className="rounded-full p-4" style={{ backgroundColor: `${template.primary}15` }}>
                                            <Package className="h-5 w-5" style={{ color: template.primary }} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border" style={{ borderLeftColor: template.secondary }}>
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Invoice Date')}</p>
                                            <h3 className="mt-2 text-lg font-bold leading-tight" style={{ color: template.secondary }}>{formatDate(invoice.invoice_date)}</h3>
                                        </div>
                                        <div className="rounded-full p-4" style={{ backgroundColor: `${template.secondary}15` }}>
                                            <Calendar className="h-5 w-5" style={{ color: template.secondary }} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="border-l-4 border-l-amber-500 hover:shadow-lg transition-shadow bg-white rounded-lg shadow-sm border">
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Due Date')}</p>
                                            <h3 className="mt-2 text-lg font-bold text-amber-600 leading-tight">{formatDate(invoice.due_date)}</h3>
                                        </div>
                                        <div className="rounded-full bg-amber-100 p-4">
                                            <FileText className="h-5 w-5 text-amber-600" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Billing Details */}
                        <div className="bg-white rounded-lg shadow-sm border">
                            <div className="border-b px-8 py-6" style={{ backgroundColor: `${template.primary}25` }}>
                                <h3 className="flex items-center text-xl font-bold text-gray-800">
                                    <User className="h-5 w-5 mr-3" />
                                    {t('Billing Details')}
                                </h3>
                            </div>
                            <div className="p-8">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                            <MapPin
                                                className="w-5 h-5 mr-2"
                                                style={{ color: template.primary }}
                                            />
                                            {t('Bill To')}
                                        </h4>
                                        {invoice.account && (
                                            <div className="space-y-2">
                                                <p className="font-semibold text-gray-900 dark:text-gray-100 text-lg">{invoice.account.name}</p>
                                                {invoice.account.email && <p className="text-gray-600 dark:text-gray-300">{invoice.account.email}</p>}
                                                {invoice.account.phone && <p className="text-gray-600 dark:text-gray-300">{invoice.account.phone}</p>}
                                            </div>
                                        )}
                                        {invoice.contact && !invoice.account && (
                                            <div className="space-y-2">
                                                <p className="font-semibold text-gray-900 dark:text-gray-100 text-lg">{invoice.contact.name}</p>
                                                {invoice.contact.email && <p className="text-gray-600 dark:text-gray-300">{invoice.contact.email}</p>}
                                                {invoice.contact.phone && <p className="text-gray-600 dark:text-gray-300">{invoice.contact.phone}</p>}
                                            </div>
                                        )}
                                        {invoice.billing_address && (
                                            <div className="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                                <p className="text-gray-700 dark:text-gray-300">{invoice.billing_address}</p>
                                                <p className="text-gray-700 dark:text-gray-300">
                                                    {invoice.billing_city && `${invoice.billing_city}, `}
                                                    {invoice.billing_state && `${invoice.billing_state} `}
                                                    {invoice.billing_postal_code}
                                                </p>
                                                {invoice.billing_country && <p className="text-gray-700 dark:text-gray-300">{invoice.billing_country}</p>}
                                            </div>
                                        )}
                                    </div>
                                    <div>
                                        <h4 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                            <Calendar
                                                className="w-5 h-5 mr-2"
                                                style={{ color: template.primary }}
                                            />
                                            {t('Invoice Details')}
                                        </h4>
                                        <div className="space-y-3">
                                            <div className="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                                <span className="text-gray-600 dark:text-gray-300 font-medium">{t('Invoice Date')}:</span>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">{formatDate(invoice.invoice_date)}</span>
                                            </div>
                                            <div className="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600">
                                                <span className="text-gray-600 dark:text-gray-300 font-medium">{t('Due Date')}:</span>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">{formatDate(invoice.due_date)}</span>
                                            </div>
                                            {invoice.payment_method && (
                                                <div className="flex justify-between items-center py-2">
                                                    <span className="text-gray-600 dark:text-gray-300 font-medium flex items-center">
                                                        <CreditCard className="w-4 h-4 mr-1" />
                                                        {t('Payment Method')}:
                                                    </span>
                                                    <span className="font-semibold text-gray-900 dark:text-gray-100 capitalize">{invoice.payment_method}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Products */}
                        <div className="bg-white rounded-lg shadow-sm border">
                            <div className="bg-gray-50 border-b px-8 py-6">
                                <h3 className="flex items-center text-xl font-bold text-gray-800">
                                    <Package className="h-5 w-5 mr-3" />
                                    {t('Products')}
                                </h3>
                            </div>
                            <div className="p-0">
                                {invoice.products && invoice.products.length > 0 ? (
                                    <div className="overflow-hidden">
                                        <table className="min-w-full">
                                            <thead>
                                                <tr style={{ backgroundColor: template.primary }}>
                                                    <th className="text-base font-bold text-white py-4 px-6 w-1/3 text-left">{t('Product')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Quantity')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Unit Price')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4">{t('Tax')}</th>
                                                    <th className="text-right text-base font-bold text-white py-4 px-4 w-1/6">{t('Total')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {invoice.products.map((product: any, index: number) => {
                                                    const lineTotal = Number(product.pivot.total_price) || 0;
                                                    const discountAmount = Number(product.pivot.discount_amount) || 0;
                                                    const finalTotal = lineTotal - discountAmount;

                                                    return (
                                                        <tr key={index} className="border-b hover:bg-gray-50">
                                                            <td className="font-semibold text-base text-gray-900 py-4 px-6">{product.name}</td>
                                                            <td className="text-right text-base font-medium py-4 px-4">{product.pivot.quantity}</td>
                                                            <td className="text-right text-base font-semibold py-4 px-4">{formatCurrency(product.pivot.unit_price)}</td>
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
                                                                <span className="text-green-600 font-semibold">{formatCurrency(finalTotal)}</span>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                                <tr style={{ backgroundColor: `${template.primary}10` }}>
                                                    <td colSpan={4} className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {t('Subtotal')}:
                                                    </td>
                                                    <td className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {formatCurrency(invoice.subtotal)}
                                                    </td>
                                                </tr>
                                                <tr style={{ backgroundColor: `${template.primary}10` }}>
                                                    <td colSpan={4} className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {t('Total Tax')}:
                                                    </td>
                                                    <td className="text-right font-semibold text-base py-3 px-4" style={{ color: template.primary }}>
                                                        {formatCurrency(invoice.tax_amount)}
                                                    </td>
                                                </tr>
                                                <tr className="border-t-2" style={{ backgroundColor: `${template.primary}15`, borderTopColor: template.primary }}>
                                                    <td colSpan={4} className="text-right font-bold text-lg py-4 px-4" style={{ color: template.primary }}>
                                                        {t('Grand Total')}:
                                                    </td>
                                                    <td className="text-right py-4 px-4">
                                                        <span className="font-bold text-xl" style={{ color: template.primary }}>{formatCurrency(invoice.total_amount)}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="text-center py-16 text-gray-500">
                                        <Package className="h-16 w-16 mx-auto mb-6 text-gray-300" />
                                        <p className="text-lg font-medium">{t('No products added to this invoice')}</p>
                                    </div>
                                )}
                            </div>
                        </div>



                        {/* Notes and Terms */}
                        {(invoice.notes || invoice.terms) && (
                            <div className="bg-white rounded-lg shadow-sm border">
                                <div className="border-b px-8 py-6" style={{ backgroundColor: `${template.primary}25` }}>
                                    <h3 className="text-xl font-bold text-gray-800">{t('Additional Information')}</h3>
                                </div>
                                <div className="p-8">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        {invoice.notes && (
                                            <div>
                                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Notes')}</label>
                                                <p className="text-base text-gray-700 mt-2 leading-relaxed">{invoice.notes}</p>
                                            </div>
                                        )}
                                        {invoice.terms && (
                                            <div>
                                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Terms')}</label>
                                                <p className="text-base text-gray-700 mt-2 leading-relaxed">{invoice.terms}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Payments */}
                        {invoice.payments && invoice.payments.length > 0 && (
                            <div className="bg-white rounded-lg shadow-sm border">
                                <div className="bg-gray-50 border-b px-8 py-6">
                                    <h3 className="flex items-center text-xl font-bold text-gray-800">
                                        <DollarSign className="h-5 w-5 mr-3" />
                                        {t('Payment History')}
                                    </h3>
                                </div>
                                <div className="p-0">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr style={{ backgroundColor: template.primary }}>
                                                <th className="text-base font-bold text-white py-4 px-6 text-left w-1/4">{t('Date')}</th>
                                                <th className="text-base font-bold text-white py-4 px-4 text-left w-1/4">{t('Method')}</th>
                                                <th className="text-right text-base font-bold text-white py-4 px-4 w-1/4">{t('Amount')}</th>
                                                <th className="text-base font-bold text-white py-4 px-4 text-left w-1/4">{t('Status')}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {invoice.payments.map((payment: any, index: number) => (
                                                <tr key={index} className="border-b hover:bg-gray-50">
                                                    <td className="py-4 px-6">{formatDate(payment.processed_at || payment.created_at)}</td>
                                                    <td className="py-4 px-4 capitalize">{payment.payment_method}</td>
                                                    <td className="text-right py-4 px-4 font-semibold">{formatCurrency(payment.amount)}</td>
                                                    <td className="py-4 px-4">
                                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${payment.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                                payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                                    'bg-red-100 text-red-800'
                                                            }`}>
                                                            {payment.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}


                    </div>
                </div>
            </div>

            {/* Payment Modal */}
            <div className='print:hidden'>
                {showPaymentModal && (
                    <InvoicePaymentModal
                        isOpen={showPaymentModal}
                        onClose={() => setShowPaymentModal(false)}
                        invoice={invoice}
                        amount={paymentAmount}
                        onAmountChange={setPaymentAmount}
                    />
                )}
            </div>
        </>
    );
}
