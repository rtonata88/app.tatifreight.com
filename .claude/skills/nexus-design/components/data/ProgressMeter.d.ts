import * as React from 'react';

/** 4px hairline-thin bar for leave balances, budget consumption, project progress, collection rate. */
export interface ProgressMeterProps {
  label: React.ReactNode;
  /** Current amount */
  value: number;
  /** Denominator; defaults to 100 */
  max?: number;
  /** Overrides the right-hand readout, e.g. "18 of 24 days" */
  display?: React.ReactNode;
  tone?: 'brass' | 'positive' | 'warning' | 'negative' | 'info';
  /** Caption under the bar */
  meta?: React.ReactNode;
  style?: React.CSSProperties;
}
export function ProgressMeter(props: ProgressMeterProps): JSX.Element;
