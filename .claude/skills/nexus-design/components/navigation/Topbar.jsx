import React from 'react';

export function Topbar({ breadcrumb = null, search, onSearch, actions = null, user, style }) {
  const [focus, setFocus] = React.useState(false);
  return (
    <header style={{
      height: 'var(--nx-topbar)', flex: 'none',
      display: 'flex', alignItems: 'center', gap: 'var(--nx-s-6)',
      padding: '0 var(--nx-gutter)',
      background: 'var(--background)',
      borderBottom: '1px solid var(--border)',
      ...style,
    }}>
      <div style={{ flex: 'none', minWidth: 0 }}>{breadcrumb}</div>

      <div style={{
        flex: 1, maxWidth: 340, minWidth: 0, marginLeft: 'auto',
        display: 'flex', alignItems: 'center', gap: 8,
        height: 30, padding: '0 2px',
        borderBottom: '1px solid ' + (focus ? 'var(--nx-brass)' : 'var(--border)'),
        transition: 'border-color var(--nx-dur) var(--nx-ease)',
      }}>
        <input
          type="search" value={search} placeholder="Search records"
          onChange={onSearch} onFocus={function () { setFocus(true); }} onBlur={function () { setFocus(false); }}
          style={{
            flex: 1, minWidth: 0, background: 'transparent', border: 0, outline: 'none',
            color: 'var(--foreground)', fontFamily: 'var(--nx-font-body)', fontSize: 'var(--nx-fs-body)',
          }}
        />
      </div>

      {actions ? <div style={{ flex: 'none', display: 'flex', alignItems: 'center', gap: 8 }}>{actions}</div> : null}
      {user ? <div style={{ flex: 'none', display: 'flex', alignItems: 'center', gap: 10 }}>{user}</div> : null}
    </header>
  );
}
