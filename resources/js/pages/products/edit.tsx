import { PageTemplate } from '@/components/page-template';
import { usePage, useForm } from '@inertiajs/react';
import { ArrowLeft, Heart, Clock, Stethoscope, Info, Lock } from 'lucide-react';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { useState, useEffect } from 'react';

export default function ProductEdit() {
    const { t } = useTranslation();
    const {
        product,
        categories,
        brands,
        taxes,
        users,
        tags,
        primaryIndications,
        availableProducts,
        healthProduct,
        override,
        isSuperAdminProduct,
        canEditOriginal,
        auth,
        mainImage,
        additionalImages
    } = usePage().props as any;

    const isCompany = auth?.user?.type === 'company';

    // If a COMPANY user is editing a super admin product, many fields are read-only.
    // Super admin can always edit ALL fields on any product.
    // Only: description, sale_price, category, contraindications, research_links,
    //       primary_indications, dosing schedule, tags, and practitioner fields are editable by company users
    const isLocked = isSuperAdminProduct && isCompany;

    const validMainImageId = product.main_image_id && mainImage ? product.main_image_id : null;
    const validAdditionalImageIds = product.additional_image_ids && additionalImages
        ? product.additional_image_ids.filter((id: number) => additionalImages.some((img: any) => img.id === id))
        : [];

    // Helper: get override value or fall back to original
    const getEffectiveValue = (field: string, originalValue: any) => {
        if (override && override[field] !== null && override[field] !== undefined) {
            return override[field];
        }
        return originalValue;
    };

    const { data, setData, setError, put, processing, errors } = useForm({
        // ===== Basic Product Fields =====
        name: product.name || '',
        description: getEffectiveValue('description', product.description || ''),
        specification: product.specification || '',
        detail: product.detail || '',
        price: product.price?.toString() || '',
        sale_price: (isLocked ? getEffectiveValue('sale_price_override', product.sale_price || '') : (product.sale_price?.toString() || ''))?.toString() || '',
        category_id: getEffectiveValue('category_id', product.category_id?.toString() || ''),
        brand_id: product.brand_id?.toString() || '',
        tax_id: product.tax_id?.toString() || '',
        status: product.status || 'active',
        stock_status: product.stock_status || 'in_stock',
        stock_quantity: product.stock_quantity?.toString() || '0',
        product_weight: product.product_weight?.toString() || '',

        // ===== SKU (read-only after creation) =====
        product_sku: healthProduct?.sku || '',

        // ===== Product Form & Size =====
        product_form: healthProduct?.product_form || '',
        bottle_size: healthProduct?.bottle_size?.toString() || '',
        product_image_url: healthProduct?.product_image_url || '',

        // ===== Full Name =====
        full_name: healthProduct?.full_name || '',

        // ===== Health Product Fields =====
        supports: healthProduct?.supports || '',
        useful_for: healthProduct?.useful_for || '',
        ingredients: healthProduct?.ingredients || '',
        contraindications: getEffectiveValue('contraindications', healthProduct?.contraindications || ''),
        research_links: getEffectiveValue('research_links', healthProduct?.research_links || ''),

        // ===== Dosing Schedule =====
        dosing_upon_rising: getEffectiveValue('dosing_upon_rising', healthProduct?.dosing_upon_rising || ''),
        dosing_breakfast: getEffectiveValue('dosing_breakfast', healthProduct?.dosing_breakfast || ''),
        dosing_between_meals_am: getEffectiveValue('dosing_between_meals_am', healthProduct?.dosing_between_meals_am || ''),
        dosing_lunch: getEffectiveValue('dosing_lunch', healthProduct?.dosing_lunch || ''),
        dosing_between_meals_pm: getEffectiveValue('dosing_between_meals_pm', healthProduct?.dosing_between_meals_pm || ''),
        dosing_dinner: getEffectiveValue('dosing_dinner', healthProduct?.dosing_dinner || ''),
        dosing_before_sleep: getEffectiveValue('dosing_before_sleep', healthProduct?.dosing_before_sleep || ''),
        dosing_na: getEffectiveValue('dosing_na', healthProduct?.dosing_na || false),

        // ===== Tags & Pairs =====
        // FIX: تم جلب الـ tags الصحيحة من الـ backend (override الشركة إن وُجدت،
        // وإلا tags السوبر ادمن كحالة ابتدائية). لا حاجة لأي منطق إضافي هنا.
        tag_id: (product.tags || []).map((tag: any) => tag.id.toString()),
        pairs_well_with: (product.pairs_well_with_ids || []).map((id: number) => id.toString()),

        // ===== Primary Indications =====
        // FIX: استخدام getEffectiveValue لعرض primary_indications من override
        // الشركة (إن وُجدت)، وإلا نعرضها من healthProduct (السوبر ادمن).
        // قبل هذا التعديل، كانت الـ form تعرض دائماً primary_indications الخاصة
        // بالسوبر ادمن، مما يجعل التعديلات التي قامت بها الشركة تختفي عند
        // إعادة تحميل الصفحة.
        primary_indications: getEffectiveValue('primary_indications', healthProduct?.primary_indications || []),

        // ===== Practitioner / Company Override Exclusive =====
        practitioner_notes: override?.practitioner_notes || '',
        custom_primary_indications: override?.custom_primary_indications || [],
        custom_dosing_notes: override?.custom_dosing_notes || '',

        // ===== Images =====
        main_image_id: validMainImageId as number | null,
        additional_image_ids: validAdditionalImageIds as number[],

    });

    const [dosingDisabled, setDosingDisabled] = useState(!!data.dosing_na);

    useEffect(() => {
        setDosingDisabled(!!data.dosing_na);
    }, [data.dosing_na]);

    const handleInputChange = (name: string, value: string | boolean | string[] | number | number[] | null) => {
        setData(name as any, value as any);
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Products'), href: route('products.index') },
        { title: product.name, href: route('products.show', product.id) },
        { title: t('Edit') }
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
            // Skip locked fields from validation (they're read-only)
            if (isLocked && ['name', 'product_sku', 'product_form', 'bottle_size', 'price', 'main_image_id'].includes(name)) {
                return;
            }
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

        toast.loading(t('Updating product...'));

        put(route('products.update', product.id), {
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

    // Locked Field Component - shows value as read-only with lock icon
    const LockedField = ({ label, value, hint }: { label: string; value: string; hint?: string }) => (
        <div className="space-y-2">
            <Label className="text-sm font-medium">{label}</Label>
            <div className="flex items-center gap-2 px-3 py-2 bg-muted rounded-md border">
                <span className="flex-1 text-sm">{value || '—'}</span>
                <Lock className="h-4 w-4 text-muted-foreground" />
            </div>
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </div>
    );

    return (
        <PageTemplate
            title={t('Edit Product')}
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
            {/* Super Admin Product Alert - Only shown for company users editing super admin products */}
            {isLocked && (
                <Alert className="mb-6 border-blue-300 bg-blue-50 dark:bg-blue-950/30">
                    <Info className="h-4 w-4 text-blue-600" />
                    <AlertDescription className="text-blue-700 dark:text-blue-400">
                        {t('You are editing a Super Admin product. Some fields are read-only. Your changes will be saved as company overrides.')}
                    </AlertDescription>
                </Alert>
            )}

            <form onSubmit={handleSubmit} className="max-w-6xl mx-auto space-y-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* ============================================ */}
                    {/* LEFT COLUMN - Main Product Info (2 cols) */}
                    {/* ============================================ */}
                    <div className="lg:col-span-2 space-y-6">

                        {/* ===== Basic Information ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {t('Basic Information')}
                                    {isLocked && (
                                        <Badge variant="secondary" className="text-xs font-normal">
                                            <Lock className="h-3 w-3 mr-1" />
                                            {t('Partial Edit')}
                                        </Badge>
                                    )}
                                </CardTitle>
                                <CardDescription>{t('Product name, category, SKU, and pricing')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Name */}
                                {isLocked ? (
                                    <LockedField label={t('Product Name')} value={data.name} />
                                ) : (
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
                                )}

                                {/* Category + SKU */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* Category - always editable */}
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

                                    {/* SKU - always read-only after creation */}
                                    <LockedField
                                        label={t('Product SKU / Item Number')}
                                        value={data.product_sku}
                                        hint={t('SKU cannot be changed after creation')}
                                    />
                                </div>

                                {/* Product Form + Bottle Size + Brand */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {isLocked ? (
                                        <LockedField label={t('Product Form')} value={data.product_form} />
                                    ) : (
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
                                    )}

                                    {isLocked ? (
                                        <LockedField label={t('Bottle Size / Count')} value={data.bottle_size} />
                                    ) : (
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
                                                className={errors.bottle_size ? 'border-red-500' : ''}
                                            />
                                            {errors.bottle_size && <p className="text-xs text-red-500">{errors.bottle_size}</p>}
                                        </div>
                                    )}

                                    {/* Brand */}
                                    {isLocked ? (
                                        <LockedField label={t('Supplier / Brand')} value={brands?.find((b: any) => b.id.toString() === data.brand_id)?.name || ''} />
                                    ) : (
                                        <div className="space-y-2">
                                            <Label className="text-sm font-medium">{t('Supplier / Brand')}</Label>
                                            <Select value={data.brand_id} onValueChange={(value) => handleInputChange('brand_id', value)}>
                                                <SelectTrigger>
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
                                        </div>
                                    )}
                                </div>

                                {/* Tags - always editable */}
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
                                    </div>
                                    {errors.tag_id && <p className="text-xs text-red-500">{errors.tag_id}</p>}
                                </div>

                                {/* Pairs Well With - read-only for super admin products */}
                                {isLocked ? (
                                    <LockedField
                                        label={t('Pairs Well With')}
                                        value={availableProducts
                                            ?.filter((p: any) => data.pairs_well_with.includes(p.id.toString()))
                                            ?.map((p: any) => p.name)
                                            ?.join(', ') || t('None')}
                                    />
                                ) : (
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">{t('Pairs Well With')}</Label>
                                        <div className="flex flex-wrap gap-2 border rounded-md p-3 min-h-[42px]">
                                            {availableProducts?.map((prod: any) => (
                                                <label key={prod.id} className="inline-flex items-center gap-1.5 cursor-pointer">
                                                    <Checkbox
                                                        checked={data.pairs_well_with.includes(prod.id.toString())}
                                                        onCheckedChange={(checked) => {
                                                            const productId = prod.id.toString();
                                                            if (checked) {
                                                                handleInputChange('pairs_well_with', [...data.pairs_well_with, productId]);
                                                            } else {
                                                                handleInputChange('pairs_well_with', data.pairs_well_with.filter((id: string) => id !== productId));
                                                            }
                                                        }}
                                                    />
                                                    <Badge variant="outline" className="cursor-pointer hover:bg-accent">
                                                        {prod.name}
                                                    </Badge>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Price + Sale Price */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {isLocked ? (
                                        <LockedField label={t('Price')} value={data.price} />
                                    ) : (
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
                                    )}

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
                                        {isLocked && (
                                            <p className="text-xs text-blue-600 flex items-center gap-1">
                                                <Info className="h-3 w-3" />
                                                {t('Your sale price override will be saved as company override')}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* Stock Status - locked for super admin products */}
                                {isLocked ? (
                                    <LockedField label={t('Stock Status')} value={data.stock_status === 'in_stock' ? t('In Stock') : data.stock_status === 'out_of_stock' ? t('Out of Stock') : t('On Backorder')} />
                                ) : (
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
                                )}

                                {/* Stock Quantity - locked for super admin products */}
                                {isLocked ? (
                                    <LockedField label={t('Stock Quantity')} value={data.stock_quantity} />
                                ) : (
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
                                )}

                                {/* Status + Tax + Weight */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    {/* Status - locked for super admin products */}
                                    {isLocked ? (
                                        <LockedField label={t('Status')} value={data.status === 'active' ? t('Active') : t('Inactive')} />
                                    ) : (
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
                                    )}
                                    <div className="space-y-2">
                                        <Label className="text-sm font-medium">{t('Tax')}</Label>
                                        <Select value={data.tax_id} onValueChange={(value) => handleInputChange('tax_id', value)}>
                                            <SelectTrigger>
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
                                    </div>
                                    {isLocked ? (
                                        <LockedField label={t('Weight')} value={data.product_weight} />
                                    ) : (
                                        <div className="space-y-2">
                                            <Label htmlFor="product_weight" className="text-sm font-medium">{t('Weight')}</Label>
                                            <Input
                                                id="product_weight"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={data.product_weight}
                                                onChange={(e) => handleInputChange('product_weight', e.target.value)}
                                            />
                                        </div>
                                    )}
                                </div>

                                {/* Full Name */}
                                {isLocked ? (
                                    <LockedField label={t('Full Product Name')} value={data.full_name} />
                                ) : (
                                    <div className="space-y-2">
                                        <Label htmlFor="full_name" className="text-sm font-medium">{t('Full Product Name')}</Label>
                                        <Input
                                            id="full_name"
                                            value={data.full_name}
                                            onChange={(e) => handleInputChange('full_name', e.target.value)}
                                            placeholder={t('Full product name with brand/line details')}
                                        />
                                        <p className="text-xs text-muted-foreground">{t('If different from the short name above')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* ===== Product Images ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {t('Product Images')}
                                    {isLocked && (
                                        <Badge variant="secondary" className="text-xs font-normal">
                                            <Lock className="h-3 w-3 mr-1" />
                                            {t('Read-only')}
                                        </Badge>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {isLocked ? (
                                    <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                                        <Info className="h-4 w-4 text-blue-600" />
                                        <AlertDescription className="text-blue-700 dark:text-blue-400">
                                            {t('Product images cannot be modified for super admin products')}
                                        </AlertDescription>
                                    </Alert>
                                ) : (
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
                                )}

                                <Separator />

                                {/* Product Image URL */}
                                {isLocked ? (
                                    <LockedField
                                        label={t('Product Image URL')}
                                        value={data.product_image_url}
                                    />
                                ) : (
                                    <div className="space-y-2">
                                        <Label htmlFor="product_image_url">{t('Product Image URL')}</Label>
                                        <Input
                                            id="product_image_url"
                                            type="url"
                                            value={data.product_image_url}
                                            onChange={(e) => handleInputChange('product_image_url', e.target.value)}
                                            placeholder={t('Enter direct link to product image')}
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* ===== Product Description ===== */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Product Description')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* Description - always editable */}
                                <div className="space-y-2">
                                    <Label htmlFor="description" className="text-sm font-medium">{t('Description')}</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => handleInputChange('description', e.target.value)}
                                        rows={4}
                                        placeholder={t('Product description...')}
                                    />
                                    {isLocked && (
                                        <p className="text-xs text-blue-600 flex items-center gap-1">
                                            <Info className="h-3 w-3" />
                                            {t('Your description override will be saved as company override')}
                                        </p>
                                    )}
                                </div>

                                {isLocked ? (
                                    <>
                                        <LockedField label={t('Specification')} value={data.specification} />
                                        <LockedField label={t('Additional Details')} value={data.detail} />
                                    </>
                                ) : (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="specification" className="text-sm font-medium">{t('Specification')}</Label>
                                            <Textarea
                                                id="specification"
                                                value={data.specification}
                                                onChange={(e) => handleInputChange('specification', e.target.value)}
                                                rows={3}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="detail" className="text-sm font-medium">{t('Additional Details')}</Label>
                                            <Textarea
                                                id="detail"
                                                value={data.detail}
                                                onChange={(e) => handleInputChange('detail', e.target.value)}
                                                rows={3}
                                            />
                                        </div>
                                    </>
                                )}
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
                            </CardHeader>
                            <CardContent className="space-y-4 pt-4">
                                {/* Primary Indications - always editable (checkboxes from primary_indications table) */}
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
                                    {isLocked && (
                                        <p className="text-xs text-blue-600 flex items-center gap-1">
                                            <Info className="h-3 w-3" />
                                            {t('Your changes will be saved as company override')}
                                        </p>
                                    )}
                                </div>

                                {/* Supports - read-only for locked products */}
                                {isLocked ? (
                                    <LockedField label={t('Supports')} value={data.supports} />
                                ) : (
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
                                )}

                                {/* Useful For - read-only for locked */}
                                {isLocked ? (
                                    <LockedField label={t('Useful For')} value={data.useful_for} />
                                ) : (
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
                                )}

                                {/* Ingredients - read-only for locked */}
                                {isLocked ? (
                                    <LockedField label={t('Key Active Ingredients')} value={data.ingredients} />
                                ) : (
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
                                )}

                                {/* Contraindications - always editable */}
                                <div className="space-y-2">
                                    <Label htmlFor="contraindications" className="text-sm font-medium">{t('Contraindications / Warnings')}</Label>
                                    <Textarea
                                        id="contraindications"
                                        value={data.contraindications}
                                        onChange={(e) => handleInputChange('contraindications', e.target.value)}
                                        rows={2}
                                        placeholder={t('e.g., Do not use during pregnancy.')}
                                    />
                                    {isLocked && (
                                        <p className="text-xs text-blue-600 flex items-center gap-1">
                                            <Info className="h-3 w-3" />
                                            {t('Your changes will be saved as company override')}
                                        </p>
                                    )}
                                </div>

                                {/* Research Links - always editable */}
                                <div className="space-y-2">
                                    <Label htmlFor="research_links" className="text-sm font-medium">{t('Research / Studies Links')}</Label>
                                    <Textarea
                                        id="research_links"
                                        value={data.research_links}
                                        onChange={(e) => handleInputChange('research_links', e.target.value)}
                                        rows={2}
                                        placeholder={t('Enter URLs one per line')}
                                    />
                                    {isLocked && (
                                        <p className="text-xs text-blue-600 flex items-center gap-1">
                                            <Info className="h-3 w-3" />
                                            {t('Your changes will be saved as company override')}
                                        </p>
                                    )}
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

                                {/* Dosing N/A Toggle - always editable */}
                                <div className="flex items-center gap-3 pt-2 border-t">
                                    <Checkbox
                                        id="dosing_na"
                                        checked={data.dosing_na}
                                        onCheckedChange={(checked) => {
                                            handleInputChange('dosing_na', !!checked);
                                            if (checked) {
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

                                {isLocked && (
                                    <p className="text-xs text-blue-600 flex items-center gap-1">
                                        <Info className="h-3 w-3" />
                                        {t('Dosing changes will be saved as company override')}
                                    </p>
                                )}
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
                                {/* Practitioner Notes - always editable */}
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

                                {/* Custom Primary Indications - always editable */}
                                <div className="space-y-2">
                                    <Label className="text-sm font-medium">{t('Custom Primary Indications')}</Label>
                                    <Textarea
                                        value={data.custom_primary_indications.join('\n')}
                                        onChange={(e) => handleInputChange('custom_primary_indications', e.target.value.split('\n').filter(v => v.trim()))}
                                        rows={2}
                                        placeholder={t('Enter custom indications, one per line')}
                                    />
                                </div>

                                {/* Custom Dosing Notes - always editable */}
                                <div className="space-y-2">
                                    <Label htmlFor="custom_dosing_notes" className="text-sm font-medium">{t('Custom Dosing Notes')}</Label>
                                    <Textarea
                                        id="custom_dosing_notes"
                                        value={data.custom_dosing_notes}
                                        onChange={(e) => handleInputChange('custom_dosing_notes', e.target.value)}
                                        rows={3}
                                        placeholder={t('e.g., Take 30 minutes before meals with warm water...')}
                                    />
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
                        {t('Update Product')}
                    </Button>
                </div>
            </form>
        </PageTemplate>
    );
}
