import { PageTemplate } from '@/components/page-template';
import { CrudTable } from '@/components/CrudTable';
import { companyPlanRequestsConfig } from '@/config/crud/company-plan-requests';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function CompanyPlanRequestsPage() {
  const { t } = useTranslation();
  const { flash, planRequests, filters: pageFilters = {}, auth } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [filterValues, setFilterValues] = useState<Record<string, any>>({});
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => {
    if (flash?.success) {
      toast.success(flash.success);
    }
    if (flash?.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  useEffect(() => {
    const initialFilters: Record<string, any> = {};
    companyPlanRequestsConfig.filters?.forEach(filter => {
      initialFilters[filter.key] = pageFilters[filter.key] || 'all';
    });
    setFilterValues(initialFilters);
  }, []);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    Object.entries(filterValues).forEach(([key, value]) => {
      if (value && value !== 'all') {
        params[key] = value;
      }
    });

    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route("company.plan-requests.index"), params, { preserveState: true, preserveScroll: true });
  };

  const handleFilterChange = (key: string, value: any) => {
    setFilterValues(prev => ({ ...prev, [key]: value }));

    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    const newFilters = { ...filterValues, [key]: value };
    Object.entries(newFilters).forEach(([k, v]) => {
      if (v && v !== 'all') {
        params[k] = v;
      }
    });

    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route("company.plan-requests.index"), params, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Plan Requests') }
  ];

  const hasActiveFilters = () => {
    return Object.entries(filterValues).some(([key, value]) => {
      return value && value !== '';
    }) || searchTerm !== '';
  };

  return (
    <PageTemplate
      title={t('Plan Requests')}
      url="/company/plan-requests"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={companyPlanRequestsConfig.filters?.map(filter => ({
            name: filter.key,
            label: t(filter.label),
            type: 'select',
            value: filterValues[filter.key] || '',
            onChange: (value) => handleFilterChange(filter.key, value),
            options: filter.options?.map(option => ({
              value: option.value,
              label: t(option.label)
            })) || []
          })) || []}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={() => {
            return Object.values(filterValues).filter(v => v && v !== '').length + (searchTerm ? 1 : 0);
          }}
          onResetFilters={() => {
            setSearchTerm('');
            setFilterValues({});
            router.get(route('company.plan-requests.index'), { page: 1 }, { preserveState: true, preserveScroll: true });
          }}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            const params: any = { page: 1, per_page: parseInt(value) };

            if (searchTerm) {
              params.search = searchTerm;
            }

            Object.entries(filterValues).forEach(([key, val]) => {
              if (val && val !== '') {
                params[key] = val;
              }
            });

            router.get(route('company.plan-requests.index'), params, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={companyPlanRequestsConfig.table.columns.map(col => ({
            ...col,
            label: t(col.label)
          }))}
          actions={[]}
          data={planRequests?.data || []}
          from={planRequests?.from || 1}
          onAction={() => {}}
          permissions={permissions}
          entityPermissions={companyPlanRequestsConfig.entity.permissions}
        />

        <Pagination
          from={planRequests?.from || 0}
          to={planRequests?.to || 0}
          total={planRequests?.total || 0}
          links={planRequests?.links}
          entityName={t("plan requests")}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}
