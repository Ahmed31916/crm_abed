import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { router } from '@inertiajs/react';

interface PaymentData {
  planId?: number;
  billingCycle?: string;
  couponCode?: string;
  invoiceId?: number;
  amount?: number;
  paymentType?: string;
  paymentMethod?: string;
  [key: string]: any;
}

interface UsePaymentProcessorOptions {
  onSuccess?: () => void;
  onError?: (error: string) => void;
}

export function usePaymentProcessor(options: UsePaymentProcessorOptions = {}) {
  const { t } = useTranslation();
  const [processing, setProcessing] = useState(false);

  const processPayment = async (paymentMethod: string, data: PaymentData) => {
    setProcessing(true);

    const routes = {
      stripe: data.invoiceId ? 'invoice.stripe.payment' : 'stripe.payment',
      paypal: data.invoiceId ? 'invoice.paypal.payment' : 'paypal.payment',
      bank: data.invoiceId ? 'invoice.bank.payment' : 'bank.payment',
      payfast: data.invoiceId ? 'invoice.payfast.payment' : 'payfast.payment',
      tap: data.invoiceId ? 'invoice.tap.payment' : 'tap.payment',
      xendit: data.invoiceId ? 'invoice.xendit.payment' : 'xendit.payment',
      razorpay: 'razorpay.payment',
      mercadopago: 'mercadopago.payment',
      paystack: 'paystack.payment',
      flutterwave: 'flutterwave.payment',
    };

    const routeName = routes[paymentMethod as keyof typeof routes];
    
    if (!routeName) {
      toast.error(t('Invalid payment method'));
      setProcessing(false);
      return;
    }

    const formattedData = formatPaymentData(paymentMethod, data);
    
    router.post(route(routeName), formattedData, {
      onSuccess: (page) => {
        // Check if there's a success message in the response
        if (page.props?.flash?.success) {
          toast.success(t(page.props.flash.success));
        } else {
          toast.success(t('Payment successful'));
        }
        options.onSuccess?.();
      },
      onError: (errors) => {
        const errorMessage = errors?.message || errors?.error || t('Payment failed');
        toast.error(errorMessage);
        options.onError?.(errorMessage);
      },
      onFinish: () => {
        setProcessing(false);
      }
    });
  };

  const validatePaymentData = (paymentMethod: string, data: PaymentData): boolean => {
    const requiredFields = {
      stripe: ['payment_method_id', 'cardholder_name'],
      paypal: ['order_id', 'payment_id'],
      bank: ['amount'],
      payfast: ['customer_details'],
      tap: [],
      xendit: [],
      razorpay: ['payment_id', 'order_id', 'signature'],
      mercadopago: ['payment_id', 'status'],
      paystack: ['payment_id'],
      flutterwave: ['payment_id'],
    };

    const required = requiredFields[paymentMethod as keyof typeof requiredFields] || [];
    
    for (const field of required) {
      if (!data[field]) {
        toast.error(t(`${field} is required`));
        return false;
      }
    }

    return true;
  };

  const formatPaymentData = (paymentMethod: string, data: PaymentData) => {
    if (data.invoiceId) {
      // Invoice payment format
      return {
        invoice_id: data.invoiceId,
        amount: data.amount,
        payment_type: data.paymentType,
        ...data,
      };
    }
    // Plan payment format
    return {
      plan_id: data.planId,
      billing_cycle: data.billingCycle,
      coupon_code: data.couponCode || '',
      ...data,
    };
  };

  return {
    processing,
    processPayment,
    validatePaymentData,
    formatPaymentData,
  };
}