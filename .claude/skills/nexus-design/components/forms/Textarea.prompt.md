Notes, expense descriptions, leave motivations, client notes.

```jsx
<FormField label="Reason for travel" required>
  <Textarea value={reason} onChange={e => setReason(e.target.value)} rows={4} />
</FormField>
```

Resizes vertically only. Keep to 3-5 rows; anything longer belongs in a dedicated notes panel.
