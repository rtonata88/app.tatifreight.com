import * as React from 'react';

/**
 * Inline toggle for a setting that changes the reading of other fields —
 * the React counterpart of the app's `ep-toggle` (e.g. "Amount includes VAT (15%)").
 * For a plain recorded fact, use Checkbox instead.
 */
export interface SwitchProps {
  checked?: boolean;
  onChange?: React.ChangeEventHandler<HTMLInputElement>;
  /** Sits to the right of the track */
  label?: React.ReactNode;
  id?: string;
  disabled?: boolean;
}
export function Switch(props: SwitchProps): JSX.Element;
