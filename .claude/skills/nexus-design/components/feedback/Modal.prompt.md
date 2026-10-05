Status updates, approvals and confirms - the pattern the Blade app uses for "Update quotation status".

```jsx
<Modal open={open} onClose={close} title="Update quotation status"
  subtitle="QT-2026-0184 · Roads Authority"
  footer={<><Button onClick={close}>Cancel</Button>
            <Button variant="primary" onClick={save}>Update status</Button></>}>
  <FormField label="Status"><Select value={s} onChange={onS} options={['Draft','Pending','Approved','Declined']} /></FormField>
</Modal>
```

Positioned absolutely - give the containing element `position: relative`. Keep to one decision per modal; anything longer is a page.
