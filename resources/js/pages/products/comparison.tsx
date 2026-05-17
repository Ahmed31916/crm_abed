import { useState } from 'react';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageTemplate } from '@/components/page-template';
import { ArrowLeft, ArrowRightLeft, RotateCcw, Package, AlertTriangle, ListChecks } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { toast } from '@/components/custom-toast';

interface Change {
    override_id: number;
    field_name: string;
    label: string;
    original_value: string | null;
    merchant_value: string;
    is_exclusive?: boolean;
}

interface ProductDifference {
    product_id: number;
    product_name: string;
    sku: string;
    changes: Change[];
}

interface ComparisonProps {
    allDifferences: ProductDifference[];
    totalProducts: number;
    totalChanges: number;
}

export default function ProductComparison({ allDifferences, totalProducts, totalChanges }: ComparisonProps) {
    const { t } = useTranslation();
    const [revertingField, setRevertingField] = useState<string | null>(null);

    const handleRevert = (overrideId: number, fieldName: string, productId: number) => {
        if (!confirm(t('Are you sure you want to revert this field to the Super Admin original?'))) {
            return;
        }

        const key = `${overrideId}-${fieldName}`;
        setRevertingField(key);

        fetch(route('products.merchant-revert-field'), {
            method: 'POST',
            body: JSON.stringify({
                id: overrideId,
                field_name: fieldName,
            }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.flag === 'success') {
                    toast.success(t(data.msg));
                    // Reload to refresh the comparison data
                    router.reload();
                } else {
                    toast.error(t(data.msg));
                }
            })
            .catch(() => {
                toast.error(t('An error occurred.'));
            })
            .finally(() => {
                setRevertingField(null);
            });
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Products'), href: route('products.index') },
        { title: t('Comparison') },
    ];

    return (
        <PageTemplate
            title={t('My Customizations Comparison')}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back to Products'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => router.visit(route('products.index')),
                },
            ]}
        >
            {allDifferences.length === 0 ? (
                /* Empty State */
                <Card className="p-8 text-center">
                    <div className="py-8">
                        <div className="mx-auto w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
                            <Package className="h-10 w-10 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            {t('No Customizations Found')}
                        </h3>
                        <p className="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            {t("You haven't customized any Super Admin products yet. When you edit a Super Admin product, your changes will appear here for comparison.")}
                        </p>
                        <Button
                            variant="outline"
                            className="mt-6"
                            onClick={() => router.visit(route('products.index'))}
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            {t('Back to Products')}
                        </Button>
                    </div>
                </Card>
            ) : (
                <div className="space-y-6">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card className="p-5 flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <AlertTriangle className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">{totalProducts}</p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{t('Products Customized')}</p>
                            </div>
                        </Card>
                        <Card className="p-5 flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <ListChecks className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">{totalChanges}</p>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{t('Total Field Changes')}</p>
                            </div>
                        </Card>
                    </div>

                    {/* Product Sections */}
                    {allDifferences.map((product) => (
                        <Card key={product.product_id} className="overflow-hidden" id={`section-${product.product_id}`}>
                            {/* Product Header */}
                            <div className="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <Package className="h-4 w-4 text-gray-400" />
                                            {product.product_name}
                                        </h3>
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-mono font-medium text-gray-600 dark:text-gray-300">
                                                SKU: {product.sku}
                                            </span>
                                            <span className="inline-flex items-center rounded-md bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                                <ArrowRightLeft className="h-3 w-3 mr-1" />
                                                {product.changes.length} {t('changes')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Changes Table */}
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700">
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style={{ width: '15%' }}>
                                                {t('Field')}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style={{ width: '35%' }}>
                                                {t('Original (Super Admin)')}
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style={{ width: '35%' }}>
                                                {t('Your Customization')}
                                            </th>
                                            <th className="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" style={{ width: '15%' }}>
                                                {t('Actions')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {product.changes.map((change, idx) => {
                                            const revertKey = `${change.override_id}-${change.field_name}`;
                                            const isReverting = revertingField === revertKey;

                                            return (
                                                <tr
                                                    key={`${change.override_id}-${change.field_name}-${idx}`}
                                                    className="border-b border-gray-100 dark:border-gray-800 last:border-b-0 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors"
                                                >
                                                    {/* Field Name */}
                                                    <td className="px-6 py-4">
                                                        <span
                                                            className={`inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ${
                                                                change.is_exclusive
                                                                    ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400'
                                                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
                                                            }`}
                                                        >
                                                            {change.label}
                                                        </span>
                                                    </td>

                                                    {/* Original Value */}
                                                    <td className="px-6 py-4">
                                                        {change.is_exclusive ? (
                                                            <div className="text-center text-gray-400 italic text-sm py-2">
                                                                {t('N/A')}
                                                            </div>
                                                        ) : (
                                                            <div className="bg-blue-50 dark:bg-blue-900/20 border-l-3 border-blue-500 rounded-r-lg px-4 py-3 text-sm text-gray-700 dark:text-gray-300 break-words max-h-40 overflow-y-auto">
                                                                {change.original_value || '—'}
                                                            </div>
                                                        )}
                                                    </td>

                                                    {/* Merchant Value */}
                                                    <td className="px-6 py-4">
                                                        <div
                                                            className={`border-l-3 rounded-r-lg px-4 py-3 text-sm text-gray-700 dark:text-gray-300 break-words max-h-40 overflow-y-auto ${
                                                                change.is_exclusive
                                                                    ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-500'
                                                                    : 'bg-green-50 dark:bg-green-900/20 border-green-500'
                                                            }`}
                                                        >
                                                            {change.merchant_value || '—'}
                                                        </div>
                                                    </td>

                                                    {/* Revert Button */}
                                                    <td className="px-6 py-4 text-center">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/20"
                                                            onClick={() => handleRevert(change.override_id, change.field_name, product.product_id)}
                                                            disabled={isReverting}
                                                        >
                                                            {isReverting ? (
                                                                <span className="animate-spin mr-1.5 inline-block w-3.5 h-3.5 border-2 border-red-400 border-t-transparent rounded-full" />
                                                            ) : (
                                                                <RotateCcw className="h-3.5 w-3.5 mr-1.5" />
                                                            )}
                                                            {t('Revert')}
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    ))}
                </div>
            )}
        </PageTemplate>
    );
}
