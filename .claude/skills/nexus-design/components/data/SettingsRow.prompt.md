A single setting inside a settings pane. Stack them; the hairlines do the structuring, so no
card is needed.

```jsx
<SettingsRow label="VAT number" description="Printed on every invoice and credit note.">
  <Input value={vat} onChange={onVat} mono align="right" />
</SettingsRow>
<SettingsRow label="Practice logo" description="Appears on quotations, invoices and payslips."
  action={<Button size="sm">Upload</Button>} last>
  <span className="nx-meta">No logo uploaded</span>
</SettingsRow>
```

Use `stacked` when the control is a textarea or a table. The description is where you state the
consequence of a change — that is the whole point of the cardless layout.
