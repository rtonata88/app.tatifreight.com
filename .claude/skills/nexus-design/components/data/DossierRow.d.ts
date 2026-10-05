import * as React from 'react';

/**
 * The dossier line: italic key on the left, plain value, mono meta on the right,
 * separated by a dotted hairline. The signature pattern of Nexus detail views.
 * @startingPoint section="Data" subtitle="Dossier lines with dotted hairlines" viewport="700x200"
 */
export interface DossierRowProps {
  /** Italic display-serif key, e.g. "Account holder" */
  label: React.ReactNode;
  /** The value */
  children?: React.ReactNode;
  /** Right-hand mono annotation - timestamp, reference, actor */
  meta?: React.ReactNode;
  /** Drops the bottom rule on the final row */
  last?: boolean;
  style?: React.CSSProperties;
}
export function DossierRow(props: DossierRowProps): JSX.Element;
