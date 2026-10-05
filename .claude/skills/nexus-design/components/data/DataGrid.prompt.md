Record facts inside a card - the detail block on every show page.

```jsx
<Card title="Employment">
  <DataGrid columns={3} items={[
    { label: 'Employee number', value: 'TWY-0142', mono: true },
    { label: 'Position', value: 'Senior civil engineer' },
    { label: 'Date engaged', value: '03 Feb 2021', mono: true },
    { label: 'Cost to company', value: 'NAD 68,400.00', mono: true, emphasis: true },
  ]} />
</Card>
```

Empty values render as an em dash - never blank, never "N/A".
