import React from 'react';

export function SettingsNav({ groups = [], activeKey, onNavigate, style }) {
  return (
    <nav aria-label="Settings" style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-6)', ...style }}>
      {groups.map(function (g, gi) {
        return (
          <div key={gi}>
            {g.title ? (
              <div className="nx-label" style={{ padding: '0 var(--nx-s-3) var(--nx-s-3)' }}>{g.title}</div>
            ) : null}
            <div style={{ display: 'flex', flexDirection: 'column' }}>
              {(g.items || []).map(function (it) {
                return <NavItem key={it.key} item={it} active={it.key === activeKey} onNavigate={onNavigate} />;
              })}
            </div>
          </div>
        );
      })}
    </nav>
  );
}

function NavItem({ item, active, onNavigate }) {
  const [hover, setHover] = React.useState(false);
  return (
    <button
      type="button" className="nx-focusable"
      onClick={function () { if (onNavigate) onNavigate(item.key); }}
      onMouseEnter={function () { setHover(true); }} onMouseLeave={function () { setHover(false); }}
      style={{
        display: 'flex', alignItems: 'center', gap: 8, width: '100%', textAlign: 'left',
        padding: '7px var(--nx-s-3)',
        background: active ? 'var(--accent)' : hover ? 'var(--secondary)' : 'transparent',
        border: 0, borderLeft: '2px solid ' + (active ? 'var(--primary)' : 'transparent'),
        borderRadius: '0 var(--nx-r-2) var(--nx-r-2) 0',
        cursor: 'pointer',
        fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)',
        fontWeight: active ? 600 : 400,
        color: active ? 'var(--accent-foreground)' : 'var(--foreground)',
        transition: 'background var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
      }}
    >
      <span style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{item.label}</span>
      {item.count != null ? (
        <span className="nx-mono" style={{ flex: 'none', fontSize: 10, color: 'var(--muted-foreground)' }}>{item.count}</span>
      ) : null}
    </button>
  );
}
