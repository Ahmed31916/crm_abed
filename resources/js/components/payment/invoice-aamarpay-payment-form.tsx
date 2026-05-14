import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';

interface InvoiceAamarpayPaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: 'full' | 'partial';
  aamarpayStoreId: string;
  currency?: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoiceAamarpayPaymentForm({
  invoiceId,
  amount,
  paymentType,
  aamarpayStoreId,
  currency = 'BDT',
  onSuccess,
  onCancel,
}: InvoiceAamarpayPaymentFormProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handlePayment = async () => {
    if (!aamarpayStoreId) {
      setError(t('Aamarpay configuration is missing'));
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch(route('invoice.aamarpay.create'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          invoice_id: invoiceId,
          amount: amount,
          payment_type: paymentType,
        }),
      });

      if (response.ok) {
        const html = await response.text();
        document.open();
        document.write(html);
        document.close();
      } else {
        const data = await response.json();
        throw new Error(data.error || t('Failed to create payment'));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : t('Payment initialization failed'));
      setIsLoading(false);
    }
  };

  const formatPrice = (price: number) => {
    return window.appSettings?.formatCurrency(Number(price || 0)) || new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(price);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <CreditCard className="h-5 w-5" />
          {t('Aamarpay Payment')}
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        <div className="bg-muted p-4 rounded-lg">
          <div className="flex justify-between items-center">
            <span className="font-medium">{t('Payment Amount')}</span>
            <span className="text-lg font-bold">{formatPrice(amount)}</span>
          </div>
          <div className="text-sm text-muted-foreground mt-1">
            {t('Payment Type')}: {t(paymentType === 'full' ? 'Full Payment' : 'Partial Payment')}
          </div>
        </div>

        {isLoading ? (
          <div className="text-center py-8">
            <Loader2 className="h-8 w-8 animate-spin mx-auto mb-4" />
            <p>{t('Redirecting to payment page...')}</p>
          </div>
        ) : (
          <div className="space-y-4">
            <Alert>
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                {t('You will be redirected to Aamarpay secure payment page to complete your payment.')}
              </AlertDescription>
            </Alert>

            <div className="flex gap-3">
              <Button
                variant="outline"
                onClick={onCancel}
                disabled={isLoading}
                className="flex-1"
              >
                {t('Cancel')}
              </Button>
              <Button
                onClick={handlePayment}
                disabled={isLoading || !aamarpayStoreId}
                className="flex-1"
              >
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    {t('Processing...')}
                  </>
                ) : (
                  <>
                    <CreditCard className="mr-2 h-4 w-4" />
                    {t('Pay with Aamarpay')}
                  </>
                )}
              </Button>
            </div>
          </div>
        )}

        <div className="text-xs text-muted-foreground text-center">
          {t('Powered by Aamarpay - Secure payment processing')}
        </div>
      </CardContent>
    </Card>
  );
}
