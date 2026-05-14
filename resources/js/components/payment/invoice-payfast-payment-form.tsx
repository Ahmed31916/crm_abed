import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, CreditCard, AlertCircle } from 'lucide-react';
import { toast } from '@/components/custom-toast';

interface InvoicePayfastPaymentFormProps {
  invoiceId: number;
  amount: number;
  paymentType: string;
  payfastMerchantId: string;
  currency: string;
  onSuccess: () => void;
  onCancel: () => void;
}

export function InvoicePayfastPaymentForm({
  invoiceId,
  amount,
  paymentType,
  payfastMerchantId,
  currency,
  onSuccess,
  onCancel
}: InvoicePayfastPaymentFormProps) {
  const { t } = useTranslation();
  const [isProcessing, setIsProcessing] = useState(false);
  const [customerDetails, setCustomerDetails] = useState({
    firstName: '',
    lastName: '',
    email: ''
  });

  const [errors, setErrors] = useState<{[key: string]: string}>({});

  const validateForm = () => {
    const newErrors: {[key: string]: string} = {};

    if (!customerDetails.firstName.trim()) {
      newErrors.firstName = t('First name is required');
    }

    if (!customerDetails.lastName.trim()) {
      newErrors.lastName = t('Last name is required');
    }

    if (!customerDetails.email.trim()) {
      newErrors.email = t('Email is required');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerDetails.email)) {
      newErrors.email = t('Please enter a valid email address');
    }

    return newErrors;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const formErrors = validateForm();
    setErrors(formErrors);

    if (Object.keys(formErrors).length > 0) {
      toast.error(t('Please fix the errors below'));
      return;
    }

    if (amount < 5) {
      toast.error(t('Minimum payment amount is R5.00'));
      return;
    }

    setIsProcessing(true);

    try {
      const response = await fetch(route('invoice.payfast.payment'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          invoice_id: invoiceId,
          amount: amount,
          payment_type: paymentType,
          customer_details: customerDetails
        })
      });

      const data = await response.json();

      if (data.success) {
        // Create and submit form to PayFast
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = data.action;
        form.innerHTML = data.inputs;
        document.body.appendChild(form);
        form.submit();
      } else {
        toast.error(data.error || t('Payment failed'));
        setIsProcessing(false);
      }

    } catch (error) {
      toast.error(t('Payment failed. Please try again.'));
      setIsProcessing(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return window.appSettings?.formatCurrency(Number(amount || 0)) || `${currency} ${Number(amount || 0).toFixed(2)}`;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <CreditCard className="h-5 w-5" />
          {t('PayFast Payment')}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstName">{t('First Name')}</Label>
              <Input
                id="firstName"
                value={customerDetails.firstName}
                onChange={(e) => {
                  setCustomerDetails(prev => ({ ...prev, firstName: e.target.value }));
                  if (errors.firstName) {
                    setErrors(prev => ({ ...prev, firstName: '' }));
                  }
                }}
                placeholder={t('Enter first name')}
                className={errors.firstName ? 'border-red-500' : ''}
                required
              />
              {errors.firstName && (
                <p className="text-sm text-red-500 mt-1">{errors.firstName}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastName">{t('Last Name')}</Label>
              <Input
                id="lastName"
                value={customerDetails.lastName}
                onChange={(e) => {
                  setCustomerDetails(prev => ({ ...prev, lastName: e.target.value }));
                  if (errors.lastName) {
                    setErrors(prev => ({ ...prev, lastName: '' }));
                  }
                }}
                placeholder={t('Enter last name')}
                className={errors.lastName ? 'border-red-500' : ''}
                required
              />
              {errors.lastName && (
                <p className="text-sm text-red-500 mt-1">{errors.lastName}</p>
              )}
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">{t('Email Address')}</Label>
            <Input
              id="email"
              type="email"
              value={customerDetails.email}
              onChange={(e) => {
                setCustomerDetails(prev => ({ ...prev, email: e.target.value }));
                if (errors.email) {
                  setErrors(prev => ({ ...prev, email: '' }));
                }
              }}
              placeholder={t('Enter email address')}
              className={errors.email ? 'border-red-500' : ''}
              required
            />
            {errors.email && (
              <p className="text-sm text-red-500 mt-1">{errors.email}</p>
            )}
            <p className="text-xs text-muted-foreground">
              {t('You will be redirected to PayFast to complete the payment')}
            </p>
          </div>

          {amount < 5 && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                {t('PayFast requires a minimum payment of R5.00. Current amount: {{amount}}', {
                  amount: formatCurrency(amount)
                })}
              </AlertDescription>
            </Alert>
          )}

          <div className="bg-muted p-3 rounded-lg">
            <div className="flex justify-between items-center">
              <span className="text-sm font-medium">{t('Payment Amount')}</span>
              <span className="text-sm font-bold">{formatCurrency(amount)}</span>
            </div>
            <div className="flex justify-between items-center mt-1">
              <span className="text-xs text-muted-foreground">{t('Payment Type')}</span>
              <span className="text-xs font-medium capitalize">{paymentType}</span>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              {t('Secure payment processing via PayFast')}
            </p>
          </div>

          <div className="flex gap-3 pt-4">
            <Button type="button" variant="outline" onClick={onCancel} className="flex-1">
              {t('Cancel')}
            </Button>
            <Button
              type="submit"
              disabled={isProcessing || amount < 5}
              className="flex-1"
            >
              {isProcessing ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {t('Processing...')}
                </>
              ) : (
                t('Pay {{amount}}', { amount: formatCurrency(amount) })
              )}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
