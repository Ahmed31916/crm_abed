// config/crud/coupons.ts
import React from 'react';
import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { Switch } from '@/components/ui/switch';
import { toast } from '@/components/custom-toast';
import { t } from '@/utils/i18n';

export const couponsConfig: CrudConfig = {
  entity: {
    name: 'coupons',
    endpoint: route('coupons.index'),
    permissions: {
      view: 'view-coupons',
      create: 'create-coupons',
      edit: 'create-coupons',
      delete: 'delete-coupons'
    }
  },
  modalSize: '4xl',
  table: {
    columns: [
      { key: 'name', label: t('Name'), sortable: true },
      {
        key: 'type',
        label: t('Type'),
        sortable: true,
        render: (value) => {
          const className = value === 'percentage'
            ? 'bg-blue-100 text-blue-800'
            : 'bg-green-100 text-green-800';
          return value === 'percentage' ? t('Percentage') : t('Flat Amount');
        }
      },
      {
        key: 'minimum_spend',
        label: t('Min Spend'),
        render: (value) => value ? (window.appSettings?.formatCurrency(value) || `$${parseFloat(value).toFixed(2)}`) : '-'
      },
      {
        key: 'maximum_spend',
        label: t('Max Spend'),
        render: (value) => value ? (window.appSettings?.formatCurrency(value) || `$${parseFloat(value).toFixed(2)}`) : '-'
      },
      {
        key: 'discount_amount',
        label: t('Discount'),
        render: (value, row) => {
          const amount = parseFloat(value);
          return row.type === 'percentage'
            ? `${amount}%`
            : (window.appSettings?.formatCurrency(amount) || `$${amount.toFixed(2)}`);
        }
      },
      { key: 'use_limit_per_coupon', label: t('Coupon Limit'), render: (value) => value || t('Unlimited') },
      { key: 'use_limit_per_user', label: t('User Limit'), render: (value) => value || t('Unlimited') },
      {
        key: 'expiry_date',
        label: t('Expiry Date'),
        sortable: true,
        render: (value) => window.appSettings.formatDateTime(value, false) || '-'
      },
      { key: 'code', label: t('Code'), sortable: true },
      {
        key: 'status',
        label: t('Status'),
        type: 'custom',
        render: (value, row) => {
          const StatusSwitch = () => {
            const [isChecked, setIsChecked] = React.useState(!!value);

            const handleToggle = async () => {
              try {
                const response = await fetch(route('coupons.toggle-status', row.id), {
                  method: 'PUT',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                  }
                });

                if (response.ok) {
                  const data = await response.json();
                  setIsChecked(data.status);
                  row.status = data.status;
                  toast.success(data.message || 'Status updated successfully');
                } else {
                  const errorData = await response.json();
                  if (errorData.demo_mode) {
                    toast.error(errorData.message || 'This action is disabled in demo mode');
                  } else {
                    toast.error('Failed to update status');
                  }
                }
              } catch (error) {
                toast.error('Network error updating status');
              }
            };

            return React.createElement('div', { className: 'flex items-center justify-center' },
              React.createElement(Switch, {
                checked: isChecked,
                onCheckedChange: handleToggle
              })
            );
          };

          return React.createElement(StatusSwitch);
        }
      }
    ],
    actions: [
      {
        label: t('View Details'),
        icon: 'Eye',
        action: 'view-details',
        href: (row: any) => route('coupons.show', row.id),
        className: 'text-blue-500'
      },
      {
        label: t('Edit'),
        icon: 'Edit',
        action: 'edit',
        className: 'text-amber-500'
      },
      {
        label: t('Delete'),
        icon: 'Trash2',
        action: 'delete',
        className: 'text-red-500'
      }
    ]
  },
  search: {
    enabled: true,
    placeholder: t('Search coupons...'),
    fields: ['name', 'code']
  },
  filters: [
    {
      key: 'type',
      label: t('Type'),
      type: 'select',
      options: [
        { value: 'all', label: t('All Types') },
        { value: 'percentage', label: t('Percentage') },
        { value: 'flat', label: t('Flat Amount') }
      ]
    },
    {
      key: 'status',
      label: t('Status'),
      type: 'select',
      options: [
        { value: 'all', label: t('All Status') },
        { value: '1', label: t('Active') },
        { value: '0', label: t('Inactive') }
      ]
    }
  ],
  form: {
    fields: [
      {
        name: 'name',
        label: t('Coupon Name'),
        type: 'text',
        required: true,
        colSpan: 12,
        placeholder: t('Enter coupon name')
      },
      {
        name: 'type',
        label: t('Discount Type'),
        type: 'select',
        required: true,
        colSpan: 6,
        options: [
          { value: 'percentage', label: t('Percentage (%)') },
          { value: 'flat', label: t('Fixed Amount ($)') }
        ]
      },
      {
        name: 'discount_amount',
        label: t('Discount Value'),
        type: 'number',
        required: true,
        colSpan: 6,
        min: 0,
        max: 99,
        step: 0.01,
        placeholder: t('Enter value')
      },
      {
        name: 'code_type',
        label: t('Code Generation'),
        type: 'radio',
        required: true,
        colSpan: 12,
        options: [
          { value: 'manual', label: t('Manual Entry') },
          { value: 'auto', label: t('Auto Generate') }
        ],
        defaultValue: 'manual'
      },
      {
        name: 'code',
        label: t('Coupon Code'),
        type: 'custom',
        required: true,
        colSpan: 12,
        render: (field: any, formData: any, onChange: any, errors: any) => {
          const generateCode = () => {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 10; i++) {
              result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            onChange('code', result);
          };

          const isAuto = formData.code_type === 'auto';
          const hasError = errors?.code;
          const inputClass = `px-3 py-2 border rounded-md${hasError ? ' border-red-500' : ''}`;

          return React.createElement('div', { className: 'space-y-2' }, [
            React.createElement('div', {
              className: isAuto ? 'flex gap-2' : '',
              key: 'input-group'
            }, isAuto ? [
              React.createElement('input', {
                key: 'code-input',
                type: 'text',
                value: formData.code || '',
                onChange: (e: any) => onChange('code', e.target.value.toUpperCase()),
                placeholder: t('Click generate to create code'),
                className: `flex-1 ${inputClass}`
              }),
              React.createElement('button', {
                key: 'generate-btn',
                type: 'button',
                onClick: generateCode,
                className: 'px-4 py-2 bg-primary text-white rounded-md hover:bg-primary'
              }, t('Generate'))
            ] : [
              React.createElement('input', {
                key: 'code-input-manual',
                type: 'text',
                value: formData.code || '',
                onChange: (e: any) => onChange('code', e.target.value.toUpperCase()),
                placeholder: t('Enter coupon code'),
                className: `w-full ${inputClass}`
              })
            ])
          ]);
        }
      },
      {
        name: 'minimum_spend',
        label: t('Minimum Spend ($)'),
        type: 'number',
        colSpan: 6,
        min: 0,
        step: 0.01,
        placeholder: t('Optional')
      },
      {
        name: 'maximum_spend',
        label: t('Maximum Spend ($)'),
        type: 'number',
        colSpan: 6,
        min: 0,
        step: 0.01,
        placeholder: t('Optional')
      },
      {
        name: 'use_limit_per_coupon',
        label: t('Total Usage Limit'),
        type: 'number',
        colSpan: 6,
        min: 1,
        placeholder: t('Leave empty for unlimited')
      },
      {
        name: 'use_limit_per_user',
        label: t('Usage Limit Per User'),
        type: 'number',
        colSpan: 6,
        min: 1,
        placeholder: t('Leave empty for unlimited')
      },
      {
        name: 'expiry_date',
        label: t('Expiry Date'),
        type: 'date',
        colSpan: 6
      },
      {
        name: 'status',
        label: t('Status'),
        type: 'switch',
        // colSpan: 6,
        defaultValue: true,
        // placeholder: t('Enable or disable this coupon')
      }
    ]
  }
};
