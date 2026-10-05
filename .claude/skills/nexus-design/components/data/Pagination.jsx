import React from 'react';

export function Pagination({ page = 1, pages = 1, total, perPage, onChange, style }) {
  const go = function (p) { if (onChange && p >= 1 && p <= pages && p !== page) onChange(p); };
  const from = total != null && perPage ? (page - 1) * perPage + 1 : null;
  const to = total != null && perPage ? Math.min(total, page * perPage) : null;
  const nums = [];
  for (let p = Math.max(1, page - 1); p <= Math.min(pages, Math.max(1, page - 1) + 2); p++) nums.push(p);

  return (
    <div style={{
      display: 'flex', alignItems: 'center', justifyContent: 'space-between',
      gap: 'var(--nx-s-4)', flexWrap: 'wrap', ...style,
    }}>
      <div className="nx-meta">
        {from != null ? 'Showing ' + from + '\u2013' + to + ' of ' + total : 'Page ' + page + ' of ' + pages}
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
        <PageBtn disabled={page <= 1} onClick={() => go(page - 1)}>Previous</PageBtn>
        {nums.map(function (p) {
          const active = p === page;
          return (
            <button key={p} type="button" onClick={() => go(p)} className="nx-focusable" style={{
              minWidth: 28, height: 28, padding: '0 6px',
              borderRadius: 'var(--nx-r-2)',
              border: '1px solid ' + (active ? 'var(--nx-brass)' : 'var(--border)'),
              background: active ? 'var(--nx-brass-wash-2)' : 'transparent',
              color: active ? 'var(--nx-brass-hi)' : 'var(--muted-foreground)',
              fontFamily: 'var(--nx-font-mono)', fontSize: 'var(--nx-fs-micro)',
              cursor: 'pointer',
              transition: 'border-color var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
            }}>{p}</button>
          );
        })}
        <PageBtn disabled={page >= pages} onClick={() => go(page + 1)}>Next</PageBtn>
      </div>
    </div>
  );
}

function PageBtn({ children, disabled, onClick }) {
  return (
    <button type="button" onClick={onClick} disabled={disabled} className="nx-focusable" style={{
      height: 28, padding: '0 10px',
      borderRadius: 'var(--nx-r-2)', border: '1px solid var(--border)',
      background: 'transparent',
      color: disabled ? 'var(--nx-fg-4)' : 'var(--muted-foreground)',
      fontSize: 'var(--nx-fs-micro)', fontWeight: 600,
      cursor: disabled ? 'not-allowed' : 'pointer',
    }}>{children}</button>
  );
}
