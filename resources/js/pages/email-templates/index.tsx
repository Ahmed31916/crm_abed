import { useState } from 'react'
import { Head, router, usePage } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Pagination } from '@/components/ui/pagination'
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar'
import { CrudTable } from '@/components/CrudTable'
import { useTranslation } from 'react-i18next'

export default function EmailTemplatesIndex() {
  const { t } = useTranslation()
  const { templates, filters: pageFilters = {} } = usePage().props as any

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '')
  const [showFilters, setShowFilters] = useState(false)

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault()
    applyFilters()
  }

  const applyFilters = () => {
    router.get(route('email-templates.index'), {
      page: 1,
      search: searchTerm || undefined,
      ...(pageFilters.per_page && pageFilters.per_page !== 10 && { per_page: pageFilters.per_page })
    }, { preserveState: true, preserveScroll: true })
  }

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'desc' ? 'asc' : 'desc'

    router.get(route('email-templates.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      ...(pageFilters.per_page && pageFilters.per_page !== 10 && { per_page: pageFilters.per_page })
    }, { preserveState: true, preserveScroll: true })
  }

  const handleAction = (action: string, item: any) => {
    if (action === 'view') {
      router.get(route('email-templates.show', item.id))
    }
  }

  const handleResetFilters = () => {
    setSearchTerm('')
    setShowFilters(false)
    router.get(route('email-templates.index'), { page: 1 }, { preserveState: true, preserveScroll: true })
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Email Templates') }
  ]

  const columns = [
    {
      key: 'name',
      label: t('Name'),
      sortable: true
    }
  ]

  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500'
    }
  ]

  return (
    <PageTemplate
      title={t('Email Templates')}
      url={route('email-templates.index')}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <Head title="Email Templates" />

      {/* Search section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={() => searchTerm !== ''}
          activeFilterCount={() => searchTerm ? 1 : 0}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || '10'}
          onPerPageChange={(value) => {
            router.get(route('email-templates.index'), {
              page: 1,
              search: searchTerm || undefined,
              ...(parseInt(value) !== 10 && { per_page: parseInt(value) })
            }, { preserveState: true, preserveScroll: true })
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={templates?.data || []}
          from={templates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={[]}
          entityPermissions={{
            view: '',
            create: '',
            edit: '',
            delete: ''
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={templates?.from || 0}
          to={templates?.to || 0}
          total={templates?.total || 0}
          links={templates?.links}
          entityName={t('templates')}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  )
}
