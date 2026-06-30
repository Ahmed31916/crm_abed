/**
 * MultiSelect — self-contained multi-select dropdown for shadcn/ui apps.
 *
 * Used for: Tags, Primary Indications, Pairs Well With, and any other
 * multi-value fields that previously used a list of checkboxes.
 *
 * Dependencies (already part of shadcn/ui):
 *   - @/components/ui/checkbox
 *   - @/components/ui/badge
 *   - @/components/ui/button
 *   - @/components/ui/input
 *   - lucide-react (ChevronDown, X, Search)
 *   - react-i18next (useTranslation)
 *
 * No external npm package needed.
 */
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ChevronDown, X, Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useState, useEffect, useRef, useMemo } from 'react';

export type MultiSelectOption = {
    /** unique value (id or name — must be a non-empty string) */
    value: string;
    /** display label */
    label: string;
    /** optional hex color (used for tags) */
    color?: string;
};

export interface MultiSelectProps {
    options: MultiSelectOption[];
    value: string[];
    onChange: (value: string[]) => void;
    placeholder?: string;
    emptyMessage?: string;
    searchPlaceholder?: string;
    searchable?: boolean;
    /** optional cap on selected items */
    maxItems?: number;
    /** show error styling + message */
    error?: string;
    /** mark as required (shows * in placeholder) */
    required?: boolean;
    /** fully disable interaction (read-only mode) */
    disabled?: boolean;
    /** dropdown list max height in px */
    maxHeight?: number;
    /** optional id for label association */
    id?: string;
}

export function MultiSelect({
    options,
    value,
    onChange,
    placeholder = 'Select...',
    emptyMessage = 'No options available',
    searchPlaceholder = 'Search...',
    searchable = true,
    maxItems,
    error,
    required = false,
    disabled = false,
    maxHeight = 240,
    id,
}: MultiSelectProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);

    // Close on outside click + Escape
    useEffect(() => {
        if (!open) return;
        const handleClick = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
                setSearch('');
            }
        };
        const handleKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setOpen(false);
                setSearch('');
            }
        };
        document.addEventListener('mousedown', handleClick);
        document.addEventListener('keydown', handleKey);
        // Autofocus search when opening
        setTimeout(() => searchInputRef.current?.focus(), 0);
        return () => {
            document.removeEventListener('mousedown', handleClick);
            document.removeEventListener('keydown', handleKey);
        };
    }, [open]);

    // Memoized filtered list (case-insensitive)
    const filtered = useMemo(() => {
        if (!search.trim()) return options;
        const q = search.toLowerCase().trim();
        return options.filter(opt => opt.label.toLowerCase().includes(q));
    }, [options, search]);

    // Build a quick lookup map for selected labels
    const optionMap = useMemo(() => {
        const m = new Map<string, MultiSelectOption>();
        options.forEach(o => m.set(o.value, o));
        return m;
    }, [options]);

    const toggle = (val: string) => {
        if (disabled) return;
        if (value.includes(val)) {
            onChange(value.filter(v => v !== val));
        } else {
            if (maxItems && value.length >= maxItems) return;
            onChange([...value, val]);
        }
    };

    const remove = (val: string) => {
        if (disabled) return;
        onChange(value.filter(v => v !== val));
    };

    const selectedItems = value
        .map(v => optionMap.get(v))
        .filter((o): o is MultiSelectOption => !!o);

    return (
        <div ref={containerRef} className="relative" id={id}>
            {/* Trigger */}
            <div
                role="combobox"
                aria-expanded={open}
                aria-haspopup="listbox"
                aria-disabled={disabled}
                tabIndex={disabled ? -1 : 0}
                onClick={() => !disabled && setOpen(o => !o)}
                onKeyDown={(e) => {
                    if (disabled) return;
                    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        setOpen(true);
                    }
                }}
                className={`flex w-full items-center justify-between rounded-md border bg-transparent px-3 py-2 text-sm text-left min-h-[40px] transition-colors outline-none ${disabled ? 'opacity-50 cursor-not-allowed bg-muted' : 'hover:bg-accent/50 cursor-pointer focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1'} ${error ? 'border-red-500' : 'border-input'} ${open && !disabled ? 'ring-2 ring-ring ring-offset-1' : ''}`}
            >
                <div className="flex flex-wrap items-center gap-1 flex-1 min-w-0">
                    {selectedItems.length === 0 ? (
                        <span className="text-muted-foreground truncate">
                            {placeholder}
                            {required && <span className="text-red-500 ml-0.5">*</span>}
                        </span>
                    ) : (
                        selectedItems.map(item => (
                            <Badge
                                key={item.value}
                                variant="secondary"
                                style={item.color ? {
                                    backgroundColor: item.color + '20',
                                    color: item.color,
                                    borderColor: item.color + '40',
                                } : undefined}
                                className="text-xs gap-1 pr-1"
                            >
                                <span className="truncate max-w-[160px]">{item.label}</span>
                                {!disabled && (
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            remove(item.value);
                                        }}
                                        className="ml-0.5 rounded-full hover:bg-black/10 dark:hover:bg-white/20 p-0.5 cursor-pointer inline-flex items-center justify-center bg-transparent border-0"
                                        aria-label={`Remove ${item.label}`}
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                )}
                            </Badge>
                        ))
                    )}
                </div>
                <ChevronDown
                    className={`h-4 w-4 opacity-50 shrink-0 ml-2 transition-transform ${open ? 'rotate-180' : ''}`}
                />
            </div>

            {/* Dropdown panel */}
            {open && !disabled && (
                <div
                    className="absolute z-50 mt-1 w-full bg-popover border rounded-md shadow-md flex flex-col"
                    role="listbox"
                    style={{ maxHeight: maxHeight + (searchable ? 50 : 0) + 8 }}
                >
                    {searchable && (
                        <div className="p-2 border-b sticky top-0 bg-popover z-10">
                            <div className="relative">
                                <Search className="absolute left-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                <Input
                                    ref={searchInputRef}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={searchPlaceholder}
                                    className="h-8 pl-7 text-sm"
                                />
                            </div>
                        </div>
                    )}
                    <div className="overflow-y-auto flex-1 p-1" style={{ maxHeight }}>
                        {filtered.length === 0 ? (
                            <p className="text-sm text-muted-foreground text-center py-4 px-2">
                                {search.trim() ? t('No matching options') : emptyMessage}
                            </p>
                        ) : (
                            filtered.map(opt => {
                                const checked = value.includes(opt.value);
                                const isMax = !checked && !!maxItems && value.length >= maxItems;
                                return (
                                    <label
                                        key={opt.value}
                                        className={`flex items-center gap-2 px-2 py-1.5 rounded cursor-pointer text-sm transition-colors ${checked ? 'bg-primary/10' : 'hover:bg-accent'} ${isMax ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    >
                                        <Checkbox
                                            checked={checked}
                                            disabled={isMax}
                                            onCheckedChange={() => !isMax && toggle(opt.value)}
                                        />
                                        {opt.color && (
                                            <span
                                                className="h-3 w-3 rounded-full inline-block shrink-0 border"
                                                style={{ backgroundColor: opt.color }}
                                                aria-hidden
                                            />
                                        )}
                                        <span className="truncate flex-1">{opt.label}</span>
                                        {checked && (
                                            <span className="text-xs text-muted-foreground">
                                                {t('Selected')}
                                            </span>
                                        )}
                                    </label>
                                );
                            })
                        )}
                    </div>
                    {value.length > 0 && (
                        <div className="border-t p-1.5 flex items-center justify-between sticky bottom-0 bg-popover">
                            <span className="text-xs text-muted-foreground px-1.5">
                                {value.length} {value.length === 1 ? t('item selected') : t('items selected')}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-7 text-xs"
                                onClick={() => onChange([])}
                            >
                                {t('Clear all')}
                            </Button>
                        </div>
                    )}
                </div>
            )}
            {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
        </div>
    );
}

export default MultiSelect;
