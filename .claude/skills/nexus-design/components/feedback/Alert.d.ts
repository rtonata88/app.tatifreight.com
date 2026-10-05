import * as React from 'react';

/** Inline page-level message: validation summary, permission notice, posting-period warning. */
export interface AlertProps {
  tone?: 'success' | 'warning' | 'error' | 'info';
  /** Short bold line stating the fact */
  title?: React.ReactNode;
  /** Detail copy */
  children?: React.ReactNode;
  /** A single text or ghost Button */
  action?: React.ReactNode;
  onDismiss?: () => void;
  style?: React.CSSProperties;
}
export function Alert(props: AlertProps): JSX.Element;
