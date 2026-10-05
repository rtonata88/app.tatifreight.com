import * as React from 'react';

export interface SelectOption { value: string; label: string; }

/** Bare select. Same hairline treatment as Input, with a chevron drawn from borders. */
export interface SelectProps extends Omit<React.SelectHTMLAttributes<HTMLSelectElement>, 'style'> {
  value?: string;
  onChange?: React.ChangeEventHandler<HTMLSelectElement>;
  /** Strings or {value,label} objects */
  options?: Array<string | SelectOption>;
  /** Empty-value first option */
  placeholder?: string;
  invalid?: boolean;
  disabled?: boolean;
  style?: React.CSSProperties;
}
export function Select(props: SelectProps): JSX.Element;
