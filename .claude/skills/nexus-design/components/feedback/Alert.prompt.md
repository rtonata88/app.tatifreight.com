Inline messages at the top of a page body, above the first card.

```jsx
<Alert tone="warning" title="Payroll period is closed"
  action={<Button variant="text">Reopen period</Button>}>
  February 2026 was closed on 26 Feb. Adjustments will post to March.
</Alert>
```

Compliance and finance facts are stated plainly, never softened. Transient confirmations after an action go to `Toast`, not `Alert`.
