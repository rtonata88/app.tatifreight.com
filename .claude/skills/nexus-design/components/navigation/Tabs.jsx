import React from 'react';

export function Tabs({ tabs = [], activeKey, onChange, style }) {
  return (
    <div role="tablist" style={{
      display: 'flex', alignItems: 'stretch', gap: 'var(--nx-s-5)',
      borderBottom: '1px solid var(--border)', ...style,
    }}>
      {tabs.map(function (t) {
        const active = t.key === activeKey;
        return (
          <button
            key={t.key} role="tab" aria-selected={active} type="button" className="nx-focusable"
            onClick={function () { if (onChange) onChange(t.key); }}
            style={{
              display: 'flex', alignItems: 'center', gap: 8,
              padding: '0 0 10px', background: 'transparent', border: 0,
              borderBottom: '2px solid ' + (active ? 'var(--nx-brass)' : 'transparent'),
              marginBottom: -1, cursor: 'pointer',
              fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)',
              fontWeight: active ? 700 : 500,
              color: active ? 'var(--foreground)' : 'var(--muted-foreground)',
              transition: 'color var(--nx-dur) var(--nx-ease), border-color var(--nx-dur) var(--nx-ease)',
            }}
          >
            {t.label}
            {t.count != null ? (
              <span style={{
                fontFamily: 'var(--nx-font-mono)', fontSize: 10,
                color: active ? 'var(--nx-brass-hi)' : 'var(--muted-foreground)',
              }}>{t.count}</span>
            ) : null}
          </button>
        );
      })}
    </div>
  );
}
