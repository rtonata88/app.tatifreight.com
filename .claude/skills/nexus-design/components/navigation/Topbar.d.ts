import * as React from 'react';

/** 56px application bar: breadcrumb left, record search right, then actions and the user block. */
export interface TopbarProps {
  /** A Breadcrumb, or plain text */
  breadcrumb?: React.ReactNode;
  search?: string;
  onSearch?: React.ChangeEventHandler<HTMLInputElement>;
  /** Global actions - notifications, mode switch */
  actions?: React.ReactNode;
  /** Avatar + name block for the signed-in user */
  user?: React.ReactNode;
  style?: React.CSSProperties;
}
export function Topbar(props: TopbarProps): JSX.Element;
