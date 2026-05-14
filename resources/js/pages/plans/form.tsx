import { PageTemplate } from '@/components/page-template';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { ArrowLeft } from 'lucide-react';


interface Plan {
    id: number;
    name: string;
    price: number;
    yearly_price: number | null;
    duration: string;
    description: string | null;
    max_users: number;
    max_projects: number;
    max_contacts: number;
    max_accounts: number;
    storage_limit: number;
    enable_branding: string;
    enable_chatgpt: string;
    module: string[] | null;
    is_trial: string | null;
    trial_day: number;
    is_plan_enable: string;
    is_default: boolean;
}

interface Props {
    plan?: Plan;
    hasDefaultPlan?: boolean;
    otherDefaultPlanExists?: boolean;
}

export default function PlanForm({ plan, hasDefaultPlan = false, otherDefaultPlanExists = false }: Props) {
    const { t } = useTranslation();

    const isEdit = !!plan;

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        price: plan?.price || 0,
        yearly_price: plan?.yearly_price || '',
        duration: plan?.duration || 'monthly',
        description: plan?.description || '',
        max_users: plan?.max_users || 0,
        max_projects: plan?.max_projects || 0,
        max_contacts: plan?.max_contacts || 0,
        max_accounts: plan?.max_accounts || 0,
        storage_limit: plan?.storage_limit || 0,
        enable_branding: plan?.enable_branding || 'on',
        enable_chatgpt: plan?.enable_chatgpt || 'off',
        is_trial: plan?.is_trial || 'off',
        trial_day: plan?.trial_day || 0,
        is_plan_enable: plan?.is_plan_enable || 'on',
        is_default: plan?.is_default || false,
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setData(prev => ({ ...prev, [name]: value }));
    };

    const handleSwitchChange = (name: string, checked: boolean) => {
        setData(prev => ({ ...prev, [name]: checked ? 'on' : 'off' }));
    };

    const handleDefaultChange = (checked: boolean) => {
        setData(prev => ({ ...prev, is_default: checked }));
    };
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit) {
            put(route('plans.update', plan.id));
        } else {
            post(route('plans.store'));
        }
    };

    return (
        <PageTemplate
            title={t(isEdit ? "Edit Plan" : "Create Plan")}
            description={t(isEdit ? "Update subscription plan details" : "Add a new subscription plan")}
            url={isEdit ? route('plans.update', plan.id) : "/plans/create"}
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
            <div className="bg-white rounded-lg shadow p-6">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="space-y-4">
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
                                    placeholder={t("Leave empty for 20% discount")}
                                    className={errors.yearly_price ? 'border-red-500' : ''}
                                />
                                <p className="text-xs text-muted-foreground mt-1">
                                    {t("If left empty, yearly price will be calculated as 80% of monthly price × 12")}
                                </p>
                                <InputError message={errors.yearly_price} />
                            </div>

                            <div>
                                <Label htmlFor="description">{t("Description")}</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    value={data.description}
                                    onChange={handleChange}
                                    rows={3}
                                    className={errors.description ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.description} />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="max_users" required>{t("Maximum Users")}</Label>
                                <Input
                                    id="max_users"
                                    name="max_users"
                                    type="number"
                                    value={data.max_users}
                                    onChange={handleChange}
                                    className={errors.max_users ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.max_users} />
                            </div>

                            <div>
                                <Label htmlFor="max_projects" required>{t("Maximum Projects")}</Label>
                                <Input
                                    id="max_projects"
                                    name="max_projects"
                                    type="number"
                                    value={data.max_projects}
                                    onChange={handleChange}
                                    className={errors.max_projects ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.max_projects} />
                            </div>

                            <div>
                                <Label htmlFor="max_contacts" required>{t("Maximum Contacts")}</Label>
                                <Input
                                    id="max_contacts"
                                    name="max_contacts"
                                    type="number"
                                    value={data.max_contacts}
                                    onChange={handleChange}
                                    className={errors.max_contacts ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.max_contacts} />
                            </div>

                            <div>
                                <Label htmlFor="max_accounts" required>{t("Maximum Accounts")}</Label>
                                <Input
                                    id="max_accounts"
                                    name="max_accounts"
                                    type="number"
                                    value={data.max_accounts}
                                    onChange={handleChange}
                                    className={errors.max_accounts ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.max_accounts} />
                            </div>

                            <div>
                                <Label htmlFor="storage_limit" required>{t("Storage Limit (GB)")}</Label>
                                <Input
                                    id="storage_limit"
                                    name="storage_limit"
                                    type="number"
                                    step="0.01"
                                    value={data.storage_limit}
                                    onChange={handleChange}
                                    className={errors.storage_limit ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.storage_limit} />
                            </div>

                            <div>
                                <Label htmlFor="trial_day">{t("Trial Days")}</Label>
                                <Input
                                    id="trial_day"
                                    name="trial_day"
                                    type="number"
                                    value={data.trial_day}
                                    onChange={handleChange}
                                    className={errors.trial_day ? 'border-red-500' : ''}
                                />
                                <InputError message={errors.trial_day} />
                            </div>
                        </div>
                    </div>

                    <div className="border rounded-lg p-4 space-y-4">
                        <h3 className="font-medium">{t("Features")}</h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div className="flex items-center justify-between">
                                <Label htmlFor="enable_chatgpt">{t("AI Integration")}</Label>
                                <Switch
                                    id="enable_chatgpt"
                                    checked={data.enable_chatgpt === 'on'}
                                    onCheckedChange={(checked) => handleSwitchChange('enable_chatgpt', checked)}
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <Label htmlFor="is_trial">{t("Enable Trial")}</Label>
                                <Switch
                                    id="is_trial"
                                    checked={data.is_trial === 'on'}
                                    onCheckedChange={(checked) => handleSwitchChange('is_trial', checked)}
                                />
                            </div>
                        </div>


                    </div>

                    <div className="border rounded-lg p-4 space-y-4">
                        <h3 className="font-medium">{t("Settings")}</h3>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="is_plan_enable">{t("Active")}</Label>
                                <Switch
                                    id="is_plan_enable"
                                    checked={data.is_plan_enable === 'on'}
                                    onCheckedChange={(checked) => handleSwitchChange('is_plan_enable', checked)}
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
