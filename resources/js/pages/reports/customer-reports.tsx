import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { Users, UserPlus, UserCheck, DollarSign } from 'lucide-react';
import { ReportFilters } from '@/components/reports/report-filters';
import { SummaryCards } from '@/components/reports/summary-cards';
import { ChartCard } from '@/components/reports/chart-card';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export default function CustomerReports() {
  const { t } = useTranslation();
  const { filters, summary, monthlyData, dailyData, topContacts } = usePage().props as any;
  const [chartView, setChartView] = useState<'daily' | 'monthly'>('monthly');
  
  const chartData = chartView === 'daily' ? dailyData : monthlyData;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Reports'), href: '#' },
    { title: t('Contact Reports') }
  ];

  const summaryCards = [
    {
      title: t('Total Contacts'),
      value: summary.total_contacts.toLocaleString(),
      icon: <Users className="h-6 w-6 text-blue-600" />,
      iconColor: 'bg-blue-100'
    },
    {
      title: t('New Contacts'),
      value: summary.new_contacts.toLocaleString(),
      icon: <UserPlus className="h-6 w-6 text-green-600" />,
      iconColor: 'bg-green-100'
    },
    {
      title: t('Active Contacts'),
      value: summary.active_contacts.toLocaleString(),
      icon: <UserCheck className="h-6 w-6 text-purple-600" />,
      iconColor: 'bg-purple-100'
    },
    {
      title: t('Contact Lifetime Value'),
      value: window.appSettings?.formatCurrency(summary.contact_lifetime_value) || `$${summary.contact_lifetime_value.toLocaleString()}`,
      icon: <DollarSign className="h-6 w-6 text-orange-600" />,
      iconColor: 'bg-orange-100'
    }
  ];

  return (
    <PageTemplate title={t("Contact Reports")} url="/reports/customers" breadcrumbs={breadcrumbs} noPadding>
      <ReportFilters filters={filters} />
      
      <SummaryCards cards={summaryCards} />

      <div className="grid grid-cols-1 gap-6 mb-6">
        <ChartCard title={t('Contact Growth')} 
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
              <Tooltip />
              <Line type="monotone" dataKey="count" stroke="#8884d8" strokeWidth={2} />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <Card className="p-6">
        <h3 className="text-lg font-semibold mb-4">{t('Top Contacts')}</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Contact Name')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Total Spent')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('Order Count')}
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {topContacts.map((contact: any, index: number) => (
                <tr key={index}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {contact.name}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                    {window.appSettings?.formatCurrency(contact.total_spent) || `$${contact.total_spent.toLocaleString()}`}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {contact.order_count}
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