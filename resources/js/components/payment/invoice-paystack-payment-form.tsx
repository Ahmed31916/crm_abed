import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import axios from 'axios';

interface InvoicePaystackPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    paystackKey: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoicePaystackPaymentForm({
    invoiceId,
    amount,
    paymentType,
    paystackKey,
    currency = 'NGN',
    onSuccess,
    onCancel
}: InvoicePaystackPaymentFormProps) {
    const { t } = useTranslation();
    const initialized = useRef(false);

    useEffect(() => {
        if (!paystackKey || initialized.current) return;

        const script = document.createElement('script');
        script.src = 'https://js.paystack.co/v1/inline.js';
        script.async = true;
        
        script.onload = () => {
            initialized.current = true;
            
            // Hide parent modal temporarily
            const modalBackdrop = document.querySelector('[data-radix-dialog-overlay]');
            if (modalBackdrop) {
                (modalBackdrop as HTMLElement).style.display = 'none';
            }
            
            const handler = (window as any).PaystackPop.setup({
                key: paystackKey,
                email: 'customer@example.com', // Should be dynamic if available
                amount: Math.round(Number(amount) * 100), // Convert to kobo as integer
                currency: currency.toUpperCase(),
                callback: function(response: any) {
                    // Restore modal backdrop
                    if (modalBackdrop) {
                        (modalBackdrop as HTMLElement).style.display = '';
                    }
                    
                    // Process payment on server
                    axios.post(route('invoice.paystack.payment'), {
                        invoice_id: invoiceId,
                        amount: amount,
                        payment_type: paymentType,
                        payment_id: response.reference,
                    })
                    .then(() => {
                        onSuccess();
                    })
                    .catch((error) => {
                        const errorMsg = error.response?.data?.error || t('Payment processing failed');
                        toast.error(errorMsg);
                    });
                },
                onClose: function() {
                    // Restore modal backdrop
                    if (modalBackdrop) {
                        (modalBackdrop as HTMLElement).style.display = '';
                    }
                    onCancel();
                }
            });
            
            handler.openIframe();
        };

        script.onerror = () => {
            toast.error(t('Failed to load Paystack checkout. Please try again.'));
        };

        document.head.appendChild(script);

        return () => {
            if (document.head.contains(script)) {
                document.head.removeChild(script);
            }
        };
    }, [paystackKey, invoiceId, amount, paymentType, currency]);

    if (!paystackKey) {
        return <div className="p-4 text-center text-red-500">{t('Paystack not configured')}</div>;
    }

    return (
        <div className="p-4 text-center">
            <p>{t('Redirecting to Paystack...')}</p>
        </div>
    );
}