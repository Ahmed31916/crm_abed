import { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { DatePicker } from '@/components/ui/date-picker';

interface ReportFiltersProps {
    filters: {
        dateFrom: string;
        dateTo: string;
        [key: string]: any;
    };
    additionalFilters?: ReactNode;
}

import { ReactNode } from 'react';

export function ReportFilters({ filters, additionalFilters }: ReportFiltersProps) {
    const { t } = useTranslation();
    const [dateFrom, setDateFrom] = useState(filters.dateFrom);
    const [dateTo, setDateTo] = useState(filters.dateTo);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(window.location.pathname, {
            date_from: dateFrom,
            date_to: dateTo,
        });
    };

    const handleClearFilters = () => {
        const defaultDateFrom = new Date();
        defaultDateFrom.setMonth(defaultDateFrom.getMonth() - 1);
        const defaultDateTo = new Date();

        setDateFrom(defaultDateFrom.toISOString().split('T')[0]);
        setDateTo(defaultDateTo.toISOString().split('T')[0]);

        router.get(window.location.pathname);
    };

    return (
        <Card className="mb-6 p-4">
            <form onSubmit={handleFilterSubmit} className="flex items-end gap-4">
                <div className="flex-1">
                    <Label htmlFor="date_from">{t('From Date')}</Label>
                    <DatePicker
                        id="date_from"
                        selected={dateFrom}
                        onChange={(e) => setDateFrom(e)}
                        className='w-full'
                        required
                    />
                </div>
                <div className="flex-1">
                    <Label htmlFor="date_to">{t('To Date')}</Label>
                    <DatePicker
                        id="date_to"
                        selected={dateTo}
                        onChange={(e) => setDateTo(e)}
                        className='w-full'
                        required
                    />
                </div>
                {additionalFilters}
                <Button type="submit">{t('Apply Filters')}</Button>
                <Button type="button" variant="outline" onClick={handleClearFilters}>{t('Clear Filters')}</Button>
            </form>
        </Card>
    );
}
