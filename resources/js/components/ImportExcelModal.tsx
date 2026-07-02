import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    FileSpreadsheet,
    Upload,
    X,
    Info,
    CheckCircle,
    AlertTriangle,
    Download,
    PlusCircle,
    RefreshCw,
    MinusCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';

interface ImportExcelModalProps {
    open: boolean;
    onClose: () => void;
    children?: React.ReactNode;
}

interface ImportResult {
    flag: 'success' | 'error' | 'warning';
    msg: string;
    imported?: number;
    updated?: number;
    skipped?: number;
    errors?: number;
    error_details?: string[];
}

/**
 * ════════════════════════════════════════════════════════════════════
 * ImportExcelModal
 * ════════════════════════════════════════════════════════════════════
 *
 * Modal لاستيراد المنتجات من ملف Excel.
 *
 * الميزات:
 *   - رفع ملف Excel/CSV
 *   - إظهار النتيجة (Added / Updated / Skipped) كبطاقات مرئية واضحة
 *   - شاشة النتيجة لا تختفي إلا عند الضغط على زر Cancel
 *   - يدعم نتائج مختلطة (إضافة + تعديل مع بعض)
 *   - إظهار تفاصيل الأخطاء (إن وجدت) في قسم قابل للطي
 *   - تحديث قائمة المنتجات تلقائياً بعد نجاح الاستيراد (دون إغلاق المودال)
 */
export default function ImportExcelModal({ open, onClose, children }: ImportExcelModalProps) {
    const { t } = useTranslation();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importing, setImporting] = useState(false);
    const [result, setResult] = useState<ImportResult | null>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const validExtensions = ['.xlsx', '.xls', '.csv'];
            const fileName = file.name.toLowerCase();
            const isValid = validExtensions.some(ext => fileName.endsWith(ext));
            if (!isValid) {
                setResult({
                    flag: 'error',
                    msg: t('Please upload a valid Excel file (.xlsx, .xls, .csv)'),
                });
                return;
            }
            setSelectedFile(file);
            // مسح النتيجة السابقة عند اختيار ملف جديد
            setResult(null);
        }
    };

    const handleImport = async () => {
        if (!selectedFile) {
            setResult({
                flag: 'error',
                msg: t('Please select an Excel file to upload.'),
            });
            return;
        }

        if (!confirm(t('Are you sure you want to import products from this Excel file?'))) {
            return;
        }

        setImporting(true);
        setResult(null);

        const formData = new FormData();
        formData.append('import_file', selectedFile);

        try {
            const response = await fetch(route('products.import-from-excel'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                    'Accept': 'application/json',
                },
            });

            const data: ImportResult = await response.json();
            setResult(data);

            // ═══════════════════════════════════════════════════════════
            // تحديث قائمة المنتجات عند نجاح الاستيراد (دون إغلاق المودال)
            // النتيجة تبقى ظاهرة حتى يضغط المستخدم Cancel
            // ═══════════════════════════════════════════════════════════
            if (data.flag === 'success' || (data.imported || 0) > 0 || (data.updated || 0) > 0) {
                router.reload({ preserveState: true, preserveScroll: true });
            }

            // تصفية حقل الملف (لكن النتيجة تبقى ظاهرة)
            setSelectedFile(null);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        } catch (error) {
            setResult({
                flag: 'error',
                msg: t('An error occurred during import.'),
            });
        } finally {
            setImporting(false);
        }
    };

    /**
     * زر الإلغاء: يخفي النتيجة + يقفل المودال
     * (النتيجة لا تختفي إلا بهذا الزر — لا auto-close)
     */
    const handleClose = () => {
        setSelectedFile(null);
        setResult(null);
        setImporting(false);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        onClose();
    };

    const expectedColumns = [
        { name: 'SKU', required: true },
        { name: 'Product Name' },
        { name: 'Full Name' },
        { name: 'Category' },
        { name: 'Supplier / Brand' },
        { name: 'Regular Price' },
        { name: 'Sale Price' },
        { name: 'Description' },
        { name: 'Is Active' },
        { name: 'Product Image URL' },
        { name: 'Bottle Size / Unit Count' },
        { name: 'Product Form' },
        { name: 'Ingredients' },
        { name: 'Contraindications' },
        { name: 'Research / Studies / Article Links' },
        { name: 'Supports' },
        { name: 'Useful For' },
        { name: 'Practitioner Notes' },
        { name: 'Upon Rising' },
        { name: 'Breakfast' },
        { name: 'Between Meals (AM)' },
        { name: 'Lunch' },
        { name: 'Between Meals (PM)' },
        { name: 'Dinner' },
        { name: 'Before Sleep' },
        { name: 'Tags' },
        { name: 'Primary Indications' },
        { name: 'Custom Primary Indications' },
        { name: 'Custom Dosing Notes' },
        // ⚡ NEW: frequency column
        { name: 'Frequency' },
    ];

    // ═══════════════════════════════════════════════════════════
    // إعدادات العرض حسب نوع النتيجة
    // ═══════════════════════════════════════════════════════════
    const resultConfig = {
        success: {
            border: 'border-green-200 dark:border-green-800',
            bg: 'bg-green-50 dark:bg-green-950/30',
            icon: <CheckCircle className="h-4 w-4 text-green-600" />,
            title: t('Import Completed Successfully'),
        },
        warning: {
            border: 'border-yellow-200 dark:border-yellow-800',
            bg: 'bg-yellow-50 dark:bg-yellow-950/30',
            icon: <AlertTriangle className="h-4 w-4 text-yellow-600" />,
            title: t('Import Completed with Warnings'),
        },
        error: {
            border: 'border-red-200 dark:border-red-800',
            bg: 'bg-red-50 dark:bg-red-950/30',
            icon: <AlertTriangle className="h-4 w-4 text-red-600" />,
            title: t('Import Failed'),
        },
    };

    const config = result ? resultConfig[result.flag] : null;
    const hasCounts = result && (result.imported !== undefined || result.updated !== undefined || result.skipped !== undefined);

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5 text-blue-600" />
                        {t('Import Products from Excel')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('Upload an Excel file to import or update products. Products with existing SKUs will be updated if there are changes.')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* children (download template link) */}
                    {children}

                    {/* Info Alert */}
                    <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                        <Info className="h-4 w-4 text-blue-600" />
                        <AlertDescription className="text-blue-700 dark:text-blue-400 text-sm">
                            {t('Products that already exist (matched by SKU) will be updated if any field differs. New SKUs will create new products. If no fields differ, the row will be skipped.')}
                        </AlertDescription>
                    </Alert>

                    {/* Expected Columns */}
                    <div className="border rounded-lg p-3">
                        <h4 className="font-semibold text-sm mb-2 flex items-center gap-1">
                            <Info className="h-3.5 w-3.5" />
                            {t('Expected Excel Columns')}:
                        </h4>
                        <div className="grid grid-cols-3 gap-1 text-xs text-muted-foreground">
                            {expectedColumns.map((col) => (
                                <div key={col.name} className="flex items-center gap-1">
                                    <span className="w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0" />
                                    <span>{col.name}</span>
                                    {col.required && <span className="text-red-500">*</span>}
                                </div>
                            ))}
                        </div>
                        <p className="text-xs text-muted-foreground mt-2">
                            {t('Use "None" or "N/A" for empty fields. First row must be headers.')}
                        </p>
                    </div>

                    {/* File Input */}
                    <div className="space-y-2">
                        <label className="text-sm font-medium">
                            {t('Select Excel File')} <span className="text-red-500">*</span>
                        </label>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={handleFileChange}
                            className="block w-full text-sm text-muted-foreground
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                                cursor-pointer border rounded-lg p-2"
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('Accepted formats: .xlsx, .xls, .csv (Max 20MB)')}
                        </p>
                    </div>

                    {/* Progress */}
                    {importing && (
                        <div className="space-y-2">
                            <Progress value={100} className="h-3 animate-pulse" />
                            <p className="text-sm text-muted-foreground text-center">{t('Importing...')}</p>
                        </div>
                    )}

                    {/* ═══════════════════════════════════════════════════════════
                        Result Panel — يبقى ظاهراً حتى يضغط المستخدم Cancel
                    ═══════════════════════════════════════════════════════════ */}
                    {result && config && (
                        <Alert className={`${config.border} ${config.bg}`}>
                            {config.icon}
                            <AlertDescription className="space-y-3">
                                {/* Result Title & Message */}
                                <div>
                                    <p className="font-semibold text-sm text-gray-900 dark:text-white">
                                        {config.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground mt-1 break-words">
                                        {result.msg}
                                    </p>
                                </div>

                                {/* ═══ Counts Cards ═══ */}
                                {hasCounts && (
                                    <div className="grid grid-cols-3 gap-3">
                                        {/* Added */}
                                        <div className="rounded-md bg-white dark:bg-gray-800 p-3 text-center border border-gray-200 dark:border-gray-700">
                                            <div className="flex items-center justify-center mb-1">
                                                <PlusCircle className="h-4 w-4 text-green-600 mr-1" />
                                                <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    {t('Added')}
                                                </span>
                                            </div>
                                            <p className="text-2xl font-bold text-green-600 dark:text-green-400">
                                                {result.imported ?? 0}
                                            </p>
                                        </div>

                                        {/* Updated */}
                                        <div className="rounded-md bg-white dark:bg-gray-800 p-3 text-center border border-gray-200 dark:border-gray-700">
                                            <div className="flex items-center justify-center mb-1">
                                                <RefreshCw className="h-4 w-4 text-blue-600 mr-1" />
                                                <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    {t('Updated')}
                                                </span>
                                            </div>
                                            <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                {result.updated ?? 0}
                                            </p>
                                        </div>

                                        {/* Skipped */}
                                        <div className="rounded-md bg-white dark:bg-gray-800 p-3 text-center border border-gray-200 dark:border-gray-700">
                                            <div className="flex items-center justify-center mb-1">
                                                <MinusCircle className="h-4 w-4 text-yellow-600 mr-1" />
                                                <span className="text-xs font-medium text-gray-600 dark:text-gray-400">
                                                    {t('Skipped')}
                                                </span>
                                            </div>
                                            <p className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                                                {result.skipped ?? 0}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Mixed Result Hints */}
                                {hasCounts && (result.imported ?? 0) > 0 && (result.updated ?? 0) > 0 && (
                                    <p className="text-xs text-muted-foreground text-center italic">
                                        {t('Some products were added and others were updated.')}
                                    </p>
                                )}
                                {hasCounts
                                    && (result.imported ?? 0) === 0
                                    && (result.updated ?? 0) === 0
                                    && (result.skipped ?? 0) > 0
                                    && (result.errors ?? 0) === 0 && (
                                    <p className="text-xs text-muted-foreground text-center italic">
                                        {t('No products were added or updated. All rows were skipped (no changes detected).')}
                                    </p>
                                )}
                                {hasCounts && (result.errors ?? 0) > 0 && (
                                    <p className="text-xs text-red-600 dark:text-red-400 text-center">
                                        {t('{{count}} row(s) failed to import. See details below.', { count: result.errors })}
                                    </p>
                                )}

                                {/* Error Details (collapsible) */}
                                {result.error_details && result.error_details.length > 0 && (
                                    <details className="mt-2">
                                        <summary className="text-xs font-medium text-red-700 dark:text-red-400 cursor-pointer hover:underline">
                                            {t('View error details')}
                                        </summary>
                                        <div className="mt-2 max-h-40 overflow-y-auto rounded bg-red-50 dark:bg-red-900/20 p-2 border border-red-200 dark:border-red-800">
                                            <ul className="text-xs text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                                                {result.error_details.map((err, idx) => (
                                                    <li key={idx} className="break-words">{err}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    </details>
                                )}
                            </AlertDescription>
                        </Alert>
                    )}
                </div>

                <DialogFooter className="gap-2">
                    <Button variant="outline" onClick={handleClose} disabled={importing}>
                        {t('Cancel')}
                    </Button>
                    <Button
                        onClick={handleImport}
                        disabled={!selectedFile || importing}
                        className="bg-blue-600 hover:bg-blue-700"
                    >
                        {importing ? (
                            <>
                                <span className="animate-spin mr-2 inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                                {t('Importing...')}
                            </>
                        ) : (
                            <>
                                <Upload className="h-4 w-4 mr-2" />
                                {t('Import')}
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
