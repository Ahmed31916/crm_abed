import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const planOrdersConfig: CrudConfig = {
  entity: {
    name: 'plan-orders',
    endpoint: route('plan-orders.index'),
    permissions: {
      view: 'view-plan-orders',
      create: 'create-plan-orders',
      edit: 'edit-plan-orders',
      delete: 'delete-plan-orders'
    }
  },
  modalSize: '4xl',
  description: t('Manage plan orders and subscription requests'),
  table: {
    columns: [
      { key: 'order_number', label: t('Order Number'), sortable: true },
      {
        key: 'ordered_at',
        label: t('Order Date'),
        sortable: true,
        render: (value) => `${window.appSettings?.formatDateTime(value, false) || '-'}`
      },
      {
        key: 'user.name',
        label: t('User'),
        sortable: false
      },
      {
        key: 'plan.name',
        label: t('Plan'),
        sortable: false
      },
      {
        key: 'original_price',
        label: t('Original Price'),
        render: (value) => `${window.appSettings.formatCurrency(value)}`
      },
      {
        key: 'coupon_code',
        label: t('Coupon Code'),
        render: (value) => value || '-'
      },
      {
        key: 'discount_amount',
        label: t('Discount'),
        render: (value) => value > 0 ? `-${window.appSettings.formatCurrency(value)}` : '-'
      },
      {
        key: 'final_price',
        label: t('Final Price'),
        render: (value) => `${window.appSettings.formatCurrency(value)}`
      },
      {
        key: 'status',
        label: t('Status'),
        // render: (value) => {
        //   const statusMap = {
        //     pending: { label: t('Pending'), className: 'bg-yellow-100 text-yellow-800' },
        //     approved: { label: t('Approved'), className: 'bg-green-100 text-green-800' },
        //     rejected: { label: t('Rejected'), className: 'bg-red-100 text-red-800' }
        //   };
        //   const status = statusMap[value as keyof typeof statusMap] || statusMap.pending;
        //   return status.label;
        // }
        render: columnRenderers.status({
          approved: 'bg-green-100 text-green-800',
          rejected: 'bg-red-100 text-red-800',
          pending: 'bg-yellow-100 text-yellow-800'
        })
      }
    ],
    actions: [
      {
        label: t('Approve'),
        icon: 'Check',
        action: 'approve',
        className: 'text-green-600',
        condition: (row: any) => row.status === 'pending',
        requiredPermission: 'approve-plan-orders'
      },
      {
        label: t('Reject'),
        icon: 'X',
        action: 'reject',
        className: 'text-red-600',
        condition: (row: any) => row.status === 'pending',
        requiredPermission: 'reject-plan-orders'
      }
    ]
  },
  search: {
    enabled: true,
    placeholder: t('Search orders...'),
    fields: ['order_number', 'user.name', 'plan.name', 'coupon_code']
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
