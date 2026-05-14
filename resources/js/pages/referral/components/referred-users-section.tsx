import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Users, Calendar, DollarSign, CheckCircle } from 'lucide-react';
import { use } from 'react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

interface ReferredUser {
    id: number;
    name: string;
    email: string;
    created_at: string;
    avatar: string | null;
    plan?: {
        id: number;
        name: string;
        price: number;
        yearly_price?: number;
    };
    plan_orders?: Array<{
        id: number;
        billing_cycle: string;
        final_price: number;
    }>;
    referrals?: Array<{
        id: number;
        amount: number;
        commission_percentage: number;
        created_at: string;
    }>;
}

interface ReferredUsersSectionProps {
    referredUsers: {
        data: ReferredUser[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: any[];
    };
    userType: string;
    currencySymbol: string;
}

export default function ReferredUsersSection({ referredUsers, userType, currencySymbol }: ReferredUsersSectionProps) {
    const { t } = useTranslation();
    const getInitials = useInitials();

    const getTotalCommission = (user: ReferredUser) => {
        return user.referrals?.reduce((total, referral) => total + (Number(referral.amount) || 0), 0) || 0;
    };

    const getTotalCommissionAll = () => {
        return referredUsers.data.reduce((total, user) => total + getTotalCommission(user), 0) || 0;
    };

    const getPlanDisplayInfo = (user: ReferredUser) => {
        if (!user.plan) return null;

        const latestOrder = user.plan_orders?.[0];

        if (latestOrder) {
            const isYearly = latestOrder.billing_cycle === 'yearly';
            return {
                name: user.plan.name,
                price: latestOrder.final_price,
                cycle: isYearly ? 'year' : 'month'
            };
        }

        return {
            name: user.plan.name,
            price: user.plan.price,
            cycle: 'month'
        };
    };

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Total Referred Users')}</p>
                                <p className="mt-2 text-2xl font-bold">{referredUsers.total}</p>
                            </div>
                            <div className="rounded-full bg-green-100 p-3 dark:bg-green-900">
                                <Users className="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Users with Plans')}</p>
                                <p className="mt-2 text-2xl font-bold">{referredUsers.data.filter(user => user.plan).length}</p>
                            </div>
                            <div className="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                                <CheckCircle className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{t('Total Commission Earned')}</p>
                                <p className="mt-2 text-2xl font-bold">{currencySymbol}{getTotalCommissionAll().toFixed(2)}</p>
                            </div>
                            <div className="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                                <DollarSign className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base font-semibold">{t('Referred Users List')}</CardTitle>
                </CardHeader>
                <CardContent>
                    {referredUsers.data.length === 0 ? (
                        <div className="text-center py-12">
                            <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <p className="text-base font-semibold text-muted-foreground mb-2">{t('No referred users yet')}</p>
                            <p className="text-sm text-muted-foreground">
                                {userType === 'superadmin'
                                    ? t('No users have registered using referral codes yet.')
                                    : t('Share your referral link to start earning commissions.')
                                }
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {referredUsers.data.map((user) => (
                                <div key={user.id} className="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex items-start space-x-3 flex-1">
                                            <div className="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                                {user?.avatar ?
                                                    <Avatar className="w-12 h-12 rounded-full object-cover shrink-0">
                                                        <AvatarImage src={user.avatar} />
                                                        <AvatarFallback>{getInitials(user.name)}</AvatarFallback>
                                                    </Avatar>
                                                    :
                                                    <span className="text-sm font-medium text-primary">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </span>}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold">{user.name}</p>
                                                <p className="text-sm text-muted-foreground mb-2">{user.email}</p>
                                                <div className="flex items-center space-x-2">
                                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                                    <span className="text-xs text-muted-foreground">
                                                        {t('Registered')} {window.appSettings?.formatDateTime(user.created_at, false) || new Date(user.created_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-start space-x-6 flex-shrink-0">
                                            <div className="text-right">
                                                {(() => {
                                                    const planInfo = getPlanDisplayInfo(user);
                                                    return planInfo ? (
                                                        <div>
                                                            <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mb-1.5">
                                                                {planInfo.name}
                                                            </span>
                                                            <p className="text-sm text-muted-foreground">
                                                                {currencySymbol}{planInfo.price}/{t(planInfo.cycle)}
                                                            </p>
                                                        </div>
                                                    ) : (
                                                        <span className="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                                            {t('No Plan')}
                                                        </span>
                                                    );
                                                })()}
                                            </div>

                                            {getTotalCommission(user) > 0 && (
                                                <div className="text-right min-w-[80px]">
                                                    <p className="text-sm font-semibold text-green-600">
                                                        +{currencySymbol}{getTotalCommission(user)}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        {t('Commission')}
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {user.referrals && user.referrals.length > 0 && (
                                        <div className="mt-4 pt-4 border-t">
                                            <p className="text-sm font-semibold mb-2">{t('Commission History')}</p>
                                            <div className="space-y-2">
                                                {user.referrals.map((referral) => (
                                                    <div key={referral.id} className="flex justify-between items-center">
                                                        <span className="text-sm text-muted-foreground">
                                                            {referral.commission_percentage}% {t('commission')}
                                                        </span>
                                                        <span className="text-sm font-semibold text-green-600">
                                                            +{currencySymbol}{referral.amount}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {referredUsers.last_page > 1 && (
                <Pagination
                    from={referredUsers.from}
                    to={referredUsers.to}
                    total={referredUsers.total}
                    links={referredUsers.links}
                    currentPage={referredUsers.current_page}
                    lastPage={referredUsers.last_page}
                    entityName={t('users')}
                    onPageChange={(url) => {
                        router.visit(url, {
                            preserveState: true,
                            preserveScroll: true,
                            only: ['referredUsers']
                        });
                    }}
                />
            )}
        </div>
    );
}
