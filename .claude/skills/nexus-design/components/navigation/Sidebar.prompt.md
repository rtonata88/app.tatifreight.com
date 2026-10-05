The persistent module rail. Mirrors the Blade sidebar's structure: a General section, then one item per module, submenus one level deep.

```jsx
<Sidebar brand="Nexus" activeKey="quotations" onNavigate={setScreen}
  sections={[
    { title: 'General', items: [
      { key: 'analytics', label: 'Dashboard', icon: <Home size={16} />, children: [
        { key: 'analytics', label: 'Analytics' }, { key: 'finance', label: 'Finance' }] },
      { key: 'leave', label: 'Leave applications', icon: <CalendarDays size={16} />, badge: 4 },
    ]},
    { title: 'Operations', items: [
      { key: 'clients', label: 'Client management', icon: <Users size={16} />, children: [
        { key: 'quotations', label: 'Quotations' }, { key: 'invoices', label: 'Invoices' }] },
    ]},
  ]} />
```

Never nest more than one level. Badges are for work waiting on the user (pending approvals), not totals.
