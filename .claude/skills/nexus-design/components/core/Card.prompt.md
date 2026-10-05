Every panel on a Nexus screen. The header rule appears only when there is a body.

```jsx
<Card title="All quotations" icon={<List size={16} />} actions={<Badge>184</Badge>} bodyPad={false}>
  <DataTable columns={cols} rows={rows} />
</Card>
```

Set `emphasis` on exactly one card per screen - it carries the brass hairline and the only glow in the system. Pass `bodyPad={false}` whenever the body is a table so rows run edge to edge.
