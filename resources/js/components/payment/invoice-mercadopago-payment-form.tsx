import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { Loader2 } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import axios from 'axios';

interface InvoiceMercadoPagoPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    accessToken: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoiceMercadoPagoPaymentForm({
    invoiceId,
    amount,
    paymentType,
    accessToken,
    currency = 'BRL',
    onSuccess,
    onCancel
}: InvoiceMercadoPagoPaymentFormProps) {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    
    const handlePayment = async () => {
        try {
            setIsLoading(true);
            
            // Create preference and redirect to MercadoPago checkout
            const response = await axios.post(route('invoice.mercadopago.create-preference'), {
                invoice_id: invoiceId,
                amount: amount,
                payment_type: paymentType
            }, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.data.redirect_url) {
                // Redirect to MercadoPago checkout
                window.location.href = response.data.redirect_url;
            } else {
                toast.error(t('Failed to create payment preference'));
                setIsLoading(false);
            }
        } catch (error: any) {
            const errorMsg = error.response?.data?.error || error.message || t('Failed to create payment preference');
            toast.error(errorMsg);
            setIsLoading(false);
        }
    };
    
    return (
        <div className="space-y-4">
            <p className="text-sm text-muted-foreground">
                {t('You will be redirected to MercadoPago to complete your payment.')}
            </p>
            
            <div className="flex gap-3">
                <Button variant="outline" onClick={onCancel} className="flex-1" disabled={isLoading}>
                    {t('Cancel')}
                </Button>
                <Button 
                    onClick={handlePayment}
                    className="flex-1"
                    disabled={isLoading}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                            {t('Processing...')}
                        </>
                    ) : (
                        t('Pay with MercadoPago')
                    )}
                </Button>
            </div>
        </div>
    );
}