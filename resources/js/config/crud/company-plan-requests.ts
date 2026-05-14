import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const companyPlanRequestsConfig: CrudConfig = {
  entity: {
    name: 'companyPlanRequests',
    endpoint: route('company.plan-requests.index'),
    permissions: {
      view: 'view-plan-requests',
      create: 'create-plan-requests',
      edit: 'edit-plan-requests',
      delete: 'delete-plan-requests'
    }
  },
  modalSize: '4xl',
  description: t('View plan upgrade requests from your team members'),
  table: {
    columns: [
      { key: 'user.name', label: t('Name'), sortable: true },
      { key: 'user.email', label: t('Email'), sortable: true },
      { key: 'plan.name', label: t('Plan Name'), sortable: true },
      {
        key: 'plan.duration',
        label: t('Plan Duration'),
        render: (value) => value === 'monthly' ? t('Monthly') : t('Yearly')
      },
      {
        key: 'status',
        label: t('Status'),
        render: columnRenderers.status()
      },
      {
        key: 'created_at',
        label: t('Requested At'),
        sortable: true,
        render: (value) => `${window.appSettings?.formatDateTime(value, false) || '-'}`
      }
    ],
    actions: []
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
