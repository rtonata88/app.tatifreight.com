import React from 'react';

export function Checkbox({ checked = false, onChange, label, hint, disabled = false, id, style }) {
  return (
    <label htmlFor={id} style={{
      display: 'flex', alignItems: 'flex-start', gap: 10,
      cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.5 : 1, ...style,
    }}>
      <span style={{
        position: 'relative', flex: 'none', width: 16, height: 16, marginTop: 2,
        borderRadius: 'var(--nx-r-1)',
        border: '1px solid ' + (checked ? 'var(--nx-brass)' : 'var(--input)'),
        background: checked ? 'var(--nx-brass)' : 'transparent',
        transition: 'background var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease)',
      }}>
        <input
          id={id} type="checkbox" checked={checked} onChange={onChange} disabled={disabled}
          style={{ position: 'absolute', inset: 0, opacity: 0, margin: 0, cursor: 'inherit' }}
        />
        {checked ? (
          <span aria-hidden="true" style={{
            position: 'absolute', left: 4, top: 2, width: 6, height: 9,
            borderRight: '1.5px solid var(--nx-ink)', borderBottom: '1.5px solid var(--nx-ink)',
            transform: 'rotate(40deg)',
          }} />
        ) : null}
      </span>
      {(label || hint) ? (
        <span style={{ minWidth: 0 }}>
          <span style={{ display: 'block', fontSize: 'var(--nx-fs-body)', fontWeight: 500 }}>{label}</span>
          {hint ? <span className="nx-meta" style={{ display: 'block', marginTop: 2 }}>{hint}</span> : null}
        </span>
      ) : null}
    </label>
  );
}
