import { StatusBadge } from '@/components/status-badge';
import { formatDate, formatNumber } from '@/lib/format';

/** One MDC calculation as shaped by MdcController::row(). */
export type MdcRow = {
    id: number;
    calculation_date: string | null;
    logbook: {
        date: string | null;
        origin_from: string;
        origin_to: string;
        booking_number: string | null;
    } | null;
    client: { name: string; company_name: string | null } | null;
    vehicle: { reg_number: string; type: string | null } | null;
    distance_km: number;
    gvm_tonnes: number | null;
    mdc_amount: number;
    amount_paid: number;
    outstanding_amount: number;
    payment_status: 'paid' | 'partially_paid' | 'unpaid' | string;
};

export function PaymentStatusBadge({ status }: { status: string }) {
    if (status === 'paid') {
        return <StatusBadge tone="green">✓ Paid</StatusBadge>;
    }
    if (status === 'partially_paid') {
        return <StatusBadge tone="amber">⚠ Partial</StatusBadge>;
    }
    return <StatusBadge tone="red">✗ Unpaid</StatusBadge>;
}

/** Logbook date + route ("Windhoek → Walvis Bay"), optionally the booking number. */
export function LogbookCell({ logbook, showBooking = false }: { logbook: MdcRow['logbook']; showBooking?: boolean }) {
    if (!logbook) {
        return <span className="text-muted-foreground">N/A</span>;
    }

    return (
        <div>
            <div className="font-medium">{formatDate(logbook.date)}</div>
            <div className="text-xs text-muted-foreground">
                {logbook.origin_from} → {logbook.origin_to}
            </div>
            {showBooking && logbook.booking_number && <div className="text-xs text-blue-600 dark:text-blue-400">Booking: {logbook.booking_number}</div>}
        </div>
    );
}

export function ClientCell({ client }: { client: MdcRow['client'] }) {
    if (!client) {
        return <span className="text-muted-foreground">Internal</span>;
    }

    return (
        <div>
            <div className="font-medium">{client.name}</div>
            {client.company_name && <div className="text-xs text-muted-foreground">{client.company_name}</div>}
        </div>
    );
}

export function VehicleCell({ vehicle }: { vehicle: MdcRow['vehicle'] }) {
    if (!vehicle) {
        return <span className="text-muted-foreground">N/A</span>;
    }

    return (
        <div>
            <div className="font-medium">{vehicle.reg_number}</div>
            {vehicle.type && <div className="text-xs text-muted-foreground">{vehicle.type}</div>}
        </div>
    );
}

/** "1,234.50 km" with the GVM underneath when known. */
export function DistanceCell({ row }: { row: Pick<MdcRow, 'distance_km' | 'gvm_tonnes'> }) {
    return (
        <div className="text-sm">
            {formatNumber(row.distance_km, 2)} km
            {row.gvm_tonnes ? <div className="text-xs text-muted-foreground">GVM: {formatNumber(row.gvm_tonnes, 1)}t</div> : null}
        </div>
    );
}
