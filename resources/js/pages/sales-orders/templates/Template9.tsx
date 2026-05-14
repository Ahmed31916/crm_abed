import QRCodeComponent from '@/components/QRCodeComponent';
import React from 'react';
import { useBrand } from '@/contexts/BrandContext';
import { useTranslation } from 'react-i18next';

interface Template9Props {
    salesOrder: any;
    items: any[];
    taxesData: any;
    settings: any;
    color: string;
    qr_invoice: string;
    qrCodeSvg?: string;
    styles?: any;
}

export default function Template9({ salesOrder, items, taxesData, settings, color, qr_invoice, qrCodeSvg, styles: externalStyles }: Template9Props) {
    const { logoDark } = useBrand();
    const { t } = useTranslation();
    const fontColor = color === 'ffffff' || color === 'fbdd03' || color === 'c1d82f' || color === '46de98' || color === '40c7d0' || color === 'fac168' ? '#000000' : '#ffffff';
    const borderColor = color === 'ffffff' ? '#000000' : `#${color}`;

    const formatCurrency = (amount: number | string) => {
        if (typeof amount === 'string' && amount.startsWith('<')) return amount;
        return (window as any).appSettings?.formatCurrency(Number(amount)) || `$${Number(amount)}`;
    };

    const formatValue = (value: any, fallback: string = '') => {
        if (typeof value === 'string' && value.startsWith('<')) return value;
        return value || fallback;
    };

    return (
        <div style={{
            width: '100%',
            margin: '0 auto',
            background: '#ffffff',
            boxShadow: '0 0 10px #ddd',
            fontFamily: 'Lato, sans-serif',
            borderRight: `40px solid #${color}`,
            ...externalStyles?.invoicePreviewMain,
        }}>
            <div style={{ padding: '15px 30px' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <tbody>
                        <tr style={{ verticalAlign: 'top' }}>
                            <td style={{ padding: '0', verticalAlign: 'top' }}>
                                <h3 style={{
                                    textTransform: 'uppercase',
                                    fontSize: '40px',
                                    fontWeight: 'bold',
                                    color: `#${borderColor}`,
                                    margin: '0 0 10px 0'
                                }}>{t('SALES ORDER')}</h3>
                                <table style={{ width: '70%', borderCollapse: 'collapse' }}>
                                    <tbody>
                                        <tr>
                                            <td style={{ padding: '0', fontSize: '13px', fontWeight: '600' }}>
                                                {t('Number')}: {formatValue(salesOrder.sales_order_number)}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style={{ padding: '0', fontSize: '13px', fontWeight: '600' }}>
                                                {t('Order Date')}: {formatValue(salesOrder.order_date)}<br />{t('Delivery Date')}: {formatValue(salesOrder.delivery_date)}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td style={{ padding: '0', verticalAlign: 'top', textAlign: 'right' }}>
                                {logoDark && (
                                    <img
                                        src={settings.salesOrderLogo || logoDark}
                                        style={{ maxWidth: '150px',maxHeight:'150px' }}
                                        alt="Logo"
                                    />
                                )}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style={{ padding: '15px 30px 0' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <tbody>
                        <tr style={{ verticalAlign: 'top' }}>
                            <td style={{ padding: '0', verticalAlign: 'top' }}>
                                <strong>{t('From')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(salesOrder.creator?.name) && <>{formatValue(salesOrder.creator.name)}<br /></>}
                                    {formatValue(salesOrder.creator?.email) && <>{formatValue(salesOrder.creator.email)}</>}
                                </p>
                            </td>
                            <td style={{ padding: '0', verticalAlign: 'top' }}>
                                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                    <tbody>
                                        <tr>
                                            <td colSpan={2} style={{ padding: '0' }}>
                                                {qr_invoice === 'on' && (
                                                    <div style={{
                                                        maxWidth: '114px',
                                                        maxHeight: '114px',
                                                        marginLeft: 'auto',
                                                        marginTop: '0',
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
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style={{ padding: '15px 30px 0' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <tbody>
                        <tr>
                            <td style={{ verticalAlign: 'top' }}>
                                <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Bill To')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(salesOrder.billing_contact?.name)}<br />
                                    {formatValue(salesOrder.billing_contact?.email)}<br />
                                    {formatValue(salesOrder.billing_contact?.phone)}<br />
                                    {formatValue(salesOrder.billing_address)}<br />
                                    {formatValue(salesOrder.billing_postal_code)}<br />
                                    {formatValue(salesOrder.billing_city)} {formatValue(salesOrder.billing_state)} {formatValue(salesOrder.billing_country)}
                                </p>
                            </td>
                            <td style={{ verticalAlign: 'top', textAlign: 'right' }}>
                                <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Ship To')}:</strong>
                                <p style={{ margin: '0', lineHeight: '1.5' }}>
                                    {formatValue(salesOrder.shipping_contact?.name)}<br />
                                    {formatValue(salesOrder.shipping_contact?.email)}<br />
                                    {formatValue(salesOrder.shipping_contact?.phone)}<br />
                                    {formatValue(salesOrder.shipping_address)}<br />
                                    {formatValue(salesOrder.shipping_postal_code)}<br />
                                    {formatValue(salesOrder.shipping_city)} {formatValue(salesOrder.shipping_state)} {formatValue(salesOrder.shipping_country)}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style={{ padding: '30px 25px 30px 25px', paddingRight: '0' }}>
                <table style={{
                    width: '100%',
                    borderCollapse: 'collapse'
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
                                <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(typeof item.price === 'string' && item.price.startsWith('<') ? item.price : (Number(item.price || 0) * Number(item.quantity || 0)))}</td>
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
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{items.reduce((sum, item) => sum + Number(item.quantity || 0), 0)}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(items.reduce((sum, item) => sum + Number(item.price || 0), 0))}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(salesOrder.total_tax || 0)}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(salesOrder.total_discount || 0)}</td>
                            <td style={{ padding: '0.75rem', textAlign: 'left', borderTop: `1px solid ${borderColor}` }}>{formatCurrency(salesOrder.sub_total || 0)}</td>
                        </tr>
                        <tr>
                            <td colSpan={6} style={{ border: 'none', padding: '0' }}>
                                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                    <tbody>
                                        {salesOrder.total_discount > 0 && (
                                            <tr>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{t('Discount')}:</td>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(salesOrder.total_discount)}</td>
                                            </tr>
                                        )}
                                        {Object.entries(taxesData || {}).map(([taxName, taxPrice]) => (
                                            <tr key={taxName}>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{taxName}:</td>
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(taxPrice as number)}</td>
                                            </tr>
                                        ))}
                                        <tr>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}><strong>{t('Total')}:</strong></td>
                                            <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}><strong>{formatCurrency(salesOrder.total_amount || 0)}</strong></td>
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
