import * as React from 'react';

export interface TabItem { key: string; label: React.ReactNode; count?: number; }

/** In-record tabs - contacts / notes / attachments on a client, or status filters on an index. */
export interface TabsProps {
  tabs: TabItem[];
  activeKey?: string;
  onChange?: (key: string) => void;
  style?: React.CSSProperties;
}
export function Tabs(props: TabsProps): JSX.Element;
