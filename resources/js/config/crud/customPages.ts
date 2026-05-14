// config/crud/customPages.ts
import { CrudConfig } from '@/types/crud';
import { t } from '@/utils/i18n';

export const customPagesConfig: CrudConfig = {
    entity: {
        name: 'pages',
        endpoint: route('landing-page.custom-pages.index'),
        permissions: {
            view: 'manage-settings',
            create: 'manage-settings',
            edit: 'manage-settings',
            delete: 'manage-settings'
        }
    },
    modalSize: '2xl',
    table: {
        columns: [
            { key: 'title', label: t('Title'), sortable: true },
            {
                key: 'content',
                label: t('Content'),
                render: (value: string) => {
                    const text = value.replace(/<[^>]*>/g, '');
                    return text.substring(0, 100) + (text.length > 100 ? '...' : '');
                }
            },
            {
                key: 'is_active',
                label: t('Status'),
                render: (value: boolean) => value ? t('Active') : t('Inactive')
            }
        ],
        actions: [
            {
                label: t('Edit'),
                icon: 'Edit',
                action: 'edit',
                className: 'text-amber-500',
                requiredPermission: 'manage-settings'
            },
            {
                label: t('Delete'),
                icon: 'Trash2',
                action: 'delete',
                className: 'text-red-500',
                requiredPermission: 'manage-settings'
            }
        ]
    },
    filters: [],
    form: {
        fields: [
            { name: 'title', label: t('Page Title'), type: 'text', required: true },
            { name: 'sort_order', label: t('Sort Order'), type: 'number', defaultValue: '0' },
            { name: 'meta_title', label: t('Meta Title (SEO)'), type: 'text' },
            { name: 'meta_description', label: t('Meta Description (SEO)'), type: 'textarea' },
            { name: 'is_active', label: t('Active'), type: 'switch', defaultValue: true }
        ]
    }
};
