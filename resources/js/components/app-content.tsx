import { SidebarInset } from '@/components/ui/sidebar';
import { useLayout } from '@/contexts/LayoutContext';
import * as React from 'react';

export function AppContent({ variant = 'header', children, ...props }) {
    const { position } = useLayout();

    if (variant === 'sidebar') {
        return (
            <SidebarInset {...props}>
                <div dir={position === 'right' ? 'rtl' : 'ltr'}>
                    {children}
                </div>
            </SidebarInset>
        );
    }

    return (
        <main className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" {...props}>
            <div dir={position === 'right' ? 'rtl' : 'ltr'}>
                {children}
            </div>
        </main>
    );
}
