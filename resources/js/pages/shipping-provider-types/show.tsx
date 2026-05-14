import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, Truck, User, Palette } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

export default function ShippingProviderTypeShow() {
  const { t } = useTranslation();
  const { shippingProviderType } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Shipping Provider Types'), href: route('shipping-provider-types.index') },
    { title: shippingProviderType.name }
  ];

  const getStatusBadge = (status: string) => {
    const statusColors = {
      active: 'bg-green-50 text-green-700 ring-green-600/20',
      inactive: 'bg-red-50 text-red-700 ring-red-600/10'
    };

    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.active}`}>
        {t(status?.charAt(0).toUpperCase() + status?.slice(1)) || t('Active')}
      </span>
    );
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  return (
    <PageTemplate
      title={shippingProviderType.name}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => window.history.back()
        }
      ]}
    >
      <div className="mx-auto space-y-6">
        {/* Header Section */}
        <div className="bg-white rounded-lg shadow-sm border p-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold flex items-center">
                <Truck className="h-5 w-5 mr-2 text-muted-foreground" />
                {shippingProviderType.name}
              </h1>
              <p className="text-sm mt-2">{shippingProviderType.description || t('No description provided')}</p>
            </div>
            <div className="text-right">
              {getStatusBadge(shippingProviderType.status)}
            </div>
          </div>
        </div>

        {/* Shipping Provider Type Information */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Shipping Provider Type Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Name')}</label>
                  <p className="text-sm mt-1">{shippingProviderType.name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Description')}</label>
                  <p className="text-sm mt-1">{shippingProviderType.description || t('-')}</p>
                </div>
                <div className="flex items-center space-x-3">
                  <Palette className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Color')}</label>
                    <div className="flex items-center space-x-2 mt-1">
                      <div
                        className="w-6 h-6 rounded border border-gray-300"
                        style={{ backgroundColor: shippingProviderType.color || '#3B82F6' }}
                      ></div>
                      <span className="text-sm font-mono">{shippingProviderType.color || '#3B82F6'}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div className="space-y-6">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">{t('Status')}</label>
                  <div className="mt-1">{getStatusBadge(shippingProviderType.status)}</div>
                </div>
                <div className="flex items-center space-x-3">
                  <User className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                    <p className="text-sm mt-1">{shippingProviderType.creator?.name || t('-')}</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Timestamps */}
        <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 border-b">
            <CardTitle className="text-lg font-semibold">{t('Record Information')}</CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(shippingProviderType.created_at, false) || new Date(shippingProviderType.created_at).toLocaleDateString()}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(shippingProviderType.updated_at, false) || new Date(shippingProviderType.updated_at).toLocaleDateString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>


      </div>
    </PageTemplate>
  );
}
