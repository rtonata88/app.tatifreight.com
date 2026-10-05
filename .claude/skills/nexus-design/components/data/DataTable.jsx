import React from 'react';

export function DataTable({
  columns = [], rows = [], dense = false, hoverable = true,
  onRowClick, emptyState = null, footer = null, style,
}) {
  const [hovered, setHovered] = React.useState(-1);
  const rowH = dense ? 'var(--nx-row-h-sm)' : 'var(--nx-row-h)';

  if (!rows.length && emptyState) return emptyState;

  return (
    <div style={{ width: '100%', overflowX: 'auto', ...style }}>
      <table style={{ width: '100%', borderCollapse: 'collapse', fontFamily: 'var(--nx-font-body)' }}>
        <thead>
          <tr>
            {columns.map(function (c) {
              return (
                <th key={c.key} style={{
                  textAlign: c.align || 'left',
                  padding: '0 var(--nx-s-4)', height: 'var(--nx-row-h-sm)',
                  width: c.width, whiteSpace: 'nowrap',
                  background: 'var(--secondary)',
                  borderBottom: '1px solid var(--border)',
                  fontSize: 'var(--nx-fs-micro)', fontWeight: 600,
                  textTransform: 'uppercase', letterSpacing: 'var(--nx-track-wide)',
                  color: 'var(--muted-foreground)',
                }}>{c.label}</th>
              );
            })}
          </tr>
        </thead>
        <tbody>
          {rows.map(function (r, i) {
            return (
              <tr
                key={r.id != null ? r.id : i}
                onMouseEnter={() => setHovered(i)}
                onMouseLeave={() => setHovered(-1)}
                onClick={onRowClick ? () => onRowClick(r, i) : undefined}
                style={{
                  background: hoverable && hovered === i ? 'var(--nx-ink-hover)' : 'transparent',
                  cursor: onRowClick ? 'pointer' : 'default',
                  transition: 'background var(--nx-dur-fast) var(--nx-ease)',
                }}
              >
                {columns.map(function (c) {
                  const raw = c.render ? c.render(r, i) : r[c.key];
                  return (
                    <td key={c.key} style={{
                      textAlign: c.align || 'left',
                      padding: '0 var(--nx-s-4)', height: rowH,
                      borderBottom: '1px solid var(--border)',
                      fontSize: 'var(--nx-fs-body)',
                      fontFamily: c.mono ? 'var(--nx-font-mono)' : 'inherit',
                      fontVariantNumeric: c.mono ? 'tabular-nums' : undefined,
                      color: c.muted ? 'var(--muted-foreground)' : 'var(--foreground)',
                      whiteSpace: c.wrap ? 'normal' : 'nowrap',
                      verticalAlign: 'middle',
                    }}>{raw}</td>
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </table>
      {footer ? (
        <div style={{ padding: 'var(--nx-s-3) var(--nx-s-4)', borderTop: '1px solid var(--border)' }}>{footer}</div>
      ) : null}
    </div>
  );
}
