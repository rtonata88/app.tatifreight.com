import * as React from 'react';

/** Square hairline icon button - the row-action affordance in every Nexus table. */
export interface IconButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  /** The 14-16px stroke icon */
  children?: React.ReactNode;
  /** Required: becomes aria-label and title */
  label: string;
  tone?: 'neutral' | 'brass' | 'positive' | 'negative';
  /** Square edge in px. 28 default, 24 for dense tables */
  size?: number;
  disabled?: boolean;
}
export function IconButton(props: IconButtonProps): JSX.Element;
