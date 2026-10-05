import * as React from 'react';

/** Nothing-here state for tables, lists and charts. States the cause, then offers the next step. */
export interface EmptyStateProps {
  /** Sentence case, states the fact: "No payslips yet" */
  title: React.ReactNode;
  /** One line explaining why it is empty and what fills it */
  description?: React.ReactNode;
  /** A single Button */
  action?: React.ReactNode;
  /** Optional 24-32px stroke icon, rendered in the faintest ink */
  icon?: React.ReactNode;
  /** Tighter padding for use inside a small card */
  compact?: boolean;
  style?: React.CSSProperties;
}
export function EmptyState(props: EmptyStateProps): JSX.Element;
