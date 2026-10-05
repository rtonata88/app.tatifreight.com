import React from 'react';

const TONES = {
  success: 'var(--nx-pos)',
  warning: 'var(--nx-warn)',
  error: 'var(--nx-neg)',
  info: 'var(--nx-info)',
};

export function Toast({ tone = 'success', children, onDismiss, style }) {
  return (
    <div role="status" style={{
      display: 'flex', alignItems: 'center', gap: 'var(--nx-s-3)',
      padding: 'var(--nx-s-3) var(--nx-s-4)',
      background: 'var(--popover)', color: 'var(--popover-foreground)',
      border: '1px solid var(--input)', borderLeft: '2px solid ' + TONES[tone],
      borderRadius: 'var(--nx-r-3)', boxShadow: 'var(--nx-lift-popover)',
      maxWidth: 380, ...style,
    }}>
      <span style={{ flex: 1, minWidth: 0, fontSize: 'var(--nx-fs-body)', textWrap: 'pretty' }}>{children}</span>
      {onDismiss ? (
        <button type="button" aria-label="Dismiss" onClick={onDismiss} className="nx-focusable" style={{
          flex: 'none', background: 'transparent', border: 0, cursor: 'pointer',
          color: 'var(--muted-foreground)', fontSize: 14, lineHeight: 1,
        }}>{'\u00d7'}</button>
      ) : null}
    </div>
  );
}
