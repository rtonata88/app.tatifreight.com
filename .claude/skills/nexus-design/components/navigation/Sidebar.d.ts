import * as React from 'react';

export interface SidebarChild { key: string; label: React.ReactNode; }
export interface SidebarItem {
  key: string;
  label: React.ReactNode;
  /** 16px stroke icon */
  icon?: React.ReactNode;
  /** Count chip, e.g. pending approvals */
  badge?: React.ReactNode;
  /** Submenu; renders a disclosure caret and indents children */
  children?: SidebarChild[];
}
export interface SidebarSection { title?: React.ReactNode; items: SidebarItem[]; }

/**
 * The Nexus rail: darkest surface, brass left hairline on the active item,
 * one level of disclosure for module submenus.
 * @startingPoint section="Navigation" subtitle="Module rail with submenus" viewport="700x420"
 */
export interface SidebarProps {
  /** Wordmark set in display type - Nexus ships no logo mark */
  brand?: React.ReactNode;
  sections: SidebarSection[];
  /** Key of the current item or child */
  activeKey?: string;
  onNavigate?: (key: string) => void;
  /** Signed-in user block or environment note */
  footer?: React.ReactNode;
  style?: React.CSSProperties;
}
export function Sidebar(props: SidebarProps): JSX.Element;
