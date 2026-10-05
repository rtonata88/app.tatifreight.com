import * as React from 'react';

/**
 * The only three button weights Nexus ships, plus a destructive outline.
 * One primary (brass) per region - a screen with two brass buttons is off-brand.
 *
 * @startingPoint section="Core" subtitle="Primary, ghost, text and danger buttons" viewport="700x180"
 */
export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  children?: React.ReactNode;
  /** primary = solid brass; ghost = hairline; text = inline link; danger = oxblood outline */
  variant?: 'primary' | 'ghost' | 'text' | 'danger';
  size?: 'sm' | 'md' | 'lg';
  /** Leading 16px stroke icon node */
  icon?: React.ReactNode;
  /** Trailing icon node (chevrons, arrows) */
  iconAfter?: React.ReactNode;
  disabled?: boolean;
  block?: boolean;
}
export function Button(props: ButtonProps): JSX.Element;