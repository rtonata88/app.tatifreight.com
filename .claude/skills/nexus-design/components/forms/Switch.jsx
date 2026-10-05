import React from 'react';

export function Switch({ checked = false, onChange, label, id, disabled = false, style }) {
  return (
    <label htmlFor={id} style={{
      display: 'inline-flex', alignItems: 'center', gap: 10,
      cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.5 : 1, ...style,
    }}>
      <span style={{
        position: 'relative', flex: 'none', width: 34, height: 18,
        borderRadius: 'var(--nx-r-pill)',
        border: '1px solid ' + (checked ? 'var(--nx-brass)' : 'var(--input)'),
        background: checked ? 'var(--nx-brass-wash-2)' : 'transparent',
        transition: 'background var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease)',
      }}>
        <input id={id} type="checkbox" checked={checked} onChange={onChange} disabled={disabled}
          style={{ position: 'absolute', inset: 0, opacity: 0, margin: 0, cursor: 'inherit' }} />
        <span style={{
          position: 'absolute', top: 2, left: checked ? 18 : 2,
          width: 12, height: 12, borderRadius: 999,
          background: checked ? 'var(--nx-brass)' : 'var(--muted-foreground)',
          transition: 'left var(--nx-dur) var(--nx-ease), background var(--nx-dur) var(--nx-ease)',
        }} />
      </span>
      {label ? <span style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 500 }}>{label}</span> : null}
    </label>
  );
}
