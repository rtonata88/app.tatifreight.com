import React from 'react';

export function Textarea({ value, onChange, placeholder, rows = 3, invalid = false, disabled = false, id, style, ...rest }) {
  const [focus, setFocus] = React.useState(false);
  const line = invalid ? 'var(--destructive)' : focus ? 'var(--nx-brass)' : 'var(--input)';
  return (
    <textarea
      id={id} value={value} onChange={onChange} placeholder={placeholder} rows={rows} disabled={disabled}
      onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
      style={{
        width: '100%', resize: 'vertical', padding: '8px 0',
        background: 'transparent', border: 0, borderBottom: '1px solid ' + line,
        outline: 'none', color: 'var(--foreground)',
        fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)', fontWeight: 500,
        lineHeight: 'var(--nx-lh-body)',
        transition: 'border-color var(--nx-dur) var(--nx-ease)',
        opacity: disabled ? 0.5 : 1, ...style,
      }}
      {...rest}
    />
  );
}
