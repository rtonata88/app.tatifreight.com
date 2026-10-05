import * as React from 'react';

export interface RadioPillOption {
  value: string;
  label: React.ReactNode;
  /** One short line under the label */
  description?: React.ReactNode;
  /** 16px stroke icon above the label */
  icon?: React.ReactNode;
}

/**
 * Two or three mutually exclusive choices shown as cards, each carrying a
 * consequence — the React counterpart of the app's `ep-type-pill` group
 * (e.g. "Draft · save for later" vs "Submit · for approval").
 */
export interface RadioPillsProps {
  options: RadioPillOption[];
  value?: string;
  onChange?: (value: string) => void;
  /** Radio group name */
  name?: string;
  /** Grid columns; defaults to one per option */
  columns?: number;
}
export function RadioPills(props: RadioPillsProps): JSX.Element;
