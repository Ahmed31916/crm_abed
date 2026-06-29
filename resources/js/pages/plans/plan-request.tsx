// pages/plans/plan-request.tsx
// v6.8 — إضافات: زر View Details + زر Change Plan + إصلاحات v6.7 (Actions/Pagination/Per Page)
import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { CrudTable } from '@/components/CrudTable';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { capitalize, getDisplayUrl } from '@/utils/helper';

/**
 * صورة افتراضية مضمّنة كـ data URI (SVG) — لا تتطلب طلب شبكة
 * ولا تُسبّب أي خطأ CORS أو loopback.
 */
const AVATAR_FALLBACK_DATA_URI =
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">' +
        '<rect width="40" height="40" fill="#e5e7eb"/>' +
        '<circle cx="20" cy="15" r="6" fill="#9ca3af"/>' +
        '<path d="M8 34c0-6 5-10 12-10s12 4 12 10" fill="#9ca3af"/>' +
        '</svg>'
    );

// ============================================================
// مكوّن صغير لعرض صف معلومات داخل مودال التفاصيل
// ============================================================
function DetailRow({ label, value, mono = false }: { label: string; value: React.ReactNode; mono?: boolean }) {
    return (
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
            <span className="text-sm text-gray-500 dark:text-gray-400 mb-1 sm:mb-0">{label}</span>
            <span className={`text-sm font-medium text-gray-900 dark:text-gray-100 text-right ${mono ? 'font-mono break-all' : ''}`}>
                {value ?? '-'}
            </span>
        </div>
    );
}

export default function PlanRequestsPage() {
    const { t } = useTranslation();
    const { planRequests, plans = [], filters: pageFilters = {}, auth, globalSettings } = usePage().props as any;
    const permissions = auth?.permissions || [];

    // ============================================================
    // v6.10: التحقق من أن المستخدم سوبر ادمن — إن لم يكن، نُعيد توجيهه
    // الـ controller يحمي فعلياً، لكن هذا يحسّن UX بعرض شاشة مناسبة
    // بدلاً من خطأ 403 غامض.
    // ============================================================
    const userIsSuperAdmin = (() => {
        const userType = auth?.user?.type;
        if (userType && ['superadmin', 'super-admin', 'super_admin'].includes(userType)) {
            return true;
        }
        // fallback: عبر permissions
        if (Array.isArray(permissions)) {
            return permissions.includes('manage-plan-requests')
                && permissions.includes('approve-plan-requests')
                && permissions.includes('reject-plan-requests');
        }
        return false;
    })();

    // ============================================================
    // State — فلاتر الجدول
    // ============================================================
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '_empty_');
    const [showFilters, setShowFilters] = useState(false);

    // ============================================================
    // State — مودال عرض التفاصيل
    // ============================================================
    const [detailsOpen, setDetailsOpen] = useState(false);
    const [detailsLoading, setDetailsLoading] = useState(false);
    const [detailsData, setDetailsData] = useState<any>(null);
    const [detailsError, setDetailsError] = useState<string | null>(null);

    // ============================================================
    // State — مودال تغيير الخطة
    // ============================================================
    const [changePlanOpen, setChangePlanOpen] = useState(false);
    const [changePlanItemId, setChangePlanItemId] = useState<number | null>(null);
    const [changePlanCurrentId, setChangePlanCurrentId] = useState<number | null>(null);
    const [newPlanId, setNewPlanId] = useState<string>('');
    const [changePlanSubmitting, setChangePlanSubmitting] = useState(false);

    // مزامنة state محلية عند تغيّر pageFilters
    useEffect(() => {
        setSearchTerm(pageFilters.search || '');
        setSelectedStatus(pageFilters.status || '_empty_');
    }, [pageFilters.search, pageFilters.status]);

    // Always-valid per_page
    const currentPerPage = pageFilters.per_page
        ? parseInt(String(pageFilters.per_page), 10) || 10
        : 10;

    const hasActiveFilters = () => {
        return selectedStatus !== '_empty_' || searchTerm !== '';
    };

    const activeFilterCount = () => {
        return (selectedStatus !== '_empty_' ? 1 : 0) +
            (searchTerm !== '' ? 1 : 0);
    };

    // ============================================================
    // Helpers للتنقّل مع الحفاظ على الفلاتر
    // ============================================================
    const navigateWithFilters = (overrides: Record<string, any> = {}) => {
        const params: Record<string, any> = {
            page: 1,
            search: searchTerm || undefined,
            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
            per_page: currentPerPage,
            sort_field: pageFilters.sort_field,
            sort_direction: pageFilters.sort_direction,
            ...overrides,
        };

        Object.keys(params).forEach(k => {
            if (params[k] === undefined || params[k] === null) delete params[k];
        });

        router.get(route('plan-requests.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        navigateWithFilters({ page: 1 });
    };

    const applyFilters = () => navigateWithFilters({ page: 1 });

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
        navigateWithFilters({ sort_field: field, sort_direction: direction, page: 1 });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('_empty_');
        setShowFilters(false);

        router.get(route('plan-requests.index'), {
            page: 1,
            per_page: currentPerPage,
        }, { preserveState: true, preserveScroll: true });
    };

    // ============================================================
    // Actions handler — approve / reject / view / change-plan
    // ============================================================
    const handleAction = (action: string, item: any) => {
        if (action === 'approve') {
            if (!globalSettings?.is_demo) toast.loading(t('Approving plan request...'));
            router.post(route('plan-requests.approve', item.id), {}, {
                onSuccess: (page) => {
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                    else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                },
                onError: (errors) => {
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (typeof errors === 'string') toast.error(t(errors));
                    else toast.error(t('Failed to approve plan request: {{errors}}', { errors: Object.values(errors).join(', ') }));
                },
            });
        } else if (action === 'reject') {
            if (!globalSettings?.is_demo) toast.loading(t('Rejecting plan request...'));
            router.post(route('plan-requests.reject', item.id), {}, {
                onSuccess: (page) => {
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                    else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                },
                onError: (errors) => {
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (typeof errors === 'string') toast.error(t(errors));
                    else toast.error(t('Failed to reject plan request: {{errors}}', { errors: Object.values(errors).join(', ') }));
                },
            });
        } else if (action === 'view') {
            openDetailsModal(item.id);
        } else if (action === 'change-plan') {
            openChangePlanModal(item);
        }
    };

    // ============================================================
    // مودال التفاصيل — جلب البيانات من show() endpoint
    // ============================================================
    const openDetailsModal = (id: number) => {
        setDetailsOpen(true);
        setDetailsLoading(true);
        setDetailsError(null);
        setDetailsData(null);

        // استخدام fetch مباشرة (وليس router.get) لأن endpoint يُرجع JSON
        // ولا نريد إعادة render صفحة Inertia كاملة
        fetch(route('plan-requests.show', id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(async (res) => {
                if (!res.ok) {
                    const text = await res.text();
                    throw new Error(text || `HTTP ${res.status}`);
                }
                return res.json();
            })
            .then((data) => {
                setDetailsData(data);
            })
            .catch((err) => {
                console.error('Failed to load plan request details:', err);
                setDetailsError(err.message || 'Failed to load details');
            })
            .finally(() => setDetailsLoading(false));
    };

    const closeDetailsModal = () => {
        setDetailsOpen(false);
        // تأخير بسيط حتى لا تومض بيانات قديمة عند الفتح التالي
        setTimeout(() => {
            setDetailsData(null);
            setDetailsError(null);
        }, 200);
    };

    // ============================================================
    // مودال تغيير الخطة
    // ============================================================
    const openChangePlanModal = (item: any) => {
        setChangePlanItemId(item.id);
        setChangePlanCurrentId(item.plan?.id ?? null);
        // ⭐ FIX: لا نُهيّئ newPlanId بالقيمة الحالية، بل نتركها فارغة
        // لتظهر placeholder "Select a plan" ولإخفاء رسالة "same as current"
        setNewPlanId('');
        setChangePlanOpen(true);
    };

    const closeChangePlanModal = () => {
        setChangePlanOpen(false);
        setChangePlanItemId(null);
        setChangePlanCurrentId(null);
        setNewPlanId('');
    };

    const submitChangePlan = () => {
        if (!changePlanItemId) return;
        if (!newPlanId) {
            toast.error(t('Please select a plan'));
            return;
        }
        if (changePlanCurrentId && Number(newPlanId) === changePlanCurrentId) {
            toast.error(t('New plan is the same as current plan'));
            return;
        }

        setChangePlanSubmitting(true);
        if (!globalSettings?.is_demo) toast.loading(t('Updating plan...'));

        router.put(
            route('plan-requests.change-plan', changePlanItemId),
            { plan_id: newPlanId },
            {
                onSuccess: (page) => {
                    setChangePlanSubmitting(false);
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (page.props.flash?.success) toast.success(t(page.props.flash.success));
                    else if (page.props.flash?.error) toast.error(t(page.props.flash.error));
                    closeChangePlanModal();
                },
                onError: (errors) => {
                    setChangePlanSubmitting(false);
                    if (!globalSettings?.is_demo) toast.dismiss();
                    if (typeof errors === 'string') toast.error(t(errors));
                    else toast.error(t('Failed to update plan: {{errors}}', { errors: Object.values(errors).join(', ') }));
                },
            }
        );
    };

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('Plans'), href: route('plans.index') },
        { title: t('Plan Requests') }
    ];

    // ============================================================
    // Table columns
    // ============================================================
    const columns = [
        {
            key: 'user.name',
            label: t('Company'),
            render: (_, row) => {
                const avatarUrl = row.user?.avatar
                    ? getDisplayUrl(row.user.avatar)
                    : getDisplayUrl('media/avatars/avatar.png');
                return (
                    <div className="flex items-center gap-3">
                        <img
                            src={avatarUrl}
                            alt={row.user?.name || 'User'}
                            className="h-10 w-10 rounded-full object-cover"
                            onError={(e) => {
                                const target = e.target as HTMLImageElement;
                                if (target.dataset.fallbackApplied === 'true') {
                                    target.src = AVATAR_FALLBACK_DATA_URI;
                                    return;
                                }
                                target.dataset.fallbackApplied = 'true';
                                target.src = getDisplayUrl('media/avatars/avatar.png');
                            }}
                        />
                        <div>
                            <div className="font-medium">{row.user?.name || '-'}</div>
                            <div className="text-xs text-gray-500">{row.user?.email || ''}</div>
                        </div>
                    </div>
                );
            }
        },
        {
            key: 'plan.name',
            label: t('Plan'),
            render: (_, row) => {
                const planName = row.plan?.name;
                if (!planName) return '-';
                return (
                    <span className={'inline-flex items-center rounded-md px-2 py-1 text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'}>
                        {capitalize(planName)}
                    </span>
                );
            }
        },
        {
            key: 'plan.duration',
            label: t('Duration'),
            render: (_, row) => {
                const duration = row.plan?.duration;
                if (!duration) return '-';
                return duration === 'monthly' ? t('Monthly') : t('Yearly');
            }
        },
        {
            key: 'status',
            label: t('Status'),
            render: (value) => {
                const statusColors: Record<string, string> = {
                    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    approved: 'bg-green-50 text-green-700 ring-green-600/20',
                    rejected: 'bg-red-50 text-red-700 ring-red-600/20'
                };
                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset capitalize ${statusColors[value] || 'bg-gray-50 text-gray-700 ring-gray-600/20'}`}>
                        {t(value)}
                    </span>
                );
            }
        },
        {
            key: 'created_at',
            label: t('Request Date'),
            sortable: true,
            render: (value) => window.appSettings?.formatDateTime(value, false) || '-'
        }
    ];

    // ============================================================
    // Actions — بدون شرط isSuperAdmin (يعتمد على permissions فقط)
    // v6.8: أضفنا "view" (Eye) و "change-plan" (Pencil/Edit)
    // ============================================================
    const actions = [
        {
            label: t('View Details'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
            requiredPermission: 'manage-plan-requests',
            // يظهر لكل الطلبات بكل الحالات
            condition: () => true,
        },
        {
            label: t('Change Plan'),
            icon: 'Pencil',
            action: 'change-plan',
            className: 'text-amber-500',
            requiredPermission: 'approve-plan-requests',
            // يظهر لكل الطلبات (يمكن تقييدها بـ pending فقط إن رغبت)
            condition: () => true,
        },
        {
            label: t('Approve'),
            icon: 'Check',
            action: 'approve',
            className: 'text-green-500',
            requiredPermission: 'approve-plan-requests',
            condition: (row) => row.status === 'pending'
        },
        {
            label: t('Reject'),
            icon: 'X',
            action: 'reject',
            className: 'text-red-500',
            requiredPermission: 'reject-plan-requests',
            condition: (row) => row.status === 'pending'
        }
    ];

    const statusOptions = [
        { value: '_empty_', label: t('All Status') },
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'rejected', label: t('Rejected') }
    ];

    // ============================================================
    // Pagination handler
    // ============================================================
    const handlePageChange = (url: string | null) => {
        if (!url) return;
        try {
            const urlObj = new URL(url, window.location.origin);
            const page = urlObj.searchParams.get('page') || 1;
            router.get(route('plan-requests.index'), {
                page,
                search: searchTerm || undefined,
                status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
                per_page: currentPerPage,
                sort_field: pageFilters.sort_field,
                sort_direction: pageFilters.sort_direction,
            }, { preserveState: true, preserveScroll: true });
        } catch {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    // ============================================================
    // Helper لتقديم شارة نوع المستخدم (صاحب/موظف)
    // ============================================================
    const renderUserTypeBadge = (u: any) => {
        if (!u) return '-';
        const isOwner = u.is_owner === true || u.type === 'company' || u.type === 'superadmin';
        const isStaff = u.is_staff === true || u.type === 'staff';

        if (isOwner) {
            return (
                <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    {t('Company Owner')}
                </span>
            );
        }
        if (isStaff) {
            return (
                <span className="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">
                    {t('Staff Member')}
                </span>
            );
        }
        return <span className="text-xs text-gray-500">{u.type || '-'}</span>;
    };

    const detailsUser = detailsData?.user;
    const detailsAvatarUrl = detailsUser?.avatar
        ? getDisplayUrl(detailsUser.avatar)
        : getDisplayUrl('media/avatars/avatar.png');

    // ============================================================
    // v6.10: عرض شاشة "Access Denied" إن لم يكن المستخدم سوبر ادمن
    // (الـ controller يحمي فعلياً، هذا مجرد UX)
    // ============================================================
    if (!userIsSuperAdmin) {
        return (
            <PageTemplate
                title={t('Plan Requests')}
                url="/plan-requests"
                breadcrumbs={breadcrumbs}
                noPadding
            >
                <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-12 flex flex-col items-center justify-center text-center">
                    <div className="h-16 w-16 rounded-full bg-red-50 dark:bg-red-900/30 flex items-center justify-center mb-4">
                        <svg className="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        {t('Access Denied')}
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 max-w-md">
                        {t('This page is only accessible to super admins. If you believe this is an error, please contact the system administrator.')}
                    </p>
                    <Button
                        variant="outline"
                        className="mt-6"
                        onClick={() => router.get(route('dashboard'))}
                    >
                        {t('Back to Dashboard')}
                    </Button>
                </div>
            </PageTemplate>
        );
    }

    return (
        <PageTemplate
            title={t('Plan Requests')}
            url="/plan-requests"
            breadcrumbs={breadcrumbs}
            noPadding
        >
            {/* Search and filters section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
                <SearchAndFilterBar
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={handleSearch}
                    filters={[
                        {
                            name: 'status',
                            label: t('Status'),
                            type: 'select',
                            value: selectedStatus,
                            onChange: setSelectedStatus,
                            options: statusOptions
                        }
                    ]}
                    showFilters={showFilters}
                    setShowFilters={setShowFilters}
                    hasActiveFilters={hasActiveFilters}
                    activeFilterCount={activeFilterCount}
                    onResetFilters={handleResetFilters}
                    onApplyFilters={applyFilters}
                    currentPerPage={String(currentPerPage)}
                    onPerPageChange={(value) => {
                        const newPerPage = parseInt(value, 10) || 10;
                        router.get(route('plan-requests.index'), {
                            page: 1,
                            per_page: newPerPage,
                            search: searchTerm || undefined,
                            status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
                            sort_field: pageFilters.sort_field,
                            sort_direction: pageFilters.sort_direction,
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Content section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={planRequests?.data || []}
                    from={planRequests?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                />

                <Pagination
                    from={planRequests?.from || 0}
                    to={planRequests?.to || 0}
                    total={planRequests?.total || 0}
                    links={planRequests?.links}
                    entityName={t("plan requests")}
                    onPageChange={handlePageChange}
                />
            </div>

            {/* ============================================================ */}
            {/* مودال عرض تفاصيل الطلب                                       */}
            {/* ============================================================ */}
            <Dialog open={detailsOpen} onOpenChange={(o) => o ? setDetailsOpen(true) : closeDetailsModal()}>
                <DialogContent className="sm:max-w-[560px] max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{t('Plan Request Details')}</DialogTitle>
                        <DialogDescription>
                            {t('Full information about this plan request and its owner.')}
                        </DialogDescription>
                    </DialogHeader>

                    {detailsLoading && (
                        <div className="py-10 flex items-center justify-center">
                            <div className="animate-spin h-8 w-8 border-2 border-gray-300 border-t-blue-600 rounded-full" />
                        </div>
                    )}

                    {detailsError && !detailsLoading && (
                        <div className="py-6 text-center text-red-600 text-sm">
                            {t('Failed to load details: {{error}}', { error: detailsError })}
                        </div>
                    )}

                    {detailsData && !detailsLoading && (
                        <div className="space-y-4">
                            {/* بطاقة المستخدم */}
                            <div className="flex items-center gap-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                                <img
                                    src={detailsAvatarUrl}
                                    alt={detailsUser?.name || 'User'}
                                    className="h-14 w-14 rounded-full object-cover"
                                    onError={(e) => {
                                        const target = e.target as HTMLImageElement;
                                        if (target.dataset.fallbackApplied === 'true') {
                                            target.src = AVATAR_FALLBACK_DATA_URI;
                                            return;
                                        }
                                        target.dataset.fallbackApplied = 'true';
                                        target.src = getDisplayUrl('media/avatars/avatar.png');
                                    }}
                                />
                                <div className="flex-1 min-w-0">
                                    <div className="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        {detailsUser?.name || '-'}
                                    </div>
                                    <div className="text-xs text-gray-500 truncate">
                                        {detailsUser?.email || ''}
                                    </div>
                                    <div className="mt-1">
                                        {renderUserTypeBadge(detailsUser)}
                                    </div>
                                </div>
                            </div>

                            {/* معلومات الشركة */}
                            <div>
                                <h4 className="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                    {t('Company Information')}
                                </h4>
                                <DetailRow label={t('Company Name')} value={detailsUser?.company_name} />
                                <DetailRow
                                    label={t('Account Type')}
                                    value={renderUserTypeBadge(detailsUser)}
                                />
                            </div>

                            {/* بيانات الترخيص */}
                            <div>
                                <h4 className="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                    {t('License Information')}
                                </h4>
                                <DetailRow
                                    label={t('License Key')}
                                    value={detailsUser?.license_key || t('Not set')}
                                    mono
                                />
                                <DetailRow
                                    label={t('Hardware ID')}
                                    value={detailsUser?.hardware_id || t('Not set')}
                                    mono
                                />
                            </div>

                            {/* بيانات الخطة */}
                            <div>
                                <h4 className="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                    {t('Plan Information')}
                                </h4>
                                <DetailRow label={t('Plan')} value={detailsData?.plan?.name ? capitalize(detailsData.plan.name) : '-'} />
                                <DetailRow
                                    label={t('Duration')}
                                    value={detailsData?.plan?.duration === 'monthly' ? t('Monthly') : detailsData?.plan?.duration === 'yearly' ? t('Yearly') : detailsData?.plan?.duration}
                                />
                                <DetailRow
                                    label={t('Status')}
                                    value={
                                        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ${
                                            detailsData?.status === 'approved' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
                                            detailsData?.status === 'rejected' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                                            'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
                                        }`}>
                                            {t(detailsData?.status)}
                                        </span>
                                    }
                                />
                            </div>

                            {/* التواريخ والموافقون */}
                            <div>
                                <h4 className="text-xs uppercase tracking-wide text-gray-500 mb-1">
                                    {t('Request Timeline')}
                                </h4>
                                <DetailRow
                                    label={t('Request Date')}
                                    value={detailsData?.created_at ? window.appSettings?.formatDateTime(detailsData.created_at, false) : '-'}
                                />
                                {detailsData?.approved_at && (
                                    <DetailRow
                                        label={t('Approved At')}
                                        value={window.appSettings?.formatDateTime(detailsData.approved_at, false)}
                                    />
                                )}
                                {detailsData?.approver && (
                                    <DetailRow label={t('Approved By')} value={detailsData.approver} />
                                )}
                                {detailsData?.rejected_at && (
                                    <DetailRow
                                        label={t('Rejected At')}
                                        value={window.appSettings?.formatDateTime(detailsData.rejected_at, false)}
                                    />
                                )}
                                {detailsData?.rejector && (
                                    <DetailRow label={t('Rejected By')} value={detailsData.rejector} />
                                )}
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={closeDetailsModal}>
                            {t('Close')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ============================================================ */}
            {/* مودال تغيير الخطة                                            */}
            {/* ============================================================ */}
            <Dialog open={changePlanOpen} onOpenChange={(o) => o ? setChangePlanOpen(true) : closeChangePlanModal()}>
                <DialogContent className="sm:max-w-[440px]">
                    <DialogHeader>
                        <DialogTitle>{t('Change Plan')}</DialogTitle>
                        <DialogDescription>
                            {t('Select a new plan for this request. If the request is already approved, the user\'s plan will be updated automatically.')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* ⭐ تنبيه إذا لم توجد خطط متاحة */}
                        {(!plans || plans.length === 0) && (
                            <div className="rounded-md bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-200">
                                <strong>{t('No plans available.')}</strong>{' '}
                                {t('Please make sure there are active plans in the system. Contact the administrator if you believe this is an error.')}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="new-plan">{t('New Plan')}</Label>
                            {/* ⭐ FIX: نمرّر plans.length بدلاً من plans فقط، لأن plans قد تكون undefined */}
                            <Select
                                value={newPlanId}
                                onValueChange={(v) => setNewPlanId(v)}
                            >
                                <SelectTrigger id="new-plan">
                                    <SelectValue placeholder={t('Select a plan')} />
                                </SelectTrigger>
                                <SelectContent
                                    // ⭐ مهم: position popper + sideOffset لتفادي مشكلة الاختفاء داخل Dialog
                                    position="popper"
                                    sideOffset={4}
                                    className="z-[100]"
                                >
                                    {(Array.isArray(plans) ? plans : []).map((p: any) => (
                                        <SelectItem key={p.id} value={String(p.id)}>
                                            {p.name}
                                            {p.duration && (
                                                <span className="text-xs text-gray-500 ml-1">
                                                    ({p.duration === 'monthly' ? t('Monthly') : t('Yearly')})
                                                </span>
                                            )}
                                            {changePlanCurrentId && p.id === changePlanCurrentId && (
                                                <span className="text-xs text-blue-600 ml-1">• {t('current')}</span>
                                            )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* ⭐ FIX: الرسالة تظهر فقط عند وجود اختيار فعلي مختلف عن فارغ */}
                        {newPlanId !== '' && changePlanCurrentId !== null && Number(newPlanId) === changePlanCurrentId && (
                            <p className="text-xs text-amber-600">
                                {t('Selected plan is the same as the current plan.')}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={closeChangePlanModal}
                            disabled={changePlanSubmitting}
                        >
                            {t('Cancel')}
                        </Button>
                        <Button
                            onClick={submitChangePlan}
                            disabled={
                                changePlanSubmitting
                                || !newPlanId
                                || (changePlanCurrentId !== null && Number(newPlanId) === changePlanCurrentId)
                                || (!plans || plans.length === 0)
                            }
                        >
                            {changePlanSubmitting ? t('Saving...') : t('Save Changes')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PageTemplate>
    );
}
