import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CrudTable } from '@/components/CrudTable';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import { Plus } from 'lucide-react';
import { useForm, router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';

interface PayoutRequestsProps {
  userType: string;
  payoutRequests: any;
  settings: any;
  stats: any;
  currencySymbol?: string;
}

export default function PayoutRequests({ userType, payoutRequests, settings, stats, currencySymbol }: PayoutRequestsProps) {
  const { t } = useTranslation();

  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    amount: '',
  });

  const handleCreatePayout = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('referral.payout-request.create'), {
      onSuccess: (page) => {
        setShowCreateDialog(false);
        reset();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (typeof errors === 'string') {
          toast.error(t(errors));
        }
      }
    });
  };

  const handleAction = (action: string, item: any) => {
    if (action === 'approve') {
      router.post(route('referral.payout-request.approve', item.id), {}, {
        onSuccess: (page) => {
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (typeof errors === 'string') {
            toast.error(t(errors));
          }
        }
      });
    } else if (action === 'reject') {
      setCurrentItem(item);
      setIsRejectModalOpen(true);
    }
  };

  const handleRejectConfirm = (notes: string) => {
    router.post(route('referral.payout-request.reject', currentItem.id), { notes }, {
      onSuccess: (page) => {
        setIsRejectModalOpen(false);
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (typeof errors === 'string') {
          toast.error(t(errors));
        }
      }
    });
  };

  // Define table columns
  const columns = [
    ...(userType === 'superadmin' ? [{
      key: 'company.name',
      label: t('Company'),
      render: (_, row) => (
        <div>
          <p className="text-sm font-semibold">{row.company?.name}</p>
          <p className="text-xs text-muted-foreground">{row.company?.email}</p>
        </div>
      )
    }] : []),
    {
      key: 'amount',
      label: t('Amount'),
      render: (value) => `${currencySymbol}${value}`
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        const statusColors: Record<string, string> = {
          pending:  'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          approved: 'bg-green-50 text-green-700 ring-green-600/20',
          rejected: 'bg-red-50 text-red-700 ring-red-600/20'
        };
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset capitalize ${statusColors[value] || 'bg-gray-50 text-gray-700 ring-gray-600/20'}`}>
            {t(value)}
          </span>
        );
      }
    },
    {
      key: 'created_at',
      label: t('Date'),
      render: (value) => window.appSettings?.formatDateTime(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  // Define table actions
  const actions = userType === 'superadmin' ? [
    {
      label: t('Approve'),
      icon: 'Check',
      action: 'approve',
      className: 'text-green-500',
      condition: (row) => row.status === 'pending'
    },
    {
      label: t('Reject'),
      icon: 'X',
      action: 'reject',
      className: 'text-red-500',
      condition: (row) => row.status === 'pending'
    }
  ] : [];

  return (
    <div className="space-y-6">
      {userType === 'company' && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-base font-semibold">{t('Create Payout Request')}</CardTitle>
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
              <DialogTrigger asChild>
                <Button disabled={stats.availableBalance < settings.threshold_amount}>
                  <Plus className="h-4 w-4 mr-2" />
                  {t('Request Payout')}
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>{t('Create Payout Request')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleCreatePayout} className="space-y-4">
                  <div>
                    <Label htmlFor="amount">{t('Amount')}</Label>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      min={settings.threshold_amount}
                      max={stats.availableBalance}
                      value={data.amount}
                      onChange={(e) => setData('amount', e.target.value)}
                      placeholder={`Min: $${settings.threshold_amount}`}
                    />
                    {errors.amount && <p className="text-sm text-red-500">{errors.amount}</p>}
                  </div>
                  <div className="text-sm text-muted-foreground">
                    <p>{t('Available Balance')}: {currencySymbol}{stats.availableBalance}</p>
                    <p>{t('Minimum Amount')}: {currencySymbol}{settings.threshold_amount}</p>
                  </div>
                  <div className="flex justify-end space-x-2">
                    <Button type="button" variant="outline" onClick={() => setShowCreateDialog(false)}>
                      {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                      {t('Submit Request')}
                    </Button>
                  </div>
                </form>
              </DialogContent>
            </Dialog>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-muted-foreground">
              {stats.availableBalance < settings.threshold_amount
                ? t('You need at least {{amount}} to request a payout', { amount: `${currencySymbol}${settings.threshold_amount}` })
                : t('You can request up to {{amount}} for payout', { amount: `${currencySymbol}${stats.availableBalance}` })}
            </p>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base font-semibold">
            {userType === 'superadmin' ? t('All Payout Requests') : t('Your Payout Requests')}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <CrudTable
            columns={columns}
            actions={actions}
            data={payoutRequests?.data || []}
            from={payoutRequests?.from || 1}
            onAction={handleAction}
            permissions={[]}
          />
        </CardContent>
      </Card>

      {/* Reject Modal */}
      <Dialog open={isRejectModalOpen} onOpenChange={setIsRejectModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('Reject Payout Request')}</DialogTitle>
          </DialogHeader>
          <form onSubmit={(e) => {
            e.preventDefault();
            const formData = new FormData(e.currentTarget);
            const notes = formData.get('notes') as string;
            handleRejectConfirm(notes);
          }}>
            <div className="space-y-4">
              <div>
                <Label htmlFor="notes">{t('Rejection Reason (Optional)')}</Label>
                <Textarea
                  id="notes"
                  name="notes"
                  placeholder={t('Enter rejection reason...')}
                  className="mt-1"
                />
              </div>
            </div>
            <DialogFooter className="mt-6">
              <Button type="button" variant="outline" onClick={() => setIsRejectModalOpen(false)}>
                {t('Cancel')}
              </Button>
              <Button type="submit" variant="destructive">
                {t('Reject')}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
