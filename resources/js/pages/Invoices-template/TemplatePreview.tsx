import React from 'react';
import { usePage } from '@inertiajs/react';

export default function TemplatePreview() {
    const { invoice, templateId, templateColor, settings } = usePage().props as any;

    const currency = settings?.defaultCurrency || '$';
    // window.appSettings?.formatCurrency(parseFloat(value))
    const formatCurrency = (amount: number) => `${amount.toFixed(2)}`;
    const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();

    return (
        <html>
            <head>
                <meta charSet="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <style dangerouslySetInnerHTML={{
                    __html: `
                        body {
                            font-family: 'Lato', sans-serif;
                            margin: 0;
                            padding: 20px;
                            background: #f5f5f5;
                        }
                        .invoice-preview-main {
                            max-width: 700px;
                            width: 100%;
                            margin: 0 auto;
                            background: #fff;
                            box-shadow: 0 0 10px #ddd;
                        }
                        .invoice-header {
                            background: ${templateColor};
                            color: #fff;
                            padding: 30px;
                        }
                        .invoice-header table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        .invoice-header td {
                            padding: 10px 0;
                        }
                        .text-right {
                            text-align: right;
                        }
                        .invoice-body {
                            padding: 30px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        table th, table td {
                            padding: 12px;
                            text-align: left;
                        }
                        .invoice-summary {
                            margin-top: 30px;
                        }
                        .invoice-summary thead {
                            background: ${templateColor};
                            color: #fff;
                        }
                        .invoice-summary tbody tr {
                            border-bottom: 1px solid ${templateColor};
                        }
                        .total-table {
                            margin-top: 20px;
                        }
                        .total-table td {
                            padding: 8px 0;
                        }
                    `
                }} />
            </head>
            <body>
                <div className="invoice-preview-main">
                    <div className="invoice-header">
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <h1 style={{ margin: 0, fontSize: '40px', fontWeight: 'bold' }}>INVOICE</h1>
                                    </td>
                                    <td className="text-right">
                                        <div>Number: {invoice.invoice_number}</div>
                                        <div>Issue Date: {formatDate(invoice.invoice_date)}</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table style={{ marginTop: '20px' }}>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>From:</strong>
                                        <p style={{ margin: '10px 0 0 0' }}>
                                            {settings?.companyName || 'Your Company'}<br />
                                            {settings?.companyAddress || '123 Business St'}<br />
                                            {settings?.companyCity || 'City'}, {settings?.companyState || 'State'} {settings?.companyZipcode || '12345'}<br />
                                            {settings?.companyCountry || 'Country'}
                                        </p>
                                    </td>
                                    <td className="text-right">
                                        <strong>Bill To:</strong>
                                        <p style={{ margin: '10px 0 0 0' }}>
                                            {invoice.account.name}<br />
                                            {invoice.account.email}<br />
                                            {invoice.account.phone}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div className="invoice-body">
                        <table className="invoice-summary">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Rate</th>
                                    <th>Tax</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoice.products.map((product: any, index: number) => (
                                    <tr key={index}>
                                        <td>{product.name}</td>
                                        <td>{product.pivot.quantity}</td>
                                        <td>{formatCurrency(product.pivot.unit_price)}</td>
                                        <td>{product.tax.name} ({product.tax.rate}%)</td>
                                        <td>{formatCurrency(product.pivot.total_price)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <table className="total-table">
                            <tbody>
                                <tr>
                                    <td className="text-right"><strong>Subtotal:</strong></td>
                                    <td className="text-right">{formatCurrency(invoice.subtotal)}</td>
                                </tr>
                                <tr>
                                    <td className="text-right"><strong>Tax:</strong></td>
                                    <td className="text-right">{formatCurrency(invoice.tax_amount)}</td>
                                </tr>
                                <tr>
                                    <td className="text-right"><strong>Total:</strong></td>
                                    <td className="text-right"><strong>{formatCurrency(invoice.total_amount)}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                        {invoice.notes && (
                            <div style={{ marginTop: '30px', padding: '15px', background: '#f9f9f9', borderRadius: '5px' }}>
                                <strong>Notes:</strong>
                                <p style={{ margin: '10px 0 0 0' }}>{invoice.notes}</p>
                            </div>
                        )}
                    </div>
                </div>
            </body>
        </html>
    );
}
