import { PageTemplate } from '@/components/page-template';
import { usePage, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';

export default function ProductCreate() {
    const { t } = useTranslation();
    const { categories, brands, taxes, users, auth } = usePage().props as any;
    const isCompany = auth?.user?.type === 'company';

    const { data, setData, setError, post, processing, errors } = useForm({
        name: '',
        sku: '',
        description: '',
        price: '',
        stock_quantity: '',
        category_id: '',
        brand_id: '',
        tax_id: '',
        status: 'active',
        assigned_to: '',
        main_image_id: null as number | null,
        additional_image_ids: null as number[] | null
    });

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Products'), href: route('products.index') },
        { title: t('Create Product') }
    ];

    const handleInputChange = (name: string, value: string) => {
        setData(name as any, value);
    };

    const requiredFields: { name: keyof typeof data, label: string }[] = [
        { name: 'name', label: t('Product Name') },
        { name: 'sku', label: t('SKU') },
        { name: 'price', label: t('Price') },
        { name: 'stock_quantity', label: t('Stock Quantity') },
        { name: 'category_id', label: t('Category') },
        { name: 'brand_id', label: t('Brand') },
        { name: 'tax_id', label: t('Tax') },
        { name: 'main_image_id', label: t('Main Image') },
        { name: 'additional_image_ids', label: t('Additional Images') },
        ...(isCompany ? [{ name: 'assigned_to' as keyof typeof data, label: t('Assign To') }] : [])
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const clientErrors: Record<string, string> = {};

        requiredFields.forEach(({ name }) => {
            if (clientErrors[name]) {
                delete clientErrors[name];
            }
        });

        requiredFields.forEach(({ name, label }) => {
            if (!data[name]) clientErrors[name] = `${label} is required`;
        });

        if (data.additional_image_ids?.length < 1) {
            clientErrors['additional_image_ids'] = t('Additional Images is required');
        }

        if (data.stock_quantity !== '' && parseFloat(data.stock_quantity) < 0) {
            clientErrors['stock_quantity'] = t('Stock Quantity must be at least 0');
        }

        if (data.price !== '' && parseFloat(data.price) < 0) {
            clientErrors['price'] = t('Price must be at least 0');
        }

        console.log(Object.keys(clientErrors).length);
        console.log({ clientErrors });

        if (Object.keys(clientErrors).length > 0) {
            Object.entries(clientErrors).forEach(([key, msg]) => setError(key as any, msg));
            return;
        }

        toast.loading(t('Creating product...'));

        post(route('products.store'), {
            onSuccess: (page) => {
                toast.dismiss();
                if (page.props.flash.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: () => {
                toast.dismiss();
            }
        });
    };

    return (
        <PageTemplate
            title={t('Create Product')}
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
            <form onSubmit={handleSubmit} className="max-w-4xl mx-auto space-y-6">
                {/* Basic Information */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Basic Information')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-sm font-medium" required>
                                    {t('Product Name')}
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sku" className="text-sm font-medium" required>
                                    {t('SKU')}
                                </Label>
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) => handleInputChange('sku', e.target.value)}
                                    className={errors.sku ? 'border-red-500' : ''}
                                />
                                {errors.sku && <p className="text-xs text-red-500">{errors.sku}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description" className="text-sm font-medium">{t('Description')}</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => handleInputChange('description', e.target.value)}
                                className={errors.description ? 'border-red-500' : ''}
                                rows={3}
                            />
                            {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="price" className="text-sm font-medium" required>
                                    {t('Price')}
                                </Label>
                                <Input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    value={data.price}
                                    onChange={(e) => handleInputChange('price', e.target.value)}
                                    className={errors.price ? 'border-red-500' : ''}
                                />
                                {errors.price && <p className="text-xs text-red-500">{errors.price}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="stock_quantity" className="text-sm font-medium" required>
                                    {t('Stock Quantity')}
                                </Label>
                                <Input
                                    id="stock_quantity"
                                    type="number"
                                    value={data.stock_quantity}
                                    onChange={(e) => handleInputChange('stock_quantity', e.target.value)}
                                    className={errors.stock_quantity ? 'border-red-500' : ''}
                                />
                                {errors.stock_quantity && <p className="text-xs text-red-500">{errors.stock_quantity}</p>}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Images */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Product Images')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="space-y-2">
                            <Label required>{t("Main Image")}</Label>
                            <MediaPicker
                                value={data.main_image_id}
                                onChange={(value) => setData('main_image_id', value as number)}
                                placeholder={t('Select main image...')}
                                showPreview={true}
                                returnType="id"
                            />
                            {errors.main_image_id && <p className="text-xs text-red-500">{errors.main_image_id}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label required>{t("Additional Images")}</Label>
                            <MediaPicker
                                value={data.additional_image_ids || []}
                                onChange={(value) => setData('additional_image_ids', value as number[])}
                                placeholder={t('Select additional images...')}
                                multiple={true}
                                showPreview={true}
                                returnType="id"
                            />
                            {errors.additional_image_ids && <p className="text-xs text-red-500">{errors.additional_image_ids}</p>}
                        </div>
                    </CardContent>
                </Card>

                {/* Categories and Settings */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('Categories & Settings')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="space-y-2">
                                <Label className="text-sm font-medium" required>
                                    {t('Category')}
                                </Label>
                                <Select value={data.category_id} onValueChange={(value) => handleInputChange('category_id', value)}>
                                    <SelectTrigger className={errors.category_id ? 'border-red-500' : ''}>
                                        <SelectValue placeholder={t('Select category')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories?.map((category: any) => (
                                            <SelectItem key={category.id} value={category.id.toString()}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.category_id && <p className="text-xs text-red-500">{errors.category_id}</p>}
                                {categories?.length === 0 && (
                                    <p className="text-xs mt-1">
                                        {t('Click here to add')} <a href={route('categories.index')} className="underline font-medium">{t('Categories')}</a>
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label className="text-sm font-medium" required>
                                    {t('Brand')}
                                </Label>
                                <Select value={data.brand_id} onValueChange={(value) => handleInputChange('brand_id', value)}>
                                    <SelectTrigger className={errors.brand_id ? 'border-red-500' : ''}>
                                        <SelectValue placeholder={t('Select brand')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {brands?.map((brand: any) => (
                                            <SelectItem key={brand.id} value={brand.id.toString()}>
                                                {brand.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.brand_id && <p className="text-xs text-red-500">{errors.brand_id}</p>}
                                {brands?.length === 0 && (
                                    <p className="text-xs mt-1">
                                        {t('Click here to add')} <a href={route('brands.index')} className="underline font-medium">{t('Brands')}</a>
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label className="text-sm font-medium" required>
                                    {t('Tax')}
                                </Label>
                                <Select value={data.tax_id} onValueChange={(value) => handleInputChange('tax_id', value)}>
                                    <SelectTrigger className={errors.tax_id ? 'border-red-500' : ''}>
                                        <SelectValue placeholder={t('Select tax')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {taxes?.map((tax: any) => (
                                            <SelectItem key={tax.id} value={tax.id.toString()}>
                                                {tax.name} ({tax.rate}%)
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.tax_id && <p className="text-xs text-red-500">{errors.tax_id}</p>}
                                {taxes?.length === 0 && (
                                    <p className="text-xs mt-1">
                                        {t('Click here to add')} <a href={route('taxes.index')} className="underline font-medium">{t('Taxes')}</a>
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {isCompany && (
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium" required>
                                        {t('Assign To')}
                                    </Label>
                                    <Select value={data.assigned_to} onValueChange={(value) => handleInputChange('assigned_to', value)}>
                                        <SelectTrigger className={errors.assigned_to ? 'border-red-500' : ''}>
                                            <SelectValue placeholder={t('Select user')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {users?.map((user: any) => (
                                                <SelectItem key={user.id} value={user.id.toString()}>
                                                    {user.name} ({user.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.assigned_to && <p className="text-xs text-red-500">{errors.assigned_to}</p>}
                                    {users?.length === 0 && (
                                        <p className="text-xs mt-1">
                                            {t('Click here to add')} <a href={route('users.index')} className="underline font-medium">{t('Users')}</a>
                                        </p>
                                    )}
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label className="text-sm font-medium">{t('Status')}</Label>
                                <Select value={data.status} onValueChange={(value) => handleInputChange('status', value)}>
                                    <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">{t('Active')}</SelectItem>
                                        <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end space-x-4">
                    <Button type="button" variant="outline" onClick={() => window.history.back()}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {t('Save')}
                    </Button>
                </div>
            </form>
        </PageTemplate>
    );
}
