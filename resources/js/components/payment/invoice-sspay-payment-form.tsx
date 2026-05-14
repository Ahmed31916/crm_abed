import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Loader2, ExternalLink } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import axios from 'axios';

interface InvoiceSSPayPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    sspayCategoryCode: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoiceSSPayPaymentForm({
    invoiceId,
    amount,
    paymentType,
    sspayCategoryCode,
    currency = 'MYR',
    onSuccess,
    onCancel
}: InvoiceSSPayPaymentFormProps) {
    const { t } = useTranslation();
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        initializePayment();
    }, []);

    const initializePayment = async () => {
        setIsProcessing(true);

        try {
            const response = await axios.post(route('invoice.sspay.create'), {
                invoice_id: invoiceId,
                amount: amount,
                payment_type: paymentType,
                _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            });

            if (response.data.success) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = response.data.payment_url;

                Object.keys(response.data.payment_data).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = response.data.payment_data[key];
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            } else {
                throw new Error(response.data.message || 'Payment initialization failed');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.message || t('Payment failed. Please try again.'));
            setIsProcessing(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <ExternalLink className="h-5 w-5 text-blue-600 mt-0.5" />
                    <div>
                        <h4 className="font-medium text-blue-900 mb-1">
                            {t('Secure Payment with SSPay')}
                        </h4>
                        <p className="text-sm text-blue-700">
                            {t('You will be redirected to SSPay secure payment page to complete your transaction.')}
                        </p>
                    </div>
                </div>
            </div>

            <div className="border rounded-lg p-4 bg-gray-50">
                <div className="flex justify-between items-center mb-2">
                    <span className="text-sm font-medium text-gray-600">{t('Payment Type')}:</span>
                    <span className="text-sm text-gray-900 capitalize">{paymentType}</span>
                </div>
                <div className="flex justify-between items-center">
                    <span className="text-sm font-medium text-gray-600">{t('Amount')}:</span>
                    <span className="text-lg font-bold text-gray-900">{currency} {amount}</span>
                </div>
            </div>

            <div className="flex items-center justify-center py-8">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
                <span className="ml-3 text-muted-foreground">{t('Redirecting to SSPay...')}</span>
            </div>

            <div className="flex gap-3">
                <Button
                    variant="outline"
                    onClick={onCancel}
                    className="flex-1"
                    disabled={isProcessing}
                >
                    {t('Cancel')}
                </Button>
            </div>
        </div>
    );
}
