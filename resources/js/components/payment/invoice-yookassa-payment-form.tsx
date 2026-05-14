import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CreditCard, AlertCircle, ExternalLink } from 'lucide-react';

interface InvoiceYooKassaPaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: string;
  yookassaShopId: string;
  currency?: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoiceYooKassaPaymentForm({
  invoiceId,
  amount,
  paymentType,
  yookassaShopId,
  currency = 'RUB',
  onSuccess,
  onCancel,
}: InvoiceYooKassaPaymentFormProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handlePayment = async () => {
    if (!yookassaShopId) {
      setError(t('YooKassa not configured'));
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await fetch(route('invoice.yookassa.create-payment'), {
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

      const data = await response.json();

      if (data.success) {
        window.location.href = data.payment_url;
      } else {
        throw new Error(data.error || t('Payment creation failed'));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : t('Payment initialization failed'));
      setIsLoading(false);
    }
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: currency,
    }).format(price);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <CreditCard className="h-5 w-5" />
          {t('YooKassa Payment')}
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
            {t('Payment Type')}: {t(paymentType)}
          </div>
        </div>

        <div className="bg-purple-50 p-4 rounded-lg border border-purple-200">
          <h4 className="font-medium text-purple-900 mb-2">{t('Supported Payment Methods')}</h4>
          <ul className="text-sm text-purple-800 space-y-1">
            <li>• Bank Cards</li>
            <li>• YooMoney</li>
            <li>• Qiwi</li>
            <li>• Sberbank Online</li>
            <li>• Alfa-Click</li>
          </ul>
        </div>

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
            disabled={isLoading || !yookassaShopId}
            className="flex-1"
          >
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                {t('Redirecting...')}
              </>
            ) : (
              <>
                <ExternalLink className="mr-2 h-4 w-4" />
                {t('Pay with YooKassa')}
              </>
            )}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
