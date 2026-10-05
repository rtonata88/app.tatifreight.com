import React from 'react';

export function FormField({ label, hint, error, required = false, children, htmlFor, span, style }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 6, minWidth: 0, gridColumn: span ? 'span ' + span : undefined, ...style }}>
      {label ? (
        <label htmlFor={htmlFor} className="nx-label">
          {label}
          {required ? <span style={{ color: 'var(--nx-brass)', marginLeft: 4 }}>*</span> : null}
        </label>
      ) : null}
      {children}
      {error ? (
        <div style={{ fontSize: 'var(--nx-fs-small)', color: 'var(--destructive)' }}>{error}</div>
      ) : hint ? (
        <div className="nx-meta">{hint}</div>
      ) : null}
    </div>
  );
}
