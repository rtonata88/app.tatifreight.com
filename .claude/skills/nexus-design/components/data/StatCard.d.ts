import * as React from 'react';

/** Small figure + caption, used inside a card in a 2x2 or 4-up quick-stats grid. */
export interface StatCardProps {
  value: React.ReactNode;
  label: React.ReactNode;
  tone?: 'neutral' | 'brass' | 'positive' | 'negative' | 'info';
  style?: React.CSSProperties;
}
export function StatCard(props: StatCardProps): JSX.Element;
