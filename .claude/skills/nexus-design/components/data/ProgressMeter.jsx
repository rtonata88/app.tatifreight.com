import React from 'react';

const TONES = {
  brass: 'var(--nx-brass)',
  positive: 'var(--nx-pos)',
  warning: 'var(--nx-warn)',
  negative: 'var(--nx-neg)',
  info: 'var(--nx-info)',
};

export function ProgressMeter({ label, value, max = 100, display, tone = 'brass', meta, style }) {
  const pct = Math.max(0, Math.min(100, (value / max) * 100));
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, minWidth: 0, ...style }}>
      <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', gap: 12 }}>
        <span style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 500 }}>{label}</span>
        <span className="nx-mono" style={{ color: 'var(--foreground)', flex: 'none' }}>
          {display != null ? display : Math.round(pct) + '%'}
        </span>
      </div>
      <div style={{ height: 4, background: 'var(--secondary)', borderRadius: 999, overflow: 'hidden' }}>
        <div style={{
          width: pct + '%', height: '100%', background: TONES[tone],
          transition: 'width var(--nx-dur-slow) var(--nx-ease)',
        }} />
      </div>
      {meta ? <div className="nx-meta">{meta}</div> : null}
    </div>
  );
}
