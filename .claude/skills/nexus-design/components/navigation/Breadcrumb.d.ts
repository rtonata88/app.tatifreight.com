import * as React from 'react';

export interface BreadcrumbItem { label: React.ReactNode; key?: string; }

/** Slash-separated trail in the Topbar. Replaces the Blade breadcrumb ol. */
export interface BreadcrumbProps {
  /** Strings or {label,key} objects; the last is the current page */
  items: Array<string | BreadcrumbItem>;
  onNavigate?: (key: string) => void;
  style?: React.CSSProperties;
}
export function Breadcrumb(props: BreadcrumbProps): JSX.Element;
