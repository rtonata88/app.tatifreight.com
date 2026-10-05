import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { InactiveBadge, PrimaryBadge, type BankAccount } from '@/components/admin/bank-account-details';
import { BankAccountDialog } from '@/components/admin/bank-account-dialog';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { bankAccounts as bankAccountsRoute, company } from '@/routes/settings';
import { destroy, primary, toggleActive } from '@/routes/settings/bank-accounts';
import type { BreadcrumbItem } from '@/types';

type Account = BankAccount & { is_active: boolean };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Company Settings', href: company() },
    { title: 'Bank Accounts', href: bankAccountsRoute() },
];

export default function BankAccounts({ bankAccounts }: { bankAccounts: Account[] }) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Account | null>(null);

    const openAdd = () => {
        setEditing(null);
        setDialogOpen(true);
    };

    const openEdit = (account: Account) => {
        setEditing(account);
        setDialogOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bank Accounts" />
            <PageContainer>
                <PageHeader
                    title="Bank Accounts"
                    description="Manage your company's bank accounts for payments"
                    actions={
                        <>
                            <Button asChild variant="ghost">
                                <Link href={company()}>
                                    <ArrowLeft /> Back to Settings
                                </Link>
                            </Button>
                            <Button onClick={openAdd}>
                                <Plus /> Add Bank Account
                            </Button>
                        </>
                    }
                />

                <div className="space-y-4">
                    {bankAccounts.length === 0 ? (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <p className="mb-4 text-muted-foreground">No bank accounts configured yet.</p>
                                <Button onClick={openAdd}>Add Your First Bank Account</Button>
                            </CardContent>
                        </Card>
                    ) : (
                        bankAccounts.map((account) => <AccountCard key={account.id} account={account} onEdit={() => openEdit(account)} />)
                    )}
                </div>

                <BankAccountDialog open={dialogOpen} onOpenChange={setDialogOpen} account={editing} />
            </PageContainer>
        </AppLayout>
    );
}

function AccountCard({ account, onEdit }: { account: Account; onEdit: () => void }) {
    const fields: [string, string | null][] = [
        ['Account Name', account.account_name],
        ['Account Number', account.account_number],
        ['Currency', account.currency],
        ['Branch', account.branch_name],
        ['Branch Code', account.branch_code],
        ['SWIFT Code', account.swift_code],
    ];

    const patch = (url: string) => router.patch(url, {}, { preserveScroll: true });

    return (
        <Card className={cn(account.is_primary && 'border-2 border-blue-500')}>
            <CardContent className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="flex-1">
                    <div className="mb-3 flex flex-wrap items-center gap-3">
                        <h3 className="text-lg font-semibold">{account.bank_name}</h3>
                        {account.is_primary && <PrimaryBadge />}
                        {!account.is_active && <InactiveBadge />}
                    </div>
                    <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 md:grid-cols-3">
                        {fields
                            .filter(([, value]) => value)
                            .map(([label, value]) => (
                                <div key={label}>
                                    <p className="mb-1 text-xs text-muted-foreground">{label}</p>
                                    <p className="font-medium">{value}</p>
                                </div>
                            ))}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {!account.is_primary && (
                        <Button size="sm" variant="ghost" onClick={() => patch(primary(account.id).url)}>
                            Set as Primary
                        </Button>
                    )}
                    <Button size="sm" variant="ghost" onClick={onEdit}>
                        <Pencil /> Edit
                    </Button>
                    <Button size="sm" variant="ghost" onClick={() => patch(toggleActive(account.id).url)}>
                        {account.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
                    {!account.is_primary && (
                        <ConfirmDialog
                            trigger={
                                <Button size="sm" variant="destructive">
                                    <Trash2 /> Delete
                                </Button>
                            }
                            description="Are you sure you want to delete this bank account?"
                            onConfirm={(done) => router.delete(destroy(account.id).url, { preserveScroll: true, onFinish: done })}
                        />
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
