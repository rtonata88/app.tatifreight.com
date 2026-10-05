Boolean choices: billable, capital expense, VAT applicable, "notify supervisor", table row selection.

```jsx
<Checkbox id="billable" checked={billable} onChange={onToggle}
          label="Billable to client" hint="Appears on the next invoice run" />
```

Use it without a label for table row-select. Nexus has no switch component - a checkbox states the fact plainly.
