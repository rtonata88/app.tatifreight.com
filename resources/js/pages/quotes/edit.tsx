import { Head, router } from '@inertiajs/react';
import { ArrowRight, Download, Eye } from 'lucide-react';
import { useState } from 'react';
import { PageContainer } from '@/components/page-container';
import { PageHeader } from '@/components/page-header';
import type { LineItem, VehicleOption } from '@/components/quotes/line-items-editor';
import { QuoteForm } from '@/components/quotes/quote-form';
import { quoteStatusTone } from '@/components/quotes/quote-status';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import { humanize } from '@/lib/format';
import { convertToBooking, edit, index } from '@/routes/quotes';
import { download as pdfDownload, view as pdfView } from '@/routes/quotes/pdf';
import type { BreadcrumbItem, Option } from '@/types';

type Props = {
    quote: {
        id: number;
        quote_number: string;
        client_id: number;
        description: string | null;
        valid_until: string | null;
        terms_conditions: string | null;
        notes: string | null;
        status: string;
        created_at: string | null;
        created_by: string | null;
        sent_at: string | null;
        approved_at: string | null;
        line_items: (LineItem & { id: number })[];
    };
    hasBooking: boolean;
    clients: Option[];
    vehicles: VehicleOption[];
    taxRate: number;
    can: { view: boolean };
};

export default function QuotesEdit({ quote, hasBooking, clients, vehicles, taxRate, can }: Props) {
    const [converting, setConverting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Quotations', href: index() },
        { title: quote.quote_number, href: edit(quote.id) },
    ];

    const convert = () => {
        setConverting(true);
        router.post(convertToBooking(quote.id).url, { open_booking: true }, { preserveScroll: true, onFinish: () => setConverting(false) });
    };

    const title = `Edit quote: ${quote.quote_number}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <PageContainer>
                <PageHeader
                    title={title}
                    actions={
                        <>
                            <StatusBadge tone={quoteStatusTone[quote.status] ?? 'gray'}>{humanize(quote.status)}</StatusBadge>
                            {can.view && (
                                <>
                                    <Button asChild variant="ghost">
                                        <a href={pdfView(quote.id).url} target="_blank" rel="noreferrer">
                                            <Eye /> View PDF
                                        </a>
                                    </Button>
                                    <Button asChild variant="ghost">
                                        <a href={pdfDownload(quote.id).url}>
                                            <Download /> Download PDF
                                        </a>
                                    </Button>
                                </>
                            )}
                            {quote.status === 'approved' && !hasBooking && (
                                <Button onClick={convert} disabled={converting}>
                                    {converting ? <Spinner /> : <ArrowRight />} Convert to booking
                                </Button>
                            )}
                            {hasBooking && <StatusBadge tone="green">Converted to booking</StatusBadge>}
                        </>
                    }
                />

                <QuoteForm
                    quoteId={quote.id}
                    clients={clients}
                    vehicles={vehicles}
                    taxRate={taxRate}
                    initial={{
                        client_id: quote.client_id,
                        valid_until: quote.valid_until ?? '',
                        status: quote.status,
                        description: quote.description ?? '',
                        terms_conditions: quote.terms_conditions ?? '',
                        notes: quote.notes ?? '',
                        items: quote.line_items.map((item) => ({ ...item, vehicle_id: item.vehicle_id ?? '' })),
                    }}
                    footer={
                        (quote.sent_at || quote.approved_at) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Timeline</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <p className="text-sm font-medium">Created</p>
                                        <p className="text-sm text-muted-foreground">
                                            {quote.created_at} by {quote.created_by}
                                        </p>
                                    </div>
                                    {quote.sent_at && (
                                        <div>
                                            <p className="text-sm font-medium">Sent to client</p>
                                            <p className="text-sm text-muted-foreground">{quote.sent_at}</p>
                                        </div>
                                    )}
                                    {quote.approved_at && (
                                        <div>
                                            <p className="text-sm font-medium">Approved</p>
                                            <p className="text-sm text-muted-foreground">{quote.approved_at}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )
                    }
                />
            </PageContainer>
        </AppLayout>
    );
}
