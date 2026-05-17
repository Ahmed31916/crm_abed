import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { FileSpreadsheet, Upload, X, Info, CheckCircle, AlertTriangle, Download } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';

interface ImportExcelModalProps {
    open: boolean;
    onClose: () => void;
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

export default function ImportExcelModal({ open, onClose }: ImportExcelModalProps) {
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

            if (data.flag === 'success') {
                // Reload page after 3 seconds to show new products
                setTimeout(() => {
                    router.reload();
                    handleClose();
                }, 3000);
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
    ];

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && handleClose()}>
            <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5 text-blue-600" />
                        {t('Import Products from Excel')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('Upload an Excel file to import or update products. Products with existing SKUs will be updated.')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Info Alert */}
                    <Alert className="border-blue-200 bg-blue-50 dark:bg-blue-950/30">
                        <Info className="h-4 w-4 text-blue-600" />
                        <AlertDescription className="text-blue-700 dark:text-blue-400 text-sm">
                            {t('Products that already exist (matched by SKU) will be updated. New SKUs will create new products.')}
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

                    {/* Result */}
                    {result && (
                        <Alert
                            className={
                                result.flag === 'success'
                                    ? 'border-green-200 bg-green-50 dark:bg-green-950/30'
                                    : result.flag === 'warning'
                                    ? 'border-yellow-200 bg-yellow-50 dark:bg-yellow-950/30'
                                    : 'border-red-200 bg-red-50 dark:bg-red-950/30'
                            }
                        >
                            {result.flag === 'success' ? (
                                <CheckCircle className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-red-600" />
                            )}
                            <AlertDescription className="space-y-2">
                                <p className={
                                    result.flag === 'success'
                                        ? 'text-green-700 dark:text-green-400 font-medium'
                                        : 'text-red-700 dark:text-red-400 font-medium'
                                }>
                                    {result.msg}
                                </p>
                                {(result.imported !== undefined || result.updated !== undefined) && (
                                    <div className="text-sm space-y-1">
                                        {result.imported !== undefined && result.imported > 0 && (
                                            <p className="text-green-600">{t('Imported')}: {result.imported} {t('products')}</p>
                                        )}
                                        {result.updated !== undefined && result.updated > 0 && (
                                            <p className="text-blue-600">{t('Updated')}: {result.updated} {t('products')}</p>
                                        )}
                                        {result.skipped !== undefined && result.skipped > 0 && (
                                            <p className="text-yellow-600">{t('Skipped')}: {result.skipped} {t('products')}</p>
                                        )}
                                        {result.errors !== undefined && result.errors > 0 && (
                                            <p className="text-red-600">{t('Errors')}: {result.errors}</p>
                                        )}
                                    </div>
                                )}
                                {result.error_details && result.error_details.length > 0 && (
                                    <div className="mt-2 max-h-40 overflow-y-auto text-xs space-y-1">
                                        {result.error_details.map((err, idx) => (
                                            <p key={idx} className="text-red-600">{err}</p>
                                        ))}
                                    </div>
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
