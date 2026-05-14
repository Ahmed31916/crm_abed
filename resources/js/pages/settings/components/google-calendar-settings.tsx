import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Save, Calendar, RefreshCw } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { Card, CardContent } from '@/components/ui/card';

interface GoogleCalendarSettingsProps {
    settings?: Record<string, string>;
}

export default function GoogleCalendarSettings({ settings = {} }: GoogleCalendarSettingsProps) {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    const [isSyncing, setIsSyncing] = useState(false);
    const [formData, setFormData] = useState({
        googleCalendarEnabled: settings.googleCalendarEnabled === '1' || settings.googleCalendarEnabled === 'true',
        googleCalendarId: settings.googleCalendarId || '',
    });
    const [jsonFile, setJsonFile] = useState<File | null>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        const data = new FormData();
        data.append('googleCalendarEnabled', formData.googleCalendarEnabled ? '1' : '0');
        data.append('googleCalendarId', formData.googleCalendarId);
        if (jsonFile) {
            data.append('googleCalendarJson', jsonFile);
        }

        router.post(route('settings.google-calendar.update'), data, {
            preserveScroll: true,
            onSuccess: (page) => {
                setIsLoading(false);
                setJsonFile(null);
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                } else {
                    toast.success(t('Calendar settings updated successfully'));
                }
            },
            onError: (errors) => {
                setIsLoading(false);
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to update Google Calendar settings');
                toast.error(errorMessage);
            }
        });
    };

    const handleSync = () => {
        setIsSyncing(true);

        router.post(route('settings.google-calendar.sync'), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                }
            },
            onError: (errors) => {
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Sync failed');
                toast.error(errorMessage);
            },
            onFinish: () => {
                setIsSyncing(false);
            }
        });
    };

    return (
        <SettingsSection
            title={t("Google Calendar Settings")}
            description={t("Configure Google Calendar integration for appointment synchronization")}
            action={
                <Button type="submit" form="google-calendar-form" disabled={isLoading} size="sm">
                    <Save className="h-4 w-4 mr-2" />
                    {isLoading ? t('Saving...') : t('Save Changes')}
                </Button>
            }
        >
            <Card>
                <CardContent className='pt-6'>
                    <form id="google-calendar-form" onSubmit={handleSubmit} className="space-y-6">
                        <div className="flex items-center justify-between">
                            <div className="space-y-0.5">
                                <Label htmlFor="googleCalendarEnabled">{t("Enable Google Calendar")}</Label>
                                <p className="text-sm text-muted-foreground">
                                    {t("Enable Google Calendar integration for appointments")}
                                </p>
                            </div>
                            <Switch
                                id="googleCalendarEnabled"
                                checked={formData.googleCalendarEnabled}
                                onCheckedChange={(checked) => setFormData(prev => ({ ...prev, googleCalendarEnabled: checked }))}
                            />
                        </div>

                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="googleCalendarId">
                                    {t("Google Calendar ID")}
                                    <span className="text-red-500 ml-1">*</span>
                                </Label>
                                <Input
                                    id="googleCalendarId"
                                    type="text"
                                    value={formData.googleCalendarId}
                                    onChange={(e) => setFormData(prev => ({ ...prev, googleCalendarId: e.target.value }))}
                                    placeholder={t("Enter your Google Calendar ID or 'primary'")}
                                    disabled={!formData.googleCalendarEnabled}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="googleCalendarJson">{t("Service Account JSON File")}</Label>
                                <Input
                                    id="googleCalendarJson"
                                    type="file"
                                    accept=".json"
                                    onChange={(e) => setJsonFile(e.target.files?.[0] || null)}
                                    disabled={!formData.googleCalendarEnabled}
                                />
                                {jsonFile && (
                                    <p className="text-sm text-green-600">
                                        {t('Selected file')}: {jsonFile.name}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    {t("Upload your Google service account JSON credentials")}
                                </p>
                            </div>

                            <div className="flex justify-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleSync}
                                    disabled={!formData.googleCalendarEnabled || isSyncing}
                                >
                                    <RefreshCw className={`mr-2 h-4 w-4 ${isSyncing ? 'animate-spin' : ''}`} />
                                    {isSyncing ? t('Syncing...') : t('Test Sync')}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </SettingsSection>
    );
}
