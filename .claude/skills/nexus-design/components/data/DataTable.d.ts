import * as React from 'react';

export interface DataTableColumn<R = any> {
  key: string;
  label: React.ReactNode;
  align?: 'left' | 'right' | 'center';
  /** CSS width, e.g. "120px" or "30%" */
  width?: string;
  /** Tabular mono cell - money, hours, codes, dates */
  mono?: boolean;
  /** Render the cell in muted foreground */
  muted?: boolean;
  /** Allow the cell to wrap (default: nowrap) */
  wrap?: boolean;
  /** Custom cell renderer; receives the row */
  render?: (row: R, index: number) => React.ReactNode;
}

/**
 * The workhorse of Nexus - every index screen is one of these inside a Card.
 * Replaces the Yajra DataTables markup one-for-one.
 * @startingPoint section="Data" subtitle="Index table with mono money columns" viewport="700x300"
 */
export interface DataTableProps<R = any> {
  columns: DataTableColumn<R>[];
  rows: R[];
  /** 36px rows instead of 44px */
  dense?: boolean;
  hoverable?: boolean;
  onRowClick?: (row: R, index: number) => void;
  /** Rendered instead of the table when rows is empty */
  emptyState?: React.ReactNode;
  /** Pagination or summary strip below the rows */
  footer?: React.ReactNode;
  style?: React.CSSProperties;
}
export function DataTable(props: DataTableProps): JSX.Element;
