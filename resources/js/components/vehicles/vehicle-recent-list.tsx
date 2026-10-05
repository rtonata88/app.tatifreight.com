import { Link } from "@inertiajs/react";
import type { ReactNode } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

/** A titled card on the vehicle page listing recent related records, with an optional "View All" link. */
export function VehicleRecentList<T>({
    title,
    items,
    viewAllHref,
    empty,
    renderItem,
}: {
    title: string;
    items: T[];
    viewAllHref?: string;
    empty?: string;
    renderItem: (item: T) => {
        key: string | number;
        main: ReactNode;
        aside?: ReactNode;
    };
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{title}</CardTitle>
                {viewAllHref && (
                    <Button asChild variant="ghost" size="sm">
                        <Link href={viewAllHref}>View all</Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {items.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {empty}
                    </p>
                ) : (
                    <div className="space-y-3">
                        {items.map((item) => {
                            const { key, main, aside } = renderItem(item);
                            return (
                                <div
                                    key={key}
                                    className="flex items-start justify-between gap-2 rounded-lg bg-muted/50 p-3"
                                >
                                    <div className="min-w-0 flex-1">{main}</div>
                                    {aside}
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
