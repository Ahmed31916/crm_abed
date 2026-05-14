import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { DollarSign, ShoppingCart, TrendingUp, Target } from 'lucide-react';
import { ReportFilters } from '@/components/reports/report-filters';
import { SummaryCards } from '@/components/reports/summary-cards';
import { ChartCard } from '@/components/reports/chart-card';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export default function SalesReports() {
  const { t } = useTranslation();
  const { filters, summary, monthlyData, dailyData, salesByStatus } = usePage().props as any;
  const [chartView, setChartView] = useState<'daily' | 'monthly'>('monthly');
  
  const chartData = chartView === 'daily' ? dailyData : monthlyData;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Reports'), href: '#' },
    { title: t('Sales Reports') }
  ];

  const summaryCards = [
    {
      title: t('Total Sales'),
      value: window.appSettings?.formatCurrency(summary.total_sales) || `$${summary.total_sales.toLocaleString()}`,
      icon: <DollarSign className="h-6 w-6 text-green-600" />,
      iconColor: 'bg-green-100'
    },
    {
      title: t('Total Orders'),
      value: summary.total_orders.toLocaleString(),
      icon: <ShoppingCart className="h-6 w-6 text-blue-600" />,
      iconColor: 'bg-blue-100'
    },
    {
      title: t('Average Order Value'),
      value: window.appSettings?.formatCurrency(summary.avg_order_value) || `$${summary.avg_order_value.toLocaleString()}`,
      icon: <Target className="h-6 w-6 text-purple-600" />,
      iconColor: 'bg-purple-100'
    },
    {
      title: t('Growth Rate'),
      value: `${summary.growth_rate.toFixed(2)}%`,
      icon: <TrendingUp className="h-6 w-6 text-orange-600" />,
      iconColor: 'bg-orange-100'
    }
  ];

  return (
    <PageTemplate title={t("Sales Reports")} url="/reports/sales" breadcrumbs={breadcrumbs} noPadding>
      <ReportFilters filters={filters} />
      
      <SummaryCards cards={summaryCards} />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <ChartCard title={t('Sales Trend')} 
          actions={
            <div className="flex gap-2">
              <Button 
                size="sm" 
                variant={chartView === 'daily' ? 'default' : 'outline'}
                onClick={() => setChartView('daily')}
              >
                {t('Daily')}
              </Button>
              <Button 
                size="sm" 
                variant={chartView === 'monthly' ? 'default' : 'outline'}
                onClick={() => setChartView('monthly')}
              >
                {t('Monthly')}
              </Button>
            </div>
          }
        >
          <ResponsiveContainer width="100%" height={300}>
            <LineChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="period" />
              <YAxis />
              <Tooltip formatter={(value) => [window.appSettings?.formatCurrency(Number(value)) || `$${Number(value).toLocaleString()}`, t('Revenue')]} />
              <Line type="monotone" dataKey="revenue" stroke="#8884d8" strokeWidth={2} />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title={t('Sales by Status')}>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={salesByStatus}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="status" />
              <YAxis />
              <Tooltip formatter={(value) => [window.appSettings?.formatCurrency(Number(value)) || `$${Number(value).toLocaleString()}`, t('Amount')]} />
              <Bar dataKey="amount" fill="#8884d8" />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <Card className="p-6">
        <h3 className="text-lg font-semibold mb-4">{t('Sales Summary')}</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <tbody className="bg-white divide-y divide-gray-200">
              <tr>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {t('Total Sales')}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                  {window.appSettings?.formatCurrency(summary.total_sales) || `$${summary.total_sales.toLocaleString()}`}
                </td>
              </tr>
              <tr>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {t('Total Orders')}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-semibold">
                  {summary.total_orders.toLocaleString()}
                </td>
              </tr>
              <tr className="bg-gray-50">
                <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                  {t('Average Order Value')}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-purple-600">
                  {window.appSettings?.formatCurrency(summary.avg_order_value) || `$${summary.avg_order_value.toLocaleString()}`}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </PageTemplate>
  );
}