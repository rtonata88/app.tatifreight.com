import * as React from 'react';

/**
 * Overlay dialog: status changes, approvals, destructive confirms, short forms.
 * The only element in Nexus that casts a real shadow.
 */
export interface ModalProps {
  open?: boolean;
  title: React.ReactNode;
  subtitle?: React.ReactNode;
  children?: React.ReactNode;
  /** Buttons, right-aligned. Cancel (ghost) then confirm (primary). */
  footer?: React.ReactNode;
  onClose?: () => void;
  /** Max width in px. 520 default, 720 for a form with two columns. */
  width?: number;
}
export function Modal(props: ModalProps): JSX.Element | null;
