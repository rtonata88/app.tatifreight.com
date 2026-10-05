import React from 'react';

const SIZES = {
  sm: { height: 'var(--nx-control-h-sm)', padding: '0 10px', fontSize: 'var(--nx-fs-micro)' },
  md: { height: 'var(--nx-control-h)', padding: '0 14px', fontSize: 'var(--nx-fs-body)' },
  lg: { height: 'var(--nx-control-h-lg)', padding: '0 20px', fontSize: 'var(--nx-fs-md)' },
};

const VARIANTS = {
  primary: { background: 'var(--primary)', color: 'var(--primary-foreground)', borderColor: 'var(--primary)' },
  ghost: { background: 'transparent', color: 'var(--foreground)', borderColor: 'var(--input)' },
  text: { background: 'transparent', color: 'var(--muted-foreground)', borderColor: 'transparent' },
  danger: { background: 'transparent', color: 'var(--destructive)', borderColor: 'var(--destructive)' },
};

const HOVER = {
  primary: { background: 'var(--nx-brass-hi)', borderColor: 'var(--nx-brass-hi)', color: 'var(--nx-ink)' },
  ghost: { borderColor: 'var(--nx-brass)', color: 'var(--nx-brass-hi)' },
  text: { color: 'var(--nx-brass-hi)' },
  danger: { background: 'var(--nx-neg-wash)', color: 'var(--destructive)', borderColor: 'var(--destructive)' },
};

export function Button({
  children, variant = 'ghost', size = 'md', icon = null, iconAfter = null,
  disabled = false, block = false, type = 'button', onClick, style, ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const base = {
    display: block ? 'flex' : 'inline-flex',
    width: block ? '100%' : undefined,
    alignItems: 'center', justifyContent: 'center', gap: 8,
    fontFamily: 'var(--nx-font-body)', fontWeight: 600, letterSpacing: '0.01em',
    borderRadius: 'var(--nx-r-3)', border: '1px solid',
    cursor: disabled ? 'not-allowed' : 'pointer',
    opacity: disabled ? 0.4 : 1, whiteSpace: 'nowrap',
    transition: 'background var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
    ...SIZES[size],
    ...VARIANTS[variant],
    ...(hover && !disabled ? HOVER[variant] : null),
    ...(variant === 'text' ? { padding: '0 4px', height: 'auto' } : null),
    ...style,
  };
  return (
    <button type={type} disabled={disabled} onClick={onClick} style={base} className="nx-focusable"
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)} {...rest}>
      {icon}{children}{iconAfter}
    </button>
  );
}