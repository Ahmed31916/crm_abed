import { toast } from '@/components/custom-toast';
import { SettingsSection } from '@/components/settings-section';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getDisplayUrl } from '@/utils/helper';
import { router, usePage } from '@inertiajs/react';
import { Lightbulb, Save, Search, Upload, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface SeoSettingsProps {
    settings?: Record<string, string>;
}

export default function SeoSettings({ settings = {} }: SeoSettingsProps) {
    const { t } = useTranslation();
    const pageProps = usePage().props as any;

    const defaultSettings = {
        metaKeywords: '',
        metaDescription: '',
        metaImage: 'seo/seo-banner.jpg',
        siteName: '',
    };

    const settingsData = Object.keys(settings).length > 0 ? settings : pageProps.settings || {};

    const [seoSettings, setSeoSettings] = useState(() => ({
        metaKeywords: settingsData.metaKeywords || defaultSettings.metaKeywords,
        metaDescription: settingsData.metaDescription || defaultSettings.metaDescription,
        metaImage: settingsData.metaImage || defaultSettings.metaImage,
        siteName: settingsData.siteName || settingsData.appName || defaultSettings.siteName,
    }));

    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [imageError, setImageError] = useState(false);
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [originalImage] = useState<string>(settingsData.metaImage || '');
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        if (Object.keys(settingsData).length > 0) {
            const mergedSettings = Object.keys(defaultSettings).reduce(
                (acc, key) => {
                    acc[key] = settingsData[key] || (defaultSettings as Record<string, string>)[key];
                    return acc;
                },
                {} as Record<string, string>,
            );
            setSeoSettings((prev) => ({ ...prev, ...mergedSettings }));
        }
    }, [settingsData]);

    const handleSeoSettingsChange = (field: string, value: string) => {
        setSeoSettings((prev) => ({ ...prev, [field]: value }));
    };

    const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            toast.error('Please select a valid image file');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Image size should be less than 5MB');
            return;
        }
        setImageError(false);
        setImagePreview(URL.createObjectURL(file));
        setImageFile(file);
    };

    const removeImage = () => {
        setImagePreview(null);
        setImageError(false);
        setImageFile(null);
        // Revert back to the original saved image instead of clearing completely
        setSeoSettings((prev) => ({ ...prev, metaImage: originalImage }));
    };

    const submitSeoSettings = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);
        if (!seoSettings.metaKeywords.trim()) {
            setIsSaving(false);
            toast.error(t('Meta Keywords is required'));
            return;
        }
        if (!seoSettings.metaDescription.trim()) {
            setIsSaving(false);
            toast.error(t('Meta Description is required'));
            return;
        }
        if (!seoSettings.metaImage.trim() && !imageFile) {
            setIsSaving(false);
            toast.error(t('Meta Image is required'));
            return;
        }
        router.post(
            route('settings.seo.update'),
            {
                metaKeywords: seoSettings.metaKeywords,
                metaDescription: seoSettings.metaDescription,
                metaImage: imageFile || seoSettings.metaImage,
            },
            {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: (page) => {
                    setIsSaving(false);
                    const flash = page.props.flash as any;
                    if (flash?.success) {
                        toast.success(flash.success);
                        setImageFile(null);
                        setImagePreview(null);
                    } else if (flash?.error) {
                        toast.error(flash.error);
                    }
                },
                onError: (errors) => {
                    setIsSaving(false);
                    toast.error(errors.error || Object.values(errors).join(', ') || t('Failed to update SEO settings'));
                },
            },
        );
    };

    const previewImageSrc = imagePreview || (seoSettings.metaImage ? getDisplayUrl(seoSettings.metaImage) : null);
    const hasImage = !!(seoSettings.metaImage || imagePreview);

    return (
        <SettingsSection
            title={t('SEO Settings')}
            description={t("Configure SEO settings to improve your website's search engine visibility")}
            action={
                <Button type="submit" form="seo-settings-form" size="sm" className="gap-2 bg-green-600 text-white hover:bg-green-700" disabled={isSaving}>
                    <Save className="h-4 w-4" />
                    {isSaving ? t('Saving...') : t('Save Changes')}
                </Button>
            }
        >
            <Card>
                <CardContent className='mt-6'>
                    <form id="seo-settings-form" onSubmit={submitSeoSettings}>
                        {/* Two-column grid: 3/5 form | 2/5 preview */}
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">

                            {/* ── LEFT: form fields ── */}
                            <div className="space-y-5 lg:col-span-3">

                                {/* Meta Keywords */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="metaKeywords">
                                        {t('Meta Keywords')} <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="metaKeywords"
                                        type="text"
                                        value={seoSettings.metaKeywords}
                                        onChange={(e) => handleSeoSettingsChange('metaKeywords', e.target.value)}
                                        placeholder={t('Enter keywords separated by commas')}
                                    />
                                    <p className="text-muted-foreground text-xs">
                                        {t('Use relevant keywords that describe your content. Separate multiple keywords with commas.')}
                                    </p>
                                </div>

                                {/* Meta Description */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="metaDescription">
                                        {t('Meta Description')} <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="relative">
                                        <Textarea
                                            id="metaDescription"
                                            value={seoSettings.metaDescription}
                                            onChange={(e) => handleSeoSettingsChange('metaDescription', e.target.value)}
                                            placeholder={t('Enter a brief description for search engines (max 160 characters)')}
                                            maxLength={160}
                                            rows={4}
                                            className="pb-6"
                                        />
                                        <span className="text-muted-foreground pointer-events-none absolute right-3 bottom-2 text-xs">
                                            {seoSettings.metaDescription.length}/160
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        {t('Write a compelling description that summarizes your page content and encourages clicks from search results.')}
                                    </p>
                                </div>

                                {/* Meta Image */}
                                <div className="space-y-1.5">
                                    <Label htmlFor="metaImageUpload">
                                        {t('Meta Image')} <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="border-input flex h-10 w-full items-center overflow-hidden rounded-md border bg-transparent text-sm">
                                        <label htmlFor="metaImageUpload" className="flex flex-1 cursor-pointer items-center gap-2 px-3 py-2 select-none">
                                            <Upload className="text-muted-foreground h-4 w-4 shrink-0" />
                                            <span className="text-muted-foreground">{hasImage ? t('Change Image') : t('Upload Image')}</span>
                                        </label>
                                        <Input id="metaImageUpload" type="file" accept="image/*" className="hidden" onChange={handleImageUpload} />
                                        {hasImage && (
                                            <>
                                                <div className="bg-border h-full w-px" />
                                                <button
                                                    type="button"
                                                    onClick={removeImage}
                                                    className="text-muted-foreground hover:text-foreground flex h-full w-10 shrink-0 cursor-pointer items-center justify-center"
                                                    aria-label="Remove image"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground text-xs">{t('Recommended size: 1200x630px for optimal social media sharing.')}</p>
                                </div>
                            </div>

                            {/* ── RIGHT: SEO Preview panel ── */}
                            <div className="lg:col-span-2">
                                <div className="h-full space-y-3 rounded-xl border p-4">

                                    {/* Panel header */}
                                    <div className="flex items-center gap-2 text-sm font-semibold">
                                        <Search className="text-muted-foreground h-4 w-4" />
                                        <span>{t('SEO Preview')}</span>
                                    </div>

                                    {/* Social Media Preview card */}
                                    <div className="rounded-lg border overflow-hidden">

                                        {/* Card title */}
                                        <div className="px-3 pt-3 pb-2">
                                            <p className="text-muted-foreground text-xs font-semibold">{t('Social Media Preview')}</p>
                                        </div>

                                        {/* Image row: gray side bars + white image area in center */}
                                        <div className="flex items-stretch bg-gray-200 dark:bg-gray-700/50">
                                            {/* Left gray bar */}
                                            <div className="w-8 shrink-0 bg-gray-200 dark:bg-gray-600" />

                                            {/* Image area */}
                                            <div className="flex flex-1 aspect-[1200/630] items-center justify-center overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                                                {previewImageSrc && !imageError ? (
                                                    <img
                                                        src={previewImageSrc}
                                                        alt="Meta Image Preview"
                                                        className="h-full w-full object-cover"
                                                        onError={() => setImageError(true)}
                                                    />
                                                ) : (
                                                    <div className="text-muted-foreground flex h-full w-full flex-col items-center justify-center gap-2 border border-dashed bg-slate-50 dark:bg-slate-800">
                                                        <Upload className="h-8 w-8 opacity-25" />
                                                        <span className="text-xs">1200 × 630px</span>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Right gray bar */}
                                            <div className="w-8 shrink-0 bg-gray-200 dark:bg-gray-600" />
                                        </div>

                                        {/* Description text */}
                                        <div className="px-3 py-3">
                                            <p className="text-muted-foreground line-clamp-3 text-xs leading-relaxed">
                                                {seoSettings.metaDescription || (
                                                    <span className="italic">{t('Your meta description will appear here…')}</span>
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    {/* SEO Tips */}
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/20">
                                        <div className="mb-2 flex items-center gap-1.5">
                                            <Lightbulb className="h-3.5 w-3.5 text-blue-500" />
                                            <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">{t('SEO Tips')}</span>
                                        </div>
                                        <ul className="space-y-1 text-xs">
                                            <li className="text-blue-600 dark:text-blue-400">
                                                <span className="font-medium">{t('Keywords:')}</span> <span>{t('Use 3-5 relevant keywords')}</span>
                                            </li>
                                            <li className="text-blue-600 dark:text-blue-400">
                                                <span className="font-medium">{t('Description:')}</span> <span>{t('150-160 characters')}</span>
                                            </li>
                                            <li className="text-blue-600 dark:text-blue-400">
                                                <span className="font-medium">{t('Image:')}</span> <span>{t('1200x630px works well')}</span>
                                            </li>
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </SettingsSection>
    );
}
