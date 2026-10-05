import React from 'react';

export function MetricCard({ label, value, delta, deltaTone = 'neutral', meta, emphasis = false, style }) {
  const tone = { positive: 'var(--nx-pos)', negative: 'var(--nx-neg)', neutral: 'var(--muted-foreground)' }[deltaTone];
  return (
    <div style={{
      background: emphasis
        ? 'radial-gradient(circle at top right, var(--nx-brass-wash), transparent 65%), var(--card)'
        : 'var(--card)',
      border: '1px solid ' + (emphasis ? 'var(--nx-rule-brass)' : 'var(--border)'),
      borderRadius: 'var(--nx-r-4)',
      padding: 'var(--nx-s-5) var(--nx-s-5) var(--nx-s-4)',
      display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-3)', minWidth: 0,
      ...style,
    }}>
      <div className="nx-eyebrow" style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{label}</div>
      <div className="nx-num" style={{
        fontSize: 'var(--nx-fs-2xl)', lineHeight: 1,
        color: emphasis ? 'var(--nx-brass-hi)' : 'var(--foreground)',
      }}>{value}</div>
      {(delta || meta) ? (
        <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, minWidth: 0 }}>
          {delta ? (
            <span style={{
              fontFamily: 'var(--nx-font-mono)', fontSize: 'var(--nx-fs-micro)',
              fontWeight: 500, color: tone, whiteSpace: 'nowrap',
            }}>{delta}</span>
          ) : null}
          {meta ? <span className="nx-meta" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{meta}</span> : null}
        </div>
      ) : null}
    </div>
  );
}
