import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem, useSidebar } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { createPortal } from 'react-dom';

// Store expanded menu state in localStorage
const STORAGE_KEY = 'nav_expanded_items';

export function NavMain({ items = [], position }: { items: NavItem[]; position: 'left' | 'right' }) {
    const { t } = useTranslation();
    const page = usePage();
    const { state } = useSidebar();

    // Check if the document is in RTL mode
    const isRtl = document.documentElement.dir === 'rtl';

    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});
    const [collapsedMenuOpen, setCollapsedMenuOpen] = useState<string | null>(null);
    const [menuPosition, setMenuPosition] = useState<{ top: number } | null>(null);

    const handleCollapsedMenuClick = (title: string, event: React.MouseEvent) => {
        const rect = event.currentTarget.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const menuHeight = 300; // Approximate max height

        let top = rect.top;
        if (top + menuHeight > viewportHeight) {
            top = viewportHeight - menuHeight - 10;
        }

        setMenuPosition({ top });
        setCollapsedMenuOpen(collapsedMenuOpen === title ? null : title);
    };

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (collapsedMenuOpen) {
                setCollapsedMenuOpen(null);
            }
        };

        const sidebarContent = document.querySelector('[data-sidebar="content"]');

        if (collapsedMenuOpen && sidebarContent) {
            document.addEventListener('click', handleClickOutside);
            (sidebarContent as HTMLElement).style.pointerEvents = 'none';
        }

        return () => {
            document.removeEventListener('click', handleClickOutside);
            if (sidebarContent) {
                (sidebarContent as HTMLElement).style.pointerEvents = '';
            }
        };
    }, [collapsedMenuOpen]);

    // Determine the actual position considering RTL mode
    const effectivePosition = position;

    // Initialize expanded state
    useEffect(() => {
        // Start with a clean slate - close all menus
        const newExpandedItems: Record<string, boolean> = {};

        // Process menus that should be expanded
        const processMenuItems = (menuItems: NavItem[], parentKey?: string) => {
            menuItems.forEach(item => {
                // If this is the active item or contains the active item
                const isItemActive = isActive(item.href);
                const hasActiveChild = item.children && isChildActive(item.children);

                // If this item or its children are active, expand it
                if (parentKey && (isItemActive || hasActiveChild)) {
                    newExpandedItems[parentKey] = true;
                }

                // If this item has children and is active, has active children, or defaultOpen is true, expand it
                if (item.children && (isItemActive || hasActiveChild || item.defaultOpen === true)) {
                    newExpandedItems[item.title] = true;

                    // Recursively check children
                    processMenuItems(item.children, item.title);
                }

                // Check nested children with their own keys
                if (item.children) {
                    checkNestedChildren(item.children, 1, newExpandedItems);
                }
            });
        };

        processMenuItems(items);

        // Update state and save to localStorage
        setExpandedItems(newExpandedItems);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newExpandedItems));
        } catch (e) {
        }
    }, [page.url, items]); // Re-run when URL changes or items change

    // Helper function to check nested children for active items
    const checkNestedChildren = (
        children: NavItem[],
        level: number,
        newExpandedItems: Record<string, boolean>
    ) => {
        children.forEach(child => {
            const childKey = `${level}-${child.title}`;
            const isChildItemActive = isActive(child.href);
            const hasActiveChild = child.children && isChildActive(child.children);

            if (child.children && (isChildItemActive || hasActiveChild)) {
                newExpandedItems[childKey] = true;
                checkNestedChildren(child.children, level + 1, newExpandedItems);
            }
        });
    };

    const toggleExpand = (title: string) => {
        const newExpandedItems = {
            ...expandedItems,
            [title]: !expandedItems[title]
        };

        setExpandedItems(newExpandedItems);

        // Save to localStorage
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newExpandedItems));
        } catch (e) {
        }
    };

    const isActive = (href?: string) => {
        if (!href) return false;

        // Extract pathname from href if it's a full URL
        const hrefPath = href.startsWith('http') ? new URL(href).pathname : href;


        // Get current path without query parameters or hash
        const currentPath = page.url.split('?')[0].split('#')[0];

        // Normalize paths by removing trailing slashes
        const normalizedHref = hrefPath.replace(/\/$/, '');
        const normalizedCurrent = currentPath.replace(/\/$/, '');

        // Check exact match first
        if (normalizedCurrent === normalizedHref) {
            return true;
        }

        // Check if current path is a sub-path of href
        // This handles cases like:
        // - /coupons/123 when href is /coupons
        // - /hr/employees/create when href is /hr/employees
        // - /meetings/meetings when href is /meetings/meetings
        if (normalizedCurrent.startsWith(normalizedHref + '/')) {
            return true;
        }

        return false;
    };

    const isChildActive = (children?: NavItem[]) => {
        if (!children) return false;
        return children.some(child => isActive(child.href) || isChildActive(child.children));
    };

    const renderSubMenu = (children: NavItem[], level: number = 1) => {
        return (
            <SidebarMenuSub>
                {children.map(child => (
                    <div key={child.title}>
                        {child.children ? (
                            // Nested submenu item with children
                            <>
                                <SidebarMenuSubItem>
                                    <SidebarMenuSubButton
                                        isActive={isChildActive(child.children)}
                                        onClick={() => toggleExpand(`${level}-${child.title}`)}
                                    >
                                        <div className={`flex items-center gap-2 ${effectivePosition === 'right' ? 'justify-end text-right' : 'justify-start text-left'}`}>
                                            <span>{child.title}</span>
                                            {state !== "collapsed" && (
                                                expandedItems[`${level}-${child.title}`] ?
                                                    <ChevronDown className="h-3 w-3 ml-auto" /> :
                                                    <ChevronRight className="h-3 w-3 ml-auto" />
                                            )}
                                        </div>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>

                                {/* Render nested children */}
                                {expandedItems[`${level}-${child.title}`] && renderSubMenu(child.children, level + 1)}
                            </>
                        ) : (
                            // Regular submenu item
                            <SidebarMenuSubItem>
                                <SidebarMenuSubButton asChild isActive={isActive(child.href)}>
                                    <Link
                                        href={child.href || '#'}
                                        prefetch
                                        target={child.target}
                                        className={`flex items-center gap-2 ${effectivePosition === 'right' ? 'justify-end text-right' : 'justify-start text-left'}`}
                                    >
                                        <span>{child.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        )}
                    </div>
                ))}
            </SidebarMenuSub>
        );
    };

    return (
        <SidebarGroup className="px-1.5 py-0">
            <SidebarGroupLabel className={`flex w-full text-xs ${effectivePosition === 'right' ? 'justify-end' : 'justify-start'}`}></SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <div key={item.title}>
                        {item.children ? (
                            // Parent item with children
                            <>
                                <SidebarMenuItem>
                                    {state === "collapsed" ? (
                                        <>
                                            <SidebarMenuButton
                                                isActive={isChildActive(item.children)}
                                                tooltip={{ children: item.title, side: effectivePosition === 'right' ? 'left' : 'right' }}
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleCollapsedMenuClick(item.title, e);
                                                }}
                                            >
                                                {item.icon && <item.icon className="h-4 w-4" />}
                                            </SidebarMenuButton>
                                            {collapsedMenuOpen === item.title && menuPosition && createPortal(
                                                <div className="z-50 min-w-[200px] rounded-md border bg-popover p-2 text-popover-foreground shadow-md outline-none"
                                                    style={{
                                                        position: 'fixed',
                                                        [effectivePosition === 'right' ? 'right' : 'left']: effectivePosition === 'right' ? 'calc(100% + 0.5rem)' : 'calc(3rem + 0.5rem)',
                                                        top: `${menuPosition.top}px`,
                                                        pointerEvents: 'auto'
                                                    }}
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    <div className="space-y-1">
                                                        {item.children.map(child => (
                                                            <Link
                                                                key={child.title}
                                                                href={child.href || '#'}
                                                                className="block px-3 py-2 text-sm rounded-md hover:bg-accent hover:text-accent-foreground"
                                                                onClick={() => setCollapsedMenuOpen(null)}
                                                            >
                                                                {child.title}
                                                            </Link>
                                                        ))}
                                                    </div>
                                                </div>,
                                                document.body
                                            )}
                                        </>
                                    ) : (
                                        <SidebarMenuButton
                                            isActive={isChildActive(item.children)}
                                            onClick={() => toggleExpand(item.title)}
                                        >
                                            <div className={`flex items-center gap-2 w-full ${effectivePosition === 'right' ? 'justify-end text-right' : 'justify-start text-left'}`}>
                                                {effectivePosition === 'right' ? (
                                                    <>
                                                        <span>{item.title}</span>
                                                        {item.icon && <item.icon className="h-4 w-4" />}
                                                        {expandedItems[item.title] ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                                                    </>
                                                ) : (
                                                    <>
                                                        {item.icon && <item.icon className="h-4 w-4"/>}
                                                        <div className="flex items-center gap-1">
                                                            <span>{item.title}</span>
                                                            {item.badge && (
                                                                <span className="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-primary text-white">
                                                                    {item.badge.label}
                                                                </span>
                                                            )}
                                                        </div>
                                                        {expandedItems[item.title] ? <ChevronDown className="h-3 w-3 ml-auto" /> : <ChevronRight className="h-3 w-3 ml-auto" />}
                                                    </>
                                                )}
                                            </div>
                                        </SidebarMenuButton>
                                    )}
                                </SidebarMenuItem>

                                {/* Child items */}
                                {state !== "collapsed" && expandedItems[item.title] && renderSubMenu(item.children)}
                            </>
                        ) : (
                            // Regular item without children
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive(item.href)} tooltip={{ children: item.title }}>
                                    {item.target === '_blank' ? (
                                        <a
                                            href={item.href || '#'}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className={`flex items-center gap-2 ${effectivePosition === 'right' ? 'justify-end text-right' : 'justify-start text-left'}`}
                                        >
                                            {effectivePosition === 'right' ? (
                                                <>
                                                    {state !== "collapsed" && <span>{item.title}</span>}
                                                    {item.icon && <item.icon className="h-4 w-4" />}
                                                </>
                                            ) : (
                                                <>
                                                    {item.icon && <item.icon className="h-4 w-4" />}
                                                    {state !== "collapsed" && <span>{item.title}</span>}
                                                </>
                                            )}
                                        </a>
                                    ) : (
                                        <Link
                                            href={item.href || '#'}
                                            prefetch
                                            className={`flex items-center gap-2 ${effectivePosition === 'right' ? 'justify-end text-right' : 'justify-start text-left'}`}
                                        >
                                            {effectivePosition === 'right' ? (
                                                <>
                                                    {state !== "collapsed" && <span>{item.title}</span>}
                                                    {item.icon && <item.icon className="h-4 w-4" />}
                                                </>
                                            ) : (
                                                <>
                                                    {item.icon && <item.icon className="h-4 w-4" />}
                                                    {state !== "collapsed" && <span>{item.title}</span>}
                                                </>
                                            )}
                                        </Link>
                                    )}
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        )}
                    </div>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
