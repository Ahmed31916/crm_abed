import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Briefcase, Building2, ShoppingBag, FileText, Ticket, Folder, Megaphone, CalendarDays, Phone, FileIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { PageWrapper } from '@/components/PageWrapper';
import { hasPermission } from '@/utils/authorization';
import { toast } from '@/components/custom-toast';

interface StreamsIndexProps {
    modules: Record<string, string>;
}

export default function Index({ modules }: StreamsIndexProps) {
    const { t } = useTranslation();
    const { auth, flash } = usePage().props as any;
    const permissions = auth?.permissions || [];

    React.useEffect(() => {
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const getModuleIcon = (moduleKey: string) => {
        const iconMap: Record<string, any> = {
            'account_activities': Building2,
            'lead_activities': Users,
            'opportunity_activities': Briefcase,
            'invoice_activities': FileText,
            'purchase_order_activities': ShoppingBag,
            'quote_activities': FileText,
            'sales_order_activities': ShoppingBag,
        };
        return iconMap[moduleKey] || FileIcon;
    };

    const handleViewStream=(key:string) => {
        if(hasPermission(permissions, 'view-stream')){
            router.visit(route(`stream.${key.replace(/_/g, '-')}`));
        }
    }

    return (
        <PageWrapper
            title={t('Streams')}
            url={route('stream.index')}
        >
            <Head title={t('Streams')} />

            {hasPermission(permissions, 'manage-stream') && (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {Object.entries(modules || {}).map(([key, title]) => {
                        const IconComponent = getModuleIcon(key);
                        return (
                            <div key={key} onClick={()=>handleViewStream(key)}>
                                <Card className="hover:shadow-lg transition-shadow cursor-pointer">
                                    <CardHeader className="flex flex-row items-center space-y-0 pb-2">
                                        <IconComponent className="h-6 w-6 text-primary mr-3" />
                                        <CardTitle className="text-lg">{t(title)}</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                    {hasPermission(permissions, 'view-stream') &&
                                        <p className="text-sm text-gray-600">
                                            {t('View activity logs for')} {t(title.toLowerCase())}
                                        </p>
                                    }
                                    </CardContent>
                                </Card>
                            </div>
                        );
                    })}
                </div>
            )}
        </PageWrapper>
    );
}
