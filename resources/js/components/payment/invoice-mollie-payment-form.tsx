import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Loader2, ExternalLink } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import axios from 'axios';

interface InvoiceMolliePaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    mollieApiKey: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoiceMolliePaymentForm({
    invoiceId,
    amount,
    paymentType,
    mollieApiKey,
    currency = 'EUR',
    onSuccess,
    onCancel
}: InvoiceMolliePaymentFormProps) {
    const { t } = useTranslation();
    const [isProcessing, setIsProcessing] = useState(false);

    const handlePayment = async () => {
        setIsProcessing(true);

        try {
            const response = await axios.post(route('invoice.mollie.payment'), {
                invoice_id: invoiceId,
                amount: amount,
                payment_type: paymentType,
                _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.data.success && response.data.checkout_url) {
                toast.success(t('Redirecting to Mollie payment page...'));
                setTimeout(() => {
                    window.location.href = response.data.checkout_url;
                }, 1000);
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
                            {t('Secure Payment with Mollie')}
                        </h4>
                        <p className="text-sm text-blue-700">
                            {t('You will be redirected to Mollie secure payment page to complete your transaction.')}
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

            <div className="flex gap-3">
                <Button
                    variant="outline"
                    onClick={onCancel}
                    className="flex-1"
                    disabled={isProcessing}
                >
                    {t('Cancel')}
                </Button>
                <Button
                    onClick={handlePayment}
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
                            <ExternalLink className="mr-2 h-4 w-4" />
                            {t('Pay with Mollie')}
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
}
