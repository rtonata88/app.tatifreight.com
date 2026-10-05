import React from 'react';

const ROWS = [
  { id: 1, ref: 'QT-2026-0184', client: 'Roads Authority', title: 'MR44 pavement rehabilitation', value: '4,862,000.00', status: 'approved', date: '13 Feb 2026' },
  { id: 2, ref: 'QT-2026-0183', client: 'NamWater', title: 'Ondangwa reticulation design', value: '1,284,500.00', status: 'pending', date: '11 Feb 2026' },
  { id: 3, ref: 'QT-2026-0181', client: 'City of Windhoek', title: 'Katutura stormwater assessment', value: '742,300.00', status: 'pending', date: '09 Feb 2026' },
  { id: 4, ref: 'QT-2026-0179', client: 'Ohangwena Regional Council', title: 'Clinic access road survey', value: '318,900.00', status: 'draft', date: '06 Feb 2026' },
  { id: 5, ref: 'QT-2026-0176', client: 'Erongo RED', title: 'Substation civil works supervision', value: '2,140,000.00', status: 'declined', date: '02 Feb 2026' },
  { id: 6, ref: 'QT-2026-0174', client: 'NamPower', title: 'Ruacana access track condition report', value: '486,200.00', status: 'completed', date: '28 Jan 2026' },
];

export function QuotationsScreen({ onNavigate, onStatusChange }) {
  const NX = window.__nxNS();
  const { PageHeader, Card, DataTable, StatusPill, Badge, Button, IconButton, Pagination, Tabs, Alert, EmptyState } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || (() => null);

  const [tab, setTab] = React.useState('all');
  const [page, setPage] = React.useState(1);

  const filtered = tab === 'all' ? ROWS : ROWS.filter(function (r) { return r.status === tab; });

  const columns = [
    { key: 'ref', label: 'Reference', mono: true, width: '150px' },
    { key: 'client', label: 'Client', width: '210px' },
    { key: 'title', label: 'Scope' },
    { key: 'value', label: 'Value (NAD)', align: 'right', mono: true, width: '150px' },
    { key: 'date', label: 'Issued', align: 'right', mono: true, muted: true, width: '120px' },
    { key: 'status', label: 'Status', width: '140px', render: function (r) { return <StatusPill status={r.status} />; } },
    { key: 'actions', label: '', align: 'right', width: '120px', render: function (r) {
      return (
        <div style={{ display: 'flex', gap: 6, justifyContent: 'flex-end' }}>
          <IconButton label="View quotation" onClick={function (e) { e.stopPropagation(); }}><Icon name="eye" size={14} /></IconButton>
          <IconButton label="Update status" tone="brass" onClick={function (e) { e.stopPropagation(); onStatusChange(r); }}><Icon name="edit" size={14} /></IconButton>
          <IconButton label="Download PDF" onClick={function (e) { e.stopPropagation(); }}><Icon name="download" size={14} /></IconButton>
        </div>
      );
    }},
  ];

  return (
    <>
      <PageHeader
        eyebrow="Client management"
        title="Quotations"
        subtitle="Create and track client quotations through to project handover."
        actions={
          <>
            <Button icon={<Icon name="download" size={15} />}>Export</Button>
            <Button variant="primary" icon={<Icon name="plus" size={15} />}>New quotation</Button>
          </>
        }
      />

      <Alert tone="info" title="Approving a quotation creates its project"
        style={{ marginBottom: 'var(--nx-s-6)' }}
        action={<Button variant="text" onClick={function () { onNavigate('projects'); }}>View projects</Button>}>
        QT-2026-0184 was approved on 13 Feb and created project NX-0231.
      </Alert>

      <Card
        title="All quotations"
        actions={<Badge>{filtered.length + ' of ' + ROWS.length}</Badge>}
        bodyPad={false}
      >
        <div style={{ padding: '0 var(--nx-s-4)' }}>
          <Tabs activeKey={tab} onChange={function (k) { setTab(k); setPage(1); }} tabs={[
            { key: 'all', label: 'All', count: ROWS.length },
            { key: 'draft', label: 'Draft', count: 1 },
            { key: 'pending', label: 'Pending', count: 2 },
            { key: 'approved', label: 'Approved', count: 1 },
            { key: 'declined', label: 'Declined', count: 1 },
          ]} />
        </div>
        <DataTable
          columns={columns}
          rows={filtered}
          onRowClick={function () { onNavigate('employee'); }}
          emptyState={<EmptyState title="No quotations in this state" description="Change the filter above, or create a quotation to get started." action={<Button variant="primary">New quotation</Button>} />}
          footer={<Pagination page={page} pages={4} total={184} perPage={6} onChange={setPage} />}
        />
      </Card>
    </>
  );
}
