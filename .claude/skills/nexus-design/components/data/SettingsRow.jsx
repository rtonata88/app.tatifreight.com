import React from 'react';

export function SettingsRow({ label, description, children, action, last = false, stacked = false, style }) {
  return (
    <div style={{
      display: 'flex', flexDirection: stacked ? 'column' : 'row',
      alignItems: stacked ? 'stretch' : 'flex-start',
      justifyContent: 'space-between',
      gap: stacked ? 'var(--nx-s-3)' : 'var(--nx-s-6)',
      flexWrap: 'wrap',
      padding: 'var(--nx-s-5) 0',
      borderBottom: last ? 'none' : '1px solid var(--border)',
      ...style,
    }}>
      <div style={{ flex: stacked ? undefined : '1 1 240px', minWidth: 0 }}>
        <div style={{ fontSize: 'var(--nx-fs-md)', fontWeight: 600 }}>{label}</div>
        {description ? (
          <p className="nx-meta" style={{ margin: '3px 0 0', maxWidth: 460, textWrap: 'pretty' }}>{description}</p>
        ) : null}
      </div>
      {children != null || action ? (
        <div style={{
          flex: stacked ? undefined : '0 1 300px', minWidth: 0,
          display: 'flex', alignItems: 'center', justifyContent: 'flex-end',
          gap: 'var(--nx-s-4)',
        }}>
          {children != null ? <div style={{ flex: 1, minWidth: 0 }}>{children}</div> : null}
          {action ? <div style={{ flex: 'none' }}>{action}</div> : null}
        </div>
      ) : null}
    </div>
  );
}
