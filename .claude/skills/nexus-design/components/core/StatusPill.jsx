import React from 'react';

const TONES = {
  active: 'var(--nx-pos)',
  approved: 'var(--nx-pos)',
  paid: 'var(--nx-pos)',
  pending: 'var(--nx-warn)',
  draft: 'var(--nx-warn)',
  submitted: 'var(--nx-warn)',
  completed: 'var(--nx-info)',
  declined: 'var(--nx-neg)',
  overdue: 'var(--nx-neg)',
  inactive: 'var(--muted-foreground)',
};

export function StatusPill({ status, children, dot = true, style }) {
  const key = String(status || '').toLowerCase();
  const color = TONES[key] || 'var(--muted-foreground)';
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 7,
      padding: '3px 10px',
      fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-micro)', fontWeight: 600,
      textTransform: 'uppercase', letterSpacing: 'var(--nx-track-wide)', lineHeight: 1.6,
      borderRadius: 'var(--nx-r-pill)', border: '1px solid ' + color, color,
      background: 'transparent', whiteSpace: 'nowrap',
      ...style,
    }}>
      {dot ? <span style={{ width: 5, height: 5, borderRadius: 999, background: color, flex: 'none' }} /> : null}
      {children || status}
    </span>
  );
}
