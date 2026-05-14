// components/CrudFormModal.tsx
import React, { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Checkbox } from '@/components/ui/checkbox';
import { Switch } from '@/components/ui/switch';
import { FormField } from '@/types/crud';
import { MultiSelectField } from '@/components/multi-select-field';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';
import MediaPicker from '@/components/MediaPicker';
import { Link } from '@inertiajs/react';
import { RichTextField } from '@/components/ui/rich-text-field';

interface CrudFormModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSubmit: (data: any) => void;
    formConfig: {
        fields: FormField[];
        modalSize?: string;
        columns?: number;
        layout?: 'grid' | 'flex' | 'default';
        productOptions?: any[];
        priceSummary?: {
            unitPrice: number;
            quantity: number;
            quantityFieldName?: string;
        };
        exportRoute?: string;
    };
    initialData?: any;
    title: string;
    mode: 'create' | 'edit' | 'view';
    description?: string;
    isSubmitting?: boolean;
}

// Standalone date input that opens picker on any click
function DateInputField({ field, dateValue, handleChange, errors, mode }: {
    field: FormField;
    dateValue: string;
    handleChange: (name: string, value: any) => void;
    errors: Record<string, string>;
    mode: string;
}) {
    const inputRef = React.useRef<HTMLInputElement>(null);

    const openPicker = () => {
        if (!field.disabled && mode !== 'view' && inputRef.current) {
            try {
                inputRef.current.showPicker?.();
            } catch {
                inputRef.current.focus();
            }
        }
    };

    return (
        <div className="relative cursor-pointer" onClick={openPicker}>
            <input
                ref={inputRef}
                id={field.name}
                name={field.name}
                type="date"
                placeholder={field.placeholder}
                value={dateValue}
                onChange={(e) => handleChange(field.name, e.target.value)}
                required={!!field.required}
                className={`border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-primary md:text-sm cursor-pointer disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 ${errors[field.name] ? 'border-red-500' : ''}`}
                disabled={field.disabled || mode === 'view'}
                readOnly={field.readOnly}
            />
        </div>
    );
}

// Standalone date input that opens picker on any click
function TimeInputField({ field, timeValue, handleChange, errors, mode }: {
    field: FormField;
    timeValue: string;
    handleChange: (name: string, value: any) => void;
    errors: Record<string, string>;
    mode: string;
}) {
    const inputRef = React.useRef<HTMLInputElement>(null);

    const openPicker = () => {
        if (!field.disabled && mode !== 'view' && inputRef.current) {
            try {
                inputRef.current.showPicker?.();
            } catch {
                inputRef.current.focus();
            }
        }
    };

    return (
        <div className="relative cursor-pointer" onClick={openPicker}>
            <input
                ref={inputRef}
                id={field.name}
                name={field.name}
                type="time"
                placeholder={field.placeholder}
                value={timeValue}
                onChange={(e) => handleChange(field.name, e.target.value)}
                required={!!field.required}
                className={`border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-primary md:text-sm cursor-pointer disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 ${errors[field.name] ? 'border-red-500' : ''}`}
                disabled={field.disabled || mode === 'view'}
                readOnly={field.readOnly}
            />
        </div>
    );
}

export function CrudFormModal({
    isOpen,
    onClose,
    onSubmit,
    formConfig,
    initialData = {},
    title,
    mode,
    description,
    isSubmitting = false
}: CrudFormModalProps) {
    const { t } = useTranslation();
    const [formData, setFormData] = useState<Record<string, any>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [relationOptions, setRelationOptions] = useState<Record<string, any[]>>({});

    // Conditionally declare handleExport only if exportRoute exists
    const handleExportAction = formConfig.exportRoute ? () => {
        window.location.href = route(formConfig.exportRoute!);
    } : undefined;

    // Calculate total price for price summary
    const calculateTotal = () => {
        if (!formConfig.priceSummary) return 0;
        const quantity = formData[formConfig.priceSummary.quantityFieldName || 'quantity'] || formConfig.priceSummary.quantity || 1;
        return formConfig.priceSummary.unitPrice * quantity;
    };

    // Load initial data when modal opens
    useEffect(() => {
        if (isOpen) {
            // Create a clean copy of the initial data
            const cleanData = { ...initialData };

            // Process fields and set default values
            formConfig.fields.forEach(field => {
                if (field.type === 'multi-select') {
                    if (cleanData[field.name] && !Array.isArray(cleanData[field.name])) {
                        // Convert to array if it's not already
                        cleanData[field.name] = Array.isArray(cleanData[field.name])
                            ? cleanData[field.name]
                            : cleanData[field.name] ? [cleanData[field.name].toString()] : [];
                    }
                }

                // Set default values for fields that don't have values yet (create mode)
                if (mode === 'create' && (cleanData[field.name] === undefined || cleanData[field.name] === null)) {
                    if (field.defaultValue !== undefined) {
                        cleanData[field.name] = field.defaultValue;
                    }
                }
            });

            setFormData(cleanData || {});
            setErrors({});

            // Load relation data for select fields
            formConfig.fields.forEach(field => {
                if (field.relation && field.relation.endpoint) {
                    fetch(field.relation.endpoint)
                        .then(res => res.json())
                        .then(data => {
                            setRelationOptions(prev => ({
                                ...prev,
                                [field.name]: Array.isArray(data) ? data : data.data || []
                            }));
                        })
                        .catch(err => {
                            // Silent error handling
                        });
                }
            });

            // Load parent records if parent_module is set in edit mode
            if (mode === 'edit' && cleanData.parent_module && cleanData.parent_module !== 'none') {
                fetch(route('api.parent-module.records', cleanData.parent_module))
                    .then(res => res.json())
                    .then(data => {
                        setRelationOptions(prev => ({
                            ...prev,
                            parent_id: data
                        }));
                    })
                    .catch(err => { });
            }

            // Load attendee records for each attendee in edit mode
            if (mode === 'edit' && cleanData.attendees && Array.isArray(cleanData.attendees)) {
                cleanData.attendees.forEach((attendee: any, index: number) => {
                    if (attendee.type) {
                        fetch(route('api.attendee-types.records', attendee.type))
                            .then(res => res.json())
                            .then(data => {
                                setRelationOptions(prev => ({
                                    ...prev,
                                    [`attendees_${index}_id`]: data
                                }));
                            })
                            .catch(err => { });
                    }
                });
            }
        }
    }, [isOpen, initialData, formConfig.fields, mode]);

    const handleChange = (name: string, value: any) => {
        const newFormData = { ...formData, [name]: value };

        // Auto-calculate amount when products change
        if (name === 'products' && Array.isArray(value)) {
            const totalAmount = value.reduce((total: number, product: any) => {
                const quantity = parseFloat(product.quantity) || 0;
                const unitPrice = parseFloat(product.unit_price) || 0;
                return total + (quantity * unitPrice);
            }, 0);
            newFormData.amount = totalAmount;
        }

        // Handle parent module change for meetings
        if (name === 'parent_module') {
            // Clear parent_id when parent_module changes
            newFormData.parent_id = '';

            // Load parent records if module is selected
            if (value && value !== 'none') {
                fetch(route('api.parent-module.records', value))
                    .then(res => res.json())
                    .then(data => {
                        setRelationOptions(prev => ({
                            ...prev,
                            parent_id: data
                        }));
                    })
                    .catch(err => { });
            } else {
                setRelationOptions(prev => ({
                    ...prev,
                    parent_id: []
                }));
            }
        }

        // Handle opportunity change for quotes
        if (name === 'opportunity_id' && value) {
            fetch(route('api.opportunities.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from opportunity
                    const updatedFormData = {
                        ...newFormData,
                        account_id: data.account_id || '',
                        billing_contact_id: data.billing_contact_id || '',
                        shipping_contact_id: data.shipping_contact_id || '',
                        products: data.products || []
                    };
                    setFormData(updatedFormData);
                })
                .catch(err => { });
        }

        // Handle quote change for sales orders
        if (name === 'quote_id' && value) {
            fetch(route('api.quotes.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from quote
                    const updatedFormData = {
                        ...newFormData,
                        account_id: data.account_id || '',
                        billing_contact_id: data.billing_contact_id || '',
                        shipping_contact_id: data.shipping_contact_id || '',
                        billing_address: data.billing_address || '',
                        billing_city: data.billing_city || '',
                        billing_state: data.billing_state || '',
                        billing_postal_code: data.billing_postal_code || '',
                        billing_country: data.billing_country || '',
                        shipping_address: data.shipping_address || '',
                        shipping_city: data.shipping_city || '',
                        shipping_state: data.shipping_state || '',
                        shipping_postal_code: data.shipping_postal_code || '',
                        shipping_country: data.shipping_country || '',
                        shipping_provider_type_id: data.shipping_provider_type_id || '',
                        products: data.products || []
                    };
                    setFormData(updatedFormData);
                })
                .catch(err => { });
        }

        // Handle sales order change for purchase orders
        if (name === 'sales_order_id' && value) {
            const isInvoiceForm = window.location.pathname.includes('/invoices');
            const apiRoute = isInvoiceForm ? 'api.invoices.sales-orders.details' : 'api.sales-orders.details';

            fetch(route(apiRoute, value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from sales order
                    const updatedFormData = {
                        ...newFormData,
                        account_id: data.account_id || '',
                        billing_contact_id: data.billing_contact_id || '',
                        shipping_contact_id: data.shipping_contact_id || '',
                        contact_id: data.contact_id || '',
                        quote_id: data.quote_id || '',
                        opportunity_id: data.opportunity_id || '',
                        billing_address: data.billing_address || '',
                        billing_city: data.billing_city || '',
                        billing_state: data.billing_state || '',
                        billing_postal_code: data.billing_postal_code || '',
                        billing_country: data.billing_country || '',
                        shipping_address: data.shipping_address || '',
                        shipping_city: data.shipping_city || '',
                        shipping_state: data.shipping_state || '',
                        shipping_postal_code: data.shipping_postal_code || '',
                        shipping_country: data.shipping_country || '',
                        shipping_provider_type_id: data.shipping_provider_type_id || '',
                        products: data.products || []
                    };
                    setFormData(updatedFormData);
                })
                .catch(err => { });
        }

        // Handle purchase order change for receipt orders
        if (name === 'purchase_order_id' && value && value !== 'none') {
            // Clear return order when purchase order is selected
            const updatedFormData = { ...newFormData, return_order_id: '' };

            fetch(route('api.receipt-orders.purchase-orders.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from purchase order
                    const finalFormData = {
                        ...updatedFormData,
                        account_id: data.account_id || '',
                        contact_id: data.contact_id || '',
                        products: data.products || []
                    };
                    setFormData(finalFormData);
                })
                .catch(err => { });
        }

        // Handle return order change for receipt orders
        if (name === 'return_order_id' && value && value !== 'none') {
            // Clear purchase order when return order is selected
            const updatedFormData = { ...newFormData, purchase_order_id: '' };

            fetch(route('api.receipt-orders.return-orders.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from return order
                    const finalFormData = {
                        ...updatedFormData,
                        account_id: data.account_id || '',
                        contact_id: data.contact_id || '',
                        products: data.products || []
                    };
                    setFormData(finalFormData);
                })
                .catch(err => { });
        }

        // Handle quote change for invoices
        if (name === 'quote_id' && value && window.location.pathname.includes('/invoices')) {
            fetch(route('api.invoices.quotes.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from quote
                    const updatedFormData = {
                        ...newFormData,
                        account_id: data.account_id || '',
                        contact_id: data.contact_id || '',
                        billing_address: data.billing_address || '',
                        billing_city: data.billing_city || '',
                        billing_state: data.billing_state || '',
                        billing_postal_code: data.billing_postal_code || '',
                        billing_country: data.billing_country || '',
                        products: data.products || []
                    };
                    setFormData(updatedFormData);
                })
                .catch(err => { });
        }

        // Handle opportunity change for invoices
        if (name === 'opportunity_id' && value && window.location.pathname.includes('/invoices')) {
            fetch(route('api.invoices.opportunities.details', value))
                .then(res => res.json())
                .then(data => {
                    // Auto-populate fields from opportunity
                    const updatedFormData = {
                        ...newFormData,
                        account_id: data.account_id || '',
                        contact_id: data.contact_id || '',
                        products: data.products || []
                    };
                    setFormData(updatedFormData);
                })
                .catch(err => { });
        }

        // Handle project change for tasks
        if (name === 'project_id' && value && window.location.pathname.includes('/project-tasks')) {
            // Clear parent_id when project changes
            newFormData.parent_id = '';

            // Load parent tasks for the selected project
            fetch(route('api.projects.details', value))
                .then(res => res.json())
                .then(data => {
                    setRelationOptions(prev => ({
                        ...prev,
                        parent_id: data.parent_tasks?.map((task: any) => ({ id: task.id, name: task.title })) || []
                    }));
                })
                .catch(err => { });
        }

        // Only set form data if not handling dependency changes (to avoid overriding)
        const isDependencyField = (name === 'opportunity_id' || name === 'quote_id' || name === 'sales_order_id' || name === 'purchase_order_id' || name === 'return_order_id' || name === 'project_id') && value;
        if (!isDependencyField) {
            setFormData(newFormData);
        } else if (name === 'project_id') {
            // For project_id, we still want to update the form data
            setFormData(newFormData);
        }

        // Clear error when field is changed
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }

        // Call field's onChange if it exists
        const field = formConfig.fields.find(f => f.name === name);
        if (field?.onChange) {
            field.onChange(value);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Process form data before validation
        const processedData = { ...formData };

        // Ensure multi-select fields are properly formatted
        formConfig.fields.forEach(field => {
            if (field.type === 'multi-select' && processedData[field.name]) {
                // Make sure it's an array of strings
                if (!Array.isArray(processedData[field.name])) {
                    processedData[field.name] = [processedData[field.name].toString()];
                }
            }
        });

        setFormData(processedData);

        // Basic validation
        const newErrors: Record<string, string> = {};
        formConfig.fields.forEach(field => {
            // For file fields in edit mode, they're never required
            if (field.type === 'file' && mode === 'edit') {
                return;
            }

            // Check if field is conditionally required based on other field values
            const isConditionallyRequired = field.conditional ? field.conditional(mode, formData) : true;

            if (field.required && isConditionallyRequired && !formData[field.name]) {
                newErrors[field.name] = `${field.label} is required`;
            }

            // File validation
            if (field.type === 'file' && formData[field.name] && field.fileValidation) {
                const file = formData[field.name];

                // Check file size
                if (field.fileValidation.maxSize && file.size > field.fileValidation.maxSize) {
                    const maxSizeMB = field.fileValidation.maxSize / (1024 * 1024);
                    newErrors[field.name] = `File size must be less than ${maxSizeMB}MB`;
                }

                // Check mime type
                if (field.fileValidation.mimeTypes && field.fileValidation.mimeTypes.length > 0) {
                    if (!field.fileValidation.mimeTypes.includes(file.type)) {
                        newErrors[field.name] = `File type must be one of: ${field.fileValidation.mimeTypes.join(', ')}`;
                    }
                }

                // Check extension
                if (field.fileValidation.extensions && field.fileValidation.extensions.length > 0) {
                    const fileName = file.name;
                    const fileExt = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();
                    if (!field.fileValidation.extensions.includes(fileExt)) {
                        newErrors[field.name] = `File extension must be one of: ${field.fileValidation.extensions.join(', ')}`;
                    }
                }
            }
        });

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        // Create a clean copy without any unexpected properties
        const cleanData = { ...formData };

        // Process multi-select fields before submission
        formConfig.fields.forEach(field => {
            if (field.type === 'multi-select' && cleanData[field.name]) {
                // Ensure it's an array of strings
                if (!Array.isArray(cleanData[field.name])) {
                    cleanData[field.name] = [cleanData[field.name].toString()];
                }
            }
        });

        onSubmit(cleanData);
    };

    const renderField = (field: FormField) => {
        // Check if field should be conditionally rendered
        if (field.conditional && !field.conditional(mode, formData)) {
            return null;
        }

        // If field has custom render function, use it
        if (field.render) {
            return field.render(field, formData, handleChange, errors, mode);
        }

        // Handle custom field type
        if (field.type === 'custom') {
            return field.render ? field.render(field, formData, handleChange, errors, mode) : null;
        }

        // If in view mode, render as read-only for non-custom fields
        if (mode === 'view') {
            // Special handling for multi-select fields
            if (field.type === 'multi-select') {
                const selectedValues = Array.isArray(formData[field.name]) ? formData[field.name] : [];
                const selectedLabels = selectedValues
                    .map((value: string) => {
                        const option = field.options?.find(opt => opt.value === value);
                        return option ? option.label : value;
                    })
                    .join(', ');

                return (
                    <div className="p-2 border rounded-md bg-gray-50">
                        {selectedLabels || '-'}
                    </div>
                );
            }

            // For array fields in view mode
            if (field.type === 'array') {
                const arrayValue = formData[field.name] || [];
                const calculateGrandTotal = () => {
                    return arrayValue.reduce((total: number, item: any) => {
                        const quantity = parseFloat(item.quantity) || 0;
                        const unitPrice = parseFloat(item.unit_price) || 0;
                        return total + (quantity * unitPrice);
                    }, 0);
                };

                return (
                    <div className="space-y-2">
                        {arrayValue.length > 0 ? (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full border-collapse border border-gray-200">
                                        <thead>
                                            <tr className="bg-gray-50">
                                                {field.fields?.map(subField => (
                                                    <th key={subField.name} className="border border-gray-200 px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        {subField.label}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {arrayValue.map((item: any, index: number) => {
                                                const itemTotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                                                return (
                                                    <tr key={index}>
                                                        {field.fields?.map(subField => (
                                                            <td key={subField.name} className="border border-gray-200 px-3 py-2 text-sm">
                                                                {subField.type === 'calculated' && subField.calculate ?
                                                                    subField.calculate(item) :
                                                                    subField.type === 'select' && subField.options ?
                                                                        subField.options.find(opt => opt.value == item[subField.name])?.label || item[subField.name] :
                                                                        subField.type === 'number' && subField.name === 'unit_price' ?
                                                                            `$${parseFloat(item[subField.name] || 0).toFixed(2)}` :
                                                                            item[subField.name] || '-'
                                                                }
                                                            </td>
                                                        ))}
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                        {field.renderFooter && (
                                            <tfoot>
                                                {field.renderFooter(arrayValue, field)}
                                            </tfoot>
                                        )}
                                    </table>
                                </div>
                            </>
                        ) : (
                            <div className="p-4 text-center text-gray-500 border border-gray-200 rounded-lg">
                                No {field.label.toLowerCase()} added
                            </div>
                        )}
                    </div>
                );
            }

            if (field.type === 'rich-textbox') {
                return (<div dangerouslySetInnerHTML={{ __html: formData.content || t('No content') }} />)
            }

            // For other field types
            return (
                <div className="p-2 border rounded-md bg-gray-50">
                    {field.type === 'select' && field.options ?
                        field.options.find(opt => opt.value === String(formData[field.name]))?.label || formData[field.name] || '-' :
                        formData[field.name] || '-'}
                </div>
            );
        }

        switch (field.type) {
            case 'text':
            case 'email':
            case 'password':
                return (
                    <Input
                        id={field.name}
                        name={field.name}
                        type={field.type}
                        placeholder={field.placeholder}
                        value={formData[field.name] || ''}
                        onChange={(e) => handleChange(field.name, e.target.value)}
                        required={field.required}
                        className={errors[field.name] ? 'border-red-500' : ''}
                        disabled={mode == 'view' || field.disabled}
                    />
                );
            case 'time':
                return (
                    <TimeInputField
                        field={field}
                        timeValue={formData[field.name] ?? ''}
                        handleChange={handleChange}
                        errors={errors}
                        mode={mode}
                    />
                );
            case 'date':
                // Format date value for input (YYYY-MM-DD format)
                const dateValue = formData[field.name] ?
                    (formData[field.name] instanceof Date ?
                        formData[field.name].toISOString().split('T')[0] :
                        (typeof formData[field.name] === 'string' && formData[field.name].includes('T') ?
                            formData[field.name].split('T')[0] :
                            formData[field.name])) : '';

                return (
                    <DateInputField
                        field={field}
                        dateValue={dateValue}
                        handleChange={handleChange}
                        errors={errors}
                        mode={mode}
                    />
                );

            case 'number':
                return (
                    <Input
                        id={field.name}
                        name={field.name}
                        type="number"
                        placeholder={field.placeholder}
                        value={formData[field.name] ?? field.defaultValue ?? ''}
                        onChange={(e) => handleChange(field.name, e.target.value ? parseFloat(e.target.value) : '')}
                        required={field.required}
                        className={errors[field.name] ? 'border-red-500' : ''}
                        disabled={mode == 'view' || field.disabled}
                    />
                );

            case 'color':
                return (
                    <div className="flex items-center gap-2">
                        <Input
                            id={field.name}
                            name={field.name}
                            type="color"
                            value={formData[field.name] ?? field.defaultValue ?? '#3B82F6'}
                            onChange={(e) => handleChange(field.name, e.target.value)}
                            required={field.required}
                            className={`w-16 h-10 p-1 cursor-pointer ${errors[field.name] ? 'border-red-500' : ''}`}
                            disabled={mode == 'view' || field.disabled}
                        />
                        <Input
                            type="text"
                            value={formData[field.name] ?? field.defaultValue ?? '#3B82F6'}
                            onChange={(e) => handleChange(field.name, e.target.value)}
                            placeholder="#3B82F6"
                            className={`flex-1 ${errors[field.name] ? 'border-red-500' : ''}`}
                            disabled={mode == 'view' || field.disabled}
                        />
                    </div>
                );

            case 'textarea':
                return (
                    <Textarea
                        id={field.name}
                        name={field.name}
                        placeholder={field.placeholder}
                        value={formData[field.name] || ''}
                        onChange={(e) => handleChange(field.name, e.target.value)}
                        required={field.required}
                        className={errors[field.name] ? 'border-red-500' : ''}
                        disabled={mode == 'view' || field.disabled}
                    />
                );

            case 'select':
                const options = field.relation
                    ? relationOptions[field.name] || []
                    : field.name === 'parent_id'
                        ? relationOptions[field.name]?.map((record: any) => ({ value: record.id.toString(), label: record.name })) || []
                        : field.options || [];

                const currentValue = String(formData[field.name] || '');
                const selectedOption = field.relation
                    ? options.find((opt: any) => String(opt[field.relation!.valueField]) === currentValue)
                    : field.name === 'parent_id' && relationOptions[field.name]
                        ? relationOptions[field.name].find((opt: any) => String(opt.id) === currentValue)
                        : options.find((opt) => String(opt.value) === currentValue);

                const displayText = selectedOption
                    ? (field.relation ? selectedOption[field.relation!.labelField] :
                        field.name === 'parent_id' ? selectedOption.name : selectedOption.label)
                    : '';

                return (
                    <>
                        <Select
                            key={`${field.name}-${currentValue}`}
                            value={currentValue}
                            onValueChange={(value) => handleChange(field.name, value)}
                            disabled={mode == 'view' || field.disabled}
                        >
                            <SelectTrigger className={errors[field.name] ? 'border-red-500' : ''}>
                                <SelectValue placeholder={field.placeholder || `Select ${field.label}`}>
                                    {displayText || field.placeholder || `Select ${field.label}`}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent className="z-[60000]">
                                {field.relation ? (
                                    options.map((option: any) => (
                                        <SelectItem
                                            key={option[field.relation!.valueField]}
                                            value={String(option[field.relation!.valueField])}
                                        >
                                            {option[field.relation!.labelField]}
                                        </SelectItem>
                                    ))
                                ) : (
                                    options.map((option) => (
                                        <SelectItem key={option.value} value={String(option.value)}>
                                            {option.label}
                                        </SelectItem>
                                    ))
                                )}
                            </SelectContent>
                        </Select>
                        {options.length === 0 && mode !== 'view' && field.emptyNote && (
                            <p className="text-xs mt-1">
                                {typeof field.emptyNote === 'function' ? (() => {
                                    const note = field.emptyNote(formData);
                                    return note ? (
                                        <>{note.text || "Click here to add"} <Link href={note.link} className="underline font-medium">{note.linkText}</Link></>
                                    ) : null;
                                })() : (
                                    <>{field.emptyNote.text || "Click here to add"} <Link href={field.emptyNote.link} className="underline font-medium">{field.emptyNote.linkText}</Link></>
                                )}
                            </p>
                        )}
                    </>
                );

            case 'radio':
                return (
                    <RadioGroup
                        value={formData[field.name] || ''}
                        onValueChange={(value) => handleChange(field.name, value)}
                        disabled={mode == 'view' || field.disabled}
                        className="flex gap-4"
                    >
                        {field.options?.map((option) => (
                            <div key={option.value} className="flex items-center space-x-2">
                                <RadioGroupItem value={option.value} id={`${field.name}-${option.value}`} />
                                <Label htmlFor={`${field.name}-${option.value}`}>{option.label}</Label>
                            </div>
                        ))}
                    </RadioGroup>
                );

            case 'checkbox':
                return (
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id={field.name}
                            checked={!!formData[field.name]}
                            onCheckedChange={(checked) => {
                                handleChange(field.name, checked);
                                if (field.onChange) {
                                    field.onChange(checked);
                                }
                            }}
                            disabled={mode == 'view' || field.disabled}
                        />
                        <Label htmlFor={field.name}>{field.placeholder || field.label}</Label>
                    </div>
                );

            case 'switch':
                // Don't render any label here, it will be handled by the parent component
                return (
                    <div className="flex items-center space-x-2">
                        <Label htmlFor={field.name}>{field.placeholder || field.label}</Label>
                        <Switch
                            id={field.name}
                            checked={!!formData[field.name]}
                            onCheckedChange={(checked) => handleChange(field.name, checked)}
                            disabled={mode == 'view' || field.disabled}
                        />
                    </div>
                );

            case 'multi-select':
                return (
                    <MultiSelectField
                        field={field}
                        formData={formData}
                        handleChange={handleChange}
                    />
                );

            case 'media-picker':
                const currentImageUrl = formData[field.name] ||
                    (mode === 'edit' && initialData[field.name] ?
                        (initialData[field.name].startsWith('http') ? initialData[field.name] : `/storage/${initialData[field.name]}`) :
                        '');

                return (
                    <MediaPicker
                        value={currentImageUrl}
                        onChange={(value) => handleChange(field.name, value)}
                        placeholder={field.placeholder || `Select ${field.label}`}
                        showPreview={true}
                    />
                );

            case 'array':
                const arrayValue = formData[field.name] || [];

                const addArrayItem = () => {
                    const newItem: any = {};
                    field.fields?.forEach(subField => {
                        newItem[subField.name] = subField.defaultValue || (subField.type === 'number' ? 0 : '');
                    });
                    handleChange(field.name, [...arrayValue, newItem]);
                };

                const removeArrayItem = (index: number) => {
                    const newArray = arrayValue.filter((_: any, i: number) => i !== index);
                    handleChange(field.name, newArray);
                };

                const updateArrayItem = (index: number, subFieldName: string, value: any) => {
                    const newArray = [...arrayValue];
                    newArray[index] = { ...newArray[index], [subFieldName]: value };

                    // Auto-populate unit price when product is selected
                    if (subFieldName === 'product_id' && (field.productOptions || formConfig.productOptions)) {
                        const productOptions = field.productOptions || formConfig.productOptions;
                        const selectedProduct = productOptions?.find((p: any) => p.id == value);
                        if (selectedProduct) {
                            newArray[index].unit_price = parseFloat(selectedProduct.price);
                        }
                    }

                    // Handle attendee type change for meetings
                    if (field.name === 'attendees' && subFieldName === 'type') {
                        // Clear the id when type changes
                        newArray[index].id = '';

                        // Load attendee records for the selected type
                        if (value) {
                            fetch(route('api.attendee-types.records', value))
                                .then(res => res.json())
                                .then(data => {
                                    // Force re-render by updating relation options
                                    setRelationOptions(prev => ({
                                        ...prev,
                                        [`attendees_${index}_id`]: data
                                    }));
                                })
                                .catch(err => { });
                        } else {
                            // Clear options when no type selected
                            setRelationOptions(prev => ({
                                ...prev,
                                [`attendees_${index}_id`]: []
                            }));
                        }
                    }

                    handleChange(field.name, newArray);
                };

                // Calculate total for all products
                const calculateGrandTotal = () => {
                    return arrayValue.reduce((total: number, item: any) => {
                        const quantity = parseFloat(item.quantity) || 0;
                        const unitPrice = parseFloat(item.unit_price) || 0;
                        return total + (quantity * unitPrice);
                    }, 0);
                };

                return (
                    <div className="space-y-2">
                        {arrayValue.map((item: any, index: number) => {
                            const itemTotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);

                            return (
                                <div key={index} className="border rounded-lg p-4 space-y-3">
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm font-medium">{field.label} #{index + 1}</span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => removeArrayItem(index)}
                                            className="text-red-600 hover:text-red-700"
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                    <div className={`gap-3 ${field.name === 'attendees'
                                        ? 'flex'
                                        : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2'
                                        }`}>
                                        {field.fields?.map(subField => (
                                            <div
                                                key={subField.name}
                                                className="space-y-1"
                                                style={field.name === 'attendees' ? {
                                                    width: subField.name === 'type' ? '30%' : '70%'
                                                } : {}}
                                            >
                                                <Label className="text-xs" required={subField.required && mode !== 'view'}>
                                                    {subField.label}
                                                </Label>
                                                {subField.type === 'calculated' ? (
                                                    <div className="h-10 px-3 py-2 bg-gray-50 border rounded-md text-sm flex items-center">
                                                        {subField.calculate ? subField.calculate(item) : '-'}
                                                    </div>
                                                ) : subField.type === 'select' ? (
                                                    <>
                                                        <Select
                                                            value={String(item[subField.name] || '')}
                                                            disabled={mode === 'view' || subField.disabled}
                                                            onValueChange={(value) => updateArrayItem(index, subField.name, value)}
                                                        >
                                                            <SelectTrigger className="h-10">
                                                                <SelectValue placeholder={`Select ${subField.label}`} />
                                                            </SelectTrigger>
                                                            <SelectContent className="z-[70000]">
                                                                {/* For attendee id field, use dynamic options based on type */}
                                                                {field.name === 'attendees' && subField.name === 'id' ? (
                                                                    relationOptions[`attendees_${index}_id`]?.map((option: any) => (
                                                                        <SelectItem key={option.id} value={String(option.id)}>
                                                                            {option.name}
                                                                        </SelectItem>
                                                                    ))
                                                                ) : (
                                                                    subField.options?.map(option => (
                                                                        <SelectItem key={option.value} value={String(option.value)}>
                                                                            {option.label}
                                                                        </SelectItem>
                                                                    ))
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        {subField.emptyNote && (() => {
                                                            const note = typeof subField.emptyNote === 'function'
                                                                ? subField.emptyNote(formData, index)
                                                                : subField.emptyNote;

                                                            const hasOptions = (field.name === 'attendees' && subField.name === 'id')
                                                                ? (relationOptions[`attendees_${index}_id`]?.length || 0) > 0
                                                                : (subField?.options?.length || 0) > 0;

                                                            return note && !hasOptions ? (
                                                                <p className="text-xs mt-1">
                                                                    {note.text || "Click here to add"}{" "}
                                                                    <Link href={note.link} className="underline font-medium">
                                                                        {note.linkText}
                                                                    </Link>
                                                                </p>
                                                            ) : null;
                                                        })()}
                                                    </>
                                                ) : (
                                                    <Input
                                                        type={subField.type || 'text'}
                                                        value={item[subField.name] ?? ''}
                                                        onChange={(e) => updateArrayItem(index, subField.name,
                                                            subField.type === 'number' ? parseFloat(e.target.value) || 0 : e.target.value
                                                        )}
                                                        placeholder={subField.placeholder}
                                                        className="h-10"
                                                        step={subField.step}
                                                        min={subField.min}
                                                        disabled={mode === 'view' || subField.disabled}
                                                    />
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}

                        {arrayValue.length > 0 && field.renderSummary && (
                            <div className="bg-gray-50 p-3 rounded-lg">
                                {field.renderSummary(arrayValue, field)}
                            </div>
                        )}

                        {!field.disabled && <Button
                            type="button"
                            variant="outline"
                            onClick={addArrayItem}
                            className="w-full"
                        >
                            Add {field.label}
                        </Button>}
                    </div>
                );

            case 'file':
                const acceptAttr = field.fileValidation?.accept || '';
                const isImageFile = acceptAttr.includes('image') ||
                    (field.fileValidation?.mimeTypes?.some(type => type.startsWith('image/')) ?? false);

                return (
                    <>
                        <Input
                            id={field.name}
                            name={field.name}
                            type="file"
                            accept={acceptAttr}
                            onChange={(e) => {
                                if (e.target.files && e.target.files[0]) {
                                    handleChange(field.name, e.target.files[0]);
                                }
                            }}
                            className={errors[field.name] ? 'border-red-500' : ''}
                            disabled={mode == 'view' || field.disabled}
                        />
                        {mode === 'edit' && initialData[field.name] && (
                            <div className="text-xs text-gray-500 mt-1">
                                Current file: {initialData.featured_image_original_name || initialData[field.name]}
                            </div>
                        )}
                        {field.fileValidation && (
                            <div className="text-xs text-gray-500 mt-1">
                                {field.fileValidation.extensions && (
                                    <span>{t("Allowed extensions")}: {field.fileValidation.extensions.join(', ')} </span>
                                )}
                                {field.fileValidation.maxSize && (
                                    <span>{t("Max size")}: {(field.fileValidation.maxSize / (1024 * 1024)).toFixed(1)}MB</span>
                                )}
                            </div>
                        )}

                        {/* Image preview for image files */}
                        {isImageFile && (
                            <div className="mt-2">
                                {formData[field.name] && formData[field.name] instanceof File ? (
                                    // Preview for newly selected file
                                    <div className="mt-2">
                                        <p className="text-xs text-gray-500 mb-1">{t("Preview")}:</p>
                                        <img
                                            src={URL.createObjectURL(formData[field.name])}
                                            alt="Preview"
                                            className="h-24 w-auto rounded-md object-cover shadow-sm"
                                        />
                                    </div>
                                ) : mode === 'edit' && initialData[field.name] && (
                                    // Show existing image in edit mode
                                    <div className="mt-2">
                                        <p className="text-xs text-gray-500 mb-1">{t("Current image")}:</p>
                                        <img
                                            src={typeof initialData[field.name] === 'string' && initialData[field.name].startsWith && initialData[field.name].startsWith('http')
                                                ? initialData[field.name]
                                                : `/storage/${initialData[field.name]}`}
                                            alt="Current"
                                            className="h-24 w-auto rounded-md object-cover shadow-sm"
                                            onError={(e) => {
                                                e.currentTarget.src = 'https://placehold.co/200x150?text=Image+Not+Found';
                                            }}
                                        />
                                    </div>
                                )}
                            </div>
                        )}
                    </>
                );

            case 'rich-textbox':
                return (
                    <RichTextField
                        key={formData.id || 'create'}
                        value={formData[field.name] || ''}
                        onChange={(content) => handleChange(field.name, content)}
                        placeholder={field.placeholder || t('Enter content...')}
                        className="min-h-[200px]"
                        disabled={mode == 'view' || field.disabled}
                    />
                );

            default:
                return null;
        }
    };

    // Map modal size to appropriate width class
    const getModalSizeClass = () => {
        const sizeMap: Record<string, string> = {
            'sm': 'sm:max-w-sm',
            'md': 'sm:max-w-md',
            'lg': 'sm:max-w-lg',
            'xl': 'sm:max-w-xl',
            '2xl': 'sm:max-w-2xl',
            '3xl': 'sm:max-w-3xl',
            '4xl': 'sm:max-w-4xl',
            '5xl': 'sm:max-w-5xl',
            'full': 'sm:max-w-full'
        };
        return formConfig.modalSize ? sizeMap[formConfig.modalSize] : 'sm:max-w-md';
    };

    // Group fields by row if specified
    const groupFieldsByRow = () => {
        const rows: Record<number, FormField[]> = {};

        formConfig.fields.forEach(field => {
            const rowNumber = field.row || 0;
            if (!rows[rowNumber]) {
                rows[rowNumber] = [];
            }
            rows[rowNumber].push(field);
        });

        return Object.entries(rows).sort(([a], [b]) => parseInt(a) - parseInt(b));
    };

    // Determine the layout type
    const layout = formConfig.layout || 'default';
    const columns = formConfig.columns || 1;

    const modalId = `crud-modal-${mode}-${title.replace(/\s+/g, '-').toLowerCase()}-${Date.now()}`;

    // Expose handleExportAction for external use
    if (handleExportAction) {
        (CrudFormModal as any).handleExport = handleExportAction;
    }

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className={`${getModalSizeClass()} max-h-[90vh]`} modalId={modalId}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description || " "}</DialogDescription>
                </DialogHeader>
                <ScrollArea className="max-h-[70vh] pr-4">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Price Summary Section */}
                        {formConfig.priceSummary && (
                            <div className="bg-gray-50 p-4 rounded-lg mb-4">
                                <div className="flex justify-between items-center mb-2">
                                    <span className="text-sm text-gray-600">{t("Unit Price")}:</span>
                                    <span className="font-medium">${formConfig.priceSummary.unitPrice.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between items-center mb-2">
                                    <span className="text-sm text-gray-600">{t("Quantity")}:</span>
                                    <span className="font-medium">{formData[formConfig.priceSummary.quantityFieldName || 'quantity'] || formConfig.priceSummary.quantity || 1}</span>
                                </div>
                                <div className="border-t pt-2">
                                    <div className="flex justify-between items-center">
                                        <span className="font-semibold">{t("Total Price")}:</span>
                                        <span className="font-bold text-lg text-primary">
                                            ${calculateTotal().toFixed(2)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}
                        {layout === 'grid' ? (
                            <div style={{ display: 'grid', gridTemplateColumns: `repeat(${columns}, 1fr)`, gap: '1rem' }}>
                                {formConfig.fields.map((field) => {
                                    if (field.conditional && !field.conditional(mode, formData)) return null;
                                    const isInline = (field.type === 'checkbox' || field.type === 'switch');
                                    return (
                                        <div
                                            key={field.name}
                                            className={isInline ? 'flex items-center gap-3' : 'space-y-2'}
                                            style={{ gridColumn: field.colSpan ? `span ${field.colSpan}` : 'span 1', width: '100%' }}
                                        >
                                            {isInline ? renderField(field) : (
                                                <>
                                                    <Label htmlFor={field.name} className="text-sm font-medium" required={field.required && !(field.type === 'file' && mode === 'edit') && mode !== 'view'}>
                                                        {field.label}
                                                    </Label>
                                                    {renderField(field)}
                                                    {errors[field.name] && <p className="text-xs text-red-500">{errors[field.name]}</p>}
                                                </>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        ) : layout === 'flex' ? (
                            <div className="flex flex-wrap gap-4">
                                {formConfig.fields.map((field) => {
                                    if (field.conditional && !field.conditional(mode, formData)) return null;
                                    const isInline = (field.type === 'checkbox' || field.type === 'switch');
                                    return (
                                        <div
                                            key={field.name}
                                            className={isInline ? 'flex items-center gap-3' : 'space-y-2'}
                                            style={{ width: field.width || '100%', flexGrow: field.width ? 0 : 1 }}
                                        >
                                            {isInline ? renderField(field) : (
                                                <>
                                                    <Label htmlFor={field.name} className="text-sm font-medium" required={field.required && !(field.type === 'file' && mode === 'edit') && mode !== 'view'}>
                                                        {field.label}
                                                    </Label>
                                                    {renderField(field)}
                                                    {errors[field.name] && <p className="text-xs text-red-500">{errors[field.name]}</p>}
                                                </>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            // Default layout with row grouping
                            groupFieldsByRow().map(([rowNumber, fields]) => (
                                <div key={rowNumber} className="flex flex-wrap gap-4 mb-4">
                                    {fields.map((field) => {
                                        if (field.conditional && !field.conditional(mode, formData)) return null;
                                        const isInline = (field.type === 'checkbox' || field.type === 'switch');
                                        return (
                                            <div
                                                key={field.name}
                                                className={isInline ? 'flex items-center gap-3' : 'space-y-2'}
                                                style={{ width: field.width || '100%' }}
                                            >
                                                {isInline ? renderField(field) : (
                                                    <>
                                                        <Label htmlFor={field.name} className="text-sm font-medium" required={field.required && !(field.type === 'file' && mode === 'edit') && mode !== 'view'}>
                                                            {field.label}
                                                        </Label>
                                                        {renderField(field)}
                                                        {errors[field.name] && <p className="text-xs text-red-500">{errors[field.name]}</p>}
                                                    </>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            ))
                        )}
                    </form>
                </ScrollArea>
                <DialogFooter className="sm:justify-end">
                    <Button type="button" variant="outline" onClick={onClose}>
                        {t("Cancel")}
                    </Button>
                    {mode !== 'view' && (
                        <Button type="button" onClick={handleSubmit} disabled={isSubmitting}>
                            {t("Save")}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
