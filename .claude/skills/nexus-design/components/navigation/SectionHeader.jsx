import React from 'react';

export function SectionHeader({ title, action = null, meta = null, style }) {
  return (
    <div style={{
      display: 'flex', alignItems: 'baseline', justifyContent: 'space-between',
      gap: 'var(--nx-s-4)', marginBottom: 'var(--nx-s-4)', ...style,
    }}>
      <div style={{ display: 'flex', alignItems: 'baseline', gap: 12, minWidth: 0 }}>
        <h2 style={{
          margin: 0, fontFamily: 'var(--nx-font-body)', fontWeight: 700,
          fontSize: 'var(--nx-fs-lg)', letterSpacing: '-0.005em',
        }}>{title}</h2>
        {meta ? <span className="nx-meta">{meta}</span> : null}
      </div>
      {action ? <div style={{ flex: 'none' }}>{action}</div> : null}
    </div>
  );
}
