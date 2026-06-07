import { NavMain } from '@/components/nav-main';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader} from '@/components/ui/sidebar';
import { useLayout } from '@/contexts/LayoutContext';
import { useSidebarSettings } from '@/contexts/SidebarContext';
import { useBrand } from '@/contexts/BrandContext';
import { type NavItem } from '@/types';
import { Link, usePage} from '@inertiajs/react';
import { Folder, LayoutGrid, ShoppingBag, Users, Settings, FileText, Briefcase, Calendar, CreditCard, Ticket, Gift, CalendarDays, Image, Building2, Phone, TrendingUp, Package, Megaphone, DollarSign, Palette, Mail, Activity, NotebookPen, MegaphoneIcon, TicketPercent } from 'lucide-react';
import AppLogo from './app-logo';
import { useEffect, useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { hasPermission } from '@/utils/authorization';
import { getDisplayUrl } from '@/utils/helper';


export function AppSidebar() {
    const { t, i18n } = useTranslation();
    const { auth } = usePage().props as any;
    const userRole = auth.user?.type || auth.user?.role;
    const permissions = auth?.permissions || [];

    // Get current direction
    const isRtl = document.documentElement.dir === 'rtl';

    // Business switch handler removed

    const getSuperAdminNavItems = (): NavItem[] => [
        {
            title: t('Dashboard'),
            href: route('dashboard'),
            icon: LayoutGrid,
        },

        {
            title: t('Companies'),
            href: route('companies.index'),
            icon: Briefcase,
        },
        {
            title: t('Products'),
            href: route('products.index'),
            icon: Package,
        },
        {
            title: t('Media Library'),
            href: route('media-library'),
            icon: Image,
        },


        {
            title: t('Plans'),
            icon: CreditCard,
            children: [
                {
                    title: t('Plans'),
                    href: route('plans.index')
                },
                {
                    title: t('Plan Request'),
                    href: route('plan-requests.index')
                },
                {
                    title: t('Plan Orders'),
                    href: route('plan-orders.index')
                }
            ]
        },
        {
            title: t('Coupons'),
            href: route('coupons.index'),
            icon: TicketPercent,
        },

        {
            title: t('Currency'),
            href: route('currencies.index'),
            icon: DollarSign,
        },
        {
            title: t('Referral Program'),
            href: route('referral.index'),
            icon: Gift,
        },
        {
            title: t('Landing Page'),
            icon: Palette,
            children: [
                {
                    title: t('Landing Page'),
                    href: route('landing-page')
                },
                {
                    title: t('Custom Pages'),
                    href: route('landing-page.custom-pages.index')
                },
                {
                    title: t('Contact Inquiries'),
                    href: route('contact-messages.index')
                },
                {
                    title: t('Newsletters'),
                    href: route('newsletters.index')
                },
            ]
        },
        {
            title: t('Email Templates'),
            href: route('email-templates.index'),
            icon: Mail,
        },
        {
            title: t('Settings'),
            href: route('settings'),
            icon: Settings,
        }
    ];

    const getCompanyNavItems = (): NavItem[] => {
        const items: NavItem[] = [];

        // 1. Dashboard
        if (hasPermission(permissions, 'manage-dashboard')) {
            items.push({
                title: t('Dashboard'),
                href: route('dashboard'),
                icon: LayoutGrid,
            });
        }

        // 2. Staff
        const staffChildren = [];
        if (hasPermission(permissions, 'manage-users')) {
            staffChildren.push({
                title: t('Users'),
                href: route('users.index')
            });
        }
        if (hasPermission(permissions, 'manage-roles')) {
            staffChildren.push({
                title: t('Roles'),
                href: route('roles.index')
            });
        }
        if (staffChildren.length > 0) {
            items.push({
                title: t('Staff'),
                icon: Users,
                children: staffChildren
            });
        }

        // 3. Lead Management
        const leadChildren = [];
        if (hasPermission(permissions, 'manage-lead-statuses')) {
            leadChildren.push({
                title: t('Lead Status'),
                href: route('lead-statuses.index')
            });
        }
        if (hasPermission(permissions, 'manage-lead-sources')) {
            leadChildren.push({
                title: t('Lead Sources'),
                href: route('lead-sources.index')
            });
        }
        if (hasPermission(permissions, 'manage-leads')) {
            leadChildren.push({
                title: t('Leads'),
                href: route('leads.index')
            });
        }
        if (leadChildren.length > 0) {
            items.push({
                title: t('Lead Management'),
                icon: Users,
                children: leadChildren
            });
        }

        // 4. Opportunity Management
        const opportunityChildren = [];
        if (hasPermission(permissions, 'manage-opportunity-stages')) {
            opportunityChildren.push({
                title: t('Opportunity Stages'),
                href: route('opportunity-stages.index')
            });
        }
        if (hasPermission(permissions, 'manage-opportunity-sources')) {
            opportunityChildren.push({
                title: t('Opportunity Sources'),
                href: route('opportunity-sources.index')
            });
        }
        if (hasPermission(permissions, 'manage-opportunities')) {
            opportunityChildren.push({
                title: t('Opportunities'),
                href: route('opportunities.index')
            });
        }
        if (opportunityChildren.length > 0) {
            items.push({
                title: t('Opportunity Management'),
                icon: Briefcase,
                children: opportunityChildren
            });
        }

        // 5. Account Management
        const accountChildren = [];
        if (hasPermission(permissions, 'manage-account-types')) {
            accountChildren.push({
                title: t('Account Types'),
                href: route('account-types.index')
            });
        }
        if (hasPermission(permissions, 'manage-account-industries')) {
            accountChildren.push({
                title: t('Account Industries'),
                href: route('account-industries.index')
            });
        }
        if (hasPermission(permissions, 'manage-accounts')) {
            accountChildren.push({
                title: t('Accounts'),
                href: route('accounts.index')
            });
        }
        if (accountChildren.length > 0) {
            items.push({
                title: t('Account Management'),
                icon: Building2,
                children: accountChildren
            });
        }

        // 6. Contacts
        if (hasPermission(permissions, 'manage-contacts')) {
            items.push({
                title: t('Contacts'),
                href: route('contacts.index'),
                icon: Users,
            });
        }

        // 7. Product Setup
        const productSetupChildren = [];
        if (hasPermission(permissions, 'manage-taxes')) {
            productSetupChildren.push({
                title: t('Taxes'),
                href: route('taxes.index')
            });
        }
        if (hasPermission(permissions, 'manage-brands')) {
            productSetupChildren.push({
                title: t('Brands'),
                href: route('brands.index')
            });
        }
        if (hasPermission(permissions, 'manage-categories')) {
            productSetupChildren.push({
                title: t('Categories'),
                href: route('categories.index')
            });
        }
        if (productSetupChildren.length > 0) {
            items.push({
                title: t('Product Setup'),
                icon: Package,
                children: productSetupChildren
            });
        }

        // 8. Products
        if (hasPermission(permissions, 'manage-products')) {
            items.push({
                title: t('Products'),
                href: route('products.index'),
                icon: ShoppingBag,
            });
        }

        // 9. Quotes
        if (hasPermission(permissions, 'manage-quotes')) {
            items.push({
                title: t('Quotes'),
                href: route('quotes.index'),
                icon: FileText,
            });
        }

        // 10. Sales Orders
        if (hasPermission(permissions, 'manage-sales-orders')) {
            items.push({
                title: t('Sales Orders'),
                href: route('sales-orders.index'),
                icon: ShoppingBag,
            });
        }

        // 11. Invoices
        if (hasPermission(permissions, 'manage-invoices')) {
            items.push({
                title: t('Invoices'),
                href: route('invoices.index'),
                icon: FileText,
            });
        }

        // 12. Delivery Orders
        if (hasPermission(permissions, 'manage-delivery-orders')) {
            items.push({
                title: t('Delivery Orders'),
                href: route('delivery-orders.index'),
                icon: Ticket,
            });
        }

        // 13. Return Orders
        if (hasPermission(permissions, 'view-return-orders')) {
            items.push({
                title: t('Return Orders'),
                href: route('return-orders.index'),
                icon: FileText,
            });
        }

        // 14. Receipt Orders
        if (hasPermission(permissions, 'manage-receipt-orders')) {
            items.push({
                title: t('Receipt Orders'),
                href: route('receipt-orders.index'),
                icon: FileText,
            });
        }

        // 15. Purchase Orders
        if (hasPermission(permissions, 'manage-purchase-orders')) {
            items.push({
                title: t('Purchase Orders'),
                href: route('purchase-orders.index'),
                icon: ShoppingBag,
            });
        }

        // 17. Document Management
        const documentChildren = [];
        if (hasPermission(permissions, 'manage-document-folders')) {
            documentChildren.push({
                title: t('Folders'),
                href: route('document-folders.index')
            });
        }
        if (hasPermission(permissions, 'manage-document-types')) {
            documentChildren.push({
                title: t('Types'),
                href: route('document-types.index')
            });
        }
        if (hasPermission(permissions, 'manage-documents')) {
            documentChildren.push({
                title: t('Documents'),
                href: route('documents.index')
            });
        }
        if (documentChildren.length > 0) {
            items.push({
                title: t('Document Management'),
                icon: Folder,
                children: documentChildren
            });
        }

        // 18. Campaign Management
        const campaignChildren = [];
        if (hasPermission(permissions, 'manage-campaign-types')) {
            campaignChildren.push({
                title: t('Campaign Types'),
                href: route('campaign-types.index')
            });
        }
        if (hasPermission(permissions, 'manage-target-lists')) {
            campaignChildren.push({
                title: t('Target Lists'),
                href: route('target-lists.index')
            });
        }
        if (hasPermission(permissions, 'manage-campaigns')) {
            campaignChildren.push({
                title: t('Campaigns'),
                href: route('campaigns.index')
            });
        }
        if (campaignChildren.length > 0) {
            items.push({
                title: t('Campaign Management'),
                icon: Megaphone,
                children: campaignChildren
            });
        }

        // 19. Project Management
        const projectChildren = [];
        if (hasPermission(permissions, 'manage-task-statuses')) {
            projectChildren.push({
                title: t('Task Status'),
                href: route('task-statuses.index')
            });
        }
        if (hasPermission(permissions, 'manage-project-tasks')) {
            projectChildren.push({
                title: t('Project Tasks'),
                href: route('project-tasks.index')
            });
        }
        if (hasPermission(permissions, 'manage-projects')) {
            projectChildren.push({
                title: t('Projects'),
                href: route('projects.index')
            });
        }
        if (projectChildren.length > 0) {
            items.push({
                title: t('Project Management'),
                icon: Briefcase,
                children: projectChildren
            });
        }

        // 20. Calendar
        if (hasPermission(permissions, 'manage-meetings') || hasPermission(permissions, 'manage-calls') || hasPermission(permissions, 'manage-project-tasks')) {
            items.push({
                title: t('Calendar'),
                href: route('calendar.index'),
                icon: Calendar,
            });
        }

        // 21. Cases
        if (hasPermission(permissions, 'manage-cases')) {
            items.push({
                title: t('Cases'),
                href: route('cases.index'),
                icon: FileText,
            });
        }

        // 22. Meetings
        if (hasPermission(permissions, 'manage-meetings')) {
            items.push({
                title: t('Meetings'),
                href: route('meetings.index'),
                icon: CalendarDays,
            });
        }

        // 23. Calls
        if (hasPermission(permissions, 'manage-calls')) {
            items.push({
                title: t('Calls'),
                href: route('calls.index'),
                icon: Phone,
            });
        }

        // 24. Reports
        if (hasPermission(permissions, 'manage-reports')) {
            items.push({
                title: t('Reports'),
                icon: TrendingUp,
                children: [
                    {
                        title: t('Lead Reports'),
                        href: route('reports.leads')
                    },
                    {
                        title: t('Sales Reports'),
                        href: route('reports.sales')
                    },
                    {
                        title: t('Product Reports'),
                        href: route('reports.product-reports')
                    },
                    {
                        title: t('Contact Reports'),
                        href: route('reports.customers')
                    },
                    {
                        title: t('Project Reports'),
                        href: route('reports.projects')
                    }
                ]
            });
        }

        // 25. Shipping Provider Types
        if (hasPermission(permissions, 'manage-shipping-provider-types')) {
            items.push({
                title: t('Shipping Provider Types'),
                href: route('shipping-provider-types.index'),
                icon: Ticket,
            });
        }



        // 26. Plans
        const planChildren = [];
        if (hasPermission(permissions, 'manage-plans')) {
            planChildren.push({
                title: t('Plans'),
                href: route('plans.index')
            });
        }
        if (hasPermission(permissions, 'manage-plan-requests')) {
            planChildren.push({
                title: t('Plan Requests'),
                href: route('plan-requests.index')
            });
        }
        if (hasPermission(permissions, 'manage-plan-orders')) {
            planChildren.push({
                title: t('Plan Orders'),
                href: route('plan-orders.index')
            });
        }
        if (planChildren.length > 0) {
            items.push({
                title: t('Plans'),
                icon: CreditCard,
                children: planChildren
            });
        }

        // 27. Referral Program
        if (hasPermission(permissions, 'manage-referral')) {
            items.push({
                title: t('Referral Program'),
                href: route('referral.index'),
                icon: Gift,
            });
        }

        // 28. Media Library
        if (hasPermission(permissions, 'manage-media')) {
            items.push({
                title: t('Media Library'),
                href: route('media-library'),
                icon: Image,
            });
        }

        // 29. Streams
        if (hasPermission(permissions, 'manage-stream')) {
            items.push({
                title: t('Streams'),
                href: route('stream.index'),
                icon: Activity,
            });
        }

        // 30. Notes
        if (hasPermission(permissions, 'manage-notes')) {
            items.push({
                title: t('Notes'),
                href: route('notes.index'),
                icon: NotebookPen,
            });
        }

        // 31. Announcements
        const announcementChildren = [];
        if (hasPermission(permissions, 'manage-announcement-categories')) {
            announcementChildren.push({
                title: t('Categories'),
                href: route('announcement-categories.index')
            });
        }
        if (hasPermission(permissions, 'manage-announcements')) {
            announcementChildren.push({
                title: t('Announcements'),
                href: route('announcements.index')
            });
        }
        if (announcementChildren.length > 0) {
            items.push({
                title: t('Announcements'),
                icon: MegaphoneIcon,
                children: announcementChildren
            });
        }

        // 32. Notification Templates
        if (hasPermission(permissions, 'manage-notification-templates')) {
            items.push({
                title: t('Notification Templates'),
                href: route('notification-templates.index'),
                icon: Mail,
            });
        }

        // 33. Settings
        if (hasPermission(permissions, 'manage-settings')) {
            items.push({
                title: t('Settings'),
                href: route('settings'),
                icon: Settings,
            });
        }

        return items;
    };

    const mainNavItems = userRole === 'superadmin' ? getSuperAdminNavItems() : getCompanyNavItems();

    const { position, effectivePosition } = useLayout();
    const { variant, collapsible, style } = useSidebarSettings();
    const { logoLight, logoDark, favicon, updateBrandSettings } = useBrand();
    const [sidebarStyle, setSidebarStyle] = useState({});

    useEffect(() => {

        // Apply styles based on sidebar style
        if (style === 'colored') {
            setSidebarStyle({ backgroundColor: 'var(--primary)', color: 'white' });
        } else if (style === 'gradient') {
            setSidebarStyle({
                background: 'linear-gradient(to bottom, var(--primary), color-mix(in srgb, var(--primary), transparent 20%))',
                color: 'white'
            });
        } else {
            setSidebarStyle({});
        }
    }, [style]);

    const filteredNavItems = mainNavItems;

    // Get the first available menu item's href for logo link
    const getFirstAvailableHref = () => {
        if (filteredNavItems.length === 0) return route('dashboard');

        const firstItem = filteredNavItems[0];
        if (firstItem.href) {
            return firstItem.href;
        } else if (firstItem.children && firstItem.children.length > 0) {
            return firstItem.children[0].href || route('dashboard');
        }
        return route('dashboard');
    };

    return (
        <Sidebar
            side={effectivePosition}
            collapsible={collapsible}
            variant={variant}
            className={style !== 'plain' ? 'sidebar-custom-style' : ''}
        >
            <SidebarHeader className={style !== 'plain' ? 'sidebar-styled' : ''} style={sidebarStyle}>
                <div className="flex justify-center items-center p-2">
                    <Link href={getFirstAvailableHref()} prefetch className="flex items-center justify-center">
                        {/* Logo for expanded sidebar */}
                        <div className="group-data-[collapsible=icon]:hidden flex items-center">
                            {(() => {
                                const isDark = document.documentElement.classList.contains('dark');
                                const currentLogo = isDark ? logoLight : logoDark;
                                const displayUrl = getDisplayUrl(currentLogo) ?? currentLogo;

                                return displayUrl ? (
                                    <img
                                        key={`${currentLogo}-${Date.now()}`}
                                        src={displayUrl}
                                        alt="Logo"
                                        className="w-auto transition-all duration-200"
                                        onError={() => updateBrandSettings({ [isDark ? 'logoLight' : 'logoDark']: '' })}
                                    />
                                ) : (
                                    <div className="h-12 text-inherit font-semibold flex items-center text-lg tracking-tight">
                                        WorkDo
                                    </div>
                                );
                            })()}
                        </div>

                        {/* Icon for collapsed sidebar */}
                        <div className="h-8 w-8 hidden group-data-[collapsible=icon]:block">
                            {(() => {
                                const displayFavicon = favicon ? getDisplayUrl(favicon) : '';

                                return displayFavicon ? (
                                    <img
                                        key={`${favicon}-${Date.now()}`}
                                        src={displayFavicon}
                                        alt="Icon"
                                        className="h-8 w-8 transition-all duration-200"
                                        onError={() => updateBrandSettings({ favicon: '' })}
                                    />
                                ) : (
                                    <div className="h-8 w-8 bg-primary text-white rounded flex items-center justify-center font-bold shadow-sm">
                                        W
                                    </div>
                                );
                            })()}
                        </div>
                    </Link>
                </div>

                {/* Business Switcher removed */}
            </SidebarHeader>

            <SidebarContent>
                <div style={sidebarStyle} className={`h-full ${style !== 'plain' ? 'sidebar-styled' : ''}`}>
                    <NavMain items={filteredNavItems} position={effectivePosition} />
                </div>
            </SidebarContent>

            <SidebarFooter>
                {/* <NavFooter items={footerNavItems} className="mt-auto" position={position} /> */}
                {/* Profile menu moved to header */}
            </SidebarFooter>
        </Sidebar>
    );
}
