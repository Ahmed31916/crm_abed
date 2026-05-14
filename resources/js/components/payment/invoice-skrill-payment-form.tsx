import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Wallet } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { router } from '@inertiajs/react';

interface InvoiceSkrillPaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: 'full' | 'partial';
  skrillMerchantId: string;
  currency: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoiceSkrillPaymentForm({
  invoiceId,
  amount,
  paymentType,
  skrillMerchantId,
  currency,
  onSuccess,
  onCancel
}: InvoiceSkrillPaymentFormProps) {
  const { t } = useTranslation();
  const [isProcessing, setIsProcessing] = useState(false);
  const [email, setEmail] = useState('');

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `${currency} ${Number(amount || 0).toFixed(2)}`;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!email) {
      toast.error(t('Please enter your email address'));
      return;
    }

    if (!email.includes('@')) {
      toast.error(t('Please enter a valid email address'));
      return;
    }

    setIsProcessing(true);

    try {
      const paymentData = {
        invoice_id: invoiceId,
        amount: amount,
        payment_type: paymentType,
        email: email
      };

      router.post(route('invoice.skrill.payment'), paymentData, {
        onSuccess: () => {
          // The controller will redirect to Skrill, so we don't need to call onSuccess here
          // onSuccess will be called when user returns from Skrill
        },
        onError: (errors) => {
          if (errors.error) {
            toast.error(errors.error);
          } else {
            toast.error(t('Payment failed. Please try again.'));
          }
          setIsProcessing(false);
        },
        onFinish: () => {
          // Don't set processing to false here as we're redirecting to Skrill
        }
      });
    } catch (error) {
      toast.error(t('Payment failed. Please try again.'));
      setIsProcessing(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Wallet className="h-5 w-5" />
          {t('Skrill Payment')}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Payment Summary */}
          <div className="p-3 bg-muted/50 rounded-lg">
            <div className="flex justify-between items-center">
              <span className="text-sm font-medium">{t('Payment Amount')}:</span>
              <span className="font-bold">{formatCurrency(amount)}</span>
            </div>
            <div className="flex justify-between items-center mt-1">
              <span className="text-sm text-muted-foreground">{t('Payment Type')}:</span>
              <span className="text-sm capitalize">{paymentType} {t('Payment')}</span>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">{t('Email Address')}</Label>
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder={t('Enter your email address')}
              required
              disabled={isProcessing}
            />
            <p className="text-xs text-muted-foreground">
              {t('You will be redirected to Skrill to complete the payment')}
            </p>
          </div>

          {/* Skrill Information */}
          <div className="p-3 border rounded-lg bg-blue-50 dark:bg-blue-950/20">
            <div className="flex items-start gap-2">
              <Wallet className="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
              <div className="text-sm">
                <p className="font-medium text-blue-900 dark:text-blue-100">
                  {t('Secure Payment with Skrill')}
                </p>
                <p className="text-blue-700 dark:text-blue-300 mt-1">
                  {t('You will be redirected to Skrill\'s secure payment page to complete your transaction.')}
                </p>
              </div>
            </div>
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              type="button"
              variant="outline"
              onClick={onCancel}
              className="flex-1"
              disabled={isProcessing}
            >
              {t('Cancel')}
            </Button>
            <Button
              type="submit"
              disabled={isProcessing || !email}
              className="flex-1"
            >
              {isProcessing ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {t('Processing...')}
                </>
              ) : (
                <>
                  <Wallet className="mr-2 h-4 w-4" />
                  {t('Pay with Skrill')}
                </>
              )}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
