import * as React from 'react';

/** Brass-filled checkbox with an optional label and hint. Also the row-select control in tables. */
export interface CheckboxProps {
  checked?: boolean;
  onChange?: React.ChangeEventHandler<HTMLInputElement>;
  label?: React.ReactNode;
  /** Secondary line under the label */
  hint?: React.ReactNode;
  disabled?: boolean;
  id?: string;
}
export function Checkbox(props: CheckboxProps): JSX.Element;
