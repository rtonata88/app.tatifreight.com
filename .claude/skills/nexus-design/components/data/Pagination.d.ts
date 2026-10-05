import * as React from 'react';

/** Table pager. Sits in a DataTable footer or a Card footer. */
export interface PaginationProps {
  page?: number;
  pages?: number;
  /** Total record count - enables the "Showing 1-25 of 184" readout */
  total?: number;
  perPage?: number;
  onChange?: (page: number) => void;
  style?: React.CSSProperties;
}
export function Pagination(props: PaginationProps): JSX.Element;
