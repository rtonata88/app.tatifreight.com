import * as React from 'react';

/**
 * The Nexus status taxonomy in a hairline pill. Uppercase, tracked, dot-led.
 * @startingPoint section="Core" subtitle="Status taxonomy pills" viewport="700x120"
 */
export interface StatusPillProps {
  /** Drives the colour. Unknown values fall back to muted. */
  status: 'active' | 'approved' | 'paid' | 'pending' | 'draft' | 'submitted' | 'completed' | 'declined' | 'overdue' | 'inactive' | string;
  /** Override the visible label; defaults to the status value */
  children?: React.ReactNode;
  dot?: boolean;
}
export function StatusPill(props: StatusPillProps): JSX.Element;
