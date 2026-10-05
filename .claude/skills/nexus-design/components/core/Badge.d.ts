import * as React from 'react';

/** Soft-wash label for counts, categories, discipline tags and reference codes. */
export interface BadgeProps {
  children?: React.ReactNode;
  tone?: 'neutral' | 'brass' | 'positive' | 'warning' | 'negative' | 'info';
  /** Set for reference codes and IDs (JetBrains Mono) */
  mono?: boolean;
}
export function Badge(props: BadgeProps): JSX.Element;
