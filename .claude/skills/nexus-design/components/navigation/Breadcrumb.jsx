import React from 'react';

export function Breadcrumb({ items = [], onNavigate, style }) {
  return (
    <nav aria-label="Breadcrumb" style={{ display: 'flex', alignItems: 'center', gap: 8, minWidth: 0, ...style }}>
      {items.map(function (raw, i) {
        const it = typeof raw === 'string' ? { label: raw } : raw;
        const last = i === items.length - 1;
        return (
          <React.Fragment key={i}>
            {i > 0 ? <span aria-hidden="true" style={{ color: 'var(--nx-fg-4)', fontSize: 'var(--nx-fs-small)' }}>/</span> : null}
            <span
              onClick={!last && onNavigate && it.key ? function () { onNavigate(it.key); } : undefined}
              style={{
                fontSize: 'var(--nx-fs-small)',
                fontWeight: last ? 600 : 400,
                color: last ? 'var(--foreground)' : 'var(--muted-foreground)',
                cursor: !last && onNavigate && it.key ? 'pointer' : 'default',
                whiteSpace: 'nowrap',
              }}
            >{it.label}</span>
          </React.Fragment>
        );
      })}
    </nav>
  );
}
