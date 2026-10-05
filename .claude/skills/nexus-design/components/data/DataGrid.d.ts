import * as React from 'react';

export interface DataGridItem {
  label: React.ReactNode;
  value?: React.ReactNode;
  /** Tabular mono value - IDs, money, dates, phone numbers */
  mono?: boolean;
  /** Larger brass value - one per grid at most */
  emphasis?: boolean;
  /** Column span */
  span?: number;
}

/** Key-value facts in a grid: employee details, client details, invoice header, expense meta. */
export interface DataGridProps {
  items: DataGridItem[];
  /** Columns; 2 in a sidebar card, 3-4 in a full-width card */
  columns?: number;
  style?: React.CSSProperties;
}
export function DataGrid(props: DataGridProps): JSX.Element;
