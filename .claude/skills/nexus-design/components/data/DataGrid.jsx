import React from 'react';

export function DataGrid({ items = [], columns = 2, style }) {
  return (
    <div style={{
      display: 'grid',
      gridTemplateColumns: 'repeat(' + columns + ', minmax(0, 1fr))',
      gap: 'var(--nx-s-5) var(--nx-s-6)',
      ...style,
    }}>
      {items.map(function (it, i) {
        return (
          <div key={i} style={{ display: 'flex', flexDirection: 'column', gap: 3, minWidth: 0, gridColumn: it.span ? 'span ' + it.span : undefined }}>
            <span className="nx-label">{it.label}</span>
            <span style={{
              fontSize: it.emphasis ? 'var(--nx-fs-lg)' : 'var(--nx-fs-body)',
              fontWeight: it.emphasis ? 700 : 500,
              fontFamily: it.mono ? 'var(--nx-font-mono)' : 'var(--nx-font-body)',
              fontVariantNumeric: it.mono ? 'tabular-nums' : undefined,
              color: it.emphasis ? 'var(--nx-brass-hi)' : 'var(--foreground)',
              textWrap: 'pretty',
            }}>{it.value != null && it.value !== '' ? it.value : '\u2014'}</span>
          </div>
        );
      })}
    </div>
  );
}
