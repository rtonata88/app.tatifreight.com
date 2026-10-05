import React from 'react';

const TONES = {
  success: { color: 'var(--nx-pos)', wash: 'var(--nx-pos-wash)' },
  warning: { color: 'var(--nx-warn)', wash: 'var(--nx-warn-wash)' },
  error: { color: 'var(--nx-neg)', wash: 'var(--nx-neg-wash)' },
  info: { color: 'var(--nx-info)', wash: 'var(--nx-info-wash)' },
};

export function Alert({ tone = 'info', title, children, action = null, onDismiss, style }) {
  const t = TONES[tone];
  return (
    <div role="status" style={{
      display: 'flex', alignItems: 'flex-start', gap: 'var(--nx-s-4)',
      padding: 'var(--nx-s-4) var(--nx-s-5)',
      background: t.wash, borderRadius: 'var(--nx-r-4)',
      border: '1px solid ' + t.color,
      ...style,
    }}>
      <span aria-hidden="true" style={{ flex: 'none', width: 5, height: 5, borderRadius: 999, background: t.color, marginTop: 7 }} />
      <div style={{ flex: 1, minWidth: 0 }}>
        {title ? <div style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 700, color: t.color, marginBottom: children ? 4 : 0 }}>{title}</div> : null}
        {children ? <div style={{ fontSize: 'var(--nx-fs-body)', color: 'var(--foreground)', textWrap: 'pretty' }}>{children}</div> : null}
      </div>
      {action ? <div style={{ flex: 'none' }}>{action}</div> : null}
      {onDismiss ? (
        <button type="button" aria-label="Dismiss" onClick={onDismiss} className="nx-focusable" style={{
          flex: 'none', background: 'transparent', border: 0, cursor: 'pointer',
          color: 'var(--muted-foreground)', fontSize: 14, lineHeight: 1, padding: 2,
        }}>{'\u00d7'}</button>
      ) : null}
    </div>
  );
}
