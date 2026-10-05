import React from 'react';

export function Select({ value, onChange, options = [], placeholder, invalid = false, disabled = false, id, style, ...rest }) {
  const [focus, setFocus] = React.useState(false);
  const line = invalid ? 'var(--destructive)' : focus ? 'var(--nx-brass)' : 'var(--input)';
  return (
    <div style={{
      position: 'relative', height: 'var(--nx-control-h)',
      borderBottom: '1px solid ' + line,
      transition: 'border-color var(--nx-dur) var(--nx-ease)',
      opacity: disabled ? 0.5 : 1, ...style,
    }}>
      <select
        id={id} value={value} onChange={onChange} disabled={disabled}
        onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
        style={{
          width: '100%', height: '100%', appearance: 'none', WebkitAppearance: 'none',
          background: 'transparent', border: 0, outline: 'none', padding: '0 20px 0 0',
          color: 'var(--foreground)', fontFamily: 'var(--nx-font-body)',
          fontSize: 'var(--nx-fs-body)', fontWeight: 500, cursor: disabled ? 'not-allowed' : 'pointer',
        }}
        {...rest}
      >
        {placeholder ? <option value="">{placeholder}</option> : null}
        {options.map(function (o) {
          const opt = typeof o === 'string' ? { value: o, label: o } : o;
          return <option key={opt.value} value={opt.value}>{opt.label}</option>;
        })}
      </select>
      <span aria-hidden="true" style={{
        position: 'absolute', right: 2, top: '50%', marginTop: -2,
        width: 7, height: 7, borderRight: '1px solid var(--muted-foreground)',
        borderBottom: '1px solid var(--muted-foreground)', transform: 'rotate(45deg)',
        pointerEvents: 'none',
      }} />
    </div>
  );
}
