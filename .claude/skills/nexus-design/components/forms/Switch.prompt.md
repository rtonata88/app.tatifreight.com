Toggles that immediately change how other values are read or calculated.

```jsx
<FormField label="VAT treatment">
  <Switch id="vat" checked={vatInclusive} onChange={onVat} label="Amount includes VAT (15%)" />
</FormField>
```

The distinction against `Checkbox` is intent: a switch changes a calculation or a mode now
(VAT inclusive/exclusive, notify supervisor); a checkbox records a fact about the record
(billable, capital expense).
