import React from 'react';
import { PageTemplate } from '@/components/page-template';
import { RefreshCw, Users, Building2, Briefcase, UserPlus, Calendar, Clock, TrendingUp, BarChart3, Bell, DollarSign, Target, PieChart, HardDrive, Megaphone, Star } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from 'react-i18next';
import { usePage, router } from '@inertiajs/react';
import { PieChart as RechartsPieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid, Legend, LineChart, Line } from 'recharts';
import { format } from 'date-fns';
import { motion } from 'framer-motion';
import { hasPermission } from '@/utils/authorization';

interface CompanyDashboardData {
    stats: {
        totalEmployees: number;
        totalLeads: number;
        totalSales: number;
        totalCustomers: number;
        totalProjects: number;
        companyRevenue: number;
        monthlyGrowth: number;
        conversionRate: number;
        storageUsed: number;
        storageLimit: number;
        storageUsagePercent: number;
        storageUsedMB: number;
        storageLimitGB: number;
    };
    charts: {
        salesTrends: Array<{ month: string; sales: number }>;
        leadConversions: Array<{ month: string; leads: number; conversions: number }>;
        customerDistribution: Array<{ name: string; value: number; color: string }>;
        employeeDistribution: Array<{ name: string; value: number; color: string }>;
    };
    recentActivities: {
        leads: Array<any>;
        sales: Array<any>;
        projects: Array<any>;
        customers: Array<any>;
        announcements: Array<any>;
    };
}

interface PageAction {
    label: string;
    icon: React.ReactNode;
    variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    onClick: () => void;
}

export default function Dashboard({ dashboardData }: { dashboardData: CompanyDashboardData }) {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;

    const pageActions: PageAction[] = [
        {
            label: t('Refresh'),
            icon: <RefreshCw className="h-4 w-4" />,
            variant: 'outline',
            onClick: () => window.location.reload()
        }
    ];

    const stats = dashboardData?.stats || {};
    const charts = dashboardData?.charts || {};
    const recentActivities = dashboardData?.recentActivities || { leads: [], sales: [], projects: [], customers: [], announcements: [] };

    const getStatusColor = (status: string) => {
        const colors = {
            'new': 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'qualified': 'bg-green-50 text-green-700 ring-green-600/20',
            'converted': 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'closed': 'bg-green-50 text-green-700 ring-green-600/20',
            'pending': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
            'in_progress': 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'planning': 'bg-purple-50 text-purple-700 ring-purple-600/20',
            'completed': 'bg-green-50 text-green-700 ring-green-600/20',
            'on_hold': 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'cancelled': 'bg-red-50 text-red-700 ring-red-600/20',
            'active': 'bg-green-50 text-green-700 ring-green-600/20',
            'inactive': 'bg-gray-50 text-gray-700 ring-gray-600/20',
            'enterprise': 'bg-purple-50 text-purple-700 ring-purple-600/20',
            'startup': 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
            'customer': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            'draft': 'bg-gray-50 text-gray-700 ring-gray-600/20',
            'shipped': 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'confirmed': 'bg-green-50 text-green-700 ring-green-600/20',
            'delivered': 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'processing': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
            'enterprise customer': 'bg-purple-50 text-purple-700 ring-purple-600/20',
            'smb customer': 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'strategic partner': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            'supplier/vendor': 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'reseller/channel': 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
            'prospect': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
        };
        return colors[status?.toLowerCase()] || 'bg-slate-50 text-slate-700 ring-slate-600/20';
    };

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.1
            }
        }
    };

    const itemVariants = {
        hidden: { y: 20, opacity: 0 },
        visible: {
            y: 0,
            opacity: 1,
            transition: {
                duration: 0.5
            }
        }
    };

    return (
        <PageTemplate
            title={t('Company Dashboard')}
            url={route('dashboard')}
            actions={pageActions}
        >
            <motion.div
                className="space-y-6"
                variants={containerVariants}
                initial="hidden"
                animate="visible"
            >
                {/* Company-Specific Metrics */}
                <motion.div variants={itemVariants} className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Total Employees')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{stats.totalEmployees || 0}</h3>
                                    <p className="text-xs text-muted-foreground mt-1">{t('team members')}</p>
                                </div>
                                <div className="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                                    <Users className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Total Leads')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{stats.totalLeads || 0}</h3>
                                    <p className="text-xs text-green-600 mt-1">{stats?.monthlyGrowth>0?'+':''}{stats.monthlyGrowth}% {t('this month')}</p>
                                </div>
                                <div className="rounded-full bg-green-100 p-3 dark:bg-green-900">
                                    <Target className="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Total Sales')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{stats.totalSales || 0}</h3>
                                    <p className="text-xs text-muted-foreground mt-1">{stats.conversionRate}% {t('conversion')}</p>
                                </div>
                                <div className="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                                    <TrendingUp className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Total Customers')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{stats.totalCustomers || 0}</h3>
                                    <p className="text-xs text-muted-foreground mt-1">{t('active customers')}</p>
                                </div>
                                <div className="rounded-full bg-orange-100 p-3 dark:bg-orange-900">
                                    <Building2 className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Total Projects')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{stats.totalProjects || 0}</h3>
                                    <p className="text-xs text-muted-foreground mt-1">{t('active projects')}</p>
                                </div>
                                <div className="rounded-full bg-indigo-100 p-3 dark:bg-indigo-900">
                                    <Briefcase className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">{t('Company Revenue')}</p>
                                    <h3 className="mt-2 text-xl font-bold">{window.appSettings?.formatCurrency(stats.companyRevenue || 0) || `$${(stats.companyRevenue || 0).toLocaleString()}`}</h3>
                                    <p className="text-xs text-green-600 mt-1">{stats?.monthlyGrowth>0?'+':''}{stats.monthlyGrowth}% {t('growth')}</p>
                                </div>
                                <div className="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                                    <DollarSign className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </motion.div>

                {/* Charts Section */}
                <motion.div variants={itemVariants} className="space-y-6">
                    {/* First Row - Sales Trends and Storage Usage */}
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Sales Trends */}
                        <Card className="hover:shadow-lg transition-all duration-300 lg:col-span-2">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                                            <TrendingUp className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <span className="text-lg font-semibold">{t('Sales Trends')}</span>
                                    </div>
                                    <Badge variant="outline" className="text-xs">
                                        {charts.salesTrends?.length || 0} months
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 pb-6">
                                {charts.salesTrends && charts.salesTrends.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={320}>
                                        <LineChart data={charts.salesTrends} margin={{ top: 20, right: 30, left: 20, bottom: 20 }}>
                                            <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" opacity={0.8} />
                                            <XAxis
                                                dataKey="month"
                                                axisLine={false}
                                                tickLine={false}
                                                tick={{ fontSize: 12, fill: 'hsl(var(--muted-foreground))' }}
                                                dy={10}
                                            />
                                            <YAxis
                                                axisLine={false}
                                                tickLine={false}
                                                tick={{ fontSize: 12, fill: 'hsl(var(--muted-foreground))' }}
                                                dx={-10}
                                            />
                                            <Tooltip
                                                content={({ active, payload, label }) => {
                                                    if (active && payload && payload.length) {
                                                        return (
                                                            <div className="rounded-lg border bg-background p-3 shadow-lg">
                                                                <p className="font-medium">{label}</p>
                                                                <p className="text-sm text-blue-600">
                                                                    Sales: <span className="font-semibold">{payload[0].value}</span>
                                                                </p>
                                                            </div>
                                                        );
                                                    }
                                                    return null;
                                                }}
                                            />
                                            <Line
                                                type="monotone"
                                                dataKey="sales"
                                                stroke="#3b82f6"
                                                strokeWidth={3}
                                                dot={{ fill: '#3b82f6', strokeWidth: 2, r: 4 }}
                                                activeDot={{ r: 6, fill: '#3b82f6', stroke: '#ffffff', strokeWidth: 2 }}
                                            />
                                        </LineChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex flex-col items-center justify-center h-[320px] text-muted-foreground">
                                        <BarChart3 className="h-12 w-12 mb-2 opacity-20" />
                                        <p className="text-sm">{t('No sales data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Storage Usage */}
                        <Card className="hover:shadow-lg transition-shadow duration-300">
                            <CardHeader className="pb-4">
                                <CardTitle className="flex items-center gap-3 text-lg">
                                    <div className="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                        <HardDrive className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <span className="text-lg font-semibold">{t('Storage Usage')}</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {(() => {
                                    const storageUsedMB = stats.storageUsedMB || 0;
                                    const storageLimitGB = stats.storageLimitGB || 1;
                                    const usedPercentage = Math.min(Math.round(stats.storageUsagePercent || 0), 100);
                                    const remainingPercentage = Math.max(100 - usedPercentage, 0);

                                    const formatStorageUsed = (mb: number) => {
                                        if (mb < 1024) return `${mb.toFixed(1)} MB`;
                                        return `${(mb / 1024).toFixed(1)} GB`;
                                    };

                                    const formatStorageLimit = (gb: number) => `${gb.toFixed(1)} GB`;

                                    return (
                                        <>
                                            <div className="flex items-center justify-center mb-6 relative">
                                                <RechartsPieChart width={140} height={140}>
                                                    <Pie
                                                        data={[
                                                            { name: 'Used', value: usedPercentage },
                                                            { name: 'Free', value: remainingPercentage }
                                                        ]}
                                                        cx={70}
                                                        cy={70}
                                                        innerRadius={45}
                                                        outerRadius={60}
                                                        dataKey="value"
                                                    >
                                                        <Cell fill={usedPercentage > 80 ? "#ef4444" : usedPercentage > 60 ? "#f59e0b" : "#3b82f6"} />
                                                        <Cell fill="#e5e7eb" />
                                                    </Pie>
                                                    <Tooltip formatter={(value) => [`${value}%`, 'Storage']} />
                                                </RechartsPieChart>
                                                <div className="absolute flex flex-col items-center">
                                                    <span className={`text-xl font-bold ${usedPercentage > 80 ? 'text-red-600' : usedPercentage > 60 ? 'text-yellow-600' : 'text-primary'}`}>{usedPercentage}%</span>
                                                    <span className="text-sm text-muted-foreground">{t("Used")}</span>
                                                </div>
                                            </div>
                                            <div className="space-y-4">
                                                <Progress value={usedPercentage} className="h-3" />
                                                <div className="text-center">
                                                    <p className="text-sm text-muted-foreground">
                                                        {formatStorageUsed(storageUsedMB)} of {formatStorageLimit(storageLimitGB)} used
                                                    </p>
                                                    {usedPercentage > 80 && (
                                                        <p className="text-xs text-red-600 mt-1">
                                                            {t('Storage limit nearly reached')}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                    );
                                })()}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Second Row - Lead Conversions and Customer Distribution */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Lead Conversions */}
                        <Card className="hover:shadow-lg transition-all duration-300">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="rounded-full bg-green-100 p-2 dark:bg-green-900">
                                            <BarChart3 className="h-4 w-4 text-green-600 dark:text-green-400" />
                                        </div>
                                        <span className="text-lg font-semibold">{t('Lead Conversions')}</span>
                                    </div>
                                    <Badge variant="outline" className="text-xs">
                                        {charts.leadConversions?.reduce((sum, item) => sum + (item.conversions || 0), 0) || 0} total
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 pb-6">
                                {charts.leadConversions && charts.leadConversions.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={320}>
                                        <BarChart data={charts.leadConversions} margin={{ top: 20, right: 30, left: 20, bottom: 20 }}>
                                            <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" opacity={0.8} />
                                            <XAxis
                                                dataKey="month"
                                                axisLine={false}
                                                tickLine={false}
                                                tick={{ fontSize: 12, fill: 'hsl(var(--muted-foreground))' }}
                                                dy={10}
                                            />
                                            <YAxis
                                                axisLine={false}
                                                tickLine={false}
                                                tick={{ fontSize: 12, fill: 'hsl(var(--muted-foreground))' }}
                                                dx={-10}
                                            />
                                            <Tooltip
                                                content={({ active, payload, label }) => {
                                                    if (active && payload && payload.length) {
                                                        return (
                                                            <div className="rounded-lg border bg-background p-3 shadow-lg">
                                                                <p className="font-medium mb-2">{label}</p>
                                                                {payload.map((entry, index) => (
                                                                    <p key={index} className="text-sm" style={{ color: entry.color }}>
                                                                        {entry.name}: <span className="font-semibold">{entry.value}</span>
                                                                    </p>
                                                                ))}
                                                            </div>
                                                        );
                                                    }
                                                    return null;
                                                }}
                                            />
                                            <Legend
                                                wrapperStyle={{ paddingTop: '20px', fontSize: '12px' }}
                                                iconType="rect"
                                            />
                                            <Bar
                                                dataKey="leads"
                                                fill="#3b82f6"
                                                name="Total Leads"
                                                radius={[4, 4, 0, 0]}
                                                opacity={0.7}
                                            />
                                            <Bar
                                                dataKey="conversions"
                                                fill="#10b77f"
                                                name="Conversions"
                                                radius={[4, 4, 0, 0]}
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex flex-col items-center justify-center h-[320px] text-muted-foreground">
                                        <Target className="h-12 w-12 mb-2 opacity-20" />
                                        <p className="text-sm">{t('No conversion data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Customer Distribution */}
                        <Card className="hover:shadow-lg transition-all duration-300">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                                            <PieChart className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <span className="text-lg font-semibold">{t('Customer Distribution')}</span>
                                    </div>
                                    <Badge variant="outline" className="text-xs">
                                        {charts.customerDistribution?.reduce((sum, item) => sum + (item.value || 0), 0) || 0} customers
                                    </Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 pb-6">
                                {charts.customerDistribution && charts.customerDistribution.length > 0 ? (
                                    <ResponsiveContainer width="100%" height={320}>
                                        <RechartsPieChart>
                                            <Pie
                                                data={charts.customerDistribution}
                                                cx="50%"
                                                cy="45%"
                                                innerRadius={50}
                                                outerRadius={90}
                                                paddingAngle={2}
                                                dataKey="value"
                                            >
                                                {charts.customerDistribution.map((entry, index) => (
                                                    <Cell
                                                        key={`cell-${index}`}
                                                        fill={entry.color}
                                                        stroke="#ffffff"
                                                        strokeWidth={2}
                                                    />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                content={({ active, payload }) => {
                                                    if (active && payload && payload.length) {
                                                        const data = payload[0].payload;
                                                        return (
                                                            <div className="rounded-lg border bg-background p-3 shadow-lg">
                                                                <p className="font-medium">{data.name}</p>
                                                                <p className="text-sm text-muted-foreground">
                                                                    Count: <span className="font-semibold text-foreground">{data.value}</span>
                                                                </p>
                                                            </div>
                                                        );
                                                    }
                                                    return null;
                                                }}
                                            />
                                            <Legend
                                                verticalAlign="bottom"
                                                height={36}
                                                wrapperStyle={{ fontSize: '12px', paddingTop: '10px' }}
                                                iconType="circle"
                                            />
                                        </RechartsPieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex flex-col items-center justify-center h-[320px] text-muted-foreground">
                                        <Building2 className="h-12 w-12 mb-2 opacity-20" />
                                        <p className="text-sm">{t('No customer data available')}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </motion.div>

                {/* Recent Activities */}
                <motion.div variants={itemVariants} className="grid gap-6 lg:grid-cols-2">

                    {/* Recent Leads */}
                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Target className="h-5 w-5" />
                                    <span className="text-lg font-semibold">{t('Latest Leads')}</span>
                                </div>
                                <Badge variant="secondary">{recentActivities.leads.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-100 overflow-y-auto">
                                {recentActivities.leads.map((lead, index) => (
                                    <div
                                        key={index}
                                        className="flex items-start gap-2 p-2 bg-card border border-border/50 rounded-lg hover:border-border hover:shadow-sm transition-all duration-200"
                                    >
                                        <div className="w-2 h-2 rounded-full bg-blue-500 mt-2 flex-shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="font-semibold text-sm truncate">{lead.name}</h4>
                                                <Badge variant="outline" className={`text-xs font-medium ring-1 ring-inset ${getStatusColor(lead.status)} ml-2 flex-shrink-0`}>
                                                    {lead.status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'New'}
                                                </Badge>
                                            </div>
                                            {/* <p className="text-sm text-muted-foreground truncate mb-1">{lead.email}</p> */}
                                            <p className="text-xs text-muted-foreground">
                                                {window.appSettings?.formatDateTime(lead.created_at, false) || new Date(lead.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Sales */}
                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <DollarSign className="h-5 w-5" />
                                    <span className="text-lg font-semibold">{t('Recent Sales')}</span>
                                </div>
                                <Badge variant="secondary">{recentActivities.sales.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-100 overflow-y-auto">
                                {recentActivities.sales.map((sale, index) => (
                                    <div
                                        key={index}
                                        className="flex items-start gap-2 p-2 bg-card border border-border/50 rounded-lg hover:border-border hover:shadow-sm transition-all duration-200"
                                    >
                                        <div className="w-2 h-2 rounded-full bg-green-500 mt-2 flex-shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="font-semibold text-sm truncate">{sale.customer}</h4>
                                                <Badge variant="outline" className={`text-xs font-medium ring-1 ring-inset ${getStatusColor(sale.status)} ml-2 flex-shrink-0`}>
                                                    {sale.status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Draft'}
                                                </Badge>
                                            </div>
                                            {/* <p className="text-sm truncate mb-1 text-green-600">{window.appSettings?.formatCurrency(sale.amount) || `$${sale.amount.toLocaleString()}`}</p> */}
                                            <p className="text-xs text-muted-foreground">
                                                {window.appSettings?.formatDateTime(sale.created_at, false) || new Date(sale.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Projects */}
                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Briefcase className="h-5 w-5" />
                                    <span className="text-lg font-semibold">{t('Active Projects')}</span>
                                </div>
                                <Badge variant="secondary">{recentActivities.projects.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-100 overflow-y-auto">
                                {recentActivities.projects.map((project, index) => (
                                    <div
                                        key={index}
                                        className="flex items-start gap-2 p-2 bg-card border border-border/50 rounded-lg hover:border-border hover:shadow-sm transition-all duration-200"
                                    >
                                        <div className="w-2 h-2 rounded-full bg-purple-500 mt-2 flex-shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="font-semibold text-sm truncate">{project.name}</h4>
                                                <Badge variant="outline" className={`text-xs font-medium ring-1 ring-inset ${getStatusColor(project.status)} ml-2 flex-shrink-0`}>
                                                    {project.status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Planning'}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {window.appSettings?.formatDateTime(project.created_at, false) || new Date(project.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Customers */}
                    <Card className="hover:shadow-lg transition-shadow duration-300">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5" />
                                    <span className="text-lg font-semibold">{t('New Customers')}</span>
                                </div>
                                <Badge variant="secondary">{recentActivities.customers.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-100 overflow-y-auto">
                                {recentActivities.customers.map((customer, index) => (
                                    <div
                                        key={index}
                                        className="flex items-start gap-2 p-2 bg-card border border-border/50 rounded-lg hover:border-border hover:shadow-sm transition-all duration-200"
                                    >
                                        <div className="w-2 h-2 rounded-full bg-orange-500 mt-2 flex-shrink-0"></div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <h4 className="font-semibold text-sm truncate">{customer.name}</h4>
                                                <Badge variant="outline" className={`text-xs font-medium ring-1 ring-inset ${getStatusColor(customer.type)} ml-2 flex-shrink-0`}>
                                                    {customer.type?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Customer'}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {window.appSettings?.formatDateTime(customer.created_at, false) || new Date(customer.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Announcements */}
                    <Card className="hover:shadow-lg transition-shadow duration-300 lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Megaphone className="h-5 w-5" />
                                    <span className="text-lg font-semibold">{t('Recent Announcements')}</span>
                                </div>
                                <Badge variant="secondary">{recentActivities.announcements.length}</Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-100 overflow-y-auto">
                                {recentActivities.announcements.map((announcement, index) => (
                                    <div
                                        key={index}
                                        onClick={() => {
                                            if (hasPermission(auth?.permissions, 'view-announcements')) {
                                                router.get(route('announcements.show', announcement.id));
                                            }
                                        }}
                                        className={`flex items-start gap-3 p-3 bg-card border border-border/50 rounded-lg hover:border-border hover:shadow-sm transition-all duration-200 ${auth.permissions?.includes('view-announcements') ? 'cursor-pointer' : ''}`}
                                    >
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="font-semibold text-sm truncate">{announcement.title}</h4>
                                                    {announcement.is_featured && (
                                                        <Badge className="bg-yellow-100 text-yellow-800 flex items-center gap-1">
                                                            <Star className="h-3 w-3" />
                                                            {t('Featured')}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <Badge variant="outline" className="text-xs font-medium ring-1 ring-inset bg-indigo-50 text-indigo-700 ring-indigo-600/20 ml-2 flex-shrink-0">
                                                    {announcement.category}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {window.appSettings?.formatDateTime(announcement.created_at, false) || new Date(announcement.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </motion.div>
            </motion.div>
        </PageTemplate>
    );
}
