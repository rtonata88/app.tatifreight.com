import { FormField } from '@/components/form-field';
import { Input } from '@/components/ui/input';

/** "From / To" date inputs shared by the report pages. */
export function DateRangeFields({
    dateFrom,
    dateTo,
    onChange,
    fromLabel = 'From Date',
    toLabel = 'To Date',
}: {
    dateFrom: string;
    dateTo: string;
    onChange: (key: 'dateFrom' | 'dateTo', value: string) => void;
    fromLabel?: string;
    toLabel?: string;
}) {
    return (
        <>
            <FormField label={fromLabel} htmlFor="dateFrom">
                <Input id="dateFrom" type="date" value={dateFrom} onChange={(e) => onChange('dateFrom', e.target.value)} />
            </FormField>
            <FormField label={toLabel} htmlFor="dateTo">
                <Input id="dateTo" type="date" value={dateTo} onChange={(e) => onChange('dateTo', e.target.value)} />
            </FormField>
        </>
    );
}
