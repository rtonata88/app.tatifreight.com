Wraps every control in a Nexus form. Labels are uppercase and tracked; the control below carries only a hairline.

```jsx
<FormField label="Expense category" required error={errors.category}>
  <Select value={v} onChange={setV} options={categories} />
</FormField>
```

Lay fields out in a CSS grid (`repeat(2, minmax(0, 1fr))`, `gap: 20px 24px`) and use `span` for full-width fields.
