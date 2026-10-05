import React from 'react';

export function Sidebar({
  brand = 'Nexus', sections = [], activeKey, onNavigate,
  footer = null, style,
}) {
  const initialOpen = {};
  sections.forEach(function (s) {
    (s.items || []).forEach(function (it) {
      if (it.children && it.children.some(function (c) { return c.key === activeKey; })) initialOpen[it.key] = true;
    });
  });
  const [open, setOpen] = React.useState(initialOpen);
  const toggle = function (k) { setOpen(function (o) { const n = { ...o }; n[k] = !n[k]; return n; }); };

  return (
    <nav style={{
      width: 'var(--nx-rail)', flex: 'none', height: '100%',
      background: 'var(--sidebar)', color: 'var(--sidebar-foreground)',
      borderRight: '1px solid var(--sidebar-border)',
      display: 'flex', flexDirection: 'column', overflow: 'hidden',
      ...style,
    }}>
      <div style={{
        height: 'var(--nx-topbar)', flex: 'none', display: 'flex', alignItems: 'center',
        padding: '0 var(--nx-s-5)', borderBottom: '1px solid var(--sidebar-border)',
      }}>
        <span className="nx-display" style={{ fontSize: 'var(--nx-fs-lg)', letterSpacing: '0.02em' }}>{brand}</span>
      </div>

      <div style={{ flex: 1, overflowY: 'auto', padding: 'var(--nx-s-4) 0 var(--nx-s-6)' }}>
        {sections.map(function (section, si) {
          return (
            <div key={si} style={{ marginBottom: 'var(--nx-s-5)' }}>
              {section.title ? (
                <div className="nx-eyebrow" style={{ padding: '0 var(--nx-s-5) var(--nx-s-3)', fontSize: 10 }}>{section.title}</div>
              ) : null}
              {(section.items || []).map(function (item) {
                const kids = item.children || [];
                const isOpen = !!open[item.key];
                const selfActive = item.key === activeKey;
                const childActive = kids.some(function (c) { return c.key === activeKey; });
                return (
                  <div key={item.key}>
                    <NavRow
                      icon={item.icon} label={item.label} badge={item.badge}
                      active={selfActive || childActive}
                      caret={kids.length ? (isOpen ? 'open' : 'closed') : null}
                      onClick={function () {
                        if (kids.length) toggle(item.key);
                        else if (onNavigate) onNavigate(item.key);
                      }}
                    />
                    {kids.length && isOpen ? (
                      <div style={{ paddingBottom: 4 }}>
                        {kids.map(function (c) {
                          const active = c.key === activeKey;
                          return (
                            <button
                              key={c.key} type="button" className="nx-focusable"
                              onClick={function () { if (onNavigate) onNavigate(c.key); }}
                              style={{
                                display: 'block', width: '100%', textAlign: 'left',
                                padding: '6px var(--nx-s-5) 6px 46px',
                                background: 'transparent', border: 0, cursor: 'pointer',
                                fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)',
                                fontWeight: active ? 600 : 400,
                                color: active ? 'var(--sidebar-accent-foreground)' : 'var(--muted-foreground)',
                                transition: 'color var(--nx-dur) var(--nx-ease)',
                              }}
                            >{c.label}</button>
                          );
                        })}
                      </div>
                    ) : null}
                  </div>
                );
              })}
            </div>
          );
        })}
      </div>

      {footer ? (
        <div style={{ flex: 'none', padding: 'var(--nx-s-4) var(--nx-s-5)', borderTop: '1px solid var(--sidebar-border)' }}>{footer}</div>
      ) : null}
    </nav>
  );
}

function NavRow({ icon, label, badge, active, caret, onClick }) {
  const [hover, setHover] = React.useState(false);
  return (
    <button
      type="button" onClick={onClick} className="nx-focusable"
      onMouseEnter={function () { setHover(true); }} onMouseLeave={function () { setHover(false); }}
      style={{
        display: 'flex', alignItems: 'center', gap: 10, width: '100%',
        padding: '8px var(--nx-s-5)', textAlign: 'left',
        background: active ? 'var(--sidebar-accent)' : 'transparent',
        border: 0, borderLeft: '2px solid ' + (active ? 'var(--nx-brass)' : 'transparent'),
        cursor: 'pointer',
        fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)',
        fontWeight: active ? 600 : 500,
        color: active ? 'var(--sidebar-accent-foreground)' : (hover ? 'var(--foreground)' : 'var(--sidebar-foreground)'),
        transition: 'background var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
      }}
    >
      <span style={{ flex: 'none', display: 'flex', width: 16, justifyContent: 'center', opacity: active ? 1 : 0.7 }}>{icon}</span>
      <span style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{label}</span>
      {badge ? (
        <span style={{
          flex: 'none', fontFamily: 'var(--nx-font-mono)', fontSize: 10,
          color: 'var(--nx-brass-hi)', background: 'var(--nx-brass-wash-2)',
          borderRadius: 'var(--nx-r-1)', padding: '1px 5px',
        }}>{badge}</span>
      ) : null}
      {caret ? (
        <span aria-hidden="true" style={{
          flex: 'none', width: 5, height: 5,
          borderRight: '1px solid currentColor', borderBottom: '1px solid currentColor',
          transform: caret === 'open' ? 'rotate(45deg)' : 'rotate(-45deg)',
          opacity: 0.6, marginRight: 2,
        }} />
      ) : null}
    </button>
  );
}
