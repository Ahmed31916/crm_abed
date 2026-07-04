import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { ArrowLeft, Package, DollarSign, Archive, Tag, Building, User, Calendar, Edit, Trash2, Eye } from 'lucide-react';
// import { ArrowLeft, Package, DollarSign, Archive, Tag, Building, User, Calendar, Edit, Trash2, Eye, Barcode as BarcodeIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { hasPermission } from '@/utils/authorization';
// import { ProductBarcode } from '@/components/Barcode';

export default function ProductShow() {
  const { t } = useTranslation();
  const { product, mainImage, additionalImages, auth } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const [selectedImage, setSelectedImage] = useState(mainImage || (additionalImages?.[0]?.url));

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Products'), href: route('products.index') },
    { title: product.name }
  ];

  const getStatusBadge = (status: string) => {
    return (
      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
        status === 'active'
          ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
          : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
      }`}>
        {status === 'active' ? t('Active') : t('Inactive')}
      </span>
    );
  };

  const formatCurrency = (amount: number) => window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;

  const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
  };

  const pageActions = [
    {
      label: t('Back'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.visit(route('products.index'))
    }
  ];

  if (hasPermission(permissions, 'edit-products')) {
    pageActions.push({
      label: t('Edit Product'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => router.visit(route('products.edit', product.id))
    });
  }

  return (
    <PageTemplate
      title={product.name}
      breadcrumbs={breadcrumbs}
      actions={pageActions}
    >
      <div className="mx-auto space-y-6">
        {/* Header Section */}
        <div className="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl shadow-sm border p-8">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Package className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <h1 className="text-lg font-bold text-gray-900 dark:text-white">{product.name}</h1>
              </div>
              <p className="text-lg text-gray-600 dark:text-gray-300 font-medium">SKU: {product.sku}</p>
              {product.description && (
                <p className="text-sm text-gray-600 dark:text-gray-400 mt-2 max-w-2xl">{product.description}</p>
              )}
            </div>
            <div className="flex flex-col items-end gap-3">
              {getStatusBadge(product.status)}
              <div className="text-right">
                <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                  {formatCurrency(product.price)}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400">
                  {product.stock_quantity} {product.stock_quantity === 1 ? t('unit') : t('units')} {t('in stock')}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Barcode Section */}
        {/* <Card className="shadow-sm">
          <CardHeader className="bg-gray-50 dark:bg-gray-800 border-b">
            <CardTitle className="text-lg font-semibold flex items-center gap-2">
              <BarcodeIcon className="h-5 w-5" />
              {t('Product Barcode')}
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="flex flex-col items-center justify-center bg-white dark:bg-gray-900 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-8">
              <ProductBarcode value={product.sku} width={2} height={50} fontSize={14} />
              <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
                {t('Scan this barcode to identify the product')}
              </p>
            </div>
          </CardContent>
        </Card> */}
        {/* Main Content */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Images Section */}
          <div className="lg:col-span-1">
            <Card className="shadow-sm">
              <CardHeader className="bg-gray-50 dark:bg-gray-800 border-b">
                <CardTitle className="text-lg font-semibold flex items-center gap-2">
                  <Eye className="h-5 w-5" />
                  {t('Product Images')}
                </CardTitle>
              </CardHeader>
              <CardContent className="p-6">
                {selectedImage ? (
                  <div className="space-y-4">
                    <div className="aspect-square bg-gray-100 dark:bg-gray-700 rounded-xl overflow-hidden shadow-inner p-4 flex items-center justify-center">
                      <img
                        src={selectedImage}
                        alt={product.name}
                        className="max-w-full max-h-full object-contain hover:scale-105 transition-transform duration-300 rounded-lg"
                      />
                    </div>

                    {(mainImage || additionalImages?.length > 0) && (
                      <div className="flex gap-2 overflow-x-auto py-2 px-1">
                        {mainImage && (
                          <button
                            onClick={() => setSelectedImage(mainImage)}
                            className={`flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all duration-200 cursor-pointer ${
                              selectedImage === mainImage ? 'border-blue-500 shadow-lg scale-105' : 'border-gray-200 hover:border-gray-300'
                            }`}
                          >
                            <img src={mainImage} alt="Main" className="w-full h-full object-contain p-1" />
                          </button>
                        )}
                        {additionalImages?.map((img: any, index: number) => (
                          <button
                            key={img.id}
                            onClick={() => setSelectedImage(img.url)}
                            className={`flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-2 transition-all duration-200 cursor-pointer ${
                              selectedImage === img.url ? 'border-blue-500 shadow-lg scale-105' : 'border-gray-200 hover:border-gray-300'
                            }`}
                          >
                            <img src={img.thumb_url || img.url} alt={`Additional ${index + 1}`} className="w-full h-full object-contain p-1" />
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-xl flex items-center justify-center">
                    <div className="text-center text-muted-foreground">
                      <Package className="h-20 w-20 mx-auto mb-4 text-gray-400" />
                      <p className="text-sm font-medium">{t('No image available')}</p>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>

          {/* Product Details */}
          <div className="lg:col-span-2 space-y-6">
            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <Card className="border-l-4 border-l-green-500">
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Price')}</p>
                      <h3 className="mt-2 text-xl font-bold text-green-600 leading-none">{formatCurrency(product.price)}</h3>
                    </div>
                    <div className="rounded-full bg-green-100 p-4">
                      <DollarSign className="h-5 w-5 text-green-600" />
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="border-l-4 border-l-blue-500">
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Stock')}</p>
                      <h3 className="mt-2 text-xl font-bold text-blue-600 leading-none">{product.stock_quantity}</h3>
                    </div>
                    <div className="rounded-full bg-blue-100 p-4">
                      <Archive className="h-5 w-5 text-blue-600" />
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="border-l-4 border-l-purple-500">
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Category')}</p>
                      <h3 className="mt-2 text-lg font-bold text-purple-600 leading-tight">{product.category?.name || t('-')}</h3>
                    </div>
                    <div className="rounded-full bg-purple-100 p-4">
                      <Tag className="h-5 w-5 text-purple-600" />
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="border-l-4 border-l-orange-500">
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-xs font-bold text-gray-500 uppercase tracking-wide">{t('Supplier')}</p>
                      <h3 className="mt-2 text-lg font-bold text-orange-600 leading-tight">{product.brand?.name || t('-')}</h3>
                    </div>
                    <div className="rounded-full bg-orange-100 p-4">
                      <Building className="h-5 w-5 text-orange-600" />
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Detailed Information */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Basic Information */}
              <Card className="shadow-sm">
                <CardHeader className="bg-gray-50 dark:bg-gray-800 border-b">
                  <CardTitle className="text-lg font-semibold flex items-center gap-2">
                    <Package className="h-5 w-5" />
                    {t('Basic Information')}
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-6 space-y-4">
                  <div className="grid grid-cols-1 gap-4">
                    <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Product Name')}</label>
                      <p className="text-sm font-medium mt-1">{product.name}</p>
                    </div>
                    <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('SKU')}</label>
                      <p className="text-sm font-medium mt-1 font-mono">{product.sku}</p>
                    </div>
                    {product.description && (
                      <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Description')}</label>
                        <p className="text-sm mt-1 whitespace-pre-line leading-relaxed">{product.description}</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Additional Details */}
              <Card className="shadow-sm">
                <CardHeader className="bg-gray-50 dark:bg-gray-800 border-b">
                  <CardTitle className="text-lg font-semibold flex items-center gap-2">
                    <User className="h-5 w-5" />
                    {t('Additional Details')}
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-6 space-y-4">
                  <div className="grid grid-cols-1 gap-4">
                    <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <div className="flex items-center gap-2">
                        <Tag className="h-4 w-4 text-gray-500" />
                        <span className="text-sm font-medium">{t('Tax')}</span>
                      </div>
                      <span className="text-sm">{product.tax ? `${product.tax.name} (${product.tax.rate}%)` : t('-')}</span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-gray-500" />
                        <span className="text-sm font-medium">{t('Assigned To')}</span>
                      </div>
                      <span className="text-sm">{product.assigned_user?.name || t('Unassigned')}</span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-gray-500" />
                        <span className="text-sm font-medium">{t('Created By')}</span>
                      </div>
                      <span className="text-sm">{product.creator?.name || t('-')}</span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4 text-gray-500" />
                        <span className="text-sm font-medium">{t('Created At')}</span>
                      </div>
                      <span className="text-sm">{formatDate(product.created_at)}</span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4 text-gray-500" />
                        <span className="text-sm font-medium">{t('Updated At')}</span>
                      </div>
                      <span className="text-sm">{formatDate(product.updated_at)}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </PageTemplate>
  );
}
