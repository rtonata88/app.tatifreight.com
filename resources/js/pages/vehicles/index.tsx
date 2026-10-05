import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, Trash2, Truck } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataPagination } from '@/components/data-pagination';
import { EmptyState } from '@/components/empty-state';
import { PageContainer } from '@/components/page-container';
import { RowActionContent, rowActionProps } from '@/components/row-action';
import { PageHeader } from '@/components/page-header';
import { StatusBadge, type BadgeTone } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatNumber, humanize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { create, destroy, edit, index, show } from '@/routes/vehicles';
import type { BreadcrumbItem, Paginated } from '@/types';

type VehicleRow = {
    id: number;
    reg_number: string;
    type: string | null;
    make: string;
    model: string;
    year: number | null;
    status: string;
    current_mileage: number;
    insurance_expiry: string | null;
    insurance_expired: boolean;
};

type Props = {
    vehicles: Paginated<VehicleRow>;
    filters: { search: string; status: string };
    can: { create: boolean; edit: boolean; delete: boolean };
};

export const statusTone: Record<string, BadgeTone> = {
    available: 'green',
    in_use: 'blue',
    maintenance: 'yellow',
    retired: 'red',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vehicles', href: index() }];

export default function VehiclesIndex({ vehicles, filters: initialFilters, can }: Props) {
    const { filters, setFilter } = useFilters(index().url, initialFilters);

    const deleteVehicle = (vehicle: VehicleRow, done: () => void) =>
        router.delete(destroy(vehicle.id).url, { preserveScroll: true, onFinish: done });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet Management" />
            <PageContainer>
                <PageHeader
                    title="Fleet Management"
                    actions={
                        can.create && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus /> Add Vehicle
                                </Link>
                            </Button>
                        )
                    }
                />

                <Card>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    className="pl-9"
                                    value={filters.search}
                                    onChange={(e) => setFilter('search', e.target.value)}
                                    placeholder="Search by reg number, make, or model..."
                                />
                            </div>
                            <NativeSelect value={filters.status} onChange={(e) => setFilter('status', e.target.value)}>
                                <option value="">All Statuses</option>
                                <option value="available">Available</option>
                                <option value="in_use">In Use</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retired">Retired</option>
                            </NativeSelect>
                        </div>

                        {vehicles.data.length === 0 ? (
                            <EmptyState icon={Truck} title="No vehicles found." description="Add your first vehicle to get started." />
                        ) : (
                            <>
                                {/* Phones: cards */}
                                <div className="space-y-3 md:hidden">
                                    {vehicles.data.map((vehicle) => (
                                        <div key={vehicle.id} className="space-y-3 rounded-lg border p-4">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <div className="text-lg font-semibold">{vehicle.reg_number}</div>
                                                    <div className="text-sm text-muted-foreground">{vehicle.type}</div>
                                                </div>
                                                <StatusBadge tone={statusTone[vehicle.status] ?? 'gray'}>{humanize(vehicle.status)}</StatusBadge>
                                            </div>
                                            <div className="border-t pt-3 text-sm font-medium">
                                                {vehicle.make} {vehicle.model}
                                                {vehicle.year && <span className="text-muted-foreground"> ({vehicle.year})</span>}
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 border-t pt-3">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Mileage</div>
                                                    <div className="text-sm font-medium">{formatNumber(vehicle.current_mileage)} km</div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">Insurance Expiry</div>
                                                    <InsuranceExpiry vehicle={vehicle} />
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-2 border-t pt-3">
                                                <RowActions vehicle={vehicle} can={can} onDelete={deleteVehicle} stretch />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Tablets and up: table */}
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Reg Number</TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead>Make &amp; Model</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Mileage</TableHead>
                                                <TableHead>Insurance Expiry</TableHead>
                                                <TableHead className="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {vehicles.data.map((vehicle) => (
                                                <TableRow key={vehicle.id}>
                                                    <TableCell className="font-semibold">{vehicle.reg_number}</TableCell>
                                                    <TableCell>{vehicle.type}</TableCell>
                                                    <TableCell>
                                                        {vehicle.make} {vehicle.model}
                                                        {vehicle.year && <span className="text-muted-foreground"> ({vehicle.year})</span>}
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge tone={statusTone[vehicle.status] ?? 'gray'}>{humanize(vehicle.status)}</StatusBadge>
                                                    </TableCell>
                                                    <TableCell>{formatNumber(vehicle.current_mileage)} km</TableCell>
                                                    <TableCell>
                                                        <InsuranceExpiry vehicle={vehicle} />
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex justify-end gap-1">
                                                            <RowActions vehicle={vehicle} can={can} onDelete={deleteVehicle} />
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </>
                        )}

                        <DataPagination paginator={vehicles} />
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}

function InsuranceExpiry({ vehicle }: { vehicle: VehicleRow }) {
    if (!vehicle.insurance_expiry) {
        return <span className="text-sm text-muted-foreground">Not set</span>;
    }

    return (
        <span className="inline-flex flex-wrap items-center gap-2 text-sm">
            <span className={vehicle.insurance_expired ? 'font-medium text-red-600 dark:text-red-400' : ''}>{formatDate(vehicle.insurance_expiry)}</span>
            {vehicle.insurance_expired && <StatusBadge tone="red">Expired</StatusBadge>}
        </span>
    );
}

function RowActions({
    vehicle,
    can,
    onDelete,
    stretch = false,
}: {
    vehicle: VehicleRow;
    can: Props['can'];
    onDelete: (vehicle: VehicleRow, done: () => void) => void;
    stretch?: boolean;
}) {
    const className = stretch ? 'flex-1' : undefined;
    const compact = !stretch;

    return (
        <>
            <Button asChild variant="ghost" {...rowActionProps(compact, 'View', className)}>
                <Link href={show(vehicle.id)}>
                    <RowActionContent icon={Eye} label="View" compact={compact} />
                </Link>
            </Button>
            {can.edit && (
                <Button asChild variant="ghost" {...rowActionProps(compact, 'Edit', className)}>
                    <Link href={edit(vehicle.id)}>
                        <RowActionContent icon={Pencil} label="Edit" compact={compact} />
                    </Link>
                </Button>
            )}
            {can.delete && (
                <ConfirmDialog
                    trigger={
                        <Button variant={compact ? 'ghost' : 'destructive'} {...rowActionProps(compact, 'Delete', cn(className, compact && 'text-destructive hover:text-destructive'))}>
                            <RowActionContent icon={Trash2} label="Delete" compact={compact} />
                        </Button>
                    }
                    description="Are you sure you want to delete this vehicle?"
                    onConfirm={(done) => onDelete(vehicle, done)}
                />
            )}
        </>
    );
}
