import * as React from 'react';

export interface SettingsNavItem {
  key: string;
  label: React.ReactNode;
  /** Record count, for list-management entries */
  count?: number;
}
export interface SettingsNavGroup { title?: React.ReactNode; items: SettingsNavItem[]; }

/**
 * The in-page settings rail: grouped links down the left, pane on the right.
 * Used instead of cards wherever a module has many small configuration surfaces.
 * @startingPoint section="Navigation" subtitle="In-page settings rail" viewport="700x420"
 */
export interface SettingsNavProps {
  groups: SettingsNavGroup[];
  activeKey?: string;
  onNavigate?: (key: string) => void;
  style?: React.CSSProperties;
}
export function SettingsNav(props: SettingsNavProps): JSX.Element;
