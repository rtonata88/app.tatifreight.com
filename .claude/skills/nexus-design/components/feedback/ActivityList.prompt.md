Audit trails and dashboard activity - the app keeps activity logs on quotations and invoices, and this renders them.

```jsx
<Card title="Recent activity">
  <ActivityList items={[
    { title: 'Invoice INV-2026-0117 issued', description: 'Roads Authority · NAD 486,200.00',
      time: '14 Feb 09:12', tone: 'positive' },
    { title: 'Leave application declined', description: 'T. Shipanga · 3 days annual',
      time: '13 Feb 16:40', tone: 'negative' },
  ]} />
</Card>
```

Titles state the event and its reference; descriptions carry the actor and the amount. Times are mono.
