import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Download } from "lucide-react";
import { FormField } from "@/components/form-field";
import { PageContainer } from "@/components/page-container";
import { PageHeader } from "@/components/page-header";
import { StatCard } from "@/components/stat-card";
import { StatusBadge } from "@/components/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { useFilters } from "@/hooks/use-filters";
import AppLayout from "@/layouts/app-layout";
import { formatDate, formatMoney } from "@/lib/format";
import { cn } from "@/lib/utils";
import { index, statement } from "@/routes/clients";
import { pdf } from "@/routes/clients/statement";
import type { BreadcrumbItem } from "@/types";

type StatementClient = {
    id: number;
    name: string;
    company_name: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    postal_code: string | null;
    country: string | null;
    classification: string;
    credit_limit: number;
    is_active: boolean;
};

type Transaction = {
    date: string | null;
    type: "invoice" | "payment";
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
};

type Props = {
    client: StatementClient;
    filters: { dateFrom: string; dateTo: string };
    transactions: Transaction[];
    totalInvoiced: number;
    totalPaid: number;
    totalOutstanding: number;
};

const money = (value: number) => formatMoney(value, "N$");
const ucfirst = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export default function ClientStatement({
    client,
    filters: initialFilters,
    transactions,
    totalInvoiced,
    totalPaid,
    totalOutstanding,
}: Props) {
    const { filters, setFilter } = useFilters(
        statement(client.id).url,
        initialFilters,
        { debounce: 0 },
    );
    const displayName = client.company_name || client.name;
    const outstanding = totalOutstanding > 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Clients", href: index() },
        { title: "Customer statement", href: statement(client.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Customer statement - ${displayName}`} />
            <PageContainer>
                <PageHeader
                    title="Customer statement"
                    description={displayName}
                    actions={
                        <>
                            <Button asChild variant="ghost">
                                <Link href={index()}>
                                    <ArrowLeft /> Back to clients
                                </Link>
                            </Button>
                            {/* Plain link: the PDF controller reads dateFrom / dateTo from the query string. */}
                            <Button asChild>
                                <a
                                    href={
                                        pdf(client.id, {
                                            query: {
                                                dateFrom: filters.dateFrom,
                                                dateTo: filters.dateTo,
                                            },
                                        }).url
                                    }
                                >
                                    <Download /> Export to PDF
                                </a>
                            </Button>
                        </>
                    }
                />

                {/* Phones: the balance figures come first, client details after. */}
                <Card className="max-md:order-1">
                    <CardContent className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-muted-foreground">
                                Client information
                            </h3>
                            <div className="space-y-1 text-sm">
                                {client.company_name && (
                                    <div>
                                        <strong>Company:</strong>{" "}
                                        {client.company_name}
                                    </div>
                                )}
                                <div>
                                    <strong>Contact:</strong> {client.name}
                                </div>
                                {client.email && (
                                    <div className="break-all">
                                        <strong>Email:</strong> {client.email}
                                    </div>
                                )}
                                {client.phone && (
                                    <div>
                                        <strong>Phone:</strong> {client.phone}
                                    </div>
                                )}
                            </div>
                        </div>
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-muted-foreground">
                                Address
                            </h3>
                            <div className="space-y-1 text-sm">
                                {client.address && (
                                    <div className="whitespace-pre-line">
                                        {client.address}
                                    </div>
                                )}
                                {(client.city || client.postal_code) && (
                                    <div>
                                        {client.city}
                                        {client.postal_code &&
                                            `, ${client.postal_code}`}
                                    </div>
                                )}
                                {client.country && <div>{client.country}</div>}
                            </div>
                        </div>
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-muted-foreground">
                                Account details
                            </h3>
                            <div className="space-y-1 text-sm">
                                <div>
                                    <strong>Type:</strong>{" "}
                                    {ucfirst(client.classification)}
                                </div>
                                <div>
                                    <strong>Credit limit:</strong>{" "}
                                    <span className="font-mono tabular-nums">
                                        {money(client.credit_limit)}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <strong>Status:</strong>
                                    <StatusBadge
                                        tone={
                                            client.is_active ? "green" : "red"
                                        }
                                    >
                                        {client.is_active
                                            ? "Active"
                                            : "Inactive"}
                                    </StatusBadge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="max-md:order-1">
                    <CardContent className="grid grid-cols-2 items-end gap-3 md:flex md:flex-wrap md:gap-4">
                        <FormField label="Date from" htmlFor="dateFrom">
                            <Input
                                id="dateFrom"
                                type="date"
                                value={filters.dateFrom}
                                onChange={(e) =>
                                    setFilter("dateFrom", e.target.value)
                                }
                            />
                        </FormField>
                        <FormField label="Date to" htmlFor="dateTo">
                            <Input
                                id="dateTo"
                                type="date"
                                value={filters.dateTo}
                                onChange={(e) =>
                                    setFilter("dateTo", e.target.value)
                                }
                            />
                        </FormField>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 gap-3 md:gap-4 max-md:[&>*:last-child:nth-child(odd)]:col-span-2 md:grid-cols-3">
                    <StatCard
                        label="Total invoiced"
                        value={money(totalInvoiced)}
                    />
                    <StatCard label="Total paid" value={money(totalPaid)} />
                    <StatCard
                        label="Outstanding balance"
                        value={money(totalOutstanding)}
                        tone={outstanding ? "negative" : "neutral"}
                        emphasis
                        className="max-md:order-first"
                    />
                </div>

                <Card className="max-md:order-1">
                    <CardHeader>
                        <CardTitle>Transaction history</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <div className="py-8 text-center text-muted-foreground">
                                No transactions found for the selected period.
                            </div>
                        ) : (
                            <>
                                <ul className="divide-y md:hidden">
                                    {transactions.map((transaction, i) => (
                                        <li
                                            key={`${transaction.type}-${transaction.reference}-${i}`}
                                            className="flex items-start justify-between gap-4 py-3"
                                        >
                                            <div className="min-w-0">
                                                <div className="font-mono text-sm">
                                                    <span className="text-muted-foreground">
                                                        {formatDate(
                                                            transaction.date,
                                                        )}
                                                    </span>{" "}
                                                    <span className="font-medium">
                                                        {transaction.reference}
                                                    </span>
                                                </div>
                                                <div className="mt-0.5 text-sm text-muted-foreground">
                                                    {transaction.description}
                                                </div>
                                            </div>
                                            <div className="shrink-0 text-right">
                                                {transaction.debit > 0 ? (
                                                    <div className="font-mono font-semibold tabular-nums text-destructive">
                                                        {money(
                                                            transaction.debit,
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div className="font-mono font-semibold tabular-nums text-success">
                                                        {money(
                                                            transaction.credit,
                                                        )}
                                                    </div>
                                                )}
                                                <div
                                                    className={cn(
                                                        "mt-0.5 font-mono text-xs tabular-nums text-muted-foreground",
                                                        transaction.balance >
                                                            0 &&
                                                            "text-destructive",
                                                    )}
                                                >
                                                    Bal{" "}
                                                    {money(transaction.balance)}
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                    <li className="flex items-center justify-between gap-4 pt-3 font-bold">
                                        <span>Balance</span>
                                        <span
                                            className={cn(
                                                "font-mono tabular-nums",
                                                outstanding &&
                                                    "text-destructive",
                                            )}
                                        >
                                            {money(totalOutstanding)}
                                        </span>
                                    </li>
                                </ul>
                                <div className="hidden md:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="uppercase">
                                                    Date
                                                </TableHead>
                                                <TableHead className="uppercase">
                                                    Reference
                                                </TableHead>
                                                <TableHead className="uppercase">
                                                    Description
                                                </TableHead>
                                                <TableHead className="text-right uppercase">
                                                    Debit
                                                </TableHead>
                                                <TableHead className="text-right uppercase">
                                                    Credit
                                                </TableHead>
                                                <TableHead className="text-right uppercase">
                                                    Balance
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {transactions.map(
                                                (transaction, i) => (
                                                    <TableRow
                                                        key={`${transaction.type}-${transaction.reference}-${i}`}
                                                        className={cn(
                                                            transaction.type ===
                                                                "payment" &&
                                                                "bg-(--nx-pos-wash)",
                                                        )}
                                                    >
                                                        <TableCell className="font-mono whitespace-nowrap">
                                                            {formatDate(
                                                                transaction.date,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="font-mono font-medium">
                                                            {
                                                                transaction.reference
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">
                                                            {
                                                                transaction.description
                                                            }
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                "text-right font-mono tabular-nums",
                                                                transaction.debit >
                                                                    0
                                                                    ? "font-semibold text-destructive"
                                                                    : "text-muted-foreground",
                                                            )}
                                                        >
                                                            {transaction.debit >
                                                            0
                                                                ? money(
                                                                      transaction.debit,
                                                                  )
                                                                : "-"}
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                "text-right font-mono tabular-nums",
                                                                transaction.credit >
                                                                    0
                                                                    ? "font-semibold text-success"
                                                                    : "text-muted-foreground",
                                                            )}
                                                        >
                                                            {transaction.credit >
                                                            0
                                                                ? money(
                                                                      transaction.credit,
                                                                  )
                                                                : "-"}
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                "text-right font-mono font-bold tabular-nums",
                                                                transaction.balance >
                                                                    0 &&
                                                                    "text-destructive",
                                                            )}
                                                        >
                                                            {money(
                                                                transaction.balance,
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ),
                                            )}
                                        </TableBody>
                                        <TableFooter>
                                            <TableRow>
                                                <TableCell
                                                    colSpan={3}
                                                    className="font-bold"
                                                >
                                                    Total
                                                </TableCell>
                                                <TableCell className="text-right font-mono font-bold tabular-nums text-destructive">
                                                    {money(totalInvoiced)}
                                                </TableCell>
                                                <TableCell className="text-right font-mono font-bold tabular-nums text-success">
                                                    {money(totalPaid)}
                                                </TableCell>
                                                <TableCell
                                                    className={cn(
                                                        "text-right font-mono font-bold tabular-nums",
                                                        outstanding &&
                                                            "text-destructive",
                                                    )}
                                                >
                                                    {money(totalOutstanding)}
                                                </TableCell>
                                            </TableRow>
                                        </TableFooter>
                                    </Table>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </PageContainer>
        </AppLayout>
    );
}
