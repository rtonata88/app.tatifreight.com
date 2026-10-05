import { StatusBadge } from '@/components/status-badge';
import { cn } from '@/lib/utils';

export type BankAccount = {
    id: number;
    bank_name: string;
    account_name: string;
    account_number: string;
    branch_name: string | null;
    branch_code: string | null;
    swift_code: string | null;
    currency: string;
    is_primary: boolean;
    is_active?: boolean;
};

/** Read-only summary of an account, used on the company settings page. */
export function BankAccountDetails({ account, compact = false }: { account: BankAccount; compact?: boolean }) {
    const rows: [string, string | null][] = [
        ['Account name', account.account_name],
        ['Account no.', account.account_number],
        ['Branch', account.branch_name],
        ['Branch code', account.branch_code],
        ['SWIFT', account.swift_code],
        ['Currency', account.currency],
    ];

    return (
        <div
            className={cn(
                'rounded-lg border p-4',
                account.is_primary && 'border-primary bg-(--nx-brass-wash-2)',
                compact && 'text-sm',
            )}
        >
            <div className="mb-2 flex items-center gap-2">
                <h4 className="font-semibold">{account.bank_name}</h4>
                {account.is_primary && <PrimaryBadge />}
            </div>
            <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-muted-foreground">
                {rows
                    .filter(([, value]) => value)
                    .map(([label, value]) => (
                        <div key={label}>
                            <strong className="text-foreground">{label}:</strong> <span className="font-mono">{value}</span>
                        </div>
                    ))}
            </div>
        </div>
    );
}

export function PrimaryBadge() {
    return <StatusBadge tone="indigo">Primary</StatusBadge>;
}

export function InactiveBadge() {
    return (
        <StatusBadge tone="gray">
            Inactive
        </StatusBadge>
    );
}
