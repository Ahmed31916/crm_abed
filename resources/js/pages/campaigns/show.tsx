import { PageTemplate } from '@/components/page-template';
import { usePage, Link } from '@inertiajs/react';
import { ArrowLeft, Target, DollarSign, Calendar, User, Building, Users, TrendingUp, BarChart3 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

export default function CampaignShow() {
    const { t } = useTranslation();
    const { campaign, campaignLeads } = usePage().props as any;

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Campaigns'), href: route('campaigns.index') },
        { title: campaign.name }
    ];

    const getStatusBadge = (status: string) => {
        const statusColors = {
            active: 'bg-green-50 text-green-700 ring-green-600/20',
            inactive: 'bg-red-50 text-red-700 ring-red-600/10'
        };

        return (
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[status as keyof typeof statusColors] || statusColors.active}`}>
                {status?.charAt(0).toUpperCase() + status?.slice(1) || t('Active')}
            </span>
        );
    };

    const formatCurrency = (amount: number) => window.appSettings?.formatCurrency(Number(amount || 0)) || `$${Number(amount || 0).toFixed(2)}`;

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        return window.appSettings?.formatDateTime(dateString, false) || new Date(dateString).toLocaleDateString();
    };

    const calculateROI = () => {
        const actualCost = campaign.actual_cost || 0;
        const budget = campaign.budget || 0;
        if (budget === 0) return t('-');
        const roi = ((budget - actualCost) / budget) * 100;
        return `${roi.toFixed(1)}%`;
    };

    const getResponseRate = () => {
        const expected = campaign.expected_response || 0;
        const actual = campaignLeads?.length || 0;
        if (expected === 0) return t('-');
        const rate = (actual / expected) * 100;
        return `${rate.toFixed(1)}%`;
    };

    return (
        <PageTemplate
            title={campaign.name}
            breadcrumbs={breadcrumbs}
            actions={[
                {
                    label: t('Back'),
                    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
                    variant: 'outline',
                    onClick: () => window.history.back()
                }
            ]}
        >
            <div className="mx-auto space-y-6">
                {/* Header Section */}
                <div className="bg-white rounded-lg shadow-sm border p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-lg font-bold">{campaign.name}</h1>
                            <p className="text-sm mt-2">{campaign.description || t('No description provided')}</p>
                        </div>
                        <div className="text-right">
                            {getStatusBadge(campaign.status)}
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card className="border-l-4 border-l-green-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Budget')}</p>
                                    <h3 className="mt-2 text-2xl font-bold text-green-600">{formatCurrency(campaign.budget)}</h3>
                                </div>
                                <div className="rounded-full bg-green-100 p-4">
                                    <DollarSign className="h-5 w-5 text-green-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-blue-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Actual Cost')}</p>
                                    <h3 className="mt-2 text-2xl font-bold text-blue-600">{formatCurrency(campaign.actual_cost)}</h3>
                                </div>
                                <div className="rounded-full bg-blue-100 p-4">
                                    <BarChart3 className="h-5 w-5 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-orange-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Response Rate')}</p>
                                    <h3 className="mt-2 text-2xl font-bold text-orange-600">{getResponseRate()}</h3>
                                </div>
                                <div className="rounded-full bg-orange-100 p-4">
                                    <TrendingUp className="h-5 w-5 text-orange-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-purple-500">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('ROI')}</p>
                                    <h3 className="mt-2 text-2xl font-bold text-purple-600">{calculateROI()}</h3>
                                </div>
                                <div className="rounded-full bg-purple-100 p-4">
                                    <Target className="h-5 w-5 text-purple-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Campaign Information */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b">
                        <CardTitle className="text-lg font-semibold">{t('Campaign Information')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-8">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div className="space-y-6">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Campaign Type')}</label>
                                    <p className="text-sm mt-1">{campaign.campaign_type?.name || t('-')}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Target List')}</label>
                                    <p className="text-sm mt-1">{campaign.target_list?.name || t('-')}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Start Date')}</label>
                                    <p className="text-sm mt-1">{formatDate(campaign.start_date)}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('End Date')}</label>
                                    <p className="text-sm mt-1">{formatDate(campaign.end_date)}</p>
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Assigned To')}</label>
                                    <p className="text-sm mt-1">{campaign.assigned_user?.name || t('Unassigned')}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Expected Response')}</label>
                                    <p className="text-sm mt-1">{campaign.expected_response || 0}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Actual Response')}</label>
                                    <p className="text-sm mt-1">{campaignLeads?.length || 0} {t('leads')}</p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">{t('Created By')}</label>
                                    <p className="text-sm mt-1">{campaign.creator?.name || t('-')}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Budget Analysis */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b">
                        <CardTitle className="flex items-center text-lg font-semibold">
                            <DollarSign className="h-5 w-5 mr-3 text-muted-foreground" />
                            {t('Budget Analysis')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div className="text-center p-4 bg-green-50 rounded-lg">
                                <h4 className="text-sm font-medium text-muted-foreground">{t('Budget')}</h4>
                                <p className="text-2xl font-bold text-green-600 mt-2">{formatCurrency(campaign.budget)}</p>
                            </div>
                            <div className="text-center p-4 bg-blue-50 rounded-lg">
                                <h4 className="text-sm font-medium text-muted-foreground">{t('Actual Cost')}</h4>
                                <p className="text-2xl font-bold text-blue-600 mt-2">{formatCurrency(campaign.actual_cost)}</p>
                            </div>
                            <div className="text-center p-4 bg-purple-50 rounded-lg">
                                <h4 className="text-sm font-medium text-muted-foreground">{t('Remaining')}</h4>
                                <p className="text-2xl font-bold text-purple-600 mt-2">
                                    {formatCurrency((campaign.budget || 0) - (campaign.actual_cost || 0))}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Related Leads */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b">
                        <CardTitle className="text-lg font-semibold">{t('Campaign Leads')} ({campaignLeads?.length || 0})</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        {campaignLeads?.length > 0 ? (
                            <div className="space-y-4">
                                {campaignLeads.slice(0, 10).map((lead: any) => (
                                    <div key={lead.id} className="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between mb-2">
                                                <p className="text-sm font-medium text-gray-700">{lead.name}</p>
                                                {lead.lead_status && (
                                                    <span
                                                        className="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-white"
                                                        style={{ backgroundColor: lead.lead_status.color || '#6B7280' }}
                                                    >
                                                        {lead.lead_status.name}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center justify-between mb-2">
                                                <div>
                                                <p className="text-sm mb-1">{lead.email}</p>
                                                <p className="text-sm text-muted-foreground">{t('Assigned to')}: {lead.assigned_user?.name || t('Unassigned')}</p>
                                                </div>
                                                <Link href={route('leads.show', lead.id)}>
                                                    <Button variant="outline" size="sm" className="bg-white ml-4">
                                                        {t('View')}
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {campaignLeads.length > 10 && (
                                    <p className="text-sm text-muted-foreground text-center">+{campaignLeads.length - 10} {t('more leads')}</p>
                                )}
                            </div>
                        ) : (
                            <p className="text-center text-gray-500">{t('No leads created from this campaign yet.')}</p>
                        )}
                    </CardContent>
                </Card>


                {/* Timestamps */}
                <Card className="shadow-sm">
                    <CardHeader className="bg-gray-50 border-b">
                        <CardTitle className="text-lg font-semibold">{t('Record Information')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Created At')}</label>
                                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(campaign.created_at, false) || new Date(campaign.created_at).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">{t('Updated At')}</label>
                                <p className="text-sm mt-1">{window.appSettings?.formatDateTime(campaign.updated_at, false) || new Date(campaign.updated_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </PageTemplate>
    );
}
