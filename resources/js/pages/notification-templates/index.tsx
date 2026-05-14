import { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { PageTemplate } from '@/components/page-template'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { CrudTable } from '@/components/CrudTable'
import { Pagination } from '@/components/ui/pagination'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Search } from 'lucide-react'
import { useTranslation } from 'react-i18next'

interface NotificationTemplate {
  id: number;
  name: string;
  type: string;
  created_at: string;
  notification_template_langs: Array<{
    lang: string;
    title: string;
    content: string;
  }>;
}

interface Props {
  templates: {
    data: NotificationTemplate[]
    from: number
    to: number
    total: number
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
  }
  filters: {
    search?: string
    sort_field?: string
    sort_direction?: string
    per_page?: number
    type?: string
  }
}

export default function NotificationTemplatesIndex({ templates, filters: pageFilters = {}, types = [] }: Props) {
  const { t } = useTranslation()
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '')
  const [activeType, setActiveType] = useState(pageFilters.type || types[0] || '')

  const handleAction = (action: string, item: NotificationTemplate) => {
    if (action === 'view') {
      router.get(route('notification-templates.show', item.id))
    }
  }

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault()
    applyFilters()
  }

  const applyFilters = () => {
    router.get(route('notification-templates.index'), {
      page: 1,
      search: searchTerm || undefined,
      type: activeType,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true })
  }

  const handleTypeChange = (type: string) => {
    setActiveType(type)
    router.get(route('notification-templates.index'), {
      page: 1,
      search: searchTerm || undefined,
      type,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true })
  }

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc'
    router.get(route('notification-templates.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      type: activeType,
      per_page: pageFilters.per_page,
    }, { preserveState: true, preserveScroll: true })
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Notification Templates') }
  ]

  const columns = [
    { key: 'name', label: t('Name'), sortable: true },
  ]

  const actions = [
    { label: t('View'), icon: 'Eye', action: 'view', className: 'text-blue-500' },
  ]

  return (
    <PageTemplate
      title={t('Notification Templates')}
      url={route('notification-templates.index')}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <Head title={t('Notification Templates')} />

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <div className="flex items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <form onSubmit={handleSearch} className="flex gap-2">
              <div className="relative w-64">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder={t('Search templates...')}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-9"
                />
              </div>
              <Button type="submit" size="sm">
                <Search className="h-4 w-4 mr-1.5" />
                {t('Search')}
              </Button>
            </form>
          </div>

          <div className="flex items-center gap-3">
            <Tabs value={activeType} onValueChange={handleTypeChange}>
              <TabsList className="grid w-fit grid-cols-2">
                <TabsTrigger value="twilio" className="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground cursor-pointer">Twilio</TabsTrigger>
                <TabsTrigger value="slack" className="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground cursor-pointer">Slack</TabsTrigger>
              </TabsList>
            </Tabs>

            <Label className="text-xs text-muted-foreground">{t('Per Page:')}</Label>
            <Select
              value={pageFilters.per_page?.toString() || '10'}
              onValueChange={(value) => {
                router.get(route('notification-templates.index'), {
                  page: 1,
                  per_page: parseInt(value),
                  search: searchTerm || undefined,
                  type: activeType,
                }, { preserveState: true, preserveScroll: true })
              }}
            >
              <SelectTrigger className="w-16 h-8">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="10">10</SelectItem>
                <SelectItem value="25">25</SelectItem>
                <SelectItem value="50">50</SelectItem>
                <SelectItem value="100">100</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={templates?.data || []}
          from={templates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction as 'asc' | 'desc'}
          onSort={handleSort}
          permissions={[]}
          entityPermissions={{ view: '', edit: '', delete: '' }}
        />

        <Pagination
          from={templates?.from || 0}
          to={templates?.to || 0}
          total={templates?.total || 0}
          links={templates?.links}
          entityName={t('templates')}
          onPageChange={(url) => {
            const parsed = new URL(url, window.location.origin)
            parsed.searchParams.set('type', activeType)
            if (searchTerm) parsed.searchParams.set('search', searchTerm)
            router.get(parsed.pathname + parsed.search)
          }}
        />
      </div>
    </PageTemplate>
  )
}
