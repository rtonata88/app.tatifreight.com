/**
 * Laravel's LengthAwarePaginator as serialised to JSON.
 * Pass the whole object to <DataPagination paginator={...} />.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
};

/** A <select> option sent from the server. */
export type Option = {
    value: string | number;
    label: string;
};
