// config/crud/contacts.ts
import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const contactsConfig: CrudConfig = {
  entity: {
    name: 'contacts',
    endpoint: route('contacts.index'),
    permissions: {
      view: 'view-contacts',
      create: 'create-contacts',
      edit: 'edit-contacts',
      delete: 'delete-contacts'
    }
  },
  table: {
    columns: [
      { key: 'business.name', label: t('Business Name'), sortable: false },
      { key: 'name', label: t('Name'), sortable: true },
      { key: 'email', label: t('Email'), sortable: true },
      { key: 'phone', label: t('Phone') },
      { key: 'message', label: t('Message') },
      { 
        key: 'status', 
        label: t('Status'), 
        sortable: true, 
        render: columnRenderers.status({
          'new': 'bg-blue-100 text-blue-800',
          'contacted': 'bg-yellow-100 text-yellow-800',
          'qualified': 'bg-purple-100 text-purple-800',
          'converted': 'bg-green-100 text-green-800',
          'closed': 'bg-gray-100 text-gray-800'
        })
      }
    ],
    actions: [
      { 
        label: t('Reply'), 
        icon: 'MessageSquare', 
        action: 'reply', 
        className: 'text-blue-500',
        requiredPermission: 'edit-contacts'
      },
      { 
        label: t('Delete'), 
        icon: 'Trash2', 
        action: 'delete', 
        className: 'text-red-500',
        requiredPermission: 'delete-contacts'
      }
    ]
  },
  filters: [
    {
      key: 'status',
      label: t('Status'),
      type: 'select',
      options: [
        { value: 'new', label: t('New') },
        { value: 'contacted', label: t('Contacted') },
        { value: 'qualified', label: t('Qualified') },
        { value: 'converted', label: t('Converted') },
        { value: 'closed', label: t('Closed') }
      ]
    }
  ],
  form: {
    fields: [
      { name: 'business_id', label: t('Business'), type: 'select', required: true },
      { name: 'name', label: t('Name'), type: 'text', required: true },
      { name: 'email', label: t('Email'), type: 'email' },
      { name: 'phone', label: t('Phone'), type: 'text' },
      { name: 'message', label: t('Message'), type: 'textarea' },
      {
        name: 'status',
        label: t('Status'),
        type: 'select',
        required: true,
        options: [
          { value: 'new', label: t('New') },
          { value: 'contacted', label: t('Contacted') },
          { value: 'qualified', label: t('Qualified') },
          { value: 'converted', label: t('Converted') },
          { value: 'closed', label: t('Closed') }
        ]
      },
      { name: 'notes', label: t('Notes'), type: 'textarea' }
    ]
  }
};