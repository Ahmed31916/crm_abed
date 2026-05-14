import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Package, CheckCircle, DollarSign, Award } from 'lucide-react';
import { ReportFilters } from '@/components/reports/report-filters';
import { SummaryCards } from '@/components/reports/summary-cards';
import { ChartCard } from '@/components/reports/chart-card';
import { Card } from '@/components/ui/card';

export default function ProductReports() {
  const { t } = useTranslation();
  const { filters, summary, productSales } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Reports'), href: '#' },
    { title: t('Product Reports') }
  ];

  const summaryCards = [
    {
      title: t('Total Products'),
      value: summary.total_products.toLocaleString(),
      icon: <Package className="h-6 w-6 text-blue-600" />,
      iconColor: 'bg-blue-100'
    },
    {
      title: t('Active Products'),
      value: summary.active_products.toLocaleString(),
      icon: <CheckCircle className="h-6 w-6 text-green-600" />,
      iconColor: 'bg-green-100'
    },
    {
      title: t('Total Revenue'),
      value: window.appSettings?.formatCurrency(summary.total_revenue) || `$${summary.total_revenue.toLocaleString()}`,
      icon: <DollarSign className="h-6 w-6 text-purple-600" />,
      iconColor: 'bg-purple-100'
    },
    {
      title: t('Best Seller'),
      value: summary.best_seller || t('-'),
      icon: <Award className="h-6 w-6 text-orange-600" />,
      iconColor: 'bg-orange-100'
    }
  ];

  const topProducts = productSales.slice(0, 10);

  return (
    <PageTemplate title={t("Product Reports")} url={route('reports.product-reports')} breadcrumbs={breadcrumbs} noPadding>
      <ReportFilters filters={filters} />
      
      <SummaryCards cards={summaryCards} />

      <div className="grid grid-cols-1 gap-6 mb-6">
        <ChartCard title={t('Top Products by Revenue')}>
          <ResponsiveContainer width="100%" height={400}>
            <BarChart data={topProducts} layout="vertical" margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis type="number" />
              <YAxis dataKey="name" type="category" width={150} />
              <Tooltip formatter={(value) => [window.appSettings?.formatCurrency(Number(value)) || `$${Number(value).toLocaleString()}`, t('Revenue')]} />
              <Bar dataKey="revenue" fill="#8884d8" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <Card className="p-6">
        <h3 className="text-lg font-semibold mb-4">{t('Product Performance')}</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Product Name')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Quantity Sold')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Revenue')}
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {productSales.map((product: any, index: number) => (
                <tr key={index}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {product.name}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {product.quantity.toLocaleString()}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                    {window.appSettings?.formatCurrency(product.revenue) || `$${product.revenue.toLocaleString()}`}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </PageTemplate>
  );
}