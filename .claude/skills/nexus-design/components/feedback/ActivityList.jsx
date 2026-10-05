import React from 'react';

const TONES = {
  brass: 'var(--nx-brass)',
  positive: 'var(--nx-pos)',
  warning: 'var(--nx-warn)',
  negative: 'var(--nx-neg)',
  info: 'var(--nx-info)',
  neutral: 'var(--muted-foreground)',
};

export function ActivityList({ items = [], style }) {
  return (
    <ol style={{ listStyle: 'none', margin: 0, padding: 0, ...style }}>
      {items.map(function (it, i) {
        const last = i === items.length - 1;
        return (
          <li key={i} style={{ display: 'flex', gap: 'var(--nx-s-4)' }}>
            <div style={{ flex: 'none', display: 'flex', flexDirection: 'column', alignItems: 'center', width: 9 }}>
              <span style={{
                width: 7, height: 7, borderRadius: 999, marginTop: 6, flex: 'none',
                background: TONES[it.tone || 'neutral'],
              }} />
              {!last ? <span style={{ flex: 1, width: 1, background: 'var(--border)', marginTop: 4 }} /> : null}
            </div>
            <div style={{ flex: 1, minWidth: 0, paddingBottom: last ? 0 : 'var(--nx-s-5)' }}>
              <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', gap: 12 }}>
                <span style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 600, textWrap: 'pretty' }}>{it.title}</span>
                {it.time ? <span className="nx-mono" style={{ flex: 'none', color: 'var(--muted-foreground)' }}>{it.time}</span> : null}
              </div>
              {it.description ? (
                <div className="nx-meta" style={{ marginTop: 3, textWrap: 'pretty' }}>{it.description}</div>
              ) : null}
            </div>
          </li>
        );
      })}
    </ol>
  );
}
