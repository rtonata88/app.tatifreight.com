import React from 'react';

const TONES = {
  neutral: { color: 'var(--muted-foreground)', background: 'var(--secondary)' },
  brass: { color: 'var(--nx-brass-hi)', background: 'var(--nx-brass-wash-2)' },
  positive: { color: 'var(--nx-pos)', background: 'var(--nx-pos-wash)' },
  warning: { color: 'var(--nx-warn)', background: 'var(--nx-warn-wash)' },
  negative: { color: 'var(--nx-neg)', background: 'var(--nx-neg-wash)' },
  info: { color: 'var(--nx-info)', background: 'var(--nx-info-wash)' },
};

export function Badge({ children, tone = 'neutral', mono = false, style }) {
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 6,
      padding: '2px 8px',
      fontFamily: mono ? 'var(--nx-font-mono)' : 'var(--nx-font-body)',
      fontSize: 'var(--nx-fs-micro)', fontWeight: 600, lineHeight: 1.7,
      borderRadius: 'var(--nx-r-2)', whiteSpace: 'nowrap',
      ...TONES[tone], ...style,
    }}>
      {children}
    </span>
  );
}
