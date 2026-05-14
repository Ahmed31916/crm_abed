import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { ReactNode } from 'react';
import { FloatingChatGpt } from '@/components/FloatingChatGpt';
import { Tooltip, TooltipContent, TooltipTrigger } from './ui/tooltip';

export interface PageAction {
    label: string;
    icon?: ReactNode;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    onClick?: () => void;
    className?: string;
    tooltip?: string;
}

export interface PageTemplateProps {
    title: string;
    description: string;
    url: string;
    actions?: PageAction[];
    children: ReactNode;
    noPadding?: boolean;
    breadcrumbs?: BreadcrumbItem[];
}

export function PageTemplate({
    title,
    description,
    url,
    actions,
    children,
    noPadding = false,
    breadcrumbs
}: PageTemplateProps) {
    // Default breadcrumbs if none provided
    const pageBreadcrumbs: BreadcrumbItem[] = breadcrumbs || [
        {
            title,
            href: url,
        },
    ];

    return (
        <AppLayout breadcrumbs={pageBreadcrumbs}>
            <Head title={`${title} - ${(usePage().props as any).globalSettings?.titleText || 'Sales SaaS'}`} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                {/* <div className="flex h-full flex-1 flex-col gap-4 p-4"> */}
                {/* Header with action buttons */}
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">{title}</h1>
                    {actions && actions.length > 0 && (
                        <div className="flex items-center gap-2">
                            {actions.map((action, index) => {
                                // Determine button size based on whether it has a label
                                const hasLabel = action.label && action.label.trim() !== '';
                                const buttonSize = hasLabel ? 'sm' : 'icon';

                                const buttonElement = (
                                    <Button
                                        variant={action.variant || 'outline'}
                                        size={buttonSize}
                                        onClick={action.onClick}
                                        className="cursor-pointer"
                                    >
                                        {action.icon && !hasLabel && action.icon}
                                        {action.icon && hasLabel && action.icon}
                                        {hasLabel && action.label}
                                    </Button>
                                );

                                // Wrap with tooltip if tooltip text is provided
                                if (action.tooltip && action.tooltip.trim() !== '') {
                                    return (
                                        <Tooltip key={index}>
                                            <TooltipTrigger asChild>
                                                {buttonElement}
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{action.tooltip}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    );
                                }

                                // Return button without tooltip
                                return <span key={index}>{buttonElement}</span>;
                            })}
                        </div>
                    )}
                </div>

                {/* Content */}
                <div className={noPadding ? "" : "rounded-xl border p-6"}>
                    {children}
                </div>
            </div>
            <FloatingChatGpt />
        </AppLayout>
    );
}
