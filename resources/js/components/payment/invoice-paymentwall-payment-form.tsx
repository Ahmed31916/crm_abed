import { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';

declare global {
    interface Window {
        Brick: any;
    }
}

interface InvoicePaymentWallPaymentFormProps {
    invoiceId: number;
    amount: number;
    paymentType: 'full' | 'partial';
    paymentwallPublicKey: string;
    currency?: string;
    onSuccess: () => void;
    onCancel: () => void;
}

export function InvoicePaymentWallPaymentForm({
    invoiceId,
    amount,
    paymentType,
    paymentwallPublicKey,
    currency = 'USD',
    onSuccess,
    onCancel
}: InvoicePaymentWallPaymentFormProps) {
    const { t } = useTranslation();
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [brickLoaded, setBrickLoaded] = useState(false);
    const [brickInstance, setBrickInstance] = useState<any>(null);
    const paymentFormRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const loadBrickScript = () => {
            if (window.Brick) {
                setBrickLoaded(true);
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://api.paymentwall.com/brick/build/brick-default.1.5.0.min.js';
            script.async = true;
            script.onload = () => setBrickLoaded(true);
            script.onerror = () => setError(t('Failed to load PaymentWall payment form'));
            document.head.appendChild(script);
        };

        loadBrickScript();
    }, [t]);

    useEffect(() => {
        if (brickLoaded && paymentwallPublicKey && !brickInstance) {
            initializeBrickForm();
        }
    }, [brickLoaded, paymentwallPublicKey, brickInstance]);

    const initializeBrickForm = async () => {
        try {
            const response = await fetch(route('invoice.paymentwall.create'), {
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

            if (data.success && data.brick_config) {
                const config = data.brick_config;

                const brick = new window.Brick({
                    public_key: config.public_key,
                    amount: config.amount,
                    currency: config.currency,
                    container: 'paymentwall-form-container',
                    action: route('invoice.paymentwall.process'),
                    form: {
                        merchant: 'Invoice Payment',
                        product: config.description,
                        pay_button: t('Pay Now'),
                        show_zip: true,
                        show_cardholder: true
                    }
                });

                brick.showPaymentForm(
                    (data: any) => {
                        onSuccess();
                    },
                    (errors: any) => {
                        if (errors && errors.length > 0) {
                            setError(errors[0].message || t('Payment failed'));
                        } else {
                            setError(t('Payment failed'));
                        }
                        setIsLoading(false);
                    }
                );

                setBrickInstance(brick);
            } else {
                throw new Error(data.message || t('Failed to initialize payment form'));
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : t('Payment initialization failed'));
        }
    };

    return (
        <div className="space-y-4">
            {error && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

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

            <div id="paymentwall-form-container" ref={paymentFormRef} className="min-h-[300px]">
                {!brickLoaded && (
                    <div className="flex items-center justify-center h-32">
                        <Loader2 className="h-6 w-6 animate-spin mr-2" />
                        <span>{t('Loading payment form...')}</span>
                    </div>
                )}
            </div>

            <form id="brick-form" style={{display: 'none'}}>
                <input type="hidden" name="invoice_id" value={invoiceId} />
                <input type="hidden" name="amount" value={amount} />
                <input type="hidden" name="payment_type" value={paymentType} />
                <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''} />
            </form>

            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h4 className="font-medium text-blue-900 mb-2">{t('Secure Payment')}</h4>
                <ul className="text-sm text-blue-800 space-y-1">
                    <li>• SSL Encrypted & PCI DSS Compliant</li>
                    <li>• Multiple Payment Methods Supported</li>
                    <li>• Powered by PaymentWall</li>
                </ul>
            </div>

            <div className="flex gap-3">
                <Button
                    variant="outline"
                    onClick={onCancel}
                    className="flex-1"
                    disabled={isLoading}
                >
                    {t('Cancel')}
                </Button>
                {!brickLoaded && (
                    <Button disabled className="flex-1">
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        {t('Loading...')}
                    </Button>
                )}
            </div>
        </div>
    );
}
