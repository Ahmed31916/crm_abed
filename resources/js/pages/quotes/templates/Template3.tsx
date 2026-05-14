import QRCodeComponent from '@/components/QRCodeComponent';
import React from 'react';
import { useBrand } from '@/contexts/BrandContext';
import { useTranslation } from 'react-i18next';

interface Template3Props {
    quote: any;
    items: any[];
    taxesData: any;
    settings: any;
    color: string;
    qr_invoice: string;
    qrCodeSvg?: string;
    styles?: any;
}

export default function Template3({ quote, items, taxesData, settings, color, qr_invoice, qrCodeSvg, styles: externalStyles }: Template3Props) {
    const { t } = useTranslation();
    const { logoDark } = useBrand();
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

    const styles = {
        root: {
            fontFamily: 'Lato, sans-serif',
            margin: 0,
            padding: 0,
            boxSizing: 'border-box' as const,
        },
        invoicePreviewMain: {
            width: '100%',
            margin: '0 auto',
            background: '#ffff',
            boxShadow: '0 0 10px #ddd',
            ...externalStyles?.invoicePreviewMain,
        },
        invoiceHeader: {
            padding: '15px 30px',
        },
        headerTable: {
            width: '100%',
            borderCollapse: 'collapse' as const,
        },
        headerCell: {
            padding: '15px 30px',
            verticalAlign: 'top' as const,
        },
        logo: {
            maxWidth: '250px',
        },
        quoteTitle: {
            textTransform: 'uppercase' as const,
            fontSize: '20px',
            fontWeight: 'bold',
            marginBottom: '15px',
        },
        invoiceBody: {
            padding: '30px 25px 30px 25px',
        },
        table: {
            width: '100%',
            borderCollapse: 'collapse' as const,
            marginTop: '30px',
        },
        tableHeader: {
            background: `#${color}`,
            color: fontColor,
        },
        th: {
            padding: '0.75rem',
            textAlign: 'left' as const,
            fontSize: '13px',
            fontWeight: '600',
        },
        td: {
            padding: '0.75rem',
            textAlign: 'left' as const,
            borderTop: `1px solid ${borderColor}`,
        },
        totalTable: {
            width: '100%',
        },
        totalTd: {
            padding: '0.75rem',
            textAlign: 'right' as const,
        },
        qrCode: {
            maxWidth: '114px',
            maxHeight: '114px',
            marginLeft: '0',
            marginRight: '0',
            marginTop: '15px',
            background: '#ffffff',
        },
        footer: {
            padding: '15px 20px',
        },
        noSpace: {
            padding: 0,
        },
    };

    return (
        <div style={styles.root}>
            <div className="invoice-preview-main" id="boxes" style={styles.invoicePreviewMain}>
                <div className="invoice-header" style={styles.invoiceHeader}>
                    <table className="vertical-align-top" style={styles.headerTable}>
                        <tbody>
                            <tr>
                                <td style={styles.headerCell}>
                                    <h3 style={{ textTransform: 'uppercase', fontSize: '20px', fontWeight: 'bold' }}>{t('QUOTE')}</h3>
                                    <div className="view-qrcode" style={{ ...styles.qrCode, marginLeft: '0', marginRight: '0' }}>
                                        {qr_invoice === 'on' && (
                                            <QRCodeComponent
                                                text={window.location.href}
                                                size={114}
                                            />
                                        )}
                                    </div>
                                </td>
                                <td className="text-right" style={{ ...styles.headerCell, textAlign: 'right' }}>
                                    <img
                                        src={settings.quoteLogo || logoDark}
                                        style={{ maxWidth: '150px',maxHeight:'150px' }}
                                        alt="Logo"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table className="vertical-align-top" style={styles.headerTable}>
                        <tbody>
                            <tr>
                                <td style={styles.headerCell}>
                                    <strong>{t('From')}:</strong>
                                    <p style={{ margin: '10px 0', lineHeight: '1.5' }}>
                                        {formatValue(quote.creator?.name) && <>{formatValue(quote.creator.name)}<br /></>}
                                        {formatValue(quote.creator?.email) && <>{formatValue(quote.creator.email)}</>}
                                    </p>
                                </td>
                                <td style={styles.headerCell}>
                                    <table className="no-space" style={{ width: '100%' }}>
                                        <tbody>
                                            <tr>
                                                <td className="text-right" style={{ ...styles.noSpace }}></td>
                                                <td className="text-right" style={{ ...styles.noSpace, textAlign: 'right' }}>{t('Number')}: {formatValue(quote.quote_number)}</td>
                                            </tr>
                                            <tr>
                                                <td style={styles.noSpace}></td>
                                                <td className="text-right" style={{ ...styles.noSpace, textAlign: 'right' }}>{t('Valid Until')}: {formatValue(quote.valid_until)}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style={styles.invoiceBody}>
                    <table style={{ width: '100%' }}>
                        <tbody>
                            <tr>
                                <td style={{ verticalAlign: 'top' }}>
                                    <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Bill To')}:</strong>
                                    <p style={{ margin: 0, lineHeight: '1.5' }}>
                                        {quote.billing_contact?.name}<br />
                                        {quote.billing_contact?.email}<br />
                                        {quote.billing_contact?.phone}<br />
                                        {quote.billing_address}<br />
                                        {quote.billing_postal_code}<br />
                                        {quote.billing_city} {quote.billing_state} {quote.billing_country}
                                    </p>
                                </td>
                                <td style={{ verticalAlign: 'top', textAlign: 'right' }}>
                                    <strong style={{ marginBottom: '10px', display: 'block' }}>{t('Ship To')}:</strong>
                                    <p style={{ margin: 0, lineHeight: '1.5' }}>
                                        {quote.shipping_contact?.name}<br />
                                        {quote.shipping_contact?.email}<br />
                                        {quote.shipping_contact?.phone}<br />
                                        {quote.shipping_address}<br />
                                        {quote.shipping_postal_code}<br />
                                        {quote.shipping_city} {quote.shipping_state} {quote.shipping_country}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table style={styles.table}>
                        <tbody>
                            <tr style={styles.tableHeader}>
                                <th style={styles.th}>{t('Item')}</th>
                                <th style={styles.th}>{t('Quantity')}</th>
                                <th style={styles.th}>{t('Rate')}</th>
                                <th style={styles.th}>{t('Tax')} (%)</th>
                                <th style={styles.th}>{t('Discount')}</th>
                                <th style={styles.th}>{t('Price')} <small style={{ display: 'block', fontSize: '12px' }}>{t('before tax & discount')}</small></th>
                            </tr>
                            {items.map((item, index) => (
                                <tr key={index}>
                                    <td style={styles.td}>{formatValue(item.name)}</td>
                                    <td style={styles.td}>{formatValue(item.quantity)}</td>
                                    <td style={styles.td}>{formatCurrency(item.price)}</td>
                                    <td style={styles.td}>
                                        {item.itemTax?.map((tax: any, taxIndex: number) => (
                                            <span key={taxIndex}>
                                                {tax.name} ({tax.rate})<br />{tax.price}
                                            </span>
                                        ))}
                                    </td>
                                    <td style={styles.td}>{item.discount ? formatCurrency(item.discount) : '-'}</td>
                                    <td style={styles.td}>{formatCurrency(typeof item.price === 'string' && item.price.startsWith('<') ? item.price : (Number(item.price) * Number(item.quantity)))}</td>
                                </tr>
                            ))}
                            <tr>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{t('Total')}</td>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{typeof items[0]?.quantity === 'string' && items[0]?.quantity.startsWith('<') ? formatValue(items[0]?.quantity) : items.reduce((sum, item) => sum + Number(item.quantity), 0)}</td>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{formatCurrency(typeof items[0]?.price === 'string' && items[0]?.price.startsWith('<') ? items[0]?.price : items.reduce((sum, item) => sum + Number(item.price), 0))}</td>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{formatCurrency(quote.total_tax || 0)}</td>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{formatCurrency(quote.total_discount || 0)}</td>
                                <td style={{ ...styles.td, borderBottom: `1px solid ${borderColor}` }}>{formatCurrency(quote.sub_total || 0)}</td>
                            </tr>
                            <tr>
                                <td colSpan={6} style={{ border: 'none', padding: '0' }}>
                                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                        <tbody>
                                            {quote.total_discount > 0 && (
                                                <tr>
                                                    <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right' }}>{t('Discount')}:</td>
                                                    <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}>{formatCurrency(quote.total_discount)}</td>
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
                                                <td style={{ padding: '0.75rem 0 0 0', textAlign: 'right', width: '146px' }}><strong>{formatCurrency(quote.total_amount || 0)}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
