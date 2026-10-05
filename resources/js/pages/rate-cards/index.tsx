import { Head, Link, router } from '@inertiajs/react';
import { BadgeDollarSign, Ban, CheckCircle2, Globe, Pencil, Plus, Search, Trash2, Users } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataPagination } from '@/components/data-pagination';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney, humanize } from '@/lib/format';
import { create, destroy, edit, index, toggleActive } from '@/routes/rate-cards';
import type { BreadcrumbItem, Option, Paginated } from '@/types';

type RateCardRow = {
    id: number;
    name: string;
    includes_mdc: boolean;
    vehicle_type: string | null;
    client: { name: string; company_name: string | null } | null;
    rate_type: string;
    rate: number;
    effective_from: string | null;
    effective_to: string | null;
    is_active: boolean;
};

type Props = {
    rateCards: Paginated<RateCardRow>;
    stats: { active: number; inactive: number; client_specific: number; general: number };
    vehicleTypes: Option[];
    filters: { search: string; rate_type: string; vehicle_type: string };
    can: { create: boolean; edit: boolean; delete: boolean };
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Rate Cards', href: index() }];

export default function RateCardsIndex({ rateCards, stats, vehicleTypes, filters: initialFilters, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);

    const toggle = (card: RateCardRow) => router.patch(toggleActive(card.id).url, {}, { preserveScroll: true });
    const remove = (card: RateCardRow, done: () => void) => router.delete(destroy(card.id).url, { preserveScroll: true, onFinish: done });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rate Card Management" />
            <PageContainer>
                <PageHeader
                    title="Rate Card Management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> New Rate Card
                                </Link>
                            </Button>
                        )
                    }
                />

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                    <StatCard label="Active Rates" value={stats.active} icon={CheckCircle2} valueClassName="text-green-700 dark:text-green-400" />
                    <StatCard label="Inactive Rates" value={stats.inactive} icon={Ban} />
                    <StatCard label="Client-Specific" value={stats.client_specific} icon={Users} valueClassName="text-blue-700 dark:text-blue-400" />
                    <StatCard label="General Rates" value={stats.general} icon={Globe} valueClassName="text-purple-700 dark:text-purple-400" />
                </div>

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input className="pl-9" value={filters.search} onChange={(e) => setFilter('search', e.target.value)} placeholder="Search rate cards..." />
                            </div>
                            <NativeSelect value={filters.vehicle_type} onChange={(e) => setFilter('vehicle_type', e.target.value)} aria-label="Filter by vehicle type">
                                <option value="">All Vehicle Types</option>
                                {vehicleTypes.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <NativeSelect value={filters.rate_type} onChange={(e) => setFilter('rate_type', e.target.value)} aria-label="Filter by rate type">
                                <option value="">All Rate Types</option>
                                <option value="hourly">Hourly</option>
                                <option value="daily">Daily</option>
                                <option value="per_km">Per Kilometer</option>
                                <option value="tonnage">Tonnage</option>
                                <option value="load_specific">Load Specific</option>
                            </NativeSelect>
                        </div>

                        {rateCards.data.length === 0 ? (
                            <EmptyState icon={BadgeDollarSign} title="No rate cards found." description="Create your first rate card to get started." />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {rateCards.data.map((card) => (
                                        <div key={card.id} className="space-y-3 rounded-lg border p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <Name card={card} />
                                                <StatusBadge tone={card.is_active ? 'green' : 'gray'}>{card.is_active ? 'Active' : 'Inactive'}</StatusBadge>
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <Field label="Vehicle Type">{card.vehicle_type}</Field>
                                                <Field label="Client">
                                                    <ClientCell card={card} />
                                                </Field>
                                                <Field label="Rate Type">
                                                    <StatusBadge tone="gray">{humanize(card.rate_type)}</StatusBadge>
                                                </Field>
                                                <Field label="Rate">
                                                    <span className="font-medium">{formatMoney(card.rate, 'N$')}</span>
                                                </Field>
                                                <Field label="Effective Period" className="col-span-2">
                                                    <Period card={card} />
                                                </Field>
                                            </div>
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                <RowActions card={card} can={can} onToggle={toggle} onDelete={remove} />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Name</TableHead>
                                                <TableHead>Vehicle Type</TableHead>
                                                <TableHead>Client</TableHead>
                                                <TableHead>Rate Type</TableHead>
                                                <TableHead>Rate</TableHead>
                                                <TableHead>Effective Period</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {rateCards.data.map((card) => (
                                                <TableRow key={card.id}>
                                                    <TableCell>
                                                        <Name card={card} />
                                                    </TableCell>
                                                    <TableCell>{card.vehicle_type}</TableCell>
                                                    <TableCell>
                                                        <ClientCell card={card} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone="gray">{humanize(card.rate_type)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell className="font-medium">{formatMoney(card.rate, 'N$')}</TableCell>
                                                    <TableCell>
                                                        <Period card={card} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone={card.is_active ? 'green' : 'gray'}>{card.is_active ? 'Active' : 'Inactive'}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex gap-2">
                                                            <RowActions card={card} can={can} onToggle={toggle} onDelete={remove} />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={rateCards} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function Name({ card }: { card: RateCardRow }) {
    return (
        <div>
            <strong>{card.name}</strong>
            {card.includes_mdc && <span className="ml-1 text-xs text-blue-600 dark:text-blue-400">(incl. MDC)</span>}
        </div>
    );
}

function ClientCell({ card }: { card: RateCardRow }) {
    if (!card.client) {
        return <span className="text-sm text-muted-foreground">General Rate</span>;
    }
    return (
        <div>
            <div className="text-sm font-medium">{card.client.name}</div>
            {card.client.company_name && <div className="text-xs text-muted-foreground">{card.client.company_name}</div>}
        </div>
    );
}

function Period({ card }: { card: RateCardRow }) {
    return (
        <div className="text-sm">
            <div>{card.effective_from ? formatDate(card.effective_from) : ''}</div>
            <div className="text-muted-foreground">{card.effective_to ? `to ${formatDate(card.effective_to)}` : 'No end date'}</div>
        </div>
    );
}

function Field({ label, children, className }: { label: string; children: React.ReactNode; className?: string }) {
    return (
        <div className={className}>
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="text-sm">{children}</div>
        </div>
    );
}

function RowActions({
    card,
    can,
    onToggle,
    onDelete,
}: {
    card: RateCardRow;
    can: Props['can'];
    onToggle: (card: RateCardRow) => void;
    onDelete: (card: RateCardRow, done: () => void) => void;
}) {
    return (
        <>
            {can.edit && (
                <>
                    <Button size="sm" variant="ghost" onClick={() => onToggle(card)}>
                        {card.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
                    <Button asChild size="sm" variant="ghost">
                        <Link href={edit(card.id)}>
                            <Pencil /> Edit
                        </Link>
                    </Button>
                </>
            )}
            {can.delete && (
                <ConfirmDialog
                    trigger={
                        <Button size="sm" variant="destructive">
                            <Trash2 /> Delete
                        </Button>
                    }
                    description="Are you sure you want to delete this rate card?"
                    onConfirm={(done) => onDelete(card, done)}
                />
            )}
        </>
    );
}
