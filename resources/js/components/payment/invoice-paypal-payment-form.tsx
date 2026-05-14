import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { usePaymentProcessor } from '@/hooks/usePaymentProcessor';

interface InvoicePayPalPaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: string;
  paypalClientId: string;
  currency: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoicePayPalPaymentForm({
  invoiceId,
  amount,
  paymentType,
  paypalClientId,
  currency,
  onSuccess,
  onCancel
}: InvoicePayPalPaymentFormProps) {
  const { t } = useTranslation();
  const paypalRef = useRef<HTMLDivElement>(null);

  const { processPayment } = usePaymentProcessor({
    onSuccess,
    onError: (error) => toast.error(error)
  });

  useEffect(() => {
    if (!paypalClientId || !paypalRef.current) return;

    // Load PayPal SDK
    const script = document.createElement('script');
    script.src = `https://www.paypal.com/sdk/js?client-id=${paypalClientId}&currency=${currency.toUpperCase()}&disable-funding=credit,card`;
    script.async = true;

    script.onload = () => {
      if (window.paypal && paypalRef.current) {
        window.paypal.Buttons({
          createOrder: (data: any, actions: any) => {
            return actions.order.create({
              purchase_units: [{
                amount: {
                  value: amount.toFixed(2),
                  currency_code: currency.toUpperCase()
                },
                description: `Invoice Payment - ${paymentType}`
              }]
            });
          },
          onApprove: (data: any, actions: any) => {
            return actions.order.capture().then((details: any) => {
              processPayment('paypal', {
                invoiceId,
                amount,
                paymentType,
                order_id: data.orderID,
                payment_id: details.id,
              });
            });
          },
          onError: (err: any) => {
            if (err.message && err.message.includes('declined')) {
              toast.error(t('Payment was declined. Please try a different payment method.'));
            } else {
              toast.error(t('Payment failed. Please try again.'));
            }
          },
          onCancel: () => {
            onCancel();
          }
        }).render(paypalRef.current);
      }
    };

    document.head.appendChild(script);

    return () => {
      if (document.head.contains(script)) {
        document.head.removeChild(script);
      }
    };
  }, [paypalClientId, invoiceId, amount, paymentType, currency]);

  if (!paypalClientId) {
    return <div className="p-4 text-center text-red-500">{t('PayPal not configured')}</div>;
  }

  return (
    <div className="space-y-4">
      <div ref={paypalRef}></div>
    </div>
  );
}

// Extend window object for PayPal
declare global {
  interface Window {
    paypal?: any;
  }
}
