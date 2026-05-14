import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';
import { PageTemplate } from '@/components/page-template';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Toaster } from '@/components/ui/toaster';

export default function CreateCustomPage() {
    const { t } = useTranslation();
    const { globalSettings } = usePage().props as any;

    const defaultData = {
        title: '',
        content: '',
        meta_title: '',
        meta_description: '',
        is_active: true,
        sort_order: 0
    };

    const [formData, setFormData] = useState(defaultData);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!globalSettings?.is_demo) {
            toast.loading(t('Creating page...'));
        }

        router.post(route('landing-page.custom-pages.store'), formData, {
            onSuccess: (page) => {
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (page.props.flash?.success) {
                    setFormData(defaultData);
                    toast.success(t(page.props.flash.success));
                } else if (page.props.flash?.error) {
                    toast.error(t(page.props.flash.error));
                }
            },
            onError: (errors) => {
                if (!globalSettings?.is_demo) {
                    toast.dismiss();
                }
                if (typeof errors === 'object' && errors !== null) {
                    const errorMessages = Object.values(errors).flat();
                    errorMessages.forEach((error) => {
                        toast.error(t(error as string));
                    });
                } else if (typeof errors === 'string') {
                    toast.error(t(errors));
                } else {
                    toast.error(t('Failed to create page'));
                }
            }
        });
    };

    const handleCancel = () => {
        router.get(route('landing-page.custom-pages.index'));
    };

    return (
        <PageTemplate
            title={t('Create Custom Page')}
            url="/custom-pages/create"
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('Landing Page'), href: route('landing-page') },
                { title: t('Custom Pages'), href: route('landing-page.custom-pages.index') },
                { title: t('Create') }
            ]}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => router.get(route('landing-page.custom-pages.index'))
                }
            ]}
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">{t('Page Information')}</CardTitle>
                    <CardDescription>{t('Create a new custom page for your landing site')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Page Title */}
                        <div className="space-y-2">
                            <Label htmlFor="title" className="text-sm font-medium">
                                {t('Page Title')} <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="title"
                                value={formData.title}
                                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                placeholder={t('e.g., About Us, Privacy Policy')}
                                required
                                className="w-full"
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('The title will be used to automatically generate the URL slug')}
                            </p>
                        </div>

                        {/* Content */}
                        <div className="space-y-2">
                            <Label htmlFor="content" className="text-sm font-medium">
                                {t('Content')} <span className="text-red-500">*</span>
                            </Label>
                            <div className="min-h-[300px]">
                                <RichTextEditor
                                    content={formData.content}
                                    onChange={(content) => setFormData({ ...formData, content })}
                                    placeholder={t('Write your page content here...')}
                                />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {t('Use the editor toolbar to format your content with headings, lists, links, and more')}
                            </p>
                        </div>

                        {/* SEO Section */}
                        <div className="space-y-4 pt-4 border-t">
                            <h3 className="text-sm font-semibold">{t('SEO Settings')}</h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Meta Title */}
                                <div className="space-y-2">
                                    <Label htmlFor="meta_title" className="text-sm font-medium">
                                        {t('Meta Title')}
                                    </Label>
                                    <Input
                                        id="meta_title"
                                        value={formData.meta_title}
                                        onChange={(e) => setFormData({ ...formData, meta_title: e.target.value })}
                                        placeholder={t('SEO optimized title')}
                                        maxLength={60}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t('Recommended: 50-60 characters')} ({formData.meta_title.length}/60)
                                    </p>
                                </div>

                                {/* Sort Order */}
                                <div className="space-y-2">
                                    <Label htmlFor="sort_order" className="text-sm font-medium">
                                        {t('Sort Order')}
                                    </Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        value={formData.sort_order}
                                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                                        placeholder="0"
                                        min="0"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t('Lower numbers appear first in navigation')}
                                    </p>
                                </div>
                            </div>

                            {/* Meta Description */}
                            <div className="space-y-2">
                                <Label htmlFor="meta_description" className="text-sm font-medium">
                                    {t('Meta Description')}
                                </Label>
                                <Textarea
                                    id="meta_description"
                                    value={formData.meta_description}
                                    onChange={(e) => setFormData({ ...formData, meta_description: e.target.value })}
                                    placeholder={t('Brief description for search engines')}
                                    rows={3}
                                    maxLength={160}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('Recommended: 150-160 characters')} ({formData.meta_description.length}/160)
                                </p>
                            </div>
                        </div>

                        {/* Publish Settings */}
                        <div className="space-y-4 pt-4 border-t">
                            <h3 className="text-sm font-semibold">{t('Publish Settings')}</h3>

                            <div className="flex items-start space-x-3 p-4 bg-muted/50 rounded-lg">
                                <Switch
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                                />
                                <div className="flex-1">
                                    <Label htmlFor="is_active" className="text-sm font-medium cursor-pointer">
                                        {t('Publish Page')}
                                    </Label>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {formData.is_active
                                            ? t('This page will be visible to the public immediately')
                                            : t('This page will be saved as a draft and hidden from public view')}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Form Actions */}
                        <div className="flex justify-end space-x-3 pt-6 border-t">
                            <Button type="button" variant="outline" onClick={handleCancel}>
                                {t('Cancel')}
                            </Button>
                            <Button type="submit">
                                {t('Save')}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Toaster />
        </PageTemplate>
    );
}
