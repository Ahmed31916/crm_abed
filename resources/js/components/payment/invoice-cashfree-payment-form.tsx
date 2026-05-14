import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import axios from 'axios';
import '../../../css/cashfree-modal-fix.css';

interface InvoiceCashfreePaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: 'full' | 'partial';
  cashfreeAppId: string;
  mode?: 'sandbox' | 'production';
  currency?: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoiceCashfreePaymentForm({
  invoiceId,
  amount,
  paymentType,
  cashfreeAppId,
  mode = 'sandbox',
  currency = 'INR',
  onSuccess,
  onCancel
}: InvoiceCashfreePaymentFormProps) {
  const { t } = useTranslation();

  useEffect(() => {
    if (window && (window as any).Cashfree) {
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
    script.async = true;
    script.onerror = () => {
      toast.error(t('Failed to load Cashfree SDK. Please try again.'));
    };
    document.body.appendChild(script);

    return () => {
      if (document.body.contains(script)) {
        document.body.removeChild(script);
      }
    };
  }, [mode]);

  const handlePayment = async () => {
    try {
      const response = await axios.post(route('invoice.cashfree.create-session'), {
        invoice_id: invoiceId,
        amount: amount,
        payment_type: paymentType,
        _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      });

      if (response.data.error) {
        toast.error(response.data.error);
        return;
      }

      const { payment_session_id, order_id, amount: orderAmount, mode: serverMode } = response.data;

      if (!payment_session_id || !order_id) {
        toast.error(t('Invalid response from server'));
        return;
      }

      if (!serverMode) {
        toast.error(t('Payment mode not configured'));
        return;
      }

      if (!(window as any).Cashfree) {
        toast.error(t('Cashfree SDK not loaded'));
        return;
      }

      const cashfreeMode = serverMode === 'production' ? 'PROD' : 'SANDBOX';
      
      try {
        const cashfree = (window as any).Cashfree({
          mode: cashfreeMode
        });
      } catch (error) {
        toast.error('Failed to initialize Cashfree: ' + error.message);
        return;
      }

      const cashfree = (window as any).Cashfree({
        mode: cashfreeMode
      });

      const checkoutOptions = {
        paymentSessionId: payment_session_id,
        returnUrl: window.location.href,
        redirectTarget: '_modal',
        mode: cashfreeMode,
        style: {
          zIndex: 99999
        }
      };

      cashfree.checkout(checkoutOptions).then((result: any) => {
        if (result.error) {
          toast.error(result.error.message || t('Payment failed'));
          return;
        }

        if (result.paymentDetails) {
          axios.post(route('invoice.cashfree.verify-payment'), {
            order_id: order_id,
            invoice_id: invoiceId,
            amount: amount,
            payment_type: paymentType,
            _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          })
          .then((response) => {
            toast.success(t('Payment successful'));
            onSuccess();
          })
          .catch((error) => {
            const errorMsg = error.response?.data?.error || t('Payment verification failed');
            toast.error(errorMsg);
          });
        } else {
          toast.error(t('Payment status unclear'));
        }
      }).catch((error: any) => {
        toast.error(error.message || t('Payment initialization failed'));
      });

    } catch (error: any) {
      const errorMsg = error.response?.data?.error || t('Failed to initialize payment');
      toast.error(errorMsg);
    }
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        {t('You will be redirected to Cashfree to complete your payment.')}
      </p>

      <div className="flex gap-3">
        <Button variant="outline" onClick={onCancel} className="flex-1">
          {t('Cancel')}
        </Button>
        <Button onClick={handlePayment} className="flex-1">
          {t('Pay with Cashfree')}
        </Button>
      </div>
    </div>
  );
}
