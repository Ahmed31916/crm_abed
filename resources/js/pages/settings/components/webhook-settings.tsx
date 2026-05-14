import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useState } from 'react';
import { Plus, Edit, Trash2, Save, X, Link2 } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import axios from 'axios';
import { Card, CardContent } from '@/components/ui/card';

interface Webhook {
    id: number;
    module: string;
    method: string;
    url: string;
    created_at: string;
}

interface WebhookSettingsProps {
    webhooks?: Webhook[];
}

export default function WebhookSettings({ webhooks = [] }: WebhookSettingsProps) {
    const { t } = useTranslation();
    const [webhookList, setWebhookList] = useState<Webhook[]>(webhooks);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingWebhook, setEditingWebhook] = useState<Webhook | null>(null);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [webhookToDelete, setWebhookToDelete] = useState<Webhook | null>(null);
    const [formData, setFormData] = useState({
        module: '',
        method: 'GET',
        url: ''
    });

    const resetForm = () => {
        setFormData({ module: '', method: 'GET', url: '' });
        setEditingWebhook(null);
    };

    const handleCreate = () => {
        resetForm();
        setIsDialogOpen(true);
    };

    const handleEdit = (webhook: Webhook) => {
        setFormData({
            module: webhook.module,
            method: webhook.method,
            url: webhook.url
        });
        setEditingWebhook(webhook);
        setIsDialogOpen(true);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        try {
            if (editingWebhook) {
                const response = await axios.put(route('settings.webhooks.update', editingWebhook.id), formData);
                setWebhookList(prev => prev.map(w => w.id === editingWebhook.id ? response.data.webhook : w));
                toast.success(response.data.message);
            } else {
                const response = await axios.post(route('settings.webhooks.store'), formData);
                setWebhookList(prev => [...prev, response.data.webhook]);
                toast.success(response.data.message);
            }
            setIsDialogOpen(false);
            resetForm();
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || t('An error occurred');
            toast.error(errorMessage);
        }
    };

    const handleDeleteClick = (webhook: Webhook) => {
        setWebhookToDelete(webhook);
        setDeleteModalOpen(true);
    };

    const handleDeleteConfirm = async () => {
        if (!webhookToDelete) return;

        try {
            const response = await axios.delete(route('settings.webhooks.destroy', webhookToDelete.id));
            setWebhookList(prev => prev.filter(w => w.id !== webhookToDelete.id));
            toast.success(response.data.message);
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || t('An error occurred');
            toast.error(errorMessage);
        } finally {
            setDeleteModalOpen(false);
            setWebhookToDelete(null);
        }
    };

    const handleDeleteCancel = () => {
        setDeleteModalOpen(false);
        setWebhookToDelete(null);
    };

    return (
        <SettingsSection
            title={t("Webhook Settings")}
            description={t("Manage webhooks for external integrations")}
            action={
                <Button onClick={handleCreate} size="sm">
                    <Plus className="h-4 w-4 mr-2" />
                    {t("Add Webhook")}
                </Button>
            }
        >
            <Card>
                <CardContent className='mt-6'>
                    <div className="space-y-4">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700">
                                    <TableHead className="py-2.5 font-semibold">{t("Module")}</TableHead>
                                    <TableHead className="py-2.5 font-semibold">{t("Method")}</TableHead>
                                    <TableHead className="py-2.5 font-semibold">{t("URL")}</TableHead>
                                    <TableHead className="w-24 py-2.5 font-semibold text-right">{t("Actions")}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {webhookList.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="h-24 text-center text-muted-foreground dark:text-gray-400">
                                            {t("No webhooks configured")}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    webhookList.map((webhook) => (
                                        <TableRow key={webhook.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 border-b dark:border-gray-700 dark:bg-gray-900">
                                            <TableCell className="py-2.5">
                                                <div className="flex items-center">
                                                    <Link2 className="h-4 w-4 mr-2 text-muted-foreground" />
                                                    <span className="text-sm font-medium">{webhook.module}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="py-2.5">
                                                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${webhook.method === 'GET'
                                                        ? 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/30'
                                                        : 'bg-green-50 text-green-700 ring-green-700/10 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/30'
                                                    }`}>
                                                    {webhook.method}
                                                </span>
                                            </TableCell>
                                            <TableCell className="py-2.5">
                                                <div className="max-w-xs truncate text-sm font-mono text-muted-foreground dark:text-gray-400">
                                                    {webhook.url}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8 text-amber-500 hover:text-amber-700"
                                                                    onClick={() => handleEdit(webhook)}
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t("Edit")}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                    <TooltipProvider>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8 text-red-500 hover:text-red-700"
                                                                    onClick={() => handleDeleteClick(webhook)}
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>{t("Delete")}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        {editingWebhook ? t("Edit Webhook") : t("Add Webhook")}
                                    </DialogTitle>
                                </DialogHeader>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="module" required>{t("Module")}</Label>
                                        <Select
                                            value={formData.module}
                                            onValueChange={(value) => setFormData(prev => ({ ...prev, module: value }))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t("Select module")} />
                                            </SelectTrigger>
                                            <SelectContent className="z-[60000]">
                                                <SelectItem value="New User">{t("New User")}</SelectItem>
                                                <SelectItem value="Lead Assigned">{t("Lead Create")}</SelectItem>
                                                <SelectItem value="Case Created">{t("Case Create")}</SelectItem>
                                                <SelectItem value="Meeting Invitation">{t("Meeting Invitation")}</SelectItem>
                                                <SelectItem value="Opportunity Created">{t("Opportunity Create")}</SelectItem>
                                                <SelectItem value="Quote Created">{t("Quote Create")}</SelectItem>
                                                <SelectItem value="Task Assigned">{t("Task Create")}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="method">{t("Method")}</Label>
                                        <Select
                                            value={formData.method}
                                            onValueChange={(value) => setFormData(prev => ({ ...prev, method: value }))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t("Select method")} />
                                            </SelectTrigger>
                                            <SelectContent className="z-[60000]">
                                                <SelectItem value="GET">GET</SelectItem>
                                                <SelectItem value="POST">POST</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="url" required>{t("URL")}</Label>
                                        <Input
                                            id="url"
                                            placeholder="https://example.com/webhook"
                                            value={formData.url}
                                            onChange={(e) => setFormData(prev => ({ ...prev, url: e.target.value }))}
                                        />
                                    </div>

                                    <div className="flex justify-end gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setIsDialogOpen(false)}
                                        >
                                            {t("Cancel")}
                                        </Button>
                                        <Button type="submit">
                                            {t("Save")}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <CrudDeleteModal
                            isOpen={deleteModalOpen}
                            onClose={handleDeleteCancel}
                            onConfirm={handleDeleteConfirm}
                            itemName={webhookToDelete?.module || ''}
                            entityName={t('Webhook')}
                        />
                    </div>
                </CardContent>
            </Card>
        </SettingsSection>
    );
}
