import React from 'react';

export function PageHeader({ eyebrow, title, subtitle, actions = null, meta = null, style }) {
  return (
    <header style={{
      display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between',
      gap: 'var(--nx-s-6)', flexWrap: 'wrap',
      paddingBottom: 'var(--nx-s-5)', marginBottom: 'var(--nx-section-gap)',
      borderBottom: '1px solid var(--border)',
      ...style,
    }}>
      <div style={{ minWidth: 0 }}>
        {eyebrow ? <div className="nx-eyebrow" style={{ marginBottom: 10 }}>{eyebrow}</div> : null}
        <h1 className="nx-h2" style={{ textWrap: 'pretty' }}>{title}</h1>
        {subtitle ? (
          <p style={{
            margin: '8px 0 0', maxWidth: 620, textWrap: 'pretty',
            fontFamily: 'var(--nx-font-body)', fontWeight: 400,
            fontSize: 'var(--nx-fs-md)', color: 'var(--muted-foreground)',
          }}>{subtitle}</p>
        ) : null}
        {meta ? <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginTop: 'var(--nx-s-3)', flexWrap: 'wrap' }}>{meta}</div> : null}
      </div>
      {actions ? <div style={{ display: 'flex', alignItems: 'center', gap: 10, flex: 'none' }}>{actions}</div> : null}
    </header>
  );
}
