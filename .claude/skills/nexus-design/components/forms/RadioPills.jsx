import React from 'react';

export function RadioPills({ options = [], value, onChange, name, columns, style }) {
  return (
    <div style={{
      display: 'grid',
      gridTemplateColumns: 'repeat(' + (columns || options.length || 1) + ', minmax(0, 1fr))',
      gap: 'var(--nx-s-3)', ...style,
    }}>
      {options.map(function (o) {
        const active = o.value === value;
        return (
          <label
            key={o.value} htmlFor={(name || 'pill') + '-' + o.value}
            style={{
              display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6,
              padding: 'var(--nx-s-4) var(--nx-s-3)', textAlign: 'center', cursor: 'pointer',
              borderRadius: 'var(--nx-r-4)',
              border: '1px solid ' + (active ? 'var(--nx-rule-brass)' : 'var(--border)'),
              background: active ? 'var(--nx-brass-wash-2)' : 'var(--secondary)',
              transition: 'background var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease)',
            }}
          >
            <input
              id={(name || 'pill') + '-' + o.value} type="radio" name={name}
              checked={active} onChange={function () { if (onChange) onChange(o.value); }}
              style={{ position: 'absolute', opacity: 0, pointerEvents: 'none' }}
            />
            {o.icon ? (
              <span style={{ color: active ? 'var(--nx-brass-hi)' : 'var(--muted-foreground)', display: 'flex' }}>{o.icon}</span>
            ) : null}
            <span style={{
              fontSize: 'var(--nx-fs-body)', fontWeight: 600,
              color: active ? 'var(--nx-brass-hi)' : 'var(--foreground)',
            }}>{o.label}</span>
            {o.description ? <span className="nx-meta" style={{ fontSize: 11 }}>{o.description}</span> : null}
          </label>
        );
      })}
    </div>
  );
}
