import { PageTemplate } from '@/components/page-template';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { router, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';

interface Feature {
    feature_name: string;
    feature_value: string;
}

interface Plan {
    id: number;
    name: string;
    price: number;
    yearly_price: number | null;
    is_plan_enable: string;
    is_default: boolean;
    features?: Feature[];
}

interface Props {
    plan?: Plan;
    hasDefaultPlan?: boolean;
    otherDefaultPlanExists?: boolean;
}

export default function PlanForm({ plan, hasDefaultPlan = false, otherDefaultPlanExists = false }: Props) {
    const { t } = useTranslation();

    const isEdit = !!plan;

    // Initialize features from plan data (edit mode) or empty array (create mode)
    const initialFeatures: Feature[] = plan?.features?.map(f => ({
        feature_name: f.feature_name || '',
        feature_value: f.feature_value || '',
    })) || [{ feature_name: '', feature_value: '' }];

    // IMPORTANT: features MUST be inside useForm data so they get sent with the request
    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        price: plan?.price || 0,
        yearly_price: plan?.yearly_price || '',
        is_plan_enable: plan?.is_plan_enable || 'on',
        is_default: plan?.is_default || false,
        features: initialFeatures,
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const { name, value } = e.target;
        setData(prev => ({ ...prev, [name]: value }));
    };

    const handleDefaultChange = (checked: boolean) => {
        setData('is_default', checked);
    };

    // ---- Features Management ----

    const addFeature = () => {
        setData('features', [...data.features, { feature_name: '', feature_value: '' }]);
    };

    const removeFeature = (index: number) => {
        setData('features', data.features.filter((_, i) => i !== index));
    };

    const updateFeature = (index: number, field: 'feature_name' | 'feature_value', value: string) => {
        const updated = data.features.map((f, i) => i === index ? { ...f, [field]: value } : f);
        setData('features', updated);
    };

    // ---- Submit ----

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Filter out empty features before submitting
        const cleanFeatures = data.features.filter(
            f => f.feature_name.trim() !== '' || f.feature_value.trim() !== ''
        );
        setData('features', cleanFeatures);

        if (isEdit) {
            put(route('plans.update', plan!.id));
        } else {
            post(route('plans.store'));
        }
    };

    return (
        <PageTemplate
            title={t(isEdit ? "Edit Plan" : "Create Plan")}
            description={t(isEdit ? "Update subscription plan details" : "Add a new subscription plan")}
            url={isEdit ? route('plans.update', plan!.id) : "/plans/create"}
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('Plans'), href: route('plans.index') },
                { title: t(isEdit ? 'Edit Plan' : 'Create Plan') }
            ]}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => router.visit(route('plans.index'))
                }
            ]}
        >
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Plan Basic Info */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <Label htmlFor="name" required>{t("Plan Name")}</Label>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                onChange={handleChange}
                                placeholder='Pro'
                                className={errors.name ? 'border-red-500' : ''}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div>
                            <Label htmlFor="price" required>{t("Monthly Price")}</Label>
                            <Input
                                id="price"
                                name="price"
                                type="number"
                                step="0.01"
                                value={data.price}
                                onChange={handleChange}
                                className={errors.price ? 'border-red-500' : ''}
                            />
                            <InputError message={errors.price} />
                        </div>

                        <div>
                            <Label htmlFor="yearly_price">{t("Yearly Price")} <span className="text-sm text-muted-foreground">({t("Optional")})</span></Label>
                            <Input
                                id="yearly_price"
                                name="yearly_price"
                                type="number"
                                step="0.01"
                                value={data.yearly_price}
                                onChange={handleChange}
                                placeholder={t("Leave empty for auto-calculation")}
                                className={errors.yearly_price ? 'border-red-500' : ''}
                            />
                            <p className="text-xs text-muted-foreground mt-1">
                                {t("If empty, yearly price = monthly price × 12 × 0.8")}
                            </p>
                            <InputError message={errors.yearly_price} />
                        </div>
                    </div>

                    {/* Features Section */}
                    <div className="border rounded-lg p-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-medium text-gray-900 dark:text-white">{t("Plan Features / Config")}</h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addFeature}
                            >
                                <Plus className="h-4 w-4 mr-1" />
                                {t("Add Feature")}
                            </Button>
                        </div>

                        <p className="text-sm text-muted-foreground">
                            {t("Add features that describe what this plan offers. These will be displayed in the plan details and available via the config API for the desktop application.")}
                        </p>

                        <div className="space-y-3">
                            {data.features.map((feature, index) => (
                                <div key={index} className="flex items-center gap-3">
                                    <div className="flex-1">
                                        <Input
                                            placeholder={t("Feature Name (e.g. Max Users)")}
                                            value={feature.feature_name}
                                            onChange={(e) => updateFeature(index, 'feature_name', e.target.value)}
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <Input
                                            placeholder={t("Feature Value (e.g. 5)")}
                                            value={feature.feature_value}
                                            onChange={(e) => updateFeature(index, 'feature_value', e.target.value)}
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-red-500 hover:text-red-700 hover:bg-red-50 shrink-0"
                                        onClick={() => removeFeature(index)}
                                        disabled={data.features.length <= 1}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                        </div>

                        {data.features.length === 0 && (
                            <div className="text-center py-4 text-muted-foreground text-sm">
                                {t("No features added. Click 'Add Feature' to add plan features.")}
                            </div>
                        )}
                    </div>

                    {/* Settings */}
                    <div className="border rounded-lg p-4 space-y-4">
                        <h3 className="font-medium text-gray-900 dark:text-white">{t("Settings")}</h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="is_plan_enable">{t("Active")}</Label>
                                <Switch
                                    id="is_plan_enable"
                                    checked={data.is_plan_enable === 'on'}
                                    onCheckedChange={(checked) => setData('is_plan_enable', checked ? 'on' : 'off')}
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <div>
                                    <Label htmlFor="is_default">{t("Default Plan")}</Label>
                                    {(isEdit ? !plan?.is_default : hasDefaultPlan) && (
                                        <p className="text-xs text-amber-600 mt-1">
                                            {t("Setting this as default will remove default status from the current default plan.")}
                                        </p>
                                    )}
                                </div>
                                <Switch
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={handleDefaultChange}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.get(route('plans.index'))}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                        >
                            {processing ? t("Saving...") : t("Save")}
                        </Button>
                    </div>
                </form>
            </div>
        </PageTemplate>
    );
}
