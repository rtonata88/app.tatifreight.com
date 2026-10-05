The settings rail. Sits inside the page body, beside a content pane — a second level of
navigation below the module `Sidebar`.

```jsx
<div style={{ display: 'grid', gridTemplateColumns: '220px minmax(0, 1fr)', gap: 'var(--nx-s-8)' }}>
  <SettingsNav activeKey={pane} onNavigate={setPane} groups={[
    { title: 'Practice', items: [
      { key: 'company', label: 'Company information' },
      { key: 'holidays', label: 'Working days & holidays' }]},
    { title: 'Lists', items: [
      { key: 'expense-cats', label: 'Expense categories', count: 18 }]},
  ]} />
  <div>{/* SettingsRow stack */}</div>
</div>
```

Panes are **cardless** — a `SettingsRow` stack separated by hairlines, not a grid of cards. Show
`count` on list-management entries; ops staff use it to find the list they mean.
