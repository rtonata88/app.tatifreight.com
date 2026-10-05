import * as React from 'react';

/**
 * Label + control + hint/error stack. Every Nexus input is wrapped in one.
 * @startingPoint section="Forms" subtitle="Bare inputs, uppercase labels, hairline underline" viewport="700x220"
 */
export interface FormFieldProps {
  /** Rendered uppercase, 11px, 0.18em tracking */
  label?: React.ReactNode;
  /** Helper copy under the control */
  hint?: React.ReactNode;
  /** Replaces the hint and turns the control oxblood */
  error?: React.ReactNode;
  required?: boolean;
  children?: React.ReactNode;
  htmlFor?: string;
  /** Grid column span when placed in a form grid */
  span?: number;
}
export function FormField(props: FormFieldProps): JSX.Element;
