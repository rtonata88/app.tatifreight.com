import React from 'react';

const DOTS = { active: 'var(--nx-pos)', pending: 'var(--nx-warn)', inactive: 'var(--muted-foreground)' };

export function Avatar({ name = '', size = 40, status, brass = false, style }) {
  const initials = String(name).trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase();
  const dot = DOTS[status];
  return (
    <div style={{ position: 'relative', flex: 'none', width: size, height: size, ...style }}>
      <div style={{
        width: '100%', height: '100%',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        borderRadius: 'var(--nx-r-3)',
        border: '1px solid ' + (brass ? 'var(--nx-rule-brass)' : 'var(--input)'),
        background: brass ? 'var(--nx-brass-wash)' : 'var(--secondary)',
        color: brass ? 'var(--nx-brass-hi)' : 'var(--foreground)',
        fontFamily: 'var(--nx-font-display)', fontWeight: 700,
        fontSize: Math.max(10, Math.round(size * 0.36)),
        letterSpacing: '0.02em',
      }}>{initials || '\u2014'}</div>
      {dot ? (
        <span style={{
          position: 'absolute', right: -2, bottom: -2,
          width: Math.max(7, Math.round(size * 0.2)), height: Math.max(7, Math.round(size * 0.2)),
          borderRadius: 999, background: dot,
          boxShadow: '0 0 0 2px var(--card)',
        }} />
      ) : null}
    </div>
  );
}
