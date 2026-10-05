import * as React from 'react';

/** Bare text input: no box, one hairline underneath, brass on focus. */
export interface InputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'prefix' | 'style'> {
  value?: string | number;
  onChange?: React.ChangeEventHandler<HTMLInputElement>;
  placeholder?: string;
  type?: string;
  /** Static leading text, e.g. "NAD" */
  prefix?: React.ReactNode;
  /** Static trailing text, e.g. "hrs" or "%" */
  suffix?: React.ReactNode;
  invalid?: boolean;
  disabled?: boolean;
  /** Tabular mono - use for money, hours and reference codes */
  mono?: boolean;
  align?: 'left' | 'right';
  style?: React.CSSProperties;
}
export function Input(props: InputProps): JSX.Element;
