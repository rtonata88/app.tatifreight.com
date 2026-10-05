import * as React from 'react';

/**
 * One setting: name and explanation on the left, its control or current value on the
 * right, separated from the next by a hairline. The cardless alternative to wrapping
 * every field in its own Card.
 */
export interface SettingsRowProps {
  label: React.ReactNode;
  /** One or two lines saying what the setting does and what changing it affects */
  description?: React.ReactNode;
  /** The control, or the current value */
  children?: React.ReactNode;
  /** Trailing button — Edit, Change, Regenerate */
  action?: React.ReactNode;
  /** Drops the bottom rule on the last row of a section */
  last?: boolean;
  /** Control below the label instead of beside it — for textareas and wide tables */
  stacked?: boolean;
}
export function SettingsRow(props: SettingsRowProps): JSX.Element;
