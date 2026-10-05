Pager for index tables.

```jsx
<DataTable columns={cols} rows={rows}
  footer={<Pagination page={page} pages={9} total={184} perPage={25} onChange={setPage} />} />
```

Always pass `total` and `perPage` when you know them - the record count is what ops staff actually read.
