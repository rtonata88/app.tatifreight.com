import React from 'react';

export function DossierRow({ label, children, meta, last = false, style }) {
  return (
    <div style={{
      display: 'flex', alignItems: 'baseline', gap: 'var(--nx-s-4)',
      flexWrap: 'wrap',
      padding: 'var(--nx-s-3) 0',
      borderBottom: last ? 'none' : '1px dotted var(--input)',
      minWidth: 0, ...style,
    }}>
      <span style={{
        flex: '0 1 150px', minWidth: 0,
        fontFamily: 'var(--nx-font-body)', fontWeight: 500,
        fontSize: 'var(--nx-fs-body)', color: 'var(--muted-foreground)',
      }}>{label}</span>
      <span style={{ flex: 1, minWidth: 0, fontSize: 'var(--nx-fs-body)', fontWeight: 500, textWrap: 'pretty' }}>
        {children != null && children !== '' ? children : '\u2014'}
      </span>
      {meta ? <span className="nx-mono" style={{ flex: 'none', color: 'var(--muted-foreground)' }}>{meta}</span> : null}
    </div>
  );
}
