import * as React from 'react';

export interface ActivityItem {
  /** What happened, with the record reference */
  title: React.ReactNode;
  /** Who did it and any amount */
  description?: React.ReactNode;
  /** Mono timestamp or relative time */
  time?: React.ReactNode;
  tone?: 'brass' | 'positive' | 'warning' | 'negative' | 'info' | 'neutral';
}

/**
 * Hairline timeline: dashboard recent activity, invoice activity log, approval history.
 * @startingPoint section="Feedback" subtitle="Hairline activity timeline" viewport="700x260"
 */
export interface ActivityListProps {
  items: ActivityItem[];
  style?: React.CSSProperties;
}
export function ActivityList(props: ActivityListProps): JSX.Element;
