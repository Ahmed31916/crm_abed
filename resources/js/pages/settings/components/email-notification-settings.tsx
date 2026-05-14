import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import axios from 'axios';
import { toast } from '@/components/custom-toast';
import { Card, CardContent } from '@/components/ui/card';

interface NotificationItem {
    name: string;
    label: string;
    description?: string;
}

export default function EmailNotificationSettings() {
    const { t } = useTranslation();
    const [notifications, setNotifications] = useState<Record<string, boolean>>({});
    const [availableNotifications, setAvailableNotifications] = useState<NotificationItem[]>([]);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        // Load available notifications
        axios.get(route('settings.email-notifications.available'))
            .then(response => {
                setAvailableNotifications(response.data);
            })
            .catch(error => {
            });

        // Load current settings
        axios.get(route('settings.email-notifications.get'))
            .then(response => {
                setNotifications(response.data);
            })
            .catch(error => {
            });
    }, []);

    const handleToggle = (key: string, enabled: boolean) => {
        setNotifications(prev => ({
            ...prev,
            [key]: enabled
        }));
    };

    const handleSave = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        toast.loading(t('Saving email notification settings...'));
        router.post(route('settings.email-notifications.update'), notifications, {
            preserveScroll: true,
            onSuccess: (page) => {
                setProcessing(false);
                toast.dismiss();
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                } else if (errorMessage) {
                    toast.error(errorMessage);
                } else {
                    toast.success('Email notification settings updated successfully.');
                }
            },
            onError: () => {
                setProcessing(false);
                toast.error('Failed to update email notification settings.');
            }
        });
    };

    return (
        <SettingsSection
            title={t("Email Notification Settings")}
            description={t("Configure which email notifications are sent")}
            action={
                <Button onClick={handleSave} disabled={processing} size="sm">
                    <Save className="h-4 w-4 mr-2" />
                    {processing ? t('Saving...') : t('Save Changes')}
                </Button>
            }
        >
            <Card>
                <CardContent className="pt-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {availableNotifications.map(item => (
                            <div key={item.name} className="flex items-center justify-between p-4 border rounded-md">
                                <div>
                                    <Label htmlFor={item.name} className="text-sm font-medium">
                                        {t(item.label)}
                                    </Label>
                                </div>
                                <Switch
                                    id={item.name}
                                    checked={notifications[item.name] || false}
                                    onCheckedChange={(checked) => handleToggle(item.name, checked)}
                                />
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </SettingsSection>
    );
}
