import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2, Coins } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { router } from '@inertiajs/react';

interface InvoiceCoingatePaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: 'full' | 'partial';
  coingateApiToken: string;
  currency: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoiceCoingatePaymentForm({
  invoiceId,
  amount,
  paymentType,
  coingateApiToken,
  currency,
  onSuccess,
  onCancel
}: InvoiceCoingatePaymentFormProps) {
  const { t } = useTranslation();
  const [isProcessing, setIsProcessing] = useState(false);

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `${currency} ${Number(amount || 0).toFixed(2)}`;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsProcessing(true);

    // Create form and submit directly to avoid CORS
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('invoice.coingate.payment');
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = csrfToken;
      form.appendChild(csrfInput);
    }
    
    // Add form data
    const formData = {
      invoice_id: invoiceId,
      amount: amount,
      payment_type: paymentType
    };
    
    Object.entries(formData).forEach(([key, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = String(value);
      form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Coins className="h-5 w-5" />
          {t('Coingate Payment')}
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

          {/* Coingate Information */}
          <div className="p-3 border rounded-lg bg-orange-50 dark:bg-orange-950/20">
            <div className="flex items-start gap-2">
              <Coins className="h-4 w-4 text-orange-600 mt-0.5 flex-shrink-0" />
              <div className="text-sm">
                <p className="font-medium text-orange-900 dark:text-orange-100">
                  {t('Secure Cryptocurrency Payment')}
                </p>
                <p className="text-orange-700 dark:text-orange-300 mt-1">
                  {t('You will be redirected to Coingate to complete your payment with cryptocurrency.')}
                </p>
                <p className="text-orange-600 dark:text-orange-400 mt-1 text-xs">
                  {t('Supports Bitcoin, Ethereum, Litecoin and 70+ other cryptocurrencies')}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
            <div className="flex items-start gap-2">
              <Coins className="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
              <div className="text-xs text-blue-800">
                <p className="font-medium mb-1">{t('Payment Process:')}</p>
                <ul className="space-y-1 list-disc list-inside">
                  <li>{t('Click "Pay with Crypto" to proceed to Coingate')}</li>
                  <li>{t('Complete payment using your selected cryptocurrency')}</li>
                  <li>{t('You will be redirected back after payment completion')}</li>
                </ul>
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
              disabled={isProcessing} 
              className="flex-1"
            >
              {isProcessing ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {t('Redirecting...')}
                </>
              ) : (
                <>
                  <Coins className="mr-2 h-4 w-4" />
                  {t('Pay with Crypto')}
                </>
              )}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}