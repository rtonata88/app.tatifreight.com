Sits above the page body, beside the rail.

```jsx
<Topbar
  breadcrumb={<Breadcrumb items={['Client management', 'Quotations']} />}
  search={q} onSearch={e => setQ(e.target.value)}
  user={<><Avatar name="Selma Kaunatjike" size={28} brass /><span className="nx-meta">Selma Kaunatjike</span></>} />
```

The search field is bare like every other Nexus input. Keep global actions to two at most.
