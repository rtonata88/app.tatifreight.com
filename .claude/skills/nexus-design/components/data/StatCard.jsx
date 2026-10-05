import React from 'react';

const TONES = {
  neutral: 'var(--foreground)',
  brass: 'var(--nx-brass-hi)',
  positive: 'var(--nx-pos)',
  negative: 'var(--nx-neg)',
  info: 'var(--nx-info)',
};

export function StatCard({ value, label, tone = 'neutral', style }) {
  return (
    <div style={{
      display: 'flex', flexDirection: 'column', gap: 4,
      padding: 'var(--nx-s-3) 0', minWidth: 0, ...style,
    }}>
      <div className="nx-num" style={{ fontSize: 'var(--nx-fs-xl)', lineHeight: 1.1, color: TONES[tone] }}>{value}</div>
      <div className="nx-meta" style={{ textWrap: 'pretty' }}>{label}</div>
    </div>
  );
}
