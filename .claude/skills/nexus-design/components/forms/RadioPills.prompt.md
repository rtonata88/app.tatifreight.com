Two or three exclusive choices where each option needs a line of consequence — initial
status on a create form, VAT treatment, employment type.

```jsx
<RadioPills name="status" value={status} onChange={setStatus} options={[
  { value: 'draft', label: 'Draft', description: 'Save for later', icon: <Icon name="file" /> },
  { value: 'pending', label: 'Submit', description: 'For approval', icon: <Icon name="send" /> },
]} />
```

Four or more options belong in a `Select`. Never use it where a `Checkbox` would do.
