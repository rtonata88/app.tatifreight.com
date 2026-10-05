import React from 'react';

/* Icons come from Lucide (the sanctioned set), loaded as a UMD global by the kit page.
   Nothing here is hand-drawn: we look the icon node up in `lucide.icons` and render it.
   In production, install `lucide-react` and import the components directly. */
const LUCIDE = {
  grid: 'LayoutGrid', calendar: 'CalendarDays', plane: 'Plane', clock: 'Clock',
  folder: 'FolderOpen', users: 'Users', building: 'Building2', money: 'Banknote',
  calculator: 'Calculator', cog: 'Settings', plus: 'Plus', download: 'Download',
  printer: 'Printer', eye: 'Eye', edit: 'PenLine', check: 'Check',
  chevronRight: 'ChevronRight', arrowLeft: 'ArrowLeft', list: 'List', file: 'FileText',
  trend: 'TrendingUp', link: 'Link', paperclip: 'Paperclip', info: 'Info',
  flag: 'Flag', save: 'Save', x: 'X', upload: 'UploadCloud', mail: 'Mail',
  phone: 'Phone', briefcase: 'Briefcase', note: 'StickyNote', idCard: 'IdCard',
  calendarCheck: 'CalendarCheck', send: 'Send', card: 'CreditCard', trash: 'Trash2',
  moon: 'Moon', sun: 'Sun',
};

function iconChildren(name) {
  const L = (typeof window !== 'undefined' && window.lucide) || {};
  const key = LUCIDE[name] || name;
  const node = (L.icons && L.icons[key]) || L[key];
  if (!node) return null;
  if (Array.isArray(node) && node[0] === 'svg') return node[2] || [];
  return Array.isArray(node) ? node : null;
}

export function Icon({ name, size = 16, strokeWidth = 1.6 }) {
  const children = iconChildren(name);
  if (!children) return <span style={{ display: 'inline-block', width: size, height: size }} />;
  return React.createElement(
    'svg',
    {
      width: size, height: size, viewBox: '0 0 24 24', fill: 'none',
      stroke: 'currentColor', strokeWidth: strokeWidth,
      strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': 'true',
      style: { display: 'block', flex: 'none' },
    },
    children.map(function (c, i) {
      return React.createElement(c[0], Object.assign({ key: i }, c[1]));
    })
  );
}

const SECTIONS = [
  { title: 'General', items: [
    { key: 'dashboard', label: 'Dashboard', icon: <Icon name="grid" />, children: [
      { key: 'dashboard', label: 'Analytics' },
      { key: 'finance-dash', label: 'Finance' },
      { key: 'projects-dash', label: 'Projects' },
    ]},
    { key: 'leave', label: 'Leave applications', icon: <Icon name="calendar" />, badge: 4 },
    { key: 'trips', label: 'Trip requests', icon: <Icon name="plane" /> },
    { key: 'timesheets', label: 'My timesheets', icon: <Icon name="clock" /> },
  ]},
  { title: 'Operations', items: [
    { key: 'projects', label: 'Projects', icon: <Icon name="folder" />, children: [
      { key: 'projects', label: 'All projects' },
      { key: 'proj-timesheets', label: 'Timesheets' },
      { key: 'proj-trips', label: 'Trip requests' },
    ]},
    { key: 'clients', label: 'Client management', icon: <Icon name="users" />, children: [
      { key: 'clients-dir', label: 'Clients' },
      { key: 'quotations', label: 'Quotations' },
      { key: 'invoices', label: 'Invoices' },
      { key: 'payments', label: 'Payments' },
    ]},
    { key: 'vendors', label: 'Vendor management', icon: <Icon name="building" />, children: [
      { key: 'vendors-dir', label: 'Vendor directory' },
      { key: 'purchase-orders', label: 'Purchase orders' },
      { key: 'vendor-invoices', label: 'Vendor invoices' },
    ]},
    { key: 'finance', label: 'Finance', icon: <Icon name="money" />, children: [
      { key: 'expenses', label: 'Expense tracking' },
      { key: 'banking', label: 'Banking' },
      { key: 'budgets', label: 'Budget management' },
      { key: 'fin-reports', label: 'Financial reports' },
    ]},
  ]},
  { title: 'People', items: [
    { key: 'payroll', label: 'Payroll', icon: <Icon name="calculator" />, children: [
      { key: 'payroll-runs', label: 'Payroll runs' },
      { key: 'payslip', label: 'Payslips' },
      { key: 'payroll-reports', label: 'Reports' },
    ]},
    { key: 'people', label: 'People management', icon: <Icon name="users" />, children: [
      { key: 'employee', label: 'Employees' },
      { key: 'leave-requests', label: 'Leave requests' },
    ]},
    { key: 'settings', label: 'Settings', icon: <Icon name="cog" /> },
  ]},
];

export function Shell({ screen, onNavigate, breadcrumb, children, overlay = null }) {
  const NX = window.__nxNS();
  const { Sidebar, Topbar, Breadcrumb, Avatar, Button } = NX;
  const [theme, setTheme] = React.useState(
    (typeof document !== 'undefined' && document.documentElement.className) || 'nx-light'
  );
  const flip = function () {
    const next = theme === 'nx-light' ? 'nx-ink' : 'nx-light';
    document.documentElement.className = next;
    setTheme(next);
  };
  return (
    <div style={{ display: 'flex', height: '100%', minHeight: 0, background: 'var(--background)', position: 'relative' }}>
      <Sidebar
        brand="Nexus" sections={SECTIONS} activeKey={screen} onNavigate={onNavigate}
        footer={
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <Avatar name="Selma Kaunatjike" size={30} brass />
            <div style={{ minWidth: 0 }}>
              <div style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 600, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Selma Kaunatjike</div>
              <div className="nx-meta" style={{ fontSize: 11 }}>Finance manager</div>
            </div>
          </div>
        }
      />
      <div style={{ flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column' }}>
        <Topbar
          breadcrumb={<Breadcrumb items={breadcrumb} onNavigate={onNavigate} />}
          actions={
            <Button size="sm" icon={<Icon name={theme === 'nx-light' ? 'moon' : 'sun'} size={14} />} onClick={flip}>
              {theme === 'nx-light' ? 'Dark' : 'Light'}
            </Button>
          }
          user={
            <>
              <span className="nx-meta">Windhoek · NAD</span>
              <Avatar name="Selma Kaunatjike" size={28} />
            </>
          }
        />
        <main style={{ flex: 1, minWidth: 0, overflowY: 'auto', padding: 'var(--nx-gutter)' }}>
          <div style={{ maxWidth: 'var(--nx-page-max)', margin: '0 auto' }}>{children}</div>
        </main>
      </div>
      {overlay}
    </div>
  );
}
