import React from 'react';

export function Card({
  title, subtitle, icon = null, actions = null, footer = null, children,
  emphasis = false, padding = 'md', bodyPad = true, style,
}) {
  const pad = padding === 'lg' ? 'var(--nx-card-pad-lg)' : padding === 'sm' ? 'var(--nx-s-4)' : 'var(--nx-card-pad)';
  const hasBody = children !== null && children !== undefined && children !== false;
  return (
    <section style={{
      background: emphasis
        ? 'radial-gradient(circle at top right, var(--nx-brass-wash), transparent 60%), var(--card)'
        : 'var(--card)',
      color: 'var(--card-foreground)',
      border: '1px solid ' + (emphasis ? 'var(--nx-rule-brass)' : 'var(--border)'),
      borderRadius: 'var(--nx-r-4)',
      boxShadow: emphasis ? 'var(--nx-lift-brass)' : 'var(--nx-lift-0)',
      display: 'flex', flexDirection: 'column', minWidth: 0,
      transition: 'border-color var(--nx-dur) var(--nx-ease), box-shadow var(--nx-dur) var(--nx-ease)',
      ...style,
    }}>
      {(title || actions) ? (
        <header style={{
          display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 'var(--nx-s-4)',
          padding: hasBody ? pad + ' ' + pad + ' var(--nx-s-4)' : pad,
          borderBottom: hasBody ? '1px solid var(--border)' : 'none',
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, minWidth: 0 }}>
            {icon ? <span style={{ color: 'var(--nx-brass)', display: 'flex', flex: 'none' }}>{icon}</span> : null}
            <div style={{ minWidth: 0 }}>
              <h3 className="nx-h4" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{title}</h3>
              {subtitle ? <div className="nx-meta" style={{ marginTop: 2 }}>{subtitle}</div> : null}
            </div>
          </div>
          {actions ? <div style={{ display: 'flex', alignItems: 'center', gap: 8, flex: 'none' }}>{actions}</div> : null}
        </header>
      ) : null}
      {hasBody ? (
        <div style={{ padding: bodyPad ? pad : 0, flex: 1, minWidth: 0 }}>{children}</div>
      ) : null}
      {footer ? (
        <footer style={{ padding: 'var(--nx-s-4) ' + pad, borderTop: '1px solid var(--border)' }}>{footer}</footer>
      ) : null}
    </section>
  );
}
