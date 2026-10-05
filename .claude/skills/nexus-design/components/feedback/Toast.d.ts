import * as React from 'react';

/**
 * Transient confirmation after a write - the React replacement for the app's
 * toastr session-flash messages. Stack bottom-right, 20px from the edges.
 */
export interface ToastProps {
  tone?: 'success' | 'warning' | 'error' | 'info';
  /** One sentence, past tense: "Quotation QT-2026-0184 approved." */
  children?: React.ReactNode;
  onDismiss?: () => void;
  style?: React.CSSProperties;
}
export function Toast(props: ToastProps): JSX.Element;
