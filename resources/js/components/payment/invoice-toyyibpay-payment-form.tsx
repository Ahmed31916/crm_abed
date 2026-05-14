import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2, ExternalLink } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import axios from 'axios';

interface InvoiceToyyibPayPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    toyyibpayCategoryCode: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoiceToyyibPayPaymentForm({
    invoiceId,
    amount,
    paymentType,
    toyyibpayCategoryCode,
    currency = 'MYR',
    onSuccess,
    onCancel
}: InvoiceToyyibPayPaymentFormProps) {
    const { t } = useTranslation();
    const [isProcessing, setIsProcessing] = useState(false);
    const [customerDetails, setCustomerDetails] = useState({
        billName: '',
        billTo: '',
        billEmail: '',
        billPhone: ''
    });

    const handlePayment = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!customerDetails.billName || !customerDetails.billTo || !customerDetails.billEmail || !customerDetails.billPhone) {
            toast.error(t('Please fill in all required fields'));
            return;
        }

        setIsProcessing(true);

        try {
            const response = await axios.post(route('invoice.toyyibpay.payment'), {
                invoice_id: invoiceId,
                amount: amount,
                payment_type: paymentType,
                billName: customerDetails.billName,
                billTo: customerDetails.billTo,
                billEmail: customerDetails.billEmail,
                billPhone: customerDetails.billPhone,
                _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.data.success && response.data.redirect_url) {
                toast.success(t('Redirecting to ToyyibPay payment page...'));
                setTimeout(() => {
                    window.location.href = response.data.redirect_url;
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
                            {t('Secure Payment with ToyyibPay')}
                        </h4>
                        <p className="text-sm text-blue-700">
                            {t('You will be redirected to ToyyibPay secure payment page to complete your transaction.')}
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

            <form onSubmit={handlePayment} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="billName">{t('Bill Name')} *</Label>
                    <Input
                        id="billName"
                        value={customerDetails.billName}
                        onChange={(e) => setCustomerDetails(prev => ({ ...prev, billName: e.target.value }))}
                        placeholder={t('Enter bill name')}
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="billTo">{t('Bill To (Name)')} *</Label>
                    <Input
                        id="billTo"
                        value={customerDetails.billTo}
                        onChange={(e) => setCustomerDetails(prev => ({ ...prev, billTo: e.target.value }))}
                        placeholder={t('Enter customer name')}
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="billEmail">{t('Email Address')} *</Label>
                    <Input
                        id="billEmail"
                        type="email"
                        value={customerDetails.billEmail}
                        onChange={(e) => setCustomerDetails(prev => ({ ...prev, billEmail: e.target.value }))}
                        placeholder={t('Enter email address')}
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="billPhone">{t('Phone Number')} *</Label>
                    <Input
                        id="billPhone"
                        value={customerDetails.billPhone}
                        onChange={(e) => setCustomerDetails(prev => ({ ...prev, billPhone: e.target.value }))}
                        placeholder="60123456789"
                        required
                    />
                    <p className="text-xs text-muted-foreground">
                        {t('Malaysian phone number format: 60123456789')}
                    </p>
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
                                <ExternalLink className="mr-2 h-4 w-4" />
                                {t('Pay with ToyyibPay')}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </div>
    );
}
