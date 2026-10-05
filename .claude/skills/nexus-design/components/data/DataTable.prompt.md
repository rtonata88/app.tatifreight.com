Every Nexus index screen. Put it in a `Card` with `bodyPad={false}` so rows run edge to edge.

```jsx
const columns = [
  { key: 'ref', label: 'Reference', mono: true, width: '150px' },
  { key: 'client', label: 'Client' },
  { key: 'amount', label: 'Amount', align: 'right', mono: true,
    render: r => 'NAD ' + r.amount.toLocaleString('en-NA', { minimumFractionDigits: 2 }) },
  { key: 'status', label: 'Status', render: r => <StatusPill status={r.status} /> },
  { key: 'actions', label: '', align: 'right', render: r => (
      <div style={{ display: 'flex', gap: 6, justifyContent: 'flex-end' }}>
        <IconButton label="View"><Eye size={14} /></IconButton>
      </div>) },
];

<Card title="All invoices" actions={<Badge>184</Badge>} bodyPad={false}>
  <DataTable columns={columns} rows={rows} footer={<Pagination page={1} pages={9} />} />
</Card>
```

Money and dates are right-aligned and `mono`. Keep the action column last, right-aligned, with an empty label. Pass `emptyState={<EmptyState .../>}` rather than rendering a bare empty table.
