import * as React from 'react';

/**
 * A headline figure on a dashboard: revenue, outstanding, active projects, on leave.
 * @startingPoint section="Data" subtitle="Dashboard metric row" viewport="700x150"
 */
export interface MetricCardProps {
  /** Uppercase eyebrow, e.g. "Monthly revenue" */
  label: React.ReactNode;
  /** Preformatted figure, e.g. "NAD 4.18m" or "27" */
  value: React.ReactNode;
  /** Period-on-period change, e.g. "+12.4% MoM" */
  delta?: React.ReactNode;
  deltaTone?: 'positive' | 'negative' | 'neutral';
  /** Secondary context line */
  meta?: React.ReactNode;
  /** Brass hairline + wash. One per metric row at most. */
  emphasis?: boolean;
  style?: React.CSSProperties;
}
export function MetricCard(props: MetricCardProps): JSX.Element;
