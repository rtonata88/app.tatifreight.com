The quick-stats cluster inside a card - avg payment days, active trips, overdue invoices, profit margin.

```jsx
<Card title="Quick stats">
  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0,1fr))', gap: 'var(--nx-s-4)' }}>
    <StatCard value="34" label="Avg days to payment" />
    <StatCard value="6" label="Overdue invoices" tone="negative" />
  </div>
</Card>
```

Unlike `MetricCard` it has no border of its own - it lives inside a card.
