import React from 'react';

const TONES = {
  neutral: 'var(--muted-foreground)',
  brass: 'var(--nx-brass)',
  positive: 'var(--nx-pos)',
  negative: 'var(--nx-neg)',
};

export function IconButton({ children, label, tone = 'neutral', size = 28, onClick, disabled = false, style, ...rest }) {
  const [hover, setHover] = React.useState(false);
  const color = TONES[tone];
  return (
    <button
      type="button" aria-label={label} title={label} onClick={onClick} disabled={disabled}
      className="nx-focusable"
      onMouseEnter={() => setHover(true)} onMouseLeave={() => setHover(false)}
      style={{
        width: size, height: size,
        display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
        borderRadius: 'var(--nx-r-2)',
        border: '1px solid ' + (hover ? color : 'var(--border)'),
        background: hover ? 'var(--secondary)' : 'transparent',
        color: hover ? color : 'var(--muted-foreground)',
        cursor: disabled ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.4 : 1,
        transition: 'background var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
        ...style,
      }}
      {...rest}
    >
      {children}
    </button>
  );
}
