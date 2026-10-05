import * as React from 'react';

/** Titles a group of cards within a page body. Sits above the cards, not inside them. */
export interface SectionHeaderProps {
  title: React.ReactNode;
  /** Usually a text-variant Button, e.g. "View all" */
  action?: React.ReactNode;
  /** Count or timeframe beside the title */
  meta?: React.ReactNode;
  style?: React.CSSProperties;
}
export function SectionHeader(props: SectionHeaderProps): JSX.Element;
