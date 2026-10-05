import * as React from 'react';

/**
 * The Nexus container. Hairline border, no drop shadow.
 * @startingPoint section="Core" subtitle="Card with header, body and footer" viewport="700x260"
 */
export interface CardProps {
  title?: React.ReactNode;
  subtitle?: React.ReactNode;
  /** 16px stroke icon; rendered brass */
  icon?: React.ReactNode;
  /** Header-right controls (buttons, badge, tabs) */
  actions?: React.ReactNode;
  footer?: React.ReactNode;
  children?: React.ReactNode;
  /** Brass hairline + top-right wash. One per screen, on the most important card. */
  emphasis?: boolean;
  padding?: 'sm' | 'md' | 'lg';
  /** false when the body is a full-bleed table */
  bodyPad?: boolean;
  style?: React.CSSProperties;
}
export function Card(props: CardProps): JSX.Element;
