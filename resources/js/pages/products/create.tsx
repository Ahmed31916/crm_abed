import { PageTemplate } from '@/components/page-template';
import { usePage, useForm } from '@inertiajs/react';
import { ArrowLeft, Heart, Clock, Stethoscope, Info } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { useState, useEffect } from 'react';

export default function ProductCreate() {
    const { t } = useTranslation();
    const { categories, brands, taxes, users, tags, primaryIndications, availableProducts, auth } = usePage().props as any;
    const isCompany = auth?.user?.type === 'company';
    const isSuperAdmin = auth?.user?.isSuperAdmin;

    const { data, setData, setError, post, processing, errors } = useForm({
        // ===== Basic Product Fields =====
        name: '',
        description: '',
        specification: '',
        detail: '',
        price: '',
        sale_price: '',
        category_id: '',
        brand_id: '',
        tax_id: '',
        status: 'active',
        stock_status: 'in_stock',
        stock_quantity: '0',
        product_weight: '',

        // ===== SKU (maps to health_products.sku) =====
        product_sku: '',

        // ===== Product Form & Size (health_products) =====
        product_form: '',
        bottle_size: '',
        product_image_url: '',

        // ===== Full Name (health_products) =====
        full_name: '',

        // ===== Health Product Fields =====
        supports: '',
        useful_for: '',
        ingredients: '',
        contraindications: '',
        research_links: '',

        // ===== Dosing Schedule =====
        dosing_upon_rising: '',
        dosing_breakfast: '',
        dosing_between_meals_am: '',
        dosing_lunch: '',
        dosing_between_meals_pm: '',
        dosing_dinner: '',
        dosing_before_sleep: '',
        dosing_na: false,

        // ===== Tags & Pairs =====
        tag_id: [] as string[],
        pairs_well_with: [] as string[],

        // ===== Primary Indications =====
        primary_indications: [] as string[],

        // ===== Practitioner / Company Override Exclusive =====
        practitioner_notes: '',
        custom_primary_indications: [] as string[],
        custom_dosing_notes: '',

        // ===== Images (Spatie MediaLibrary) =====
        main_image_id: null as number | null,
        additional_image_ids: [] as number[],

    });

    // Dosing N/A toggle: disable all dosing fields when checked
    const [dosingDisabled, setDosingDisabled] = useState(false);

    useEffect(() => {
        setDosingDisabled(data.dosing_na);
    }, [data.dosing_na]);

    const handleInputChange = (name: string, value: string | boolean | string[] | number | number[] | null) => {
        setData(name as any, value as any);
    };

    // Auto-set bottle_size_unit based on product_form
    useEffect(() => {
        // bottle_size_unit is auto-set server-side:
        // Liquid → 'oz', Caps → 'caps'
        // We just show a hint in the UI
    }, [data.product_form]);

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Products'), href: route('products.index') },
        { title: t('Create Product') }
    ];

    const requiredFields: { name: keyof typeof data, label: string }[] = [
        { name: 'name', label: t('Product Name') },
        { name: 'product_sku', label: t('SKU') },
        { name: 'product_form', label: t('Product Form') },
        { name: 'bottle_size', label: t('Bottle Size') },
        { name: 'price', label: t('Price') },
        { name: 'category_id', label: t('Category') },
        { name: 'tag_id', label: t('Tags') },
        { name: 'main_image_id', label: t('Main Image') },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const clientErrors: Record<string, string> = {};

        requiredFields.forEach(({ name, label }) => {
            const value = data[name];
            if (!value || (Array.isArray(value) && value.length === 0)) {
                clientErrors[name] = `${label} is required`;
            }
        });

        if (data.price !== '' && parseFloat(data.price) < 0) {
            clientErrors['price'] = t('Price must be at least 0');
        }

        if (data.sale_price !== '' && data.sale_price && parseFloat(data.sale_price) >= parseFloat(data.price)) {
            clientErrors['sale_price'] = t('Sale price must be less than the regular price');
        }

        if (Object.keys(clientErrors).length > 0) {
            Object.entries(clientErrors).forEach(([key, msg]) => setError(key as any, msg));
            return;
        }

        toast.loading(t('Creating product...'));

        post(route('products.store'), {
            onSuccess: (page: any) => {
                toast.dismiss();
                if (page.props.flash?.success) {
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash?.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: () => {
                toast.dismiss();
            }
        });
    };

    const dosingFields = [
        { key: 'dosing_upon_rising', label: t('Upon Rising'), icon: '🌅' },
        { key: 'dosing_breakfast', label: t('Breakfast'), icon: '☕' },
        { key: 'dosing_between_meals_am', label: t('Between Meals (AM)'), icon: '🕐' },
        { key: 'dosing_lunch', label: t('Lunch'), icon: '☀️' },
        { key: 'dosing_between_meals_pm', label: t('Between Meals (PM)'), icon: '🕐' },
        { key: 'dosing_dinner', label: t('Dinner'), icon: '🌙' },
        { key: 'dosing_before_sleep', label: t('Before Sleep'), icon: '😴' },
    ];

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
            <form onSubmit={handleSubmit} className="max-w-6xl mx-auto space-y-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* ============================================ */}
                    {/* LEFT COLUMN - Main Product Info (2 cols) */}
                    {/* ============================================ */}
                    <div className="lg:col-span-2 space-y-6">

                        {/* ===== Basic Information ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Basic Information')}</CardTitle>
                                <CardDescription>{t('Product name, category, SKU, and pricing')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Name */}
                                <div className="space-y-2">
                                    <Label htmlFor="name" className="text-sm font-medium" required>
                                        {t('Product Name')}
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => handleInputChange('name', e.target.value)}
                                        placeholder={t('Enter product name')}
                                        className={errors.name ? 'border-red-500' : ''}
                                    />
                                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                                </div>

                                {/* Category + Product SKU */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium" required>
                                            {t('Category')}
                                        </Label>
                                        <Select value={data.category_id} onValueChange={(value) => handleInputChange('category_id', value)}>
                                            <SelectTrigger className={errors.category_id ? 'border-red-500' : ''}>
                                                <SelectValue placeholder={t('Select Category')} />
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
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="product_sku" className="text-sm font-medium" required>
                                            {t('Product SKU / Item Number')}
                                        </Label>
                                        <Input
                                            id="product_sku"
                                            value={data.product_sku}
                                            onChange={(e) => handleInputChange('product_sku', e.target.value)}
                                            placeholder={t('Enter product code or SKU')}
                                            className={errors.product_sku ? 'border-red-500' : ''}
                                        />
                                        {errors.product_sku && <p className="text-xs text-red-500">{errors.product_sku}</p>}
                                    </div>
                                </div>

                                {/* Product Form + Bottle Size + Brand */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium" required>
                                            {t('Product Form')}
                                        </Label>
                                        <Select value={data.product_form} onValueChange={(value) => handleInputChange('product_form', value)}>
                                            <SelectTrigger className={errors.product_form ? 'border-red-500' : ''}>
                                                <SelectValue placeholder={t('Select Product Form')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Liquid">{t('Liquid')}</SelectItem>
                                                <SelectItem value="Caps">{t('Caps')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.product_form && <p className="text-xs text-red-500">{errors.product_form}</p>}
                                        {data.product_form && (
                                            <p className="text-xs text-muted-foreground">
                                                {data.product_form === 'Liquid' ? t('Unit: oz') : t('Unit: caps')}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="bottle_size" className="text-sm font-medium" required>
                                            {t('Bottle Size / Count')}
                                        </Label>
                                        <Input
                                            id="bottle_size"
                                            type="number"
                                            min="0"
                                            step="any"
                                            value={data.bottle_size}
                                            onChange={(e) => handleInputChange('bottle_size', e.target.value)}
                                            placeholder={t('e.g., 2, 90')}
                                            className={errors.bottle_size ? 'border-red-500' : ''}
                                        />
                                        {errors.bottle_size && <p className="text-xs text-red-500">{errors.bottle_size}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">
                                            {t('Supplier / Brand')}
                                        </Label>
                                        <Select value={data.brand_id} onValueChange={(value) => handleInputChange('brand_id', value)}>
                                            <SelectTrigger className={errors.brand_id ? 'border-red-500' : ''}>
                                                <SelectValue placeholder={t('Select Supplier')} />
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
                                    </div>
                                </div>

                                {/* Tags */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium" required>
                                        {t('Tags')}
                                    </Label>
                                    <div className="flex flex-wrap gap-2 border rounded-md p-3 min-h-[42px]">
                                        {tags?.map((tag: any) => (
                                            <label key={tag.id} className="inline-flex items-center gap-1.5 cursor-pointer">
                                                <Checkbox
                                                    checked={data.tag_id.includes(tag.id.toString())}
                                                    onCheckedChange={(checked) => {
                                                        const tagId = tag.id.toString();
                                                        if (checked) {
                                                            handleInputChange('tag_id', [...data.tag_id, tagId]);
                                                        } else {
                                                            handleInputChange('tag_id', data.tag_id.filter((id: string) => id !== tagId));
                                                        }
                                                    }}
                                                />
                                                <Badge
                                                    variant="secondary"
                                                    className="cursor-pointer hover:bg-primary/10"
                                                    style={tag.color ? { backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color + '40' } : undefined}
                                                >
                                                    {tag.name}
                                                </Badge>
                                            </label>
                                        ))}
                                        {(!tags || tags.length === 0) && (
                                            <p className="text-xs text-muted-foreground">{t('No tags available')}</p>
                                        )}
                                    </div>
                                    {errors.tag_id && <p className="text-xs text-red-500">{errors.tag_id}</p>}
                                </div>

                                {/* Pairs Well With */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">
                                        {t('Pairs Well With')}
                                    </Label>
                                    <div className="flex flex-wrap gap-2 border rounded-md p-3 min-h-[42px]">
                                        {availableProducts?.map((product: any) => (
                                            <label key={product.id} className="inline-flex items-center gap-1.5 cursor-pointer">
                                                <Checkbox
                                                    checked={data.pairs_well_with.includes(product.id.toString())}
                                                    onCheckedChange={(checked) => {
                                                        const productId = product.id.toString();
                                                        if (checked) {
                                                            handleInputChange('pairs_well_with', [...data.pairs_well_with, productId]);
                                                        } else {
                                                            handleInputChange('pairs_well_with', data.pairs_well_with.filter((id: string) => id !== productId));
                                                        }
                                                    }}
                                                />
                                                <Badge variant="outline" className="cursor-pointer hover:bg-accent">
                                                    {product.name}
                                                </Badge>
                                            </label>
                                        ))}
                                        {(!availableProducts || availableProducts.length === 0) && (
                                            <p className="text-xs text-muted-foreground">{t('No other products available')}</p>
                                        )}
                                    </div>
                                    <p className="text-xs text-muted-foreground">{t('Select products that pair well with this one.')}</p>
                                </div>

                                {/* Price + Sale Price */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="price" className="text-sm font-medium" required>
                                            {t('Price')}
                                        </Label>
                                        <Input
                                            id="price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.price}
                                            onChange={(e) => handleInputChange('price', e.target.value)}
                                            className={errors.price ? 'border-red-500' : ''}
                                        />
                                        {errors.price && <p className="text-xs text-red-500">{errors.price}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="sale_price" className="text-sm font-medium">
                                            {t('Sale Price')}
                                        </Label>
                                        <Input
                                            id="sale_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.sale_price}
                                            onChange={(e) => handleInputChange('sale_price', e.target.value)}
                                            className={errors.sale_price ? 'border-red-500' : ''}
                                        />
                                        {errors.sale_price && <p className="text-xs text-red-500">{errors.sale_price}</p>}
                                    </div>
                                </div>

                                {/* Stock Status */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">{t('Stock Status')}</Label>
                                    <div className="flex flex-wrap gap-4">
                                        {[
                                            { value: 'in_stock', label: t('In Stock') },
                                            { value: 'out_of_stock', label: t('Out of Stock') },
                                            { value: 'on_backorder', label: t('On Backorder') },
                                        ].map((option) => (
                                            <label key={option.value} className="inline-flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="stock_status"
                                                    value={option.value}
                                                    checked={data.stock_status === option.value}
                                                    onChange={(e) => handleInputChange('stock_status', e.target.value)}
                                                    className="h-4 w-4 text-primary border-gray-300 focus:ring-primary"
                                                />
                                                <span className="text-sm">{option.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                {/* Stock Quantity */}
                                <div className="space-y-2">
                                    <Label htmlFor="stock_quantity" className="text-sm font-medium">
                                        {t('Stock Quantity')}
                                    </Label>
                                    <Input
                                        id="stock_quantity"
                                        type="number"
                                        min="0"
                                        value={data.stock_quantity}
                                        onChange={(e) => handleInputChange('stock_quantity', e.target.value)}
                                    />
                                </div>

                                {/* Status + Tax + Weight */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">{t('Status')}</Label>
                                        <Select value={data.status} onValueChange={(value) => handleInputChange('status', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">{t('Active')}</SelectItem>
                                                <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">{t('Tax')}</Label>
                                        <Select value={data.tax_id} onValueChange={(value) => handleInputChange('tax_id', value)}>
                                            <SelectTrigger className={errors.tax_id ? 'border-red-500' : ''}>
                                                <SelectValue placeholder={t('Select Tax')} />
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
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="product_weight" className="text-sm font-medium">
                                            {t('Weight')}
                                        </Label>
                                        <Input
                                            id="product_weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.product_weight}
                                            onChange={(e) => handleInputChange('product_weight', e.target.value)}
                                            placeholder={t('e.g., 0.5')}
                                        />
                                    </div>
                                </div>

                                {/* Full Name */}
                                <div className="space-y-2">
                                    <Label htmlFor="full_name" className="text-sm font-medium">
                                        {t('Full Product Name')}
                                    </Label>
                                    <Input
                                        id="full_name"
                                        value={data.full_name}
                                        onChange={(e) => handleInputChange('full_name', e.target.value)}
                                        placeholder={t('Full product name with brand/line details')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('If different from the short name above')}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ===== Product Images ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Product Images')}</CardTitle>
                                <CardDescription>{t('Main image, gallery, and external image URL')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label required>{t('Main Image')}</Label>
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
                                        <Label>{t('Additional Images')}</Label>
                                        <MediaPicker
                                            value={data.additional_image_ids || []}
                                            onChange={(value) => setData('additional_image_ids', value as number[])}
                                            placeholder={t('Select additional images...')}
                                            multiple={true}
                                            showPreview={true}
                                            returnType="id"
                                        />
                                    </div>
                                </div>
                                <Separator />
                                <div className="space-y-2">
                                    <Label htmlFor="product_image_url">{t('Product Image URL')}</Label>
                                    <Input
                                        id="product_image_url"
                                        type="url"
                                        value={data.product_image_url}
                                        onChange={(e) => handleInputChange('product_image_url', e.target.value)}
                                        placeholder={t('Enter direct link to product image')}
                                        className={errors.product_image_url ? 'border-red-500' : ''}
                                    />
                                    {errors.product_image_url && <p className="text-xs text-red-500">{errors.product_image_url}</p>}
                                    <p className="text-xs text-muted-foreground">{t('Optional: external image URL for API/reference')}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ===== Product Description ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Product Description')}</CardTitle>
                                <CardDescription>{t('Detailed description, specifications, and additional details')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="description" className="text-sm font-medium">{t('Description')}</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => handleInputChange('description', e.target.value)}
                                        rows={4}
                                        placeholder={t('Product description...')}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="specification" className="text-sm font-medium">{t('Specification')}</Label>
                                    <Textarea
                                        id="specification"
                                        value={data.specification}
                                        onChange={(e) => handleInputChange('specification', e.target.value)}
                                        rows={3}
                                        placeholder={t('Product specifications...')}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="detail" className="text-sm font-medium">{t('Additional Details')}</Label>
                                    <Textarea
                                        id="detail"
                                        value={data.detail}
                                        onChange={(e) => handleInputChange('detail', e.target.value)}
                                        rows={3}
                                        placeholder={t('Additional product details...')}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* ============================================ */}
                    {/* RIGHT COLUMN - Health & Dosing (1 col) */}
                    {/* ============================================ */}
                    <div className="space-y-6">

                        {/* ===== Health Product Details ===== */}
                        <Card className="border-blue-200 dark:border-blue-900">
                            <CardHeader className="bg-blue-50 dark:bg-blue-950/50 rounded-t-lg">
                                <CardTitle className="flex items-center gap-2 text-blue-700 dark:text-blue-400">
                                    <Heart className="h-5 w-5" />
                                    {t('Health Product Details')}
                                </CardTitle>
                                <CardDescription>{t('Clinical and health-specific information')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 pt-4">
                                {/* Primary Indications - Checkboxes from primary_indications table */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">{t('Primary Indications')}</Label>
                                    <div className="border rounded-md p-3 max-h-[200px] overflow-y-auto">
                                        {primaryIndications && primaryIndications.length > 0 ? (
                                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                                {primaryIndications.map((indication: { id: number; name: string }) => {
                                                    const isChecked = data.primary_indications.includes(indication.name);
                                                    return (
                                                        <label
                                                            key={indication.id}
                                                            className={`flex items-center gap-2 p-2 rounded border cursor-pointer transition-colors hover:bg-accent ${isChecked ? 'bg-primary/10 border-primary' : 'border-input'}`}
                                                        >
                                                            <Checkbox
                                                                checked={isChecked}
                                                                onCheckedChange={(checked) => {
                                                                    const newValue = checked
                                                                        ? [...data.primary_indications, indication.name]
                                                                        : data.primary_indications.filter((n: string) => n !== indication.name);
                                                                    handleInputChange('primary_indications', newValue);
                                                                }}
                                                            />
                                                            <span className="text-sm">{indication.name}</span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            <p className="text-sm text-muted-foreground text-center py-4">
                                                {t('No primary indications available. Please seed the PrimaryIndicationSeeder first.')}
                                            </p>
                                        )}
                                    </div>
                                    {data.primary_indications.length > 0 && (
                                        <div className="flex flex-wrap gap-1 mt-2">
                                            {data.primary_indications.map((ind: string) => (
                                                <Badge key={ind} variant="secondary" className="text-xs">
                                                    {ind}
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                    <p className="text-xs text-muted-foreground">{t('Select the primary indications for this product')}</p>
                                </div>

                                {/* Supports */}
                                <div className="space-y-2">
                                    <Label htmlFor="supports" className="text-sm font-medium">{t('Supports')}</Label>
                                    <Textarea
                                        id="supports"
                                        value={data.supports}
                                        onChange={(e) => handleInputChange('supports', e.target.value)}
                                        rows={2}
                                        placeholder={t('e.g., Liver drainage pathways, detoxification...')}
                                    />
                                </div>

                                {/* Useful For */}
                                <div className="space-y-2">
                                    <Label htmlFor="useful_for" className="text-sm font-medium">{t('Useful For')}</Label>
                                    <Textarea
                                        id="useful_for"
                                        value={data.useful_for}
                                        onChange={(e) => handleInputChange('useful_for', e.target.value)}
                                        rows={2}
                                        placeholder={t('e.g., General detox protocols, digestive support...')}
                                    />
                                </div>

                                {/* Ingredients */}
                                <div className="space-y-2">
                                    <Label htmlFor="ingredients" className="text-sm font-medium">{t('Key Active Ingredients')}</Label>
                                    <Input
                                        id="ingredients"
                                        value={data.ingredients}
                                        onChange={(e) => handleInputChange('ingredients', e.target.value)}
                                        placeholder={t('e.g., Milk Thistle, Dandelion Root, Turmeric')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('Separate ingredients with commas')}</p>
                                </div>

                                {/* Contraindications */}
                                <div className="space-y-2">
                                    <Label htmlFor="contraindications" className="text-sm font-medium">{t('Contraindications / Warnings')}</Label>
                                    <Textarea
                                        id="contraindications"
                                        value={data.contraindications}
                                        onChange={(e) => handleInputChange('contraindications', e.target.value)}
                                        rows={2}
                                        placeholder={t('e.g., Do not use during pregnancy.')}
                                    />
                                </div>

                                {/* Research Links */}
                                <div className="space-y-2">
                                    <Label htmlFor="research_links" className="text-sm font-medium">{t('Research / Studies Links')}</Label>
                                    <Textarea
                                        id="research_links"
                                        value={data.research_links}
                                        onChange={(e) => handleInputChange('research_links', e.target.value)}
                                        rows={2}
                                        placeholder={t('Enter URLs one per line')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('Only links from catalog or standard references')}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ===== Dosing Schedule ===== */}
                        <Card className="border-green-200 dark:border-green-900">
                            <CardHeader className="bg-green-50 dark:bg-green-950/50 rounded-t-lg">
                                <CardTitle className="flex items-center gap-2 text-green-700 dark:text-green-400">
                                    <Clock className="h-5 w-5" />
                                    {t('Dosing Schedule')}
                                </CardTitle>
                                <CardDescription>{t('Standard dosing for each time slot')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 pt-4">
                                <div className="flex items-center gap-2 p-3 bg-muted rounded-md text-sm text-muted-foreground">
                                    <Info className="h-4 w-4 shrink-0" />
                                    {t('Enter standard dosing for each time slot exactly as written in catalog.')}
                                </div>

                                <div className="grid grid-cols-1 gap-3">
                                    {dosingFields.map(({ key, label, icon }) => (
                                        <div key={key} className="flex items-center gap-2">
                                            <span className="text-lg w-6 text-center shrink-0">{icon}</span>
                                            <Input
                                                value={data[key as keyof typeof data] as string}
                                                onChange={(e) => handleInputChange(key, e.target.value)}
                                                placeholder={label}
                                                disabled={dosingDisabled}
                                                className={!dosingDisabled ? '' : 'opacity-50 bg-muted'}
                                            />
                                        </div>
                                    ))}
                                </div>

                                {/* Dosing N/A Toggle */}
                                <div className="flex items-center gap-3 pt-2 border-t">
                                    <Checkbox
                                        id="dosing_na"
                                        checked={data.dosing_na}
                                        onCheckedChange={(checked) => {
                                            handleInputChange('dosing_na', !!checked);
                                            if (checked) {
                                                // Clear dosing fields when N/A
                                                handleInputChange('dosing_upon_rising', '');
                                                handleInputChange('dosing_breakfast', '');
                                                handleInputChange('dosing_between_meals_am', '');
                                                handleInputChange('dosing_lunch', '');
                                                handleInputChange('dosing_between_meals_pm', '');
                                                handleInputChange('dosing_dinner', '');
                                                handleInputChange('dosing_before_sleep', '');
                                            }
                                        }}
                                    />
                                    <Label htmlFor="dosing_na" className="text-sm font-medium cursor-pointer">
                                        <strong>N/A</strong> — {t('No dosing information')}
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        {/* ===== Practitioner Notes & Customizations ===== */}
                        <Card className="border-amber-200 dark:border-amber-900">
                            <CardHeader className="bg-amber-50 dark:bg-amber-950/50 rounded-t-lg">
                                <CardTitle className="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                                    <Stethoscope className="h-5 w-5" />
                                    {t('Practitioner Notes & Customizations')}
                                </CardTitle>
                                <CardDescription>{t('Visible only to your store customers')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 pt-4">
                                {/* Practitioner Notes */}
                                <div className="space-y-2">
                                    <Label htmlFor="practitioner_notes" className="text-sm font-medium">{t('Practitioner Notes')}</Label>
                                    <Textarea
                                        id="practitioner_notes"
                                        value={data.practitioner_notes}
                                        onChange={(e) => handleInputChange('practitioner_notes', e.target.value)}
                                        rows={3}
                                        placeholder={t('Add notes for your practitioners about this product...')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('Visible only to your store customers')}</p>
                                </div>

                                {/* Custom Primary Indications */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">{t('Custom Primary Indications')}</Label>
                                    <Textarea
                                        value={data.custom_primary_indications.join('\n')}
                                        onChange={(e) => handleInputChange('custom_primary_indications', e.target.value.split('\n').filter(v => v.trim()))}
                                        rows={2}
                                        placeholder={t('Enter custom indications, one per line')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('Select from list or type to add custom indications')}</p>
                                </div>

                                {/* Custom Dosing Notes */}
                                <div className="space-y-2">
                                    <Label htmlFor="custom_dosing_notes" className="text-sm font-medium">{t('Custom Dosing Notes')}</Label>
                                    <Textarea
                                        id="custom_dosing_notes"
                                        value={data.custom_dosing_notes}
                                        onChange={(e) => handleInputChange('custom_dosing_notes', e.target.value)}
                                        rows={3}
                                        placeholder={t('e.g., Take 30 minutes before meals with warm water...')}
                                    />
                                    <p className="text-xs text-muted-foreground">{t('Add custom dosing instructions for your patients')}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* ===== Submit Buttons ===== */}
                <div className="flex justify-end space-x-4 pt-4 border-t">
                    <Button type="button" variant="outline" onClick={() => window.history.back()}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {t('Create Product')}
                    </Button>
                </div>
            </form>
        </PageTemplate>
    );
}
