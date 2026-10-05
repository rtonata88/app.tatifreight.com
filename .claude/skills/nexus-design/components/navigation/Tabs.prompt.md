Switches panels within one record, or filters an index by status.

```jsx
<Tabs activeKey={tab} onChange={setTab} tabs={[
  { key: 'contacts', label: 'Contacts', count: 4 },
  { key: 'notes', label: 'Notes', count: 12 },
  { key: 'attachments', label: 'Attachments', count: 3 },
]} />
```

The active tab is marked by a 2px brass underline - the one place brass appears as a rule rather than a fill. Use `Sidebar` for navigation between modules, never tabs.
