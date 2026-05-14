import { useEffect, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Card } from '@/components/ui/card';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { usePage, router } from '@inertiajs/react';
import ReactCountryFlag from 'react-country-flag';
import { cn } from '@/lib/utils';
import { CreateLanguageModal } from '@/components/create-language-modal';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Plus, Trash2, Power, RefreshCw, Lock } from 'lucide-react';
import { hasRole } from '@/utils/authorization';

interface Language {
    code: string;
    name: string;
    countryCode?: string;
    enabled?: boolean;
}

interface PageProps {
    languages: Language[];
    defaultLang: string;
    defaultData: Record<string, string>;
    availableLanguages: Array<{
        code: string;
        name: string;
        countryCode: string;
        flag: string;
        enabled?: boolean;
    }>;
    isCurrentLanguageEnabled: boolean;
    auth?: {
        user?: {
            roles?: Array<{ name: string }>;
        };
    };
    [key: string]: any; // Fix for Inertia PageProps constraint
}

export default function ManageLanguagePage() {
    const { t } = useTranslation();
    const { languages, defaultLang, defaultData, availableLanguages, isCurrentLanguageEnabled, auth } = usePage<PageProps>().props;

    // Initialize selectedLang from props
    const [selectedLang, setSelectedLang] = useState(defaultLang);
    const [labels, setLabels] = useState<{ [key: string]: string }>(defaultData);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [search, setSearch] = useState("");

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [isToggling, setIsToggling] = useState(false);

    const userRoles = auth?.roles || [];
    const isSuperAdmin = hasRole('superadmin', userRoles);

    // Update selectedLang when defaultLang changes (from Inertia navigation)
    useEffect(() => {
        setSelectedLang(defaultLang);
    }, [defaultLang]);

    // Load language data from backend when component mounts or language changes
    useEffect(() => {
        // If defaultData is already available for the current language, use it
        if (selectedLang === defaultLang && Object.keys(defaultData).length > 0) {
            setLabels(defaultData);
            return;
        }

        setLoading(true);
        fetch(`${route('language.load')}?lang=${selectedLang}`)
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    setLabels(res.data);
                } else {
                    setLabels({});
                }
                setLoading(false);
            })
            .catch(() => {
                setLabels({});
                setLoading(false);
                toast.error(t('Failed to load language file'));
            });
    }, [selectedLang, defaultLang, defaultData, t]);

    const handleLabelChange = (key: string, value: string) => {
        setLabels((prev) => ({ ...prev, [key]: value }));
    };

    // Save language data to backend
    const handleSave = (e) => {
        // Prevent default form submission behavior
        if (e) e.preventDefault();

        setSaving(true);

        // Use fetch instead of Inertia to prevent page refresh
        fetch(route('language.save'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest', // Add this to ensure Laravel detects AJAX request
            },
            body: JSON.stringify({
                _method: 'PATCH', // Laravel method spoofing for PATCH
                lang: selectedLang,
                data: labels
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toast.success(data.success || t('Language updated successfully'));
                } else if (data.error) {
                    toast.error(data.error);
                } else {
                    toast.error(t(data.message));
                }
                setSaving(false);
            })
            .catch(error => {
                toast.error(t('Failed to update language file'));
                setSaving(false);
            });

        // Return false to prevent any form submission
        return false;
    };

    // Handle delete language
    const handleDeleteLanguage = async () => {
        try {
            const response = await fetch(route('languages.delete', selectedLang), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                toast.success('Language deleted successfully');
                setShowDeleteConfirm(false);
                // Redirect to English after deletion
                router.get(route('manage-language', { lang: 'en' }));
            } else {
                const data = await response.json();
                toast.error(data.message || 'Failed to delete language');
            }
        } catch (error) {
            toast.error('Failed to delete language');
        }
    };

    // Handle toggle language status
    const handleToggleLanguage = async () => {
        setIsToggling(true);
        try {
            const response = await fetch(route('languages.toggle', selectedLang), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                toast.success('Language status updated successfully');
                // Redirect to English after disabling current language
                router.get(route('manage-language', { lang: 'en' }));
            } else {
                const data = await response.json();
                toast.error(data.message || 'Failed to update language status');
            }
        } catch (error) {
            toast.error('Failed to update language status');
        } finally {
            setIsToggling(false);
        }
    };

    return (
        <PageTemplate
            title={t('Manage Language')}
            url="/manage-language"
            actions={[
                ...(selectedLang !== 'en' && isSuperAdmin ? [
                    {
                        label: isToggling ? t('Updating...') : (isCurrentLanguageEnabled ? t('Disable Language') : t('Enable Language')),
                        icon: isToggling ? <RefreshCw className="h-4 w-4 animate-spin" /> : <Power className="h-4 w-4" />,
                        variant: 'outline' as const,
                        onClick: handleToggleLanguage
                    },
                    {
                        label: t('Delete Language'),
                        icon: <Trash2 className="h-4 w-4" />,
                        variant: 'destructive' as const,
                        onClick: () => setShowDeleteConfirm(true)
                    }
                ] : [])
            ]}
        >
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar: Language List */}
                <div className="md:w-64 flex-shrink-0">
                    <div className="sticky top-20">
                        <ScrollArea className="h-[calc(100vh-5rem)]">
                            <div className="pr-4 space-y-1">
                                {availableLanguages.map((lang) => {
                                    const isEnabled = lang.enabled === true || (lang.enabled === undefined && !lang.hasOwnProperty('enabled'));
                                    return (
                                        <Button
                                            key={lang.code}
                                            variant="ghost"
                                            className={cn('w-full justify-start gap-2', {
                                                'bg-muted font-medium': selectedLang === lang.code,
                                                'text-muted-foreground': !isEnabled
                                            })}
                                            onClick={() => {
                                                if (selectedLang !== lang.code) {
                                                    // Navigate to the language page using Inertia
                                                    router.get(route('manage-language', { lang: lang.code }));
                                                }
                                            }}
                                        >
                                            {lang.countryCode && (
                                                <ReactCountryFlag
                                                    countryCode={lang.countryCode}
                                                    svg
                                                    style={{ width: '1.2em', height: '1.2em' }}
                                                />
                                            )}
                                            <span className={!isEnabled ? 'text-muted-foreground' : ''}>
                                                {lang.name}
                                            </span>
                                            {!isEnabled && (
                                                <Lock className="h-3 w-3 ml-auto text-muted-foreground" />
                                            )}
                                        </Button>
                                    );
                                })}
                            </div>
                        </ScrollArea>
                    </div>
                </div>
                {/* Main Content: Language Labels */}
                <div className="flex-1">
                    <Card className="p-6">
                        <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
                            <h2 className="text-lg font-semibold">{t('Edit Labels for')} {languages.find(l => l.code === selectedLang)?.name}</h2>
                            <Input
                                placeholder={t('Search labels...')}
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="w-full md:w-72"
                            />
                        </div>
                        {loading ? (
                            <div>{t('Loading...')}</div>
                        ) : (
                            <form onSubmit={(e) => { e.preventDefault(); handleSave(e); return false; }}>
                                <div className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {Object.entries(labels)
                                            .filter(([key, value]) =>
                                                key.toLowerCase().includes(search.toLowerCase()) ||
                                                value.toLowerCase().includes(search.toLowerCase())
                                            )
                                            .map(([key, value]) => (
                                                <div key={key} className="flex flex-col gap-1">
                                                    <label className="text-xs text-muted-foreground truncate mb-1">{key}</label>
                                                    <Input
                                                        className="w-full"
                                                        value={value}
                                                        onChange={e => handleLabelChange(key, e.target.value)}
                                                    />
                                                </div>
                                            ))}
                                    </div>
                                    <div className="pt-6 text-right">
                                        <Button
                                            type="submit"
                                            disabled={saving}
                                        >
                                            {saving ? (
                                                <span className="flex items-center gap-2"><span className="animate-spin h-4 w-4 border-2 border-t-transparent border-primary rounded-full"></span>{t('Saving...')}</span>
                                            ) : t('Save Changes')}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        )}
                    </Card>
                </div>
            </div>



            <Dialog open={showDeleteConfirm} onOpenChange={setShowDeleteConfirm}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Language')}</DialogTitle>
                    </DialogHeader>
                    <p>{t('Are you sure you want to delete the')} <strong>{selectedLang}</strong> {t('language? This will remove all translation files and cannot be undone.')}.</p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowDeleteConfirm(false)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDeleteLanguage}>
                            {t('Delete Language')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
