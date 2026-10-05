import React from 'react';

export function EmptyState({ title, description, action, icon = null, compact = false, style }) {
  return (
    <div style={{
      display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
      textAlign: 'center', gap: 'var(--nx-s-3)',
      padding: compact ? 'var(--nx-s-7) var(--nx-s-5)' : 'var(--nx-s-10) var(--nx-s-6)',
      ...style,
    }}>
      {icon ? <span style={{ color: 'var(--nx-fg-4)', display: 'flex' }}>{icon}</span> : null}
      <div>
        <div className="nx-h4" style={{ marginBottom: 6 }}>{title}</div>
        {description ? (
          <p className="nx-meta" style={{ margin: 0, maxWidth: 380, textWrap: 'pretty' }}>{description}</p>
        ) : null}
      </div>
      {action ? <div style={{ marginTop: 'var(--nx-s-2)' }}>{action}</div> : null}
    </div>
  );
}
