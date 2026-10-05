import * as React from 'react';

/**
 * Opens every Nexus screen: module eyebrow, display title, italic subtitle, actions right.
 * @startingPoint section="Navigation" subtitle="Page header with actions" viewport="700x180"
 */
export interface PageHeaderProps {
  /** Module the screen belongs to, e.g. "Client management" */
  eyebrow?: React.ReactNode;
  /** Sentence case, never Title Case */
  title: React.ReactNode;
  /** One line, set in italic display type */
  subtitle?: React.ReactNode;
  /** Primary and secondary buttons */
  actions?: React.ReactNode;
  /** Status pills, badges or record identifiers under the title */
  meta?: React.ReactNode;
  style?: React.CSSProperties;
}
export function PageHeader(props: PageHeaderProps): JSX.Element;
