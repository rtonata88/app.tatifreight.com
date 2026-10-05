import React from 'react';

export function Modal({ open = true, title, subtitle, children, footer = null, onClose, width = 520 }) {
  if (!open) return null;
  return (
    <div
      role="dialog" aria-modal="true"
      style={{
        position: 'absolute', inset: 0, zIndex: 60,
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        padding: 'var(--nx-s-6)',
        background: 'rgba(6, 5, 4, 0.72)',
      }}
      onClick={onClose}
    >
      <div
        onClick={function (e) { e.stopPropagation(); }}
        style={{
          width: '100%', maxWidth: width, maxHeight: '100%', overflowY: 'auto',
          background: 'var(--popover)', color: 'var(--popover-foreground)',
          border: '1px solid var(--input)', borderRadius: 'var(--nx-r-5)',
          boxShadow: 'var(--nx-lift-overlay)',
        }}
      >
        <header style={{
          display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 'var(--nx-s-4)',
          padding: 'var(--nx-s-6) var(--nx-s-6) var(--nx-s-4)',
          borderBottom: '1px solid var(--border)',
        }}>
          <div style={{ minWidth: 0 }}>
            <h2 className="nx-h4">{title}</h2>
            {subtitle ? <div className="nx-meta" style={{ marginTop: 4 }}>{subtitle}</div> : null}
          </div>
          {onClose ? (
            <button type="button" aria-label="Close" onClick={onClose} className="nx-focusable" style={{
              flex: 'none', background: 'transparent', border: 0, cursor: 'pointer',
              color: 'var(--muted-foreground)', fontSize: 18, lineHeight: 1, padding: 2,
            }}>{'\u00d7'}</button>
          ) : null}
        </header>
        <div style={{ padding: 'var(--nx-s-6)' }}>{children}</div>
        {footer ? (
          <footer style={{
            display: 'flex', justifyContent: 'flex-end', gap: 10,
            padding: 'var(--nx-s-4) var(--nx-s-6) var(--nx-s-6)',
            borderTop: '1px solid var(--border)',
          }}>{footer}</footer>
        ) : null}
      </div>
    </div>
  );
}
