import QRCodeComponent from '@/components/QRCodeComponent';
import React from 'react';
import { useBrand } from '@/contexts/BrandContext';
import { useTranslation } from 'react-i18next';

interface Template4Props {
    invoice: any;
    items: any[];
    taxesData: any;
    settings: any;
    color: string;
    qr_invoice: string;
    qrCodeSvg?: string;
    styles?: any;
}

export default function Template4({ invoice, items, taxesData, settings, color, qr_invoice, qrCodeSvg, styles: externalStyles }: Template4Props) {
    const { t } = useTranslation();
    const { logoDark } = useBrand();
    const fontColor = color === 'ffffff' || color === 'fbdd03' || color === 'c1d82f' || color === '46de98' || color === '40c7d0' || color === 'fac168' ? '#000000' : '#ffffff';
    const borderColor = color === 'ffffff' ? '#000000' : `#${color}`;
    const paidAmount = invoice.payments?.reduce((total: number, payment: any) => {
        const amount = Number(payment.amount) || 0;
        return payment.status === 'completed' ? total + amount : total;
    }, 0) || 0;
    const dueAmount = Math.max(0, (Number(invoice.total_amount) || 0) - paidAmount);

    const formatCurrency = (amount: number | string) => {
        if (typeof amount === 'string' && amount.startsWith('<')) return amount;
        return (window as any).appSettings?.formatCurrency(Number(amount)) || `$${Number(amount)}`;
    };

    const formatValue = (value: any, fallback: string = '') => {
        if (typeof value === 'string' && value.startsWith('<')) return value;
        return value || fallback;
    };

    return (
        <div className="invoice-preview-main" style={{
            width: '100%',
            margin: '0 auto',
            background: '#ffffff',
            boxShadow: '0 0 10px #ddd',
            fontFamily: 'Lato, sans-serif',
            ...externalStyles?.invoicePreviewMain,
        }}>
            <div className="invoice-header">
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <tbody>
                        <tr>
                            <td style={{ padding: '15px 30px', verticalAlign: 'top' }}>
                                <h3 style={{
                                    textTransform: 'uppercase',
                                    fontSize: '30px',
                                    fontWeight: 'bold',
                                    marginBottom: '10px',
                                    margin: '0 0 10px 0'
                                }}>
                                    {t('INVOICE')}
                                </h3>
                                <strong>{t('From')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(invoice.creator?.name) && <>{formatValue(invoice.creator.name)}<br /></>}
                                    {formatValue(invoice.creator?.email) && <>{formatValue(invoice.creator.email)}</>}
                                </p>
                            </td>
                            <td style={{ padding: '15px 30px', verticalAlign: 'top', textAlign: 'right' }}>
                                {logoDark && (
                                    <img
                                        src={settings.invoiceLogo || logoDark}
                                        style={{ maxWidth: '150px',maxHeight:'150px' }}
                                        alt="Logo"
                                    />
                                )}
                                <table style={{ width: '100%', borderCollapse: 'collapse', marginLeft: 'auto' }}>
                                    <tbody>
                                        <tr>
                                            <td colSpan={2} style={{ textAlign: 'right' }}>
                                                {qr_invoice === 'on' && (
                                                    <div style={{
                                                        maxWidth: '114px',
                                                        maxHeight: '114px',
                                                        marginLeft: 'auto',
                                                        marginTop: '0',
                                                        marginBottom: '10px',
                                                        background: '#ffffff'
                                                    }}>
                                                        <QRCodeComponent
                                                            text={window.location.href}
                                                            size={114}
                                                        />
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style={{ padding: '0', textAlign: 'right' }}></td>
                                            <td style={{ padding: '0', textAlign: 'right' }}>{t('Number')}: {formatValue(invoice.invoice_number)}</td>
                                        </tr>
                                        <tr>
                                            <td style={{ padding: '0' }}></td>
                                            <td style={{ padding: '0', textAlign: 'right' }}>{t('Invoice Date')}: {formatValue(invoice.invoice_date)}<br />{t('Due Date')}: {formatValue(invoice.due_date)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div className="invoice-body" style={{ padding: '30px 25px 30px 25px' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <tbody>
                        <tr>
                            <td style={{ verticalAlign: 'top' }}>
                                <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Bill To')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(invoice.account?.name || invoice.contact?.name)}<br />
                                    {formatValue(invoice.account?.email || invoice.contact?.email)}<br />
                                    {formatValue(invoice.account?.phone || invoice.contact?.phone)}<br />
                                    {formatValue(invoice.billing_address)}<br />
                                    {formatValue(invoice.billing_city)} {formatValue(invoice.billing_state)} {formatValue(invoice.billing_country)}
                                </p>
                            </td>
                            <td style={{ verticalAlign: 'top', textAlign: 'right' }}>
                                <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Ship To')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(invoice.creator?.name)}<br />
                                    {formatValue(invoice.creator?.email)}<br />
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table style={{
                    width: '100%',
                    borderCollapse: 'collapse',
                    marginTop: '30px'
                }}>
                    <tbody>
                        <tr style={{ background: `#${color}`, color: fontColor }}>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Item')}</th>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Quantity')}</th>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Rate')}</th>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Tax')} (%)</th>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Discount')}</th>
                            <th style={{ padding: '0.75rem', textAlign: 'left' }}>{t('Price')} <small style={{ display: 'block', fontSize: '12px' }}>{t('before tax & discount')}</small></th>
                        </tr>
                        {items.length > 0 ? items.map((item, index) => (
                            <tr key={index} style={{ borderTop: `1px solid ${borderColor}` }}>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatValue(item.name)}</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatValue(item.quantity)}</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(item.price)}</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>
                                    {item.itemTax?.map((tax: any, taxIndex: number) => (
                                        <span key={taxIndex}>
                                            <span>{tax.name}</span> <span>({tax.rate})</span><br /><span>{tax.price}</span>
                                        </span>
                                    ))}
                                </td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{item.discount ? formatCurrency(item.discount) : '-'}</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(typeof item.price === 'string' && item.price.startsWith('<') ? item.price : (Number(item.price) * Number(item.quantity)))}</td>
                            </tr>
                        )) : (
                            <tr style={{ borderTop: `1px solid ${borderColor}` }}>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>-</td>
                            </tr>
                        )}
                        <tr style={{ borderTop: `1px solid ${borderColor}`, borderBottom: `1px solid ${borderColor}` }}>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{t('Total')}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{invoice.totalQuantity || 0}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(invoice.totalRate || 0)}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(invoice.totalTaxPrice || 0)}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{invoice.discount_apply === 1 ? formatCurrency(invoice.totalDiscount || 0) : '-'}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(invoice.sub_total || 0)}</td>
                        </tr>
                        <tr>
                            <td colSpan={6} style={{ border: 'none', padding: '0' }}>
                                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                    <tbody>
                                        {invoice.total_discount > 0 && (
                                            <tr>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{t('Discount')}:</td>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(invoice.total_discount)}</td>
                                            </tr>
                                        )}
                                        {Object.entries(taxesData || {}).map(([taxName, taxPrice]) => (
                                            <tr key={taxName}>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{taxName}:</td>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(taxPrice as number)}</td>
                                            </tr>
                                        ))}
                                        <tr>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{t('Paid')}:</td>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(paidAmount)}</td>
                                        </tr>
                                        <tr>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{t('Due')}:</td>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(dueAmount)}</td>
                                        </tr>
                                        <tr>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}><strong>{t('Total')}:</strong></td>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}><strong>{formatCurrency(invoice.total_amount || 0)}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}
