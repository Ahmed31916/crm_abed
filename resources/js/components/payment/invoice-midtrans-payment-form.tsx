import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';
import { router } from '@inertiajs/react';

interface InvoiceMidtransPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    midtransClientKey: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoiceMidtransPaymentForm({
    invoiceId,
    amount,
    paymentType,
    midtransClientKey,
    currency = 'IDR',
    onSuccess,
    onCancel,
}: InvoiceMidtransPaymentFormProps) {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handlePayment = async () => {
        if (!midtransClientKey) {
            setError(t('Midtrans not configured'));
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(route('invoice.midtrans.create'), {
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
                initializeMidtransSnap(data.snap_token, data.order_id);
            } else {
                throw new Error(data.error || t('Payment creation failed'));
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : t('Payment initialization failed'));
            setIsLoading(false);
        }
    };

    const initializeMidtransSnap = (snapToken: string, orderId: string) => {
        if (!window.snap) {
            const script = document.createElement('script');
            script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
            script.setAttribute('data-client-key', midtransClientKey);
            script.onload = () => {
                openSnapPayment(snapToken, orderId);
            };
            script.onerror = () => {
                setError(t('Failed to load Midtrans script'));
                setIsLoading(false);
            };
            document.head.appendChild(script);
        } else {
            openSnapPayment(snapToken, orderId);
        }
    };

    const openSnapPayment = (snapToken: string, orderId: string) => {
        window.snap.pay(snapToken, {
            onSuccess: (result: any) => {
                handlePaymentSuccess(result, orderId);
            },
            onPending: (result: any) => {
                setIsLoading(false);
            },
            onError: (result: any) => {
                setError(t('Payment failed'));
                setIsLoading(false);
            },
            onClose: () => {
                setIsLoading(false);
            }
        });
    };

    const handlePaymentSuccess = (result: any, orderId: string) => {
        router.visit(route('invoice.midtrans.success', {
            invoice_id: invoiceId,
            amount: amount,
            payment_type: paymentType,
            order_id: orderId,
            transaction_status: result.transaction_status,
        }), {
            onSuccess: () => {
                onSuccess();
            },
            onError: () => {
                setError(t('Payment processing failed'));
                setIsLoading(false);
            },
        });
    };

    const formatPrice = (price: number) => {
        return window.appSettings?.formatCurrency(Number(price || 0)) || new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        }).format(price);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <CreditCard className="h-5 w-5" />
                    {t('Midtrans Payment')}
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
                        {t('Payment Type')}: {t(paymentType === 'full' ? 'Full Payment' : 'Partial Payment')}
                    </div>
                </div>

                <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h4 className="font-medium text-blue-900 mb-2">{t('Supported Payment Methods')}</h4>
                    <ul className="text-sm text-blue-800 space-y-1">
                        <li>• Credit/Debit Cards</li>
                        <li>• Bank Transfer</li>
                        <li>• E-Wallets (GoPay, OVO, DANA)</li>
                        <li>• Convenience Stores</li>
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
                        disabled={isLoading || !midtransClientKey}
                        className="flex-1"
                    >
                        {isLoading ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                {t('Processing...')}
                            </>
                        ) : (
                            <>
                                <CreditCard className="mr-2 h-4 w-4" />
                                {t('Pay with Midtrans')}
                            </>
                        )}
                    </Button>
                </div>

            </CardContent>
        </Card>
    );
}
