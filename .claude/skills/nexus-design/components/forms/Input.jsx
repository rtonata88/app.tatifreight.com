import React from 'react';

export function Input({
  value, onChange, placeholder, type = 'text', prefix, suffix,
  invalid = false, disabled = false, mono = false, align = 'left', id, style, ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const line = invalid ? 'var(--destructive)' : focus ? 'var(--nx-brass)' : 'var(--input)';
  return (
    <div style={{
      display: 'flex', alignItems: 'center', gap: 8,
      height: 'var(--nx-control-h)',
      borderBottom: '1px solid ' + line,
      transition: 'border-color var(--nx-dur) var(--nx-ease)',
      opacity: disabled ? 0.5 : 1,
      ...style,
    }}>
      {prefix ? <span className="nx-meta" style={{ flex: 'none' }}>{prefix}</span> : null}
      <input
        id={id} type={type} value={value} placeholder={placeholder} disabled={disabled}
        onChange={onChange} onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
        style={{
          flex: 1, minWidth: 0, height: '100%',
          background: 'transparent', border: 0, outline: 'none', padding: 0,
          color: 'var(--foreground)', textAlign: align,
          fontFamily: mono ? 'var(--nx-font-mono)' : 'var(--nx-font-body)',
          fontSize: 'var(--nx-fs-body)', fontWeight: 500,
          fontVariantNumeric: mono ? 'tabular-nums' : undefined,
        }}
        {...rest}
      />
      {suffix ? <span className="nx-meta" style={{ flex: 'none' }}>{suffix}</span> : null}
    </div>
  );
}
