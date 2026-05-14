import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useState, useRef, useCallback, useEffect } from 'react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import MediaPicker from '@/components/MediaPicker';
import { FileText, Save } from 'lucide-react';
import Template1 from '@/pages/quotes/templates/Template1';
import Template2 from '@/pages/quotes/templates/Template2';
import Template3 from '@/pages/quotes/templates/Template3';
import Template4 from '@/pages/quotes/templates/Template4';
import Template5 from '@/pages/quotes/templates/Template5';
import Template6 from '@/pages/quotes/templates/Template6';
import Template7 from '@/pages/quotes/templates/Template7';
import Template8 from '@/pages/quotes/templates/Template8';
import Template9 from '@/pages/quotes/templates/Template9';
import Template10 from '@/pages/quotes/templates/Template10';
import { SettingsSection } from '@/components/settings-section';
import IframePortal from '@/components/IframePortal';

interface QuoteTemplateSettingsProps { }

const templateComponents = {
    template1: Template1,
    template2: Template2,
    template3: Template3,
    template4: Template4,
    template5: Template5,
    template6: Template6,
    template7: Template7,
    template8: Template8,
    template9: Template9,
    template10: Template10,
};

const mockData = {
    quote: {
        quote_id: '00001',
        quote_number: 'QT-2024-000001',
        issue_date: new Date().toISOString().split('T')[0],
        due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        date_quoted: new Date().toISOString().split('T')[0],
        valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        total_tax: 60,
        total_discount: 150,
        sub_total: 300,
        total_amount: 210,
        totalQuantity: 3,
        totalRate: 300,
        totalTaxPrice: 60,
        totalDiscount: 150,
        creator: {
            name: '<Creator Name>',
            email: '<Creator Email>',
        },
        billing_contact: {
            name: '<Billing Contact>',
            email: '<Billing Email>',
            phone: '<Billing Phone>',
        },
        shipping_contact: {
            name: '<Shipping Contact>',
            email: '<Shipping Email>',
            phone: '<Shipping Phone>',
        },
        billing_address: '<Billing Address>',
        billing_city: '<Billing City>',
        billing_state: '<Billing State>',
        billing_postal_code: '<Billing Zip>',
        billing_country: '<Billing Country>',
        shipping_address: '<Shipping Address>',
        shipping_city: '<Shipping City>',
        shipping_state: '<Shipping State>',
        shipping_postal_code: '<Shipping Zip>',
        shipping_country: '<Shipping Country>',
    },
    items: [
        {
            name: 'Item 1',
            quantity: 1,
            price: 100,
            discount: 50,
            itemTax: [
                { name: 'Tax 0', rate: '10 %', price: '$10' },
            ],
        },
        {
            name: 'Item 2',
            quantity: 1,
            price: 100,
            discount: 50,
            itemTax: [
                { name: 'Tax 1', rate: '10 %', price: '$10' },
            ],
        },
        {
            name: 'Item 3',
            quantity: 1,
            price: 100,
            discount: 50,
            itemTax: [
                { name: 'Tax 0', rate: '10 %', price: '$10' },
            ],
        },
    ],
    taxesData: {
        'Tax 0': 30,
        'Tax 1': 30,
    },
    settings: {
        quoteLogo: null,
    },
};

const templates = {
    template1: 'New York',
    template2: 'Toronto',
    template3: 'Rio',
    template4: 'London',
    template5: 'Istanbul',
    template6: 'Mumbai',
    template7: 'Hong Kong',
    template8: 'Tokyo',
    template9: 'Sydney',
    template10: 'Paris',
};

const colors = [
    '003580', '666666', '6676ef', 'f50102', 'f9b034',
    'fbdd03', 'c1d82f', '37a4e4', '8a7966', '6a737b',
    '050f2c', '0e3666', '3baeff', '3368e6', 'b84592',
    'f64f81', 'f66c5f', 'fac168', '46de98', '40c7d0',
    'be0028', '2f9f45', '371676', '52325d', '511378',
    '0f3866', '48c0b6', '297cc0', 'ffffff', '000000'
];

export default function QuoteTemplateSettings() {
    const { t } = useTranslation();
    const { settings } = usePage().props as any;
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const [saving, setSaving] = useState(false);
    const [settingsHeight, setSettingsHeight] = useState(0);

    const { data, setData, errors, reset } = useForm({
        quoteTemplate: settings.quoteTemplate || 'template1',
        quoteColor: settings.quoteColor || 'ffffff',
        quoteQrEnabled: settings.quoteQrEnabled === 'on' || settings.quoteQrEnabled === true,
        quoteLogoId: settings.quoteLogoId || null,
        quoteLogoUrl: settings.quoteLogo || null,
    });

    //   const settingsRef = useCallback((node: HTMLDivElement | null) => {
    //     if (node) {
    //       const measure = () => setSettingsHeight(node.offsetHeight-25);
    //       measure();
    //       setTimeout(measure, 100);
    //     }
    //   }, [data]);
    const settingsRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!settingsRef.current) return;

        const observer = new ResizeObserver(([entry]) => {
            setSettingsHeight(entry.contentRect.height);
        });

        observer.observe(settingsRef.current);

        return () => observer.disconnect();
    }, [data.quoteLogoUrl]);



    const SelectedTemplate = templateComponents[data.quoteTemplate as keyof typeof templateComponents];

    const renderPreview = () => {
        if (!SelectedTemplate) return null;

        const previewSettings = {
            ...mockData.settings,
            quoteLogo: data.quoteLogoUrl,
            logoDark: settings.logoDark,
        };

        const styles = {
            invoicePreviewMain: {
                maxWidth: '1000px',
            },
        };

        return (
            <div className="transform scale-90 origin-top pt-10 w-full">
                <IframePortal>
                    <SelectedTemplate
                        quote={mockData.quote}
                        items={mockData.items}
                        taxesData={mockData.taxesData}
                        settings={previewSettings}
                        color={data.quoteColor}
                        qr_invoice={data.quoteQrEnabled ? 'on' : 'off'}
                        qrCodeSvg={null}
                        styles={styles}
                    />
                </IframePortal>
            </div>
        );
    };

    const handleLogoSelect = (value: number | '') => {
        setData('quoteLogoId', value);

        if (!value) {
            setData('quoteLogoUrl', null);
        } else {
            fetch(route('api.media.index'))
                .then(res => res.json())
                .then(media => {
                    const item = media.find((m: any) => m.id === Number(value));
                    if (item) {
                        setData('quoteLogoUrl', item.url);
                    }
                });
        }

        setTimeout(() => {
            const node = document.querySelector('[ref]') as HTMLDivElement;
            if (node) setSettingsHeight(node.offsetHeight);
        }, 150);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);

        try {
            const { data: result } = await axios.post(route('settings.quote-template'), {
                quoteTemplate: data.quoteTemplate,
                quoteColor: data.quoteColor,
                quoteQrEnabled: data.quoteQrEnabled,
                quoteLogoId: data.quoteLogoId,
            });

            if (result.success) {
                toast.success(result.success);
                reset('quoteLogo');
            } else if (result.error) {
                toast.error(result.error);
            } else {
                toast.error(t(result.message));
            }
        } catch (e: any) {
            toast.error(t(e.response?.data?.message || 'Failed to update quote template settings'));
        } finally {
            setSaving(false);
        }
    };

    return (
        <SettingsSection
            title={t("Quote Templates")}
            description={t("Configure quote template, colors, and display options")}
            action={
                <Button type="submit" disabled={saving} form="quote-template-settings-form" size="sm">
                    <Save className="h-4 w-4 mr-2" />
                    {saving ? t('Saving...') : t('Save Changes')}
                </Button>
            }>
            <Card>
                <CardContent className='pt-6'>
                    <form onSubmit={handleSubmit} id="quote-template-settings-form" className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-[320px_1fr] lg:items-start gap-6">
                            <div ref={settingsRef} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="quote_template">{t('Quote Template')}</Label>
                                    <Select
                                        value={data.quoteTemplate}
                                        onValueChange={(value) => setData('quoteTemplate', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select template')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(templates).map(([key, name]) => (
                                                <SelectItem key={key} value={key}>
                                                    {name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.quoteTemplate && (
                                        <p className="text-sm text-red-600">{errors.quoteTemplate}</p>
                                    )}
                                </div>

                                <div className="flex items-center justify-between">
                                    <Label htmlFor="quote_qr_enabled" className="text-sm font-medium">
                                        {t('QR Display?')}
                                    </Label>
                                    <Switch
                                        id="quote_qr_enabled"
                                        checked={data.quoteQrEnabled}
                                        onCheckedChange={(checked) => setData('quoteQrEnabled', checked)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>{t('Color Input')}</Label>
                                    <div className="grid grid-cols-6 gap-1 w-50">
                                        {colors.map((color) => (
                                            <label key={color} className="cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="quoteColor"
                                                    value={color}
                                                    checked={data.quoteColor === color}
                                                    onChange={(e) => setData('quoteColor', e.target.value)}
                                                    className="sr-only"
                                                />
                                                <div
                                                    className={`w-6 h-6 border-2 ${data.quoteColor === color
                                                        ? 'border-primary ring-2 ring-primary/20'
                                                        : 'border-gray-300'
                                                        }`}
                                                    style={{ backgroundColor: `#${color}` }}
                                                />
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="quoteLogo">{t('Quote Logo')}</Label>
                                    <MediaPicker
                                        label=""
                                        value={data.quoteLogoId || ''}
                                        onChange={handleLogoSelect}
                                        placeholder={t('Select quote logo...')}
                                        showPreview={true}
                                        returnType="id"
                                    />
                                    {errors.quoteLogo && (
                                        <p className="text-sm text-red-600">{errors.quoteLogo}</p>
                                    )}
                                    <p className="text-xs text-gray-500">
                                        {t('Select a logo for quotes')}
                                    </p>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>{t('Preview')}</Label>
                                <div className="border rounded-lg overflow-y-auto overflow-x-auto bg-white lg:sticky lg:top-6" style={{ height: settingsHeight - 25 || 'auto' }}>
                                    {renderPreview()}
                                </div>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </SettingsSection>
    );
}
