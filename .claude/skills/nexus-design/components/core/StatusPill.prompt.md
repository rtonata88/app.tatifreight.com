Record state - quotation, invoice, leave application, payroll run, employee.

```jsx
<StatusPill status="approved" />
<StatusPill status="overdue">31 days overdue</StatusPill>
```

Moss = active/approved/paid, amber = pending/draft/submitted, slate = completed, oxblood = declined/overdue, muted = inactive. Never use a status pill for a count - that is `Badge`.
