import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { ArrowLeft, FileIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { useTranslation } from 'react-i18next';

export default function ShowDocumentType() {
  const { t } = useTranslation();
  const { auth, documentType } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const pageActions = [];

  pageActions.push({
    label: t('Back'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: () => router.get(route('document-types.index'))
  });

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Document Types'), href: route('document-types.index') },
    { title: documentType.type_name }
  ];

  return (
    <PageTemplate
      title={documentType.type_name}
      url={`/document-types/${documentType.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
        <div className="flex items-center gap-4 mb-6">
          <div className="flex h-16 w-16 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
            <FileIcon className="h-8 w-8" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{documentType.type_name}</h1>
            <p className="text-gray-600 dark:text-gray-400">{t('Document Type Details')}</p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">{t('Basic Information')}</h3>
            <dl className="space-y-4">
              <div>
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Type Name')}</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white">{documentType.type_name}</dd>
              </div>
              <div>
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Status')}</dt>
                <dd className="mt-1">
                  <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${documentType.status === 'active'
                    ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                    : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                    }`}>
                    {documentType.status === 'active' ? t('Active') : t('Inactive')}
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          <div>
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">{t('System Information')}</h3>
            <dl className="space-y-4">
              <div>
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Created At')}</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                  {window.appSettings?.formatDateTime(documentType.created_at, false) || new Date(documentType.created_at).toLocaleDateString()}
                </dd>
              </div>
              <div>
                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Updated At')}</dt>
                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                  {window.appSettings?.formatDateTime(documentType.updated_at, false) || new Date(documentType.updated_at).toLocaleDateString()}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </PageTemplate>
  );
}
