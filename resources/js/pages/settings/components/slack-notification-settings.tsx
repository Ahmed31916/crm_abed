import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Save, Link, Key, Send, Bell, AlertCircle, Slack } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { SettingsSection } from '@/components/settings-section';
import axios from 'axios';
import { toast } from '@/components/custom-toast';

interface NotificationItem {
    name: string;
    label: string;
    description?: string;
}

export default function SlackNotificationSettings() {
    const { t } = useTranslation();
    const [notifications, setNotifications] = useState<Record<string, boolean>>({});
    const [availableNotifications, setAvailableNotifications] = useState<NotificationItem[]>([]);
    const [slackWebhookUrl, setSlackWebhookUrl] = useState('');
    const [isSendingMessage, setIsSendingMessage] = useState(false);
    const [testMessageResult, setTestMessageResult] = useState<{ success: boolean, message: string } | null>(null);
    const [isAvailableSlackWebhookUrl, setIsAvailableSlackWebhookUrl] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        // Load available notifications
        axios.get(route('settings.slack-notifications.available'))
            .then(response => {
                setAvailableNotifications(response.data);
            })
            .catch(error => {
            });

        // Load current settings
        axios.get(route('settings.slack-notifications.get'))
            .then(response => {
                setNotifications(response.data);
            })
            .catch(error => {
            });

        // Load Slack configuration
        axios.get(route('settings.slack-config.get'))
            .then(response => {
                setSlackWebhookUrl(response.data.slack_webhook_url || '');
                setIsAvailableSlackWebhookUrl(response.data.slack_webhook_url);
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
        setProcessing(true);
        e.preventDefault();
        const data = {
            ...notifications,
            slack_webhook_url: slackWebhookUrl
        };

        toast.loading(t('Saving slack settings...'));

        router.post(route('settings.slack-notifications.update'), data, {
            preserveScroll: true,
            onSuccess: (page) => {
                setProcessing(false);
                toast.dismiss();
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                    setIsAvailableSlackWebhookUrl(slackWebhookUrl);
                } else if (errorMessage) {
                    setSlackWebhookUrl(isAvailableSlackWebhookUrl);
                    toast.error(errorMessage);
                } else {
                    toast.success('Slack settings updated successfully.');
                }
            },
            onError: () => {
                setProcessing(false);
                toast.error('Failed to update Slack settings.');
            }
        });
    };

    const handleTestMessage = (e: React.FormEvent) => {
        e.preventDefault();

        setIsSendingMessage(true);
        setTestMessageResult(null);
        toast.loading(t('Sending test message...'));

        router.post(route('settings.slack.test'), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                setIsSendingMessage(false);
                toast.dismiss();
                const successMessage = page.props.flash?.success;
                const errorMessage = page.props.flash?.error;

                if (successMessage) {
                    toast.success(successMessage);
                    setTestMessageResult({ success: true, message: successMessage });
                } else if (errorMessage) {
                    toast.error(errorMessage);
                    setTestMessageResult({ success: false, message: errorMessage });
                } else {
                    const message = t('Test message sent successfully');
                    toast.success(message);
                    setTestMessageResult({ success: true, message });
                }

                setTimeout(() => {
                    setTestMessageResult(null);
                }, 5000);
            },
            onError: (errors) => {
                setIsSendingMessage(false);
                toast.dismiss();
                const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to send test message');
                toast.error(errorMessage);
                setTestMessageResult({ success: false, message: errorMessage });

                setTimeout(() => {
                    setTestMessageResult(null);
                }, 5000);
            }
        });
    };

    return (
        <SettingsSection
            title={t("Slack Settings")}
            description={t("Configure Slack settings for notifications and communications")}
            action={
                <Button onClick={handleSave} disabled={processing} size="sm">
                    <Save className="h-4 w-4 mr-2" />
                    {processing ? t('Saving...') : t('Save Changes')}
                </Button>
            }
        >
            <div className="space-y-4">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column - Configuration */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Credentials */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <div className="grid grid-cols-1 gap-6">
                                <div>
                                    <Label className="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                        <Link className="h-4 w-4" />
                                        {t("Webhook URL")}
                                    </Label>
                                    <Input
                                        value={slackWebhookUrl}
                                        required={true}
                                        onChange={(e) => setSlackWebhookUrl(e.target.value)}
                                        placeholder="https://hooks.slack.com/services/..."
                                        className="font-mono"
                                    />
                                </div>
                            </div>



                            {/* Slack Notification Settings */}
                            <div className="flex items-center gap-2 my-6">
                                <Bell className="h-5 w-5 text-emerald-500" />
                                <h3 className="font-medium text-gray-900">{t("Slack Notification Settings")}</h3>
                            </div>
                            {availableNotifications.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {availableNotifications.map(item => (
                                        <div key={item.name} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                            <Label htmlFor={item.name} className="text-sm font-medium">{t(item.label)}</Label>
                                            <Switch
                                                id={item.name}
                                                checked={notifications[item.name] || false}
                                                onCheckedChange={(checked) => handleToggle(item.name, checked)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    {t("No notification template available")}
                                </div>
                            )}
                        </div>

                    </div>

                    {/* Right Column - Test Configuration */}
                    <div className="bg-white border border-gray-200 rounded-lg p-6 h-fit">
                        <div className="flex items-center gap-2 mb-4">
                            <Send className="h-4 w-4 text-emerald-500" />
                            <h3 className="font-medium text-gray-900">{t("Test Slack Configuration")}</h3>
                        </div>
                        <form onSubmit={handleTestMessage} className="space-y-4">
                            <p className="text-xs text-gray-600">
                                {t("Send a test message to verify your slack webhook.")}
                            </p>

                            <Button
                                type="submit"
                                className="w-full bg-emerald-500 hover:bg-emerald-600 text-white"
                                disabled={isSendingMessage || !isAvailableSlackWebhookUrl}
                            >
                                {isSendingMessage ? (
                                    <>
                                        <span className="animate-spin mr-2">◌</span>
                                        {t("Sending...")}
                                    </>
                                ) : (
                                    <>
                                        <Send className="h-4 w-4 mr-2" />
                                        {t("Send Test Message")}
                                    </>
                                )}
                            </Button>
                        </form>
                        {/* Setup Instructions */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-4">
                            <div className="flex items-center gap-2 mb-2">
                                <Slack className="h-4 w-4 text-blue-600" />
                                <h3 className="text-sm font-medium text-blue-900">{t("Slack Setup Instructions")}</h3>
                            </div>
                            <ol className="space-y-2 text-xs text-blue-800">
                                <li>{t("1. Go to your Slack workspace")}</li>
                                <li>{t("2. Create a new Slack app")}</li>
                                <li>{t("3. Enable Incoming Webhooks")}</li>
                                <li>{t("4. Add webhook to workspace")}</li>
                                <li>{t("5. Copy the webhook URL here")}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsSection>
    );
}
