import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const planRequestsConfig: CrudConfig = {
  entity: {
    name: 'planRequests',
    endpoint: route('plan-requests.index'),
    permissions: {
      view: 'view-plan-requests',
      create: 'create-plan-requests',
      edit: 'edit-plan-requests',
      delete: 'delete-plan-requests'
    }
  },
  modalSize: '4xl',
  description: t('Manage plan upgrade requests from users'),
  table: {
    columns: [
      { key: 'user.name', label: t('Name'), sortable: true },
      { key: 'user.email', label: t('Email'), sortable: true },
      { key: 'plan.name', label: t('Plan'), sortable: true },
      {
        key: 'duration',
        label: t('Plan Duration'),
        render: (value) => value === 'monthly' ? t('Monthly') : t('Yearly')
      },
      {
        key: 'status',
        label: t('Status'),
        render: columnRenderers.status({
          approved: 'bg-green-100 text-green-800',
          rejected: 'bg-red-100 text-red-800',
          pending: 'bg-yellow-100 text-yellow-800'
        })
      },
      {
        key: 'created_at',
        label: t('Requested At'),
        sortable: true,
        render: (value) => `${window.appSettings?.formatDateTime(value, false) || '-'}`
      }
    ],
    actions: [
      {
        label: t('Approve'),
        icon: 'Check',
        action: 'approve',
        className: 'text-green-600',
        condition: (item: any) => item.status === 'pending',
        requiredPermission: 'approve-plan-requests'
      },
      {
        label: t('Reject'),
        icon: 'X',
        action: 'reject',
        className: 'text-red-600',
        condition: (item: any) => item.status === 'pending',
        requiredPermission: 'reject-plan-requests'
      }
    ]
  },
  search: {
    enabled: true,
    placeholder: t('Search plan requests...'),
    fields: ['user.name', 'user.email', 'plan.name']
  },
  filters: [
    {
      key: 'status',
      label: t('Status'),
      type: 'select',
      options: [
        { value: 'all', label: t('All Status') },
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'rejected', label: t('Rejected') }
      ]
    }
  ],
  form: {
    fields: []
  }
};
