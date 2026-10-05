The first element in every page body. Replaces the Blade `ep-page-header` and its icon tile - Nexus uses type, not an icon chip.

```jsx
<PageHeader eyebrow="Client management" title="Quotations"
  subtitle="Create and track client quotations through to project handover."
  meta={<><StatusPill status="pending" />, <Badge mono>QT-2026-0184</Badge></>}
  actions={<><Button icon={<Download size={15} />}>Export</Button>
             <Button variant="primary" icon={<Plus size={15} />}>New quotation</Button></>} />
```

Exactly one `primary` button here. The subtitle is a full sentence with a full stop.
