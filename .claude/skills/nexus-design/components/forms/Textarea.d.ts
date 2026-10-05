import * as React from 'react';

/** Multi-line input for notes, motivations and descriptions. Hairline underline only. */
export interface TextareaProps extends Omit<React.TextareaHTMLAttributes<HTMLTextAreaElement>, 'style'> {
  value?: string;
  onChange?: React.ChangeEventHandler<HTMLTextAreaElement>;
  placeholder?: string;
  rows?: number;
  invalid?: boolean;
  disabled?: boolean;
  style?: React.CSSProperties;
}
export function Textarea(props: TextareaProps): JSX.Element;
