import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';

export type VehicleOption = {
    id: number;
    reg_number: string;
    type_name: string | null;
    base_rate_daily: number | null;
};

export type LineItem = {
    id?: number | null;
    vehicle_id: string | number;
    description: string;
    unit: string;
    quantity: string | number;
    unit_price: string | number;
};

export const UNITS: { value: string; label: string }[] = [
    { value: 'trip', label: 'Trip' },
    { value: 'day', label: 'Day' },
    { value: 'hour', label: 'Hour' },
    { value: 'km', label: 'Km' },
    { value: 'tonne', label: 'Tonne' },
    { value: 'load', label: 'Load' },
    { value: 'pallet', label: 'Pallet' },
    { value: 'container', label: 'Container' },
    { value: 'cbm', label: 'CBM' },
    { value: 'item', label: 'Item' },
    { value: 'week', label: 'Week' },
    { value: 'month', label: 'Month' },
];

export const emptyLineItem = (): LineItem => ({
    id: null,
    vehicle_id: '',
    description: '',
    quantity: 1,
    unit_price: '',
    unit: 'trip',
});

const num = (value: string | number | null | undefined) => {
    const parsed = parseFloat(String(value ?? ''));
    return Number.isFinite(parsed) ? parsed : 0;
};

/** calculateLineAmount(): quantity × unit price. */
export const lineAmount = (item: LineItem) => num(item.quantity) * num(item.unit_price);

/** calculateTotals(): subtotal of amounts, VAT at taxRate %, total = subtotal + VAT. */
export function calculateTotals(items: LineItem[], taxRate: number) {
    const subtotal = items.reduce((sum, item) => sum + lineAmount(item), 0);
    const taxAmount = subtotal * (taxRate / 100);
    return { subtotal, taxAmount, total: subtotal + taxAmount };
}

type Props = {
    items: LineItem[];
    onChange: (items: LineItem[]) => void;
    vehicles: VehicleOption[];
    taxRate: number;
    errors: Record<string, string | undefined>;
};

export function LineItemsEditor({ items, onChange, vehicles, taxRate, errors }: Props) {
    const totals = calculateTotals(items, taxRate);

    const updateItem = (index: number, patch: Partial<LineItem>) => onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)));

    const addItem = () => onChange([...items, emptyLineItem()]);

    const removeItem = (index: number) => onChange(items.filter((_, i) => i !== index));

    /** loadVehicleRate(): description "<type> - <reg>", unit price = type's daily rate when set. */
    const selectVehicle = (index: number, vehicleId: string) => {
        const vehicle = vehicles.find((v) => String(v.id) === vehicleId);
        if (!vehicle) {
            updateItem(index, { vehicle_id: vehicleId });
            return;
        }
        updateItem(index, {
            vehicle_id: vehicleId,
            description: `${vehicle.type_name ?? ''} - ${vehicle.reg_number}`,
            ...(vehicle.base_rate_daily ? { unit_price: vehicle.base_rate_daily } : {}),
        });
    };

    const vehicleSelect = (item: LineItem, index: number, className?: string, id?: string) => (
        <NativeSelect
            id={id}
            aria-label="Vehicle"
            className={className}
            value={item.vehicle_id ?? ''}
            onChange={(e) => selectVehicle(index, e.target.value)}
            aria-invalid={!!errors[`items.${index}.vehicle_id`]}
        >
            <option value="">Select vehicle</option>
            {vehicles.map((vehicle) => (
                <option key={vehicle.id} value={vehicle.id}>
                    {vehicle.reg_number} - {vehicle.type_name}
                </option>
            ))}
        </NativeSelect>
    );

    const unitSelect = (item: LineItem, index: number, className?: string, id?: string) => (
        <NativeSelect id={id} aria-label="Unit" className={className} value={item.unit} onChange={(e) => updateItem(index, { unit: e.target.value })}>
            {UNITS.map((unit) => (
                <option key={unit.value} value={unit.value}>
                    {unit.label}
                </option>
            ))}
        </NativeSelect>
    );

    const fieldError = (index: number, field: string) => <InputError message={errors[`items.${index}.${field}`]} className="text-xs" />;

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>Line items</CardTitle>
                <Button type="button" variant="ghost" size="sm" onClick={addItem}>
                    <Plus /> Add line
                </Button>
            </CardHeader>
            <CardContent className="space-y-6">
                {errors.items && <InputError message={errors.items} />}

                {/* Phones: one card per line */}
                <div className="space-y-4 md:hidden">
                    {items.length === 0 ? (
                        <div className="rounded-lg border bg-muted/50 p-8 text-center text-sm text-muted-foreground">
                            No line items added yet. Use "Add line" to get started.
                        </div>
                    ) : (
                        items.map((item, index) => (
                            <div key={index} className="space-y-3 rounded-lg border p-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-muted-foreground">LINE ITEM #{index + 1}</span>
                                    {items.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-destructive hover:text-destructive/80"
                                            onClick={() => removeItem(index)}
                                            aria-label="Remove line"
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor={`items-${index}-vehicle`} className="text-xs">
                                        Vehicle
                                    </Label>
                                    {vehicleSelect(item, index, undefined, `items-${index}-vehicle`)}
                                    {fieldError(index, 'vehicle_id')}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor={`items-${index}-description`} className="text-xs">
                                        Description *
                                    </Label>
                                    <Input
                                        id={`items-${index}-description`}
                                        value={item.description}
                                        placeholder="Item description"
                                        onChange={(e) => updateItem(index, { description: e.target.value })}
                                        aria-invalid={!!errors[`items.${index}.description`]}
                                    />
                                    {fieldError(index, 'description')}
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor={`items-${index}-unit`} className="text-xs">
                                            Unit
                                        </Label>
                                        {unitSelect(item, index, undefined, `items-${index}-unit`)}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor={`items-${index}-qty`} className="text-xs">
                                            Quantity *
                                        </Label>
                                        <Input
                                            id={`items-${index}-qty`}
                                            type="number"
                                            min="1"
                                            inputMode="numeric"
                                            value={item.quantity}
                                            onChange={(e) => updateItem(index, { quantity: e.target.value })}
                                            aria-invalid={!!errors[`items.${index}.quantity`]}
                                        />
                                        {fieldError(index, 'quantity')}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 items-start gap-3">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor={`items-${index}-price`} className="text-xs">
                                            Unit price *
                                        </Label>
                                        <Input
                                            id={`items-${index}-price`}
                                            type="number"
                                            step="0.01"
                                            inputMode="decimal"
                                            placeholder="0.00"
                                            value={item.unit_price}
                                            onChange={(e) => updateItem(index, { unit_price: e.target.value })}
                                            aria-invalid={!!errors[`items.${index}.unit_price`]}
                                        />
                                        {fieldError(index, 'unit_price')}
                                    </div>
                                    <div className="grid gap-1.5 text-right">
                                        <span className="text-xs font-medium">Amount</span>
                                        <span className="flex h-11 items-center justify-end font-mono font-semibold tabular-nums">{formatMoney(lineAmount(item))}</span>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                    <Button type="button" variant="outline" className="w-full" onClick={addItem}>
                        <Plus /> Add line
                    </Button>
                </div>

                {/* Tablets and up: spreadsheet-style table */}
                <div className="hidden overflow-x-auto md:block">
                    <table className="min-w-full border-collapse border text-sm">
                        <thead>
                            <tr className="bg-muted">
                                <th className="w-[3%] border px-2 py-2 text-left font-semibold">#</th>
                                <th className="w-[22%] border px-2 py-2 text-left font-semibold">Vehicle</th>
                                <th className="w-[25%] border px-2 py-2 text-left font-semibold">Description</th>
                                <th className="w-[12%] border px-2 py-2 text-left font-semibold">Unit</th>
                                <th className="w-[10%] border px-2 py-2 text-center font-semibold">Qty</th>
                                <th className="w-[13%] border px-2 py-2 text-right font-semibold">Unit price</th>
                                <th className="w-[13%] border px-2 py-2 text-right font-semibold">Amount</th>
                                <th className="w-[2%] border px-2 py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            {items.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="border px-4 py-8 text-center text-muted-foreground">
                                        No line items added yet. Use "Add line" to get started.
                                    </td>
                                </tr>
                            ) : (
                                items.map((item, index) => (
                                    <tr key={index} className="hover:bg-muted/50">
                                        <td className="border bg-muted/50 px-2 py-2 text-center text-muted-foreground">{index + 1}</td>
                                        <td className="border p-1">
                                            {vehicleSelect(item, index, cellClass)}
                                            {fieldError(index, 'vehicle_id')}
                                        </td>
                                        <td className="border p-1">
                                            <Input
                                                className={cellClass}
                                                value={item.description}
                                                placeholder="Item description"
                                                onChange={(e) => updateItem(index, { description: e.target.value })}
                                                aria-invalid={!!errors[`items.${index}.description`]}
                                            />
                                            {fieldError(index, 'description')}
                                        </td>
                                        <td className="border p-1">{unitSelect(item, index, cellClass)}</td>
                                        <td className="border p-1">
                                            <Input
                                                type="number"
                                                min="1"
                                                inputMode="numeric"
                                                aria-label="Quantity"
                                                className={cn(cellClass, 'text-center')}
                                                value={item.quantity}
                                                onChange={(e) => updateItem(index, { quantity: e.target.value })}
                                                aria-invalid={!!errors[`items.${index}.quantity`]}
                                            />
                                            {fieldError(index, 'quantity')}
                                        </td>
                                        <td className="border p-1">
                                            <Input
                                                type="number"
                                                step="0.01"
                                                inputMode="decimal"
                                                placeholder="0.00"
                                                aria-label="Unit price"
                                                className={cn(cellClass, 'text-right')}
                                                value={item.unit_price}
                                                onChange={(e) => updateItem(index, { unit_price: e.target.value })}
                                                aria-invalid={!!errors[`items.${index}.unit_price`]}
                                            />
                                            {fieldError(index, 'unit_price')}
                                        </td>
                                        <td className="border bg-muted/50 px-2 py-1.5 text-right font-mono font-medium tabular-nums">{lineAmount(item).toFixed(2)}</td>
                                        <td className="border p-1 text-center">
                                            {items.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(index)}
                                                    className="text-destructive hover:text-destructive/80"
                                                    title="Remove line"
                                                    aria-label="Remove line"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Totals summary */}
                <div className="border-t pt-6">
                    <div className="ml-auto max-w-md space-y-2">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Subtotal:</span>
                            <span className="font-mono font-medium tabular-nums" data-testid="quote-subtotal">
                                {formatMoney(totals.subtotal)}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">VAT ({taxRate}%):</span>
                            <span className="font-mono font-medium tabular-nums">{formatMoney(totals.taxAmount)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-2 text-lg font-bold">
                            <span>Total:</span>
                            <span className="font-mono tabular-nums">{formatMoney(totals.total)}</span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

const cellClass = 'h-8 border-0 shadow-none focus-visible:ring-1';
